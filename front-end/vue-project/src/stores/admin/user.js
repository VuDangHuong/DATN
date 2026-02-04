// File: src/stores/user.js
import { defineStore } from 'pinia'
import { ref, reactive } from 'vue'
import { userApi } from '@/api/admin/userApi'

export const useUserStore = defineStore('user', () => {
  // --- STATE (Dữ liệu) ---
  const users = ref([])
  const loading = ref(false)

  // Phân trang
  const pagination = reactive({
    current_page: 1,
    last_page: 1,
    total: 0,
  })

  // Bộ lọc tìm kiếm
  const filters = reactive({
    search: '',
    role: 'all',
    page: 1,
  })

  // 1. Lấy danh sách User
  async function fetchUsers() {
    loading.value = true
    try {
      const res = await userApi.getAll(filters)
      users.value = res.data.data

      // Cập nhật phân trang
      pagination.current_page = res.data.current_page
      pagination.last_page = res.data.last_page
      pagination.total = res.data.total
    } catch (error) {
      console.error('Lỗi tải danh sách:', error)
    } finally {
      loading.value = false
    }
  }

  // 2. Tạo User mới
  async function createUser(userData) {
    try {
      const response = await userApi.create(userData)
      const successMessage = response.data.message
      await fetchUsers() // Load lại danh sách sau khi thêm
      return { success: true, message: successMessage }
    } catch (error) {
      const msg = error.response?.data?.message || 'Lỗi thêm mới'
      return { success: false, message: msg }
    }
  }

  // 3. Cập nhật User
  async function updateUser(id, userData) {
    try {
      const response = await userApi.update(id, userData)
      const successMessage = response.data.message
      await fetchUsers()
      return { success: true, message: successMessage }
    } catch (error) {
      const msg = error.response?.data?.message || 'Lỗi cập nhật'
      return { success: false, message: msg }
    }
  }

  // 4. Xóa User
  async function deleteUser(id) {
    try {
      const response = await userApi.delete(id)
      const successMessage = response.data.message
      await fetchUsers()
      return { success: true, message: successMessage }
    } catch (error) {
      const errorMsg = error.response?.data?.message || 'Xóa thất bại'
      return { success: false, message: errorMsg }
    }
  }

  // 5. Toggle Status (Khóa/Mở)
  async function toggleStatus(user) {
    const originalStatus = user.is_active
    try {
      // Cập nhật UI trước cho mượt (Optimistic UI)
      user.is_active = !user.is_active
      await userApi.update(user.id, { is_active: user.is_active })
    } catch (error) {
      // Nếu lỗi thì revert lại trạng thái cũ
      user.is_active = originalStatus
      throw error
    }
  }

  // 6. Import Excel
  async function importUsers(formData) {
    try {
      const response = await userApi.importExcel(formData)
      const successMessage = response.data.message
      await fetchUsers()
      return { success: true, message: successMessage }
    } catch (error) {
      const msg = error.response?.data?.message || 'Lỗi import'
      return { success: false, message: msg }
    }
  }

  // Helper: Đổi trang
  function setPage(page) {
    if (page >= 1 && page <= pagination.last_page) {
      filters.page = page
      fetchUsers()
    }
  }

  // Helper: Đổi bộ lọc
  function setFilterRole(role) {
    filters.role = role
    filters.page = 1
    fetchUsers()
  }

  return {
    users,
    loading,
    pagination,
    filters,
    fetchUsers,
    createUser,
    updateUser,
    deleteUser,
    toggleStatus,
    importUsers,
    setPage,
    setFilterRole,
  }
})
