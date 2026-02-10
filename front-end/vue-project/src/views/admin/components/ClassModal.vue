<script setup>
import { reactive, watch, computed } from 'vue'

const props = defineProps({
  show: Boolean,
  editingItem: Object,
  subjects: { type: Array, default: () => [] },
  semesters: { type: Array, default: () => [] },
  lecturers: { type: Array, default: () => [] },
})

const emit = defineEmits(['close', 'save'])

const defaultForm = {
  id: null,
  code: '',
  name: '',
  semester_id: '',
  lecturer_id: '',
  default_max_members: 60,
  subject_details: [],
}

const form = reactive({ ...defaultForm })

const selectedSubjectsList = computed(() => {
  if (!form.subject_details) return []
  // Map thông tin chi tiết từ props.subjects vào để hiển thị tên
  return form.subject_details.map((detail) => {
    const originalSubject = props.subjects.find((s) => s.id === detail.subject_id)
    return {
      ...detail,
      name: originalSubject?.name || 'Unknown',
      code: originalSubject?.code || '---',
    }
  })
})

// Xử lý khi chọn/bỏ chọn checkbox
const toggleSubject = (subjectId) => {
  const index = form.subject_details.findIndex((item) => item.subject_id === subjectId)

  if (index === -1) {
    form.subject_details.push({
      subject_id: subjectId,
      max_members: form.default_max_members,
    })
  } else {
    // Nếu có rồi -> Xóa đi
    form.subject_details.splice(index, 1)
  }
}

// Kiểm tra xem ID môn học có đang được chọn không (để checked checkbox)
const isSelected = (subjectId) => {
  return form.subject_details.some((item) => item.subject_id === subjectId)
}

// Cập nhật sĩ số cho 1 môn cụ thể
const updateMemberForSubject = (subjectId, val) => {
  const item = form.subject_details.find((i) => i.subject_id === subjectId)
  if (item) item.max_members = Number(val)
}

watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      if (props.editingItem) {
        // --- EDIT ---
        Object.assign(form, {
          id: props.editingItem.id,
          code: props.editingItem.code,
          name: props.editingItem.name,
          semester_id: props.editingItem.semester_id,
          lecturer_id: props.editingItem.lecturer_id,
          default_max_members: props.editingItem.max_members || 60,

          // Convert dữ liệu từ server (nếu server trả về pivot)
          subject_details: props.editingItem.subjects
            ? props.editingItem.subjects.map((s) => ({
                subject_id: s.id,
                // Lấy sĩ số từ bảng trung gian (pivot) nếu có, nếu không lấy của lớp
                max_members: s.pivot?.max_members || props.editingItem.max_members || 60,
              }))
            : [],
        })
      } else {
        // --- CREATE ---
        Object.assign(form, defaultForm)
        form.subject_details = []
        if (props.semesters.length > 0) form.semester_id = props.semesters[0].id
      }
    }
  },
)

// Khi thay đổi "Sĩ số mặc định", cập nhật cho tất cả các môn (nếu muốn tiện lợi)
const updateAllMembers = () => {
  form.subject_details.forEach((item) => {
    item.max_members = form.default_max_members
  })
}

const handleSubmit = () => {
  if (form.subject_details.length === 0) {
    alert('Vui lòng chọn ít nhất một môn học.')
    return
  }
  // Gửi form đi. Lưu ý Backend cần xử lý mảng subject_details này
  emit('save', { ...form })
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm px-4"
    >
      <div
        class="bg-white rounded-xl shadow-xl w-full max-w-2xl overflow-hidden animate-fade-in-up"
      >
        <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
          <h3 class="text-lg font-bold text-gray-800">
            {{ editingItem ? 'Cập nhật Lớp học phần' : 'Mở Lớp học phần mới' }}
          </h3>
          <button @click="emit('close')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="col-span-2">
              <label class="block text-sm font-bold text-gray-700 mb-2">
                1. Chọn Môn học <span class="text-red-500">*</span>
              </label>
              <div
                v-if="subjects.length > 0"
                class="grid grid-cols-2 gap-2 border rounded-lg p-3 max-h-40 overflow-y-auto bg-gray-50"
              >
                <div v-for="s in subjects" :key="s.id" class="flex items-center">
                  <input
                    type="checkbox"
                    :id="'sub-' + s.id"
                    :checked="isSelected(s.id)"
                    @change="toggleSubject(s.id)"
                    class="h-4 w-4 text-blue-600 rounded cursor-pointer"
                  />
                  <label
                    :for="'sub-' + s.id"
                    class="ml-2 text-sm text-gray-700 cursor-pointer select-none"
                  >
                    {{ s.code }} - {{ s.name }}
                  </label>
                </div>
              </div>
              <div v-else class="text-xs text-orange-500 bg-orange-50 p-2 rounded">
                ⚠️ Vui lòng chọn Ngành ở bộ lọc để tải môn học.
              </div>
            </div>

            <div class="col-span-2 bg-blue-50 p-3 rounded-lg border border-blue-100">
              <label class="block text-sm font-bold text-blue-800 mb-2">2. Cấu hình sĩ số</label>

              <div class="flex items-center gap-2 mb-3">
                <span class="text-sm text-gray-600">Áp dụng chung:</span>
                <input
                  v-model.number="form.default_max_members"
                  @input="updateAllMembers"
                  type="number"
                  class="w-20 border rounded p-1 text-center font-bold"
                />
                <span class="text-xs text-gray-400">(Nhập để tự điền cho tất cả)</span>
              </div>

              <div
                v-if="selectedSubjectsList.length > 0"
                class="space-y-2 mt-2 max-h-40 overflow-y-auto pr-1"
              >
                <div
                  v-for="item in selectedSubjectsList"
                  :key="item.subject_id"
                  class="flex justify-between items-center bg-white p-2 rounded border shadow-sm"
                >
                  <span class="text-sm font-medium text-gray-700 truncate w-2/3">
                    {{ item.code }} - {{ item.name }}
                  </span>
                  <div class="flex items-center gap-1 w-1/3 justify-end">
                    <span class="text-xs text-gray-500">Max:</span>
                    <input
                      type="number"
                      :value="item.max_members"
                      @input="(e) => updateMemberForSubject(item.subject_id, e.target.value)"
                      class="w-16 border rounded p-1 text-center text-sm"
                      min="1"
                      required
                    />
                  </div>
                </div>
              </div>
              <div v-else class="text-center text-sm text-gray-400 italic py-2">
                Chưa chọn môn học nào
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >Học kỳ <span class="text-red-500">*</span></label
              >
              <select
                v-model="form.semester_id"
                class="w-full border-gray-300 rounded-lg p-2"
                required
              >
                <option v-for="sem in semesters" :key="sem.id" :value="sem.id">
                  {{ sem.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >Mã lớp <span class="text-red-500">*</span></label
              >
              <input
                v-model="form.code"
                type="text"
                class="w-full border-gray-300 rounded-lg p-2 uppercase"
                required
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >Tên lớp <span class="text-red-500">*</span></label
              >
              <input
                v-model="form.name"
                type="text"
                class="w-full border-gray-300 rounded-lg p-2"
                required
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Giảng viên</label>
              <select v-model="form.lecturer_id" class="w-full border-gray-300 rounded-lg p-2">
                <option value="">-- Chưa phân công --</option>
                <option v-for="L in lecturers" :key="L.id" :value="L.id">{{ L.name }}</option>
              </select>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t mt-4">
            <button
              type="button"
              @click="emit('close')"
              class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
            >
              Hủy
            </button>
            <button
              type="submit"
              class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700"
            >
              Lưu
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
