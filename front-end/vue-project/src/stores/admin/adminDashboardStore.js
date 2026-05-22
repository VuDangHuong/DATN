// src/stores/admin/adminDashboardStore.js

import { defineStore } from 'pinia'
import { ref } from 'vue'
import axiosClient from '@/api/axiosClient'

export const useAdminDashboardStore = defineStore('adminDashboard', () => {
  const stats = ref({})
  const charts = ref({})
  const recentUsers = ref([])
  const recentClasses = ref([])
  const topLecturers = ref([])
  const recentActivities = ref([])

  const loading = ref(false)
  const lastError = ref('')

  async function fetchDashboard() {
    loading.value = true
    lastError.value = ''
    try {
      const { data } = await axiosClient.get('/admin/dashboard')

      stats.value = data.stats ?? {}
      charts.value = data.charts ?? {}
      recentUsers.value = data.recent_users ?? []
      recentClasses.value = data.recent_classes ?? []
      topLecturers.value = data.top_lecturers ?? []
      recentActivities.value = data.recent_activities ?? []

      console.log('Dashboard loaded:', data)
      return { success: true }
    } catch (e) {
      lastError.value = e.response?.data?.message ?? e.message
      console.error('fetchDashboard error:', e.response?.data ?? e)
      return { success: false, message: lastError.value }
    } finally {
      loading.value = false
    }
  }

  return {
    stats,
    charts,
    recentUsers,
    recentClasses,
    topLecturers,
    recentActivities,
    loading,
    lastError,
    fetchDashboard,
  }
})
