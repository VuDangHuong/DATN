<script setup>
import { reactive, watch, computed } from 'vue'

const props = defineProps({
  show: Boolean,
  editingItem: Object,
  // Dữ liệu dropdown nhận từ cha
  subjects: { type: Array, default: () => [] },
  semesters: { type: Array, default: () => [] },
  lecturers: { type: Array, default: () => [] },
})

const emit = defineEmits(['close', 'save'])

const defaultForm = {
  id: null,
  code: '',
  name: '',
  subject_id: '',
  semester_id: '',
  lecturer_id: '',
  max_members: 60,
}

const form = reactive({ ...defaultForm })

// Khi mở Modal hoặc đổi item
watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      if (props.editingItem) {
        console.log('Dữ liệu sửa:', props.editingItem)
        console.log('Danh sách học kỳ:', props.semesters)
        Object.assign(form, {
          id: props.editingItem.id,
          code: props.editingItem.code,
          name: props.editingItem.name,
          subject_id: props.editingItem.subject_id ? Number(props.editingItem.subject_id) : '',
          semester_id: props.editingItem.semester_id ? Number(props.editingItem.semester_id) : '',
          lecturer_id: props.editingItem.lecturer_id ? Number(props.editingItem.lecturer_id) : '',
          max_members: props.editingItem.max_members,
        })
      } else {
        Object.assign(form, defaultForm)
        // Auto select học kỳ mới nhất nếu có
        if (props.semesters && props.semesters.length > 0) {
          form.semester_id = props.semesters[0].id
        }
      }
    }
  },
)

const handleSubmit = () => {
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
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >Môn học <span class="text-red-500">*</span></label
              >
              <select
                v-model="form.subject_id"
                class="w-full border-gray-300 rounded-lg p-2"
                required
                :disabled="!!editingItem"
                :class="{ 'bg-gray-100': !!editingItem }"
              >
                <option value="" disabled>-- Chọn môn học --</option>
                <option v-for="s in subjects" :key="s.id" :value="s.id">
                  {{ s.code }} - {{ s.name }} ({{ s.credits }} TC)
                </option>
              </select>
              <p v-if="subjects.length === 0" class="text-xs text-orange-500 mt-1">
                ⚠️ Vui lòng chọn Ngành ở bộ lọc bên ngoài để tải danh sách môn.
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >Học kỳ <span class="text-red-500">*</span></label
              >
              <select
                v-model="form.semester_id"
                class="w-full border-gray-300 rounded-lg p-2"
                required
                :disabled="!!editingItem"
                :class="{ 'bg-gray-100': !!editingItem }"
              >
                <option v-for="sem in semesters" :key="sem.id" :value="sem.id">
                  {{ sem.name }} ({{ sem.year }})
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >Mã lớp (Unique) <span class="text-red-500">*</span></label
              >
              <input
                v-model="form.code"
                type="text"
                placeholder="VD: CSE481_01"
                class="w-full border-gray-300 rounded-lg p-2 uppercase"
                required
                :disabled="!!editingItem"
                :class="{ 'bg-gray-100': !!editingItem }"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >Tên lớp (Hành chính)</label
              >
              <input
                v-model="form.name"
                type="text"
                placeholder="VD: 63CNTT1"
                class="w-full border-gray-300 rounded-lg p-2"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Sĩ số tối đa</label>
              <input
                v-model="form.max_members"
                type="number"
                min="1"
                class="w-full border-gray-300 rounded-lg p-2"
                required
              />
            </div>

            <div class="col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >Giảng viên phụ trách</label
              >
              <select v-model="form.lecturer_id" class="w-full border-gray-300 rounded-lg p-2">
                <option value="">-- Chưa phân công --</option>
                <option v-for="L in lecturers" :key="L.id" :value="L.id">
                  {{ L.name }} ({{ L.email }})
                </option>
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
              class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm"
            >
              {{ editingItem ? 'Lưu thay đổi' : 'Mở lớp' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.animate-fade-in-up {
  animation: fadeInUp 0.3s ease-out;
}
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
