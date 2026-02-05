<script setup>
import { ref, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useSemesterStore } from '@/stores/admin/semester'
import { useToastStore } from '@/stores/toast'
import SvgIcon from '@/components/icons/SVG.vue'
import SemesterModal from './components/SemesterModal.vue'

const semesterStore = useSemesterStore()
const toast = useToastStore()
const { semesters, loading } = storeToRefs(semesterStore)

const showModal = ref(false)
const editingItem = ref(null)
const formErrors = ref({})
const searchQuery = ref('')

const debounce = (fn, delay = 300) => {
  let timeout
  return (...args) => {
    clearTimeout(timeout)
    timeout = setTimeout(() => fn(...args), delay)
  }
}

const handleSearch = debounce(() => {
  semesterStore.fetchSemesters({ search: searchQuery.value })
}, 500)

const formatDate = (dateString) => {
  if (!dateString) return '---'
  const date = new Date(dateString)
  if (isNaN(date.getTime())) return dateString
  return date.toLocaleDateString('vi-VN')
}

// 1. Mở Modal Thêm mới
const openCreateModal = () => {
  editingItem.value = null
  formErrors.value = {}
  showModal.value = true
}

// 2. Mở Modal Sửa
const openEditModal = (item) => {
  editingItem.value = item
  formErrors.value = {}
  showModal.value = true
}

// 3. Xử lý sự kiện lưu từ Modal gửi về
const handleSave = async (formData) => {
  formErrors.value = {}
  const payload = { ...formData }
  delete payload.id

  let result
  if (editingItem.value) {
    result = await semesterStore.updateSemester(formData.id, payload)
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
  const formatDateForPayload = (d) => (d ? d.toString().substring(0, 10) : '')

  const result = await semesterStore.updateSemester(item.id, {
    ...item,
    start_date: formatDateForPayload(item.start_date),
    end_date: formatDateForPayload(item.end_date),
    is_active: !item.is_active,
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
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Quản lý Học kỳ</h1>
        <p class="text-sm text-gray-500">Thiết lập thời gian và trạng thái các kỳ học.</p>
      </div>

      <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
        <div class="relative w-full sm:w-64">
          <span
            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"
          >
            <SvgIcon name="search" class="h-5 w-5" />
          </span>
          <input
            v-model="searchQuery"
            @input="handleSearch"
            type="text"
            placeholder="Tìm theo tên hoặc mã..."
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm"
          />
        </div>

        <button
          @click="openCreateModal"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center justify-center shadow-sm transition whitespace-nowrap"
        >
          <SvgIcon name="plus" class="h-5 w-5 mr-2" />
          Tạo học kỳ mới
        </button>
      </div>
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
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ item.year }}</td>
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

    <SemesterModal
      :show="showModal"
      :editing-item="editingItem"
      :errors="formErrors"
      @close="showModal = false"
      @save="handleSave"
    />
  </div>
</template>
