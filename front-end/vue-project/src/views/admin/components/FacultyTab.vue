<script setup>
import { ref, reactive } from 'vue'
import { useCategoryStore } from '@/stores/admin/category'
import { categoryApi } from '@/api/admin/category'
import { useToastStore } from '@/stores/toast'
import SvgIcon from '@/components/icons/SVG.vue'

const store = useCategoryStore()
const toast = useToastStore()

const isEditing = ref(false)
const showModal = ref(false)
const form = reactive({ id: null, code: '', name: '' })

// Actions
const openModal = (item = null) => {
  if (item) {
    isEditing.value = true
    Object.assign(form, item)
  } else {
    isEditing.value = false
    Object.assign(form, { id: null, code: '', name: '' })
  }
  showModal.value = true
}

const handleSave = async () => {
  try {
    if (isEditing.value) {
      await categoryApi.updateFaculty(form.id, form)
      toast.success('Cập nhật khoa thành công')
    } else {
      await categoryApi.createFaculty(form)
      toast.success('Thêm khoa thành công')
    }
    store.fetchFaculties() // Refresh store
    showModal.value = false
  } catch (e) {
    toast.error(e.response?.data?.message || 'Có lỗi xảy ra')
  }
}

const handleDelete = async (id) => {
  if (!confirm('Bạn có chắc muốn xóa khoa này?')) return
  try {
    await categoryApi.deleteFaculty(id)
    toast.success('Đã xóa khoa')
    store.fetchFaculties()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Không thể xóa')
  }
}
</script>

<template>
  <div>
    <div class="flex justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-700">Danh sách Khoa / Viện</h3>
      <button
        @click="openModal()"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center"
      >
        <SvgIcon name="plus" class="w-4 h-4 mr-2" /> Thêm Khoa
      </button>
    </div>

    <table class="w-full border-collapse">
      <thead>
        <tr class="bg-gray-50 border-b">
          <th class="p-3 text-left">Mã Khoa</th>
          <th class="p-3 text-left">Tên Khoa</th>
          <th class="p-3 text-right">Hành động</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in store.faculties" :key="item.id" class="border-b hover:bg-gray-50">
          <td class="p-3 font-mono text-blue-600">{{ item.code }}</td>
          <td class="p-3 font-medium">{{ item.name }}</td>
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
        <h3 class="text-lg font-bold mb-4">{{ isEditing ? 'Sửa Khoa' : 'Thêm Khoa' }}</h3>
        <input
          v-model="form.code"
          placeholder="Mã khoa (VD: CNTT)"
          class="w-full mb-3 p-2 border rounded"
        />
        <input v-model="form.name" placeholder="Tên khoa" class="w-full mb-4 p-2 border rounded" />
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
