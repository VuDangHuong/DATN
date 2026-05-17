// src/stores/admin/adminDashboardStore.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import axiosClient from '@/api/axiosClient'

export const useAdminDashboardStore = defineStore('adminDashboard', () => {
  const stats = ref({
    total_users: 0,
    total_admins: 0,
    total_lecturers: 0,
    total_students: 0,
    active_users: 0,
    total_classes: 0,
    active_classes: 0,
    total_subjects: 0,
    total_semesters: 0,
    total_groups: 0,
    total_assignments: 0,
    total_submissions: 0,
    submissions_pending: 0,
    total_sign_requests: 0,
    sign_requests_pending: 0,
    sign_requests_signed: 0,
    lecturers_with_pki: 0,
    new_users_today: 0,
    submissions_today: 0,
    sign_requests_today: 0,
  })

  const charts = ref({
    users_growth_12months: { labels: [], datasets: [] },
    system_activity_30days: { labels: [], datasets: [] },
    users_by_role: [],
    sign_requests_status: [],
    submissions_status: [],
    top_classes_by_groups: [],
  })

  const recentUsers = ref([])
  const recentClasses = ref([])
  const recentActivities = ref([])
  const topLecturers = ref([])

  const loading = ref(false)
  const error = ref(null)

  async function fetchDashboard() {
    loading.value = true
    error.value = null
    try {
      const { data } = await axiosClient.get('/admin/dashboard')
      stats.value = data.stats
      charts.value = data.charts ?? charts.value
      recentUsers.value = data.recent_users ?? []
      recentClasses.value = data.recent_classes ?? []
      recentActivities.value = data.recent_activities ?? []
      topLecturers.value = data.top_lecturers ?? []
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Lỗi tải dashboard'
    } finally {
      loading.value = false
    }
  }

  return {
    stats,
    charts,
    recentUsers,
    recentClasses,
    recentActivities,
    topLecturers,
    loading,
    error,
    fetchDashboard,
  }
})
