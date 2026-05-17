// src/stores/lecturer/lecturerDashboardStore.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import axiosClient from '@/api/axiosClient'

export const useLecturerDashboardStore = defineStore('lecturerDashboard', () => {
  const stats = ref({
    classes_count: 0,
    students_count: 0,
    groups_count: 0,
    submissions_pending: 0,
    sign_requests_pending: 0,
    sign_requests_signed_today: 0,
  })

  const charts = ref({
    sign_activity_7days: { labels: [], datasets: [] },
    sign_requests_status: [],
    submissions_status: [],
    submissions_by_class: [],
  })

  const pendingSubmissions = ref([])
  const pendingSignRequests = ref([])
  const recentActivities = ref([])

  const loading = ref(false)
  const error = ref(null)

  async function fetchDashboard() {
    loading.value = true
    error.value = null
    try {
      const { data } = await axiosClient.get('/lecturer/dashboard')
      stats.value = data.stats
      charts.value = data.charts ?? charts.value
      pendingSubmissions.value = data.pending_submissions ?? []
      pendingSignRequests.value = data.pending_sign_requests ?? []
      recentActivities.value = data.recent_activities ?? []
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Lỗi tải dashboard'
    } finally {
      loading.value = false
    }
  }

  return {
    stats,
    charts,
    pendingSubmissions,
    pendingSignRequests,
    recentActivities,
    loading,
    error,
    fetchDashboard,
  }
})
