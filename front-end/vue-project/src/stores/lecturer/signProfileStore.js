// src/stores/lecturer/signProfileStore.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { signProfileApi } from '@/api/lecturer/signProfileApi'

export const useSignProfileStore = defineStore('signProfile', () => {
  // ─── State ──────────────────────
  const profile = ref(null) // chữ ký active hiện tại
  const history = ref([]) // lịch sử các chữ ký
  const pendingRequest = ref(null)
  const deactivationRequests = ref([])
  const loading = ref(false)
  const submitting = ref(false)

  // Getters
  const hasProfile = computed(() => !!profile.value)
  const isValid = computed(() => profile.value?.is_valid ?? false)
  const isExpired = computed(() => profile.value?.is_expired ?? false)
  const isExpiringSoon = computed(() => profile.value?.is_expiring_soon ?? false)
  const daysUntilExpire = computed(() => profile.value?.days_until_expired ?? null)

  // Đang chờ admin duyệt vô hiệu hóa
  const isPendingDeactivation = computed(
    () => profile.value?.pending_deactivation === true || !!pendingRequest.value,
  )

  // Có ký được không
  // Điều kiện: có profile + active + không pending deactivation + chưa hết hạn
  const canSign = computed(
    () =>
      hasProfile.value &&
      profile.value?.is_active &&
      !isPendingDeactivation.value &&
      !isExpired.value,
  )

  // ─── Actions ────────────────────
  async function fetchProfile() {
    loading.value = true
    try {
      const { data } = await signProfileApi.show()
      profile.value = data.profile
    } catch (e) {
      if (e.response?.status === 404) {
        profile.value = null
      } else {
        throw e
      }
    } finally {
      loading.value = false
    }
  }

  async function fetchHistory() {
    try {
      const { data } = await signProfileApi.history()
      history.value = data.profiles ?? []
    } catch {
      history.value = []
    }
  }

  async function register(formData) {
    submitting.value = true
    try {
      const { data } = await signProfileApi.upsert(formData)
      profile.value = data.profile
      await fetchHistory()
      return { success: true, data: data.profile, message: data.message }
    } catch (e) {
      return {
        success: false,
        message: e.response?.data?.message ?? 'Đăng ký thất bại',
        errors: e.response?.data?.errors ?? {},
      }
    } finally {
      submitting.value = false
    }
  }


  // GV gửi yêu cầu vô hiệu hóa
  async function requestDeactivation(reason) {
    submitting.value = true
    try {
      const { data } = await signProfileApi.requestDeactivation(reason)
      // Cập nhật pending request local
      pendingRequest.value = data.request
      // Cập nhật profile (đánh dấu pending)
      if (profile.value) {
        profile.value.pending_deactivation = true
      }
      return { success: true, message: data.message }
    } catch (e) {
      return {
        success: false,
        message: e.response?.data?.message ?? 'Gửi yêu cầu thất bại',
      }
    } finally {
      submitting.value = false
    }
  }

  // Lấy request pending hiện tại (call khi vào trang)
  async function fetchPendingRequest() {
    try {
      const { data } = await signProfileApi.getCurrentPendingRequest()
      pendingRequest.value = data.request // null nếu không có
    } catch {
      pendingRequest.value = null
    }
  }

  // Lịch sử tất cả yêu cầu vô hiệu của GV
  async function fetchDeactivationRequests() {
    try {
      const { data } = await signProfileApi.getDeactivationRequests()
      deactivationRequests.value = data.requests ?? []
    } catch {
      deactivationRequests.value = []
    }
  }

  function reset() {
    profile.value = null
    history.value = []
    pendingRequest.value = null
    deactivationRequests.value = []
  }

  return {
    // state
    profile,
    history,
    pendingRequest,
    deactivationRequests,
    loading,
    submitting,
    // getters
    hasProfile,
    isValid,
    isExpired,
    isExpiringSoon,
    daysUntilExpire,
    isPendingDeactivation,
    canSign,
    // actions
    fetchProfile,
    fetchHistory,
    register,
    requestDeactivation,
    fetchPendingRequest,
    fetchDeactivationRequests,
    reset,
  }
})
