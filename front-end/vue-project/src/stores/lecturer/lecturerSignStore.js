// src/stores/lecturer/lecturerSignStore.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { lecturerSignApi } from '@/api/lecturer/lecturerSignApi'
import { useToastStore } from '@/stores/toast'

export const useLecturerSignStore = defineStore('lecturerSign', () => {
  const toast = useToastStore()

  // ── State ────────────────────────────────────────────
  const requests = ref([])
  const selectedRequest = ref(null)
  const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
  const filterStatus = ref('')
  const stats = ref({ total: 0, pending: 0, signed: 0, completed: 0 })

  const loading = ref(false)
  const signing = ref(false)
  const rejecting = ref(false)
  const previewing = ref(false)

  // ── Computed ─────────────────────────────────────────
  const canSign = computed(() =>
    ['forwarded', 'lecturer_reviewing'].includes(selectedRequest.value?.status),
  )

  // ── Actions ──────────────────────────────────────────
  async function loadRequests(page = 1) {
    loading.value = true
    try {
      const { data } = await lecturerSignApi.getRequests({
        status: filterStatus.value || undefined,
        page,
      })
      requests.value = data.data
      pagination.value = {
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total,
      }
      calcStats(data.data)
    } catch {
      requests.value = []
    } finally {
      loading.value = false
    }
  }

  function calcStats(list) {
    stats.value = {
      total: pagination.value.total,
      pending: list.filter((r) => ['forwarded', 'lecturer_reviewing'].includes(r.status)).length,
      signed: list.filter((r) => r.status === 'signed').length,
      completed: list.filter((r) => r.status === 'completed').length,
    }
  }

  async function loadDetail(req) {
    selectedRequest.value = null
    try {
      const { data } = await lecturerSignApi.getDetail(req.id)
      selectedRequest.value = data.data
    } catch {
      toast.error('Không thể tải chi tiết')
    }
  }

  async function previewFile() {
    if (!selectedRequest.value) return
    previewing.value = true
    try {
      const { data } = await lecturerSignApi.preview(selectedRequest.value.id)
      window.open(data.url, '_blank')
    } catch {
      toast.error('Không thể tải file gốc')
    } finally {
      previewing.value = false
    }
  }

  async function signRequest() {
    if (!selectedRequest.value) return false
    signing.value = true
    try {
      await lecturerSignApi.sign(selectedRequest.value.id)
      toast.success('Đã xác nhận ký số! Admin sẽ phát hành cho sinh viên.')
      closeDetail()
      await loadRequests()
      return true
    } catch (e) {
      toast.error(e.response?.data?.message ?? 'Lỗi khi xác nhận ký số')
      return false
    } finally {
      signing.value = false
    }
  }

  async function rejectRequest(reason) {
    if (!selectedRequest.value) return false
    rejecting.value = true
    try {
      await lecturerSignApi.reject(selectedRequest.value.id, reason)
      toast.success('Đã từ chối yêu cầu ký số')
      closeDetail()
      await loadRequests()
      return true
    } catch (e) {
      toast.error(e.response?.data?.message ?? 'Lỗi khi từ chối')
      return false
    } finally {
      rejecting.value = false
    }
  }

  function setFilter(value) {
    filterStatus.value = value
    loadRequests()
  }

  function changePage(page) {
    if (page < 1 || page > pagination.value.last_page) return
    loadRequests(page)
  }

  function closeDetail() {
    selectedRequest.value = null
  }

  return {
    // State
    requests,
    selectedRequest,
    pagination,
    filterStatus,
    stats,
    loading,
    signing,
    rejecting,
    previewing,
    // Computed
    canSign,
    // Actions
    loadRequests,
    loadDetail,
    previewFile,
    signRequest,
    rejectRequest,
    setFilter,
    changePage,
    closeDetail,
  }
})
