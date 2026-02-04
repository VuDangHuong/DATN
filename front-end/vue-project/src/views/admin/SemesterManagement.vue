<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useSemesterStore } from '@/stores/admin/semester'
import { useToastStore } from '@/stores/toast'
import SvgIcon from '@/components/icons/SVG.vue'

const semesterStore = useSemesterStore()
const toast = useToastStore()
const { semesters, loading } = storeToRefs(semesterStore)

const showModal = ref(false)
const isEditing = ref(false)
const formErrors = ref({})

const defaultForm = {
  id: null,
  name: '',
  code: '',
  year: new Date().getFullYear(),
  start_date: '',
  end_date: '',
  is_active: false,
}

const form = reactive({ ...defaultForm })

const formatDate = (dateString) => {
  if (!dateString) return '---'
  const date = new Date(dateString)
  if (isNaN(date.getTime())) return dateString
  return date.toLocaleDateString('vi-VN')
}

const formatDateForInput = (dateString) => {
  if (!dateString) return ''
  return dateString.toString().substring(0, 10)
}

//ACTIONS
const openCreateModal = () => {
  isEditing.value = false
  formErrors.value = {}
  Object.assign(form, defaultForm)
  form.is_active = true
  showModal.value = true
}

const openEditModal = (item) => {
  isEditing.value = true
  formErrors.value = {}
  Object.assign(form, {
    id: item.id,
    name: item.name,
    code: item.code,
    year: item.year,
    start_date: formatDateForInput(item.start_date),
    end_date: formatDateForInput(item.end_date),
    is_active: Boolean(item.is_active),
  })
  showModal.value = true
}

const handleSave = async () => {
  formErrors.value = {}
  const payload = { ...form }
  delete payload.id

  let result
  if (isEditing.value) {
    result = await semesterStore.updateSemester(form.id, payload)
  } else {
    result = await semesterStore.createSemester(payload)
  }

  if (result.success) {
    toast.success(result.message)
    showModal.value = false
  } else {
    if (result.errors) {
      formErrors.value = result.errors
    } else {
      toast.error(result.message)
    }
  }
}

const handleDelete = async (id) => {
  if (!confirm('Bạn chắc chắn muốn ẩn học kỳ này?')) return
  const result = await semesterStore.deleteSemester(id)
  if (result.success) {
    toast.success(result.message)
  } else {
    toast.error(result.message)
  }
}

const toggleStatus = async (item) => {
  const newStatus = !item.is_active
  const result = await semesterStore.updateSemester(item.id, {
    ...item,
    start_date: formatDateForInput(item.start_date),
    end_date: formatDateForInput(item.end_date),
    is_active: newStatus,
  })

  if (result.success) {
    toast.success('Cập nhật trạng thái thành công')
  } else {
    toast.error('Lỗi cập nhật trạng thái')
  }
}

onMounted(() => {
  semesterStore.fetchSemesters()
})
</script>

<template>
  <div class="p-6">
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Quản lý Học kỳ</h1>
        <p class="text-sm text-gray-500">Thiết lập thời gian và trạng thái các kỳ học.</p>
      </div>
      <button
        @click="openCreateModal"
        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center shadow-sm transition"
      >
        <SvgIcon name="plus" class="h-5 w-5 mr-2" />
        Tạo học kỳ mới
      </button>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Mã / Tên
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Năm học
            </th>
            <th
              class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Thời gian
            </th>
            <th
              class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Trạng thái
            </th>
            <th
              class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              Hành động
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="loading">
            <td colspan="5" class="px-6 py-4 text-center text-gray-500">Đang tải dữ liệu...</td>
          </tr>
          <tr v-else-if="semesters.length === 0">
            <td colspan="5" class="px-6 py-4 text-center text-gray-500">Chưa có học kỳ nào.</td>
          </tr>

          <tr v-for="item in semesters" :key="item.id" class="hover:bg-gray-50 transition">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-medium text-gray-900">{{ item.name }}</div>
              <div
                class="text-xs text-gray-500 font-mono bg-gray-100 inline-block px-2 py-0.5 rounded mt-1"
              >
                {{ item.code }}
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
              {{ item.year }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
              <div>
                Bắt đầu: <span class="font-medium">{{ formatDate(item.start_date) }}</span>
              </div>
              <div>
                Kết thúc: <span class="font-medium">{{ formatDate(item.end_date) }}</span>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
              <button
                @click="toggleStatus(item)"
                :class="[
                  'px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full cursor-pointer select-none transition-colors',
                  item.is_active
                    ? 'bg-green-100 text-green-800 hover:bg-green-200'
                    : 'bg-gray-100 text-gray-800 hover:bg-gray-200',
                ]"
              >
                {{ item.is_active ? 'Đang mở' : 'Đã đóng' }}
              </button>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button
                @click="openEditModal(item)"
                class="text-indigo-600 hover:text-indigo-900 mr-3 inline-flex items-center"
              >
                <SvgIcon name="edit" class="h-4 w-4 mr-1" /> Sửa
              </button>

              <button
                @click="handleDelete(item.id)"
                class="text-red-600 hover:text-red-900 inline-flex items-center"
              >
                <SvgIcon name="trash" class="h-4 w-4 mr-1" /> Ẩn
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm px-4"
      >
        <div
          class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden animate-fade-in-up"
        >
          <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">
              {{ isEditing ? 'Cập nhật Học kỳ' : 'Thêm Học kỳ mới' }}
            </h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>

          <form @submit.prevent="handleSave" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tên hiển thị</label>
                <input
                  v-model="form.name"
                  type="text"
                  placeholder="Ví dụ: Học kỳ 1"
                  class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                  required
                />
                <p v-if="formErrors.name" class="text-red-500 text-xs mt-1">
                  {{ formErrors.name[0] }}
                </p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mã học kỳ</label>
                <input
                  v-model="form.code"
                  type="text"
                  placeholder="HK1-2024"
                  class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 uppercase"
                  required
                />
                <p v-if="formErrors.code" class="text-red-500 text-xs mt-1">
                  {{ formErrors.code[0] }}
                </p>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Năm học</label>
              <input
                v-model="form.year"
                type="number"
                min="2000"
                class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                required
              />
              <p v-if="formErrors.year" class="text-red-500 text-xs mt-1">
                {{ formErrors.year[0] }}
              </p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ngày bắt đầu</label>
                <input
                  v-model="form.start_date"
                  type="date"
                  class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                  required
                />
                <p v-if="formErrors.start_date" class="text-red-500 text-xs mt-1">
                  {{ formErrors.start_date[0] }}
                </p>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ngày kết thúc</label>
                <input
                  v-model="form.end_date"
                  type="date"
                  class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                  required
                />
                <p v-if="formErrors.end_date" class="text-red-500 text-xs mt-1">
                  {{ formErrors.end_date[0] }}
                </p>
              </div>
            </div>

            <div class="flex items-center mt-2">
              <input
                v-model="form.is_active"
                id="is_active"
                type="checkbox"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label for="is_active" class="ml-2 block text-sm text-gray-900">
                Kích hoạt ngay (Sẽ tự động đóng các kỳ khác)
              </label>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t mt-4">
              <button
                type="button"
                @click="showModal = false"
                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
              >
                Hủy
              </button>
              <button
                type="submit"
                class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm"
              >
                {{ isEditing ? 'Cập nhật' : 'Tạo mới' }}
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
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
