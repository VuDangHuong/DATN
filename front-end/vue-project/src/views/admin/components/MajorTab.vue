<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { useCategoryStore } from '@/stores/admin/category'
import { categoryApi } from '@/api/admin/category'
import { useToastStore } from '@/stores/toast'
import SvgIcon from '@/components/icons/SVG.vue'

const store = useCategoryStore()
const toast = useToastStore()

const majors = ref([])
const selectedFaculty = ref('')
const showModal = ref(false)
const isEditing = ref(false)
const form = reactive({ id: null, faculty_id: '', code: '', name: '' })

// Lấy danh sách ngành
const loadMajors = async () => {
  const res = await categoryApi.getMajors({ faculty_id: selectedFaculty.value || null })
  majors.value = res.data
}

// Khi đổi khoa ở dropdown lọc
watch(selectedFaculty, loadMajors)

const openModal = (item = null) => {
  if (item) {
    isEditing.value = true
    Object.assign(form, item)
  } else {
    isEditing.value = false
    // Tự động điền khoa đang chọn vào form thêm mới
    Object.assign(form, { id: null, faculty_id: selectedFaculty.value || '', code: '', name: '' })
  }
  showModal.value = true
}

const handleSave = async () => {
  try {
    if (isEditing.value) {
      await categoryApi.updateMajor(form.id, form)
      toast.success('Cập nhật ngành thành công')
    } else {
      await categoryApi.createMajor(form)
      toast.success('Thêm ngành thành công')
    }
    loadMajors()
    showModal.value = false
  } catch (e) {
    toast.error(e.response?.data?.message || 'Có lỗi xảy ra')
  }
}

const handleDelete = async (id) => {
  if (!confirm('Bạn có chắc muốn xóa ngành này?')) return
  try {
    await categoryApi.deleteMajor(id)
    toast.success('Đã xóa ngành')
    loadMajors()
  } catch (e) {
    toast.error(e.response?.data?.message)
  }
}

onMounted(() => {
  loadMajors()
})
</script>

<template>
  <div>
    <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
      <div class="flex items-center gap-2 w-full md:w-auto">
        <label class="text-sm font-medium whitespace-nowrap">Lọc theo Khoa:</label>
        <select v-model="selectedFaculty" class="border rounded p-2 text-sm w-full md:w-64">
          <option value="">-- Tất cả các khoa --</option>
          <option v-for="f in store.faculties" :key="f.id" :value="f.id">{{ f.name }}</option>
        </select>
      </div>

      <button
        @click="openModal()"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center whitespace-nowrap"
      >
        <SvgIcon name="plus" class="w-4 h-4 mr-2" /> Thêm Ngành
      </button>
    </div>

    <table class="w-full border-collapse">
      <thead>
        <tr class="bg-gray-50 border-b">
          <th class="p-3 text-left">Mã Ngành</th>
          <th class="p-3 text-left">Tên Ngành</th>
          <th class="p-3 text-left">Thuộc Khoa</th>
          <th class="p-3 text-right">Hành động</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="majors.length === 0">
          <td colspan="4" class="p-4 text-center text-gray-500">Chưa có dữ liệu</td>
        </tr>
        <tr v-for="item in majors" :key="item.id" class="border-b hover:bg-gray-50">
          <td class="p-3 font-mono text-blue-600">{{ item.code }}</td>
          <td class="p-3 font-medium">{{ item.name }}</td>
          <td class="p-3 text-sm text-gray-600">{{ item.faculty?.name || '---' }}</td>
          <td class="p-3 text-right space-x-2">
            <button @click="openModal(item)" class="text-indigo-600 hover:underline">Sửa</button>
            <button @click="handleDelete(item.id)" class="text-red-600 hover:underline">Xóa</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div
      v-if="showModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white p-6 rounded-lg w-96">
        <h3 class="text-lg font-bold mb-4">{{ isEditing ? 'Sửa Ngành' : 'Thêm Ngành' }}</h3>

        <label class="block text-sm mb-1">Khoa trực thuộc</label>
        <select v-model="form.faculty_id" class="w-full mb-3 p-2 border rounded">
          <option disabled value="">Chọn khoa</option>
          <option v-for="f in store.faculties" :key="f.id" :value="f.id">{{ f.name }}</option>
        </select>

        <label class="block text-sm mb-1">Mã ngành</label>
        <input
          v-model="form.code"
          placeholder="VD: 7480201"
          class="w-full mb-3 p-2 border rounded"
        />

        <label class="block text-sm mb-1">Tên ngành</label>
        <input
          v-model="form.name"
          placeholder="VD: Công nghệ thông tin"
          class="w-full mb-4 p-2 border rounded"
        />

        <div class="flex justify-end gap-2">
          <button
            @click="showModal = false"
            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded"
          >
            Hủy
          </button>
          <button @click="handleSave" class="px-4 py-2 bg-blue-600 text-white rounded">Lưu</button>
        </div>
      </div>
    </div>
  </div>
</template>
