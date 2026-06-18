import { defineStore } from 'pinia'
import { ref } from 'vue'
import { semesterApi } from '@/api/admin/semester'

export const useSemesterStore = defineStore('semester', () => {
  const semesters = ref([])
  const loading = ref(false)
  const error = ref(null)
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 5,
    total: 0,
  })
  // --- ACTIONS ---

  // 1. Lấy danh sách học kỳ
  async function fetchSemesters(params = {}) {
    loading.value = true
    try {
      const response = await semesterApi.getAll(params)
      const data = response.data
      semesters.value = data.data ?? []
      pagination.value = {
        current_page: data.current_page ?? 1,
        last_page: data.last_page ?? 1,
        per_page: data.per_page ?? 5,
        total: data.total ?? 0,
      }
    } catch (err) {
      error.value = err
      console.error('Lỗi tải danh sách học kỳ:', err)
    } finally {
      loading.value = false
    }
  }

  // 2. Tạo mới
  async function createSemester(data) {
    loading.value = true
    try {
      const response = await semesterApi.create(data)
      // Sau khi tạo xong, fetch lại list để đảm bảo thứ tự sắp xếp từ backend
      await fetchSemesters()
      return { success: true, message: response.data.message }
    } catch (err) {
      return {
        success: false,
        message: err.response?.data?.message || 'Lỗi khi tạo học kỳ.',
        errors: err.response?.data?.errors, // Trả về lỗi validation nếu có
      }
    } finally {
      loading.value = false
    }
  }

  // 3. Cập nhật
  async function updateSemester(id, data) {
    loading.value = true
    try {
      const response = await semesterApi.update(id, data)
      await fetchSemesters() // Refresh list để cập nhật trạng thái các kỳ khác (nếu kỳ này active)
      return { success: true, message: response.data.message }
    } catch (err) {
      return {
        success: false,
        message: err.response?.data?.message || 'Lỗi khi cập nhật.',
        errors: err.response?.data?.errors,
      }
    } finally {
      loading.value = false
    }
  }

  // 4. Xóa mềm (Ẩn)
  async function deleteSemester(id) {
    loading.value = true
    try {
      const response = await semesterApi.delete(id)
      // Update trực tiếp state để đỡ gọi API lại (vì delete chỉ đổi status)
      const target = semesters.value.find((s) => s.id === id)
      if (target) target.is_active = false

      return { success: true, message: response.data.message }
    } catch (err) {
      return { success: false, message: err.response?.data?.message || 'Lỗi khi xóa.' }
    } finally {
      loading.value = false
    }
  }

  return {
    semesters,
    loading,
    fetchSemesters,
    createSemester,
    updateSemester,
    deleteSemester,
    pagination,
  }
})
