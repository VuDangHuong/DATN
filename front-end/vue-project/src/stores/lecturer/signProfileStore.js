// src/stores/lecturer/signProfileStore.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { signProfileApi } from '@/api/lecturer/signProfileApi'

export const useSignProfileStore = defineStore('signProfile', () => {
  // State
  const profile = ref(null) // chữ ký active hiện tại
  const history = ref([]) // lịch sử các chữ ký
  const loading = ref(false)
  const submitting = ref(false)

  // Getters
  const hasProfile = computed(() => !!profile.value)
  const isValid = computed(() => profile.value?.is_valid ?? false)
  const isExpired = computed(() => profile.value?.is_expired ?? false)
  const isExpiringSoon = computed(() => profile.value?.is_expiring_soon ?? false)
  const daysUntilExpire = computed(() => profile.value?.days_until_expired ?? null)

  // Actions
  async function fetchProfile() {
    loading.value = true
    try {
      const { data } = await signProfileApi.show()
      profile.value = data.profile
    } catch (e) {
      // 404 — chưa đăng ký
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
      await fetchHistory() // refresh history
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

  // src/stores/lecturer/signProfileStore.js
  async function deactivate(accountPassword) {
    submitting.value = true
    try {
      await signProfileApi.deactivate(accountPassword)
      profile.value = null
      await fetchHistory()
      return { success: true }
    } catch (e) {
      return {
        success: false,
        message: e.response?.data?.message ?? 'Có lỗi xảy ra',
      }
    } finally {
      submitting.value = false
    }
  }

  function reset() {
    profile.value = null
    history.value = []
  }

  return {
    // state
    profile,
    history,
    loading,
    submitting,
    // getters
    hasProfile,
    isValid,
    isExpired,
    isExpiringSoon,
    daysUntilExpire,
    // actions
    fetchProfile,
    fetchHistory,
    register,
    deactivate,
    reset,
  }
})
