<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { useCategoryStore } from '@/stores/admin/category'
import { categoryApi } from '@/api/admin/category'
import { useToastStore } from '@/stores/toast'
import SvgIcon from '@/components/icons/SVG.vue'

const store = useCategoryStore()
const toast = useToastStore()

// State
const subjects = ref([])
const filterFaculty = ref('')
const filterMajor = ref('')
const availableMajors = ref([]) // Dropdown ngành

// Modal State
const showModal = ref(false)
const isEditing = ref(false)
const form = reactive({ id: null, major_id: '', code: '', name: '', credits: 3 })

// 1. Logic Lọc: Chọn Khoa
watch(filterFaculty, async (newVal) => {
  filterMajor.value = ''
  subjects.value = [] // Reset list
  if (newVal) {
    availableMajors.value = await store.fetchMajors(newVal)
  } else {
    availableMajors.value = []
  }
})

// 2. Logic Lọc: Chọn Ngành -> Load Môn
watch(filterMajor, loadSubjects)

async function loadSubjects() {
  if (!filterMajor.value) {
    subjects.value = []
    return
  }
  try {
    const res = await categoryApi.getSubjects({ major_id: filterMajor.value })
    subjects.value = res.data
  } catch (e) {
    console.error(e)
  }
}

// 3. Actions CRUD
const openModal = (item = null) => {
  if (item) {
    // Edit
    isEditing.value = true
    Object.assign(form, item)
  } else {
    // Create
    if (!filterMajor.value) {
      toast.error('Vui lòng chọn Ngành trước khi thêm môn.')
      return
    }
    isEditing.value = false
    Object.assign(form, {
      id: null,
      major_id: filterMajor.value,
      code: '',
      name: '',
      credits: 3,
    })
  }
  showModal.value = true
}

const handleSave = async () => {
  try {
    if (isEditing.value) {
      await categoryApi.updateSubject(form.id, form)
      toast.success('Cập nhật môn học thành công')
    } else {
      await categoryApi.createSubject(form)
      toast.success('Thêm môn học thành công')
    }
    showModal.value = false
    loadSubjects()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Có lỗi xảy ra')
  }
}

const handleDelete = async (id) => {
  if (!confirm('Bạn chắc chắn muốn xóa môn học này?')) return
  try {
    await categoryApi.deleteSubject(id)
    toast.success('Đã xóa môn học')
    loadSubjects()
  } catch (e) {
    toast.error(e.response?.data?.message)
  }
}
</script>

<template>
  <div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 bg-gray-50 p-4 rounded-lg">
      <div>
        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Lọc Khoa</label>
        <select v-model="filterFaculty" class="w-full border rounded p-2 text-sm">
          <option value="">-- Chọn Khoa --</option>
          <option v-for="f in store.faculties" :key="f.id" :value="f.id">{{ f.name }}</option>
        </select>
      </div>

      <div>
        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Lọc Ngành</label>
        <select
          v-model="filterMajor"
          class="w-full border rounded p-2 text-sm"
          :disabled="!filterFaculty"
        >
          <option value="">-- Chọn Ngành --</option>
          <option v-for="m in availableMajors" :key="m.id" :value="m.id">{{ m.name }}</option>
        </select>
      </div>

      <div class="flex items-end">
        <button
          @click="openModal()"
          class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full flex justify-center items-center disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="!filterMajor"
          title="Vui lòng chọn Ngành trước"
        >
          <SvgIcon name="plus" class="w-4 h-4 mr-2" /> Thêm Môn Học
        </button>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full border-collapse min-w-[600px]">
        <thead>
          <tr class="bg-white border-b border-gray-200 text-sm">
            <th class="p-3 text-left w-32">Mã Môn</th>
            <th class="p-3 text-left">Tên Môn Học</th>
            <th class="p-3 text-center w-24">Số TC</th>
            <th class="p-3 text-right w-32">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="subjects.length === 0">
            <td colspan="4" class="p-6 text-center text-gray-500">
              {{ filterMajor ? 'Chưa có môn học nào.' : 'Vui lòng chọn Ngành để xem danh sách.' }}
            </td>
          </tr>

          <tr v-for="s in subjects" :key="s.id" class="border-b hover:bg-gray-50 text-sm">
            <td class="p-3 font-mono text-blue-600 font-bold">{{ s.code }}</td>
            <td class="p-3 font-medium">{{ s.name }}</td>
            <td class="p-3 text-center">
              <span class="bg-gray-100 text-gray-800 text-xs font-bold px-2 py-1 rounded">{{
                s.credits
              }}</span>
            </td>
            <td class="p-3 text-right space-x-2">
              <button @click="openModal(item)" class="text-indigo-600 hover:underline">
                <SvgIcon name="edit" class="h-4 w-4 mr-1" />
                Sửa
              </button>
              <button @click="handleDelete(item.id)" class="text-red-600 hover:underline">
                <SvgIcon name="trash" class="h-4 w-4 mr-1" />
                Xóa
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      >
        <div class="bg-white p-6 rounded-lg w-[500px] shadow-xl animate-fade-in-up">
          <div class="flex justify-between items-center mb-4 border-b pb-2">
            <h3 class="text-lg font-bold">
              {{ isEditing ? 'Cập nhật Môn học' : 'Thêm Môn học mới' }}
            </h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>

          <form @submit.prevent="handleSave" class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-1"
                >Mã môn học <span class="text-red-500">*</span></label
              >
              <input
                v-model="form.code"
                class="w-full border rounded p-2 uppercase"
                placeholder="VD: CSE101"
                required
              />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1"
                >Tên môn học <span class="text-red-500">*</span></label
              >
              <input
                v-model="form.name"
                class="w-full border rounded p-2"
                placeholder="VD: Nhập môn lập trình"
                required
              />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1"
                >Số tín chỉ <span class="text-red-500">*</span></label
              >
              <input
                v-model="form.credits"
                type="number"
                min="1"
                max="10"
                class="w-full border rounded p-2"
                required
              />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Thuộc Ngành</label>
              <select
                v-model="form.major_id"
                class="w-full border rounded p-2 bg-gray-100"
                disabled
              >
                <option v-for="m in availableMajors" :key="m.id" :value="m.id">{{ m.name }}</option>
              </select>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t">
              <button
                type="button"
                @click="showModal = false"
                class="px-4 py-2 text-gray-600 bg-gray-100 rounded hover:bg-gray-200"
              >
                Hủy
              </button>
              <button
                type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
              >
                Lưu
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.animate-fade-in-up {
  animation: fadeInUp 0.3s ease-out;
}
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
