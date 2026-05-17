// src/stores/admin/sign/adminSignProfileStore.js
import { adminSignProfileApi } from '@/api/admin/sign/adminSignProfileApi'
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAdminSignProfileStore = defineStore('adminSignProfile', () => {
  // ─── Profiles ────────────────
  const profiles = ref([])
  const stats = ref({
    total: 0,
    active: 0,
    pending_deactivation: 0,
    inactive: 0,
    expired: 0,
    pending_requests: 0,
  })
  const pagination = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 })

  // ─── Deactivation requests ───
  const requests = ref([])
  const requestsPagination = ref({ current_page: 1, last_page: 1, per_page: 20, total: 0 })
  const currentRequest = ref(null)

  const loading = ref(false)

  // ─── Profiles ─────────────────────
  async function fetchProfiles({ status = '', search = '', page = 1, perPage = 20 } = {}) {
    loading.value = true
    try {
      const { data } = await adminSignProfileApi.list({
        status,
        search,
        page,
        per_page: perPage,
      })
      profiles.value = data.profiles
      pagination.value = data.pagination
      return { success: true }
    } catch (e) {
      return { success: false, message: e.response?.data?.message }
    } finally {
      loading.value = false
    }
  }

  async function fetchStats() {
    try {
      const { data } = await adminSignProfileApi.stats()
      stats.value = data.stats
    } catch (e) {
      console.error(e)
    }
  }

  // ─── Deactivation requests ──────
  async function fetchRequests({ status = '', search = '', page = 1, perPage = 20 } = {}) {
    loading.value = true
    try {
      const { data } = await adminSignProfileApi.listRequests({
        status,
        search,
        page,
        per_page: perPage,
      })
      requests.value = data.requests
      requestsPagination.value = data.pagination
      return { success: true }
    } catch (e) {
      return { success: false, message: e.response?.data?.message }
    } finally {
      loading.value = false
    }
  }

  async function fetchRequestDetail(id) {
    try {
      const { data } = await adminSignProfileApi.showRequest(id)
      currentRequest.value = data.request
      return { success: true, data: data.request }
    } catch (e) {
      return { success: false, message: e.response?.data?.message }
    }
  }

  async function approveRequest(id) {
    try {
      const { data } = await adminSignProfileApi.approveRequest(id)
      // Cập nhật local
      const idx = requests.value.findIndex((r) => r.id === id)
      if (idx >= 0) requests.value[idx].status = 'approved'
      return { success: true, message: data.message }
    } catch (e) {
      return { success: false, message: e.response?.data?.message }
    }
  }

  async function rejectRequest(id, note) {
    try {
      const { data } = await adminSignProfileApi.rejectRequest(id, note)
      const idx = requests.value.findIndex((r) => r.id === id)
      if (idx >= 0) {
        requests.value[idx].status = 'rejected'
        requests.value[idx].admin_note = note
      }
      return { success: true, message: data.message }
    } catch (e) {
      return { success: false, message: e.response?.data?.message }
    }
  }

  return {
    profiles,
    stats,
    pagination,
    requests,
    requestsPagination,
    currentRequest,
    loading,
    fetchProfiles,
    fetchStats,
    fetchRequests,
    fetchRequestDetail,
    approveRequest,
    rejectRequest,
  }
})
