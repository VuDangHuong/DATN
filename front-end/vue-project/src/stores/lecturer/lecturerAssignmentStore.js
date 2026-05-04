import { lecturerAssignmentApi } from '@/api/lecturer/lecturerAssignmentApi'
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useLecturerAssignmentStore = defineStore('lecturerAssignment', () => {
  const assignments = ref([])
  const currentAssignment = ref(null)
  const submissions = ref([])
  const missing = ref([])
  const stats = ref({})
  const loading = ref(false)
  const error = ref(null)
  const documentCategories = ref([])
  async function fetchByClass(classId) {
    loading.value = true
    error.value = null
    try {
      const { data } = await lecturerAssignmentApi.getByClass(classId)
      assignments.value = data
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Lỗi tải danh sách'
    } finally {
      loading.value = false
    }
  }

  async function fetchDetail(id) {
    loading.value = true
    try {
      const { data } = await lecturerAssignmentApi.getDetail(id)
      currentAssignment.value = data.assignment
      submissions.value = data.submissions
      missing.value = data.missing
      stats.value = data.stats
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Lỗi tải chi tiết'
    } finally {
      loading.value = false
    }
  }

  async function createAssignment(classId, payload) {
    try {
      const { data } = await lecturerAssignmentApi.create(classId, payload)
      assignments.value.unshift(data.assignment)
      return { success: true, data }
    } catch (e) {
      return { success: false, message: e.response?.data?.message ?? 'Lỗi tạo đợt nộp' }
    }
  }

  async function updateAssignment(id, payload) {
    try {
      const { data } = await lecturerAssignmentApi.update(id, payload)
      const idx = assignments.value.findIndex((a) => a.id === id)
      if (idx !== -1) assignments.value[idx] = data.assignment
      if (currentAssignment.value?.id === id) currentAssignment.value = data.assignment
      return { success: true }
    } catch (e) {
      return { success: false, message: e.response?.data?.message ?? 'Lỗi cập nhật' }
    }
  }

  async function deleteAssignment(id) {
    try {
      await lecturerAssignmentApi.delete(id)
      assignments.value = assignments.value.filter((a) => a.id !== id)
      return { success: true }
    } catch (e) {
      return { success: false, message: e.response?.data?.message ?? 'Lỗi xóa' }
    }
  }

  async function loadDocumentCategories() {
    try {
      const { data } = await lecturerAssignmentApi.getDocumentCategories()
      documentCategories.value = data
    } catch {
      // Fallback hardcode
      documentCategories.value = [
        { value: 'bao_cao_thuc_tap', label: 'Báo cáo thực tập' },
        { value: 'nckh', label: 'Nghiên cứu khoa học' },
        { value: 'do_an_tot_nghiep', label: 'Đồ án tốt nghiệp' },
        { value: 'bao_cao_du_an', label: 'Báo cáo dự án' },
        { value: 'khoa_luan', label: 'Khóa luận' },
      ]
    }
  }

  return {
    assignments,
    currentAssignment,
    submissions,
    missing,
    stats,
    loading,
    error,
    fetchByClass,
    fetchDetail,
    createAssignment,
    updateAssignment,
    deleteAssignment,
    documentCategories,
    loadDocumentCategories,
  }
})
