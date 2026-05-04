// src/stores/admin/adminSignStore.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useToastStore } from '@/stores/toast'
import { adminSignApi } from '@/api/admin/sign/adminSignApi'

export const useAdminSignStore = defineStore('adminSign', () => {
  const toast = useToastStore()

  // ── State ────────────────────────────────────────────
  const requests = ref([])
  const selectedRequest = ref(null)
  const lecturers = ref([])
  const documentCategories = ref([])
  const stats = ref({ total: 0, pending: 0, forwarded: 0, completed: 0 })
  const statsByCategory = ref([])
  const pagination = ref({ current_page: 1, last_page: 1, total: 0 })

  const filterStatus = ref('')
  const filterCategory = ref('')

  const loading = ref(false)
  const forwarding = ref(false)
  const forwardingId = ref(null)
  const rejecting = ref(false)
  const completing = ref(false)

  // ── Computed ─────────────────────────────────────────
  const canForward = computed(() =>
    ['pending', 'admin_reviewing'].includes(selectedRequest.value?.status),
  )
  const canReject = computed(() =>
    ['pending', 'admin_reviewing'].includes(selectedRequest.value?.status),
  )
  const canComplete = computed(() => selectedRequest.value?.status === 'signed')

  // ── Actions ──────────────────────────────────────────
  async function loadRequests(page = 1) {
    loading.value = true
    try {
      const { data } = await adminSignApi.getRequests({
        status: filterStatus.value || undefined,
        category: filterCategory.value || undefined,
        page,
      })
      requests.value = data.data
      pagination.value = {
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total,
      }
    } catch {
      requests.value = []
    } finally {
      loading.value = false
    }
  }

  async function loadStats() {
    try {
      const { data } = await adminSignApi.getStats()
      const byStatus = {}
      let total = 0
      ;(data.by_status ?? []).forEach((s) => {
        byStatus[s.status] = s.total
        total += s.total
      })
      stats.value = {
        total,
        pending: (byStatus.pending ?? 0) + (byStatus.admin_reviewing ?? 0),
        forwarded: (byStatus.forwarded ?? 0) + (byStatus.lecturer_reviewing ?? 0),
        completed: byStatus.completed ?? 0,
      }
      statsByCategory.value = data.by_category ?? []
    } catch {}
  }

  async function loadLecturers() {
    try {
      const { data } = await adminSignApi.getLecturers()
      lecturers.value = data.data ?? data
    } catch {}
  }

  async function loadDocumentCategories() {
    try {
      const { data } = await adminSignApi.getDocumentCategories()
      documentCategories.value = data
    } catch {
      documentCategories.value = []
    }
  }

  async function loadDetail(req) {
    selectedRequest.value = null
    try {
      const { data } = await adminSignApi.getDetail(req.id)
      selectedRequest.value = data.data
      return data.data
    } catch {
      toast.error('Không thể tải chi tiết')
      return null
    }
  }

  // Forward thẳng GV lớp (từ table row)
  async function quickForward(req) {
    if (!req.lecturer?.id) {
      toast.error('Lớp chưa có GV phụ trách. Vào "Xem & Xử lý" để chọn thủ công.')
      return false
    }
    forwardingId.value = req.id
    try {
      await adminSignApi.forward(req.id, { lecturer_id: req.lecturer.id, note: '' })
      toast.success(`Đã chuyển cho ${req.lecturer.name}`)
      await Promise.all([loadRequests(), loadStats()])
      return true
    } catch (e) {
      toast.error(e.response?.data?.message ?? 'Lỗi khi chuyển yêu cầu')
      return false
    } finally {
      forwardingId.value = null
    }
  }

  // Forward GV lớp từ detail modal
  async function forwardToClassLecturer() {
    if (!selectedRequest.value?.lecturer?.id) return false
    forwarding.value = true
    try {
      await adminSignApi.forward(selectedRequest.value.id, {
        lecturer_id: selectedRequest.value.lecturer.id,
        note: '',
      })
      toast.success(`Đã chuyển cho ${selectedRequest.value.lecturer.name}`)
      selectedRequest.value = null
      await Promise.all([loadRequests(), loadStats()])
      return true
    } catch (e) {
      toast.error(e.response?.data?.message ?? 'Lỗi khi chuyển yêu cầu')
      return false
    } finally {
      forwarding.value = false
    }
  }

  // Forward GV khác (Admin chọn thủ công)
  async function forwardToOtherLecturer(lecturerId, note = '') {
    forwarding.value = true
    try {
      await adminSignApi.forward(selectedRequest.value.id, { lecturer_id: lecturerId, note })
      toast.success('Đã chuyển yêu cầu cho giảng viên')
      selectedRequest.value = null
      await Promise.all([loadRequests(), loadStats()])
      return true
    } catch (e) {
      toast.error(e.response?.data?.message ?? 'Lỗi khi chuyển yêu cầu')
      return false
    } finally {
      forwarding.value = false
    }
  }

  async function rejectRequest(reason) {
    rejecting.value = true
    try {
      await adminSignApi.reject(selectedRequest.value.id, reason)
      toast.success('Đã từ chối yêu cầu')
      selectedRequest.value = null
      await Promise.all([loadRequests(), loadStats()])
      return true
    } catch (e) {
      toast.error(e.response?.data?.message ?? 'Lỗi khi từ chối')
      return false
    } finally {
      rejecting.value = false
    }
  }

  async function completeRequest() {
    completing.value = true
    try {
      await adminSignApi.complete(selectedRequest.value.id)
      toast.success('Đã phát hành tài liệu cho sinh viên')
      selectedRequest.value = null
      await Promise.all([loadRequests(), loadStats()])
      return true
    } catch (e) {
      toast.error(e.response?.data?.message ?? 'Lỗi khi phát hành')
      return false
    } finally {
      completing.value = false
    }
  }

  async function previewFile(id) {
    try {
      const { data } = await adminSignApi.preview(id)
      window.open(data.url, '_blank')
    } catch {
      toast.error('Không thể tải file')
    }
  }

  function setStatusFilter(value) {
    filterStatus.value = value
    loadRequests()
  }

  function setCategoryFilter(category) {
    filterCategory.value = filterCategory.value === category ? '' : category
    loadRequests()
  }

  function resetFilter() {
    filterStatus.value = ''
    filterCategory.value = ''
    loadRequests()
  }

  function changePage(page, lastPage) {
    if (page < 1 || page > lastPage) return
    loadRequests(page)
  }

  function closeDetail() {
    selectedRequest.value = null
  }

  // ── Init ─────────────────────────────────────────────
  async function init() {
    await Promise.all([loadRequests(), loadStats(), loadLecturers(), loadDocumentCategories()])
  }

  return {
    // State
    requests,
    selectedRequest,
    lecturers,
    documentCategories,
    stats,
    statsByCategory,
    pagination,
    filterStatus,
    filterCategory,
    loading,
    forwarding,
    forwardingId,
    rejecting,
    completing,
    // Computed
    canForward,
    canReject,
    canComplete,
    // Actions
    init,
    loadRequests,
    loadStats,
    loadDetail,
    quickForward,
    forwardToClassLecturer,
    forwardToOtherLecturer,
    rejectRequest,
    completeRequest,
    previewFile,
    setStatusFilter,
    setCategoryFilter,
    resetFilter,
    changePage,
    closeDetail,
  }
})
