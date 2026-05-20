import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { studentMaterialApi } from '@/api/students/materialApi'

export const useStudentMaterialStore = defineStore('studentMaterial', () => {
  const materials = ref([])
  const stats = ref({})
  const loading = ref(false)

  const filters = ref({ category: '', search: '' })

  const totalMaterials = computed(() => materials.value.length)
  const totalFiles = computed(() =>
    materials.value.reduce((sum, m) => sum + (m.file_count ?? 0), 0),
  )

  const categoryStats = computed(() => ({
    lecture: stats.value.lecture ?? 0,
    exercise: stats.value.exercise ?? 0,
    reference: stats.value.reference ?? 0,
    exam: stats.value.exam ?? 0,
    other: stats.value.other ?? 0,
  }))

  async function fetchMaterials(classId) {
    loading.value = true
    try {
      const { data } = await studentMaterialApi.getMaterials(classId, filters.value)
      materials.value = data.materials ?? []
      stats.value = data.stats ?? {}
      return { success: true }
    } catch (e) {
      return {
        success: false,
        message: e.response?.data?.message ?? 'Không thể tải tài liệu',
      }
    } finally {
      loading.value = false
    }
  }

  async function downloadFile(fileId, materialId) {
    try {
      const { data } = await studentMaterialApi.downloadFile(fileId)

      // Mở tab mới để tải
      if (data.download_url) {
        window.open(data.download_url, '_blank')
      }

      // Update local download count
      const m = materials.value.find((m) => m.id === materialId)
      if (m) {
        const f = m.files?.find((f) => f.id === fileId)
        if (f) f.download_count = (f.download_count ?? 0) + 1
      }

      return { success: true }
    } catch (e) {
      return {
        success: false,
        message: e.response?.data?.message ?? 'Không thể tải file',
      }
    }
  }

  function setFilter(key, value) {
    filters.value[key] = value
  }

  function reset() {
    materials.value = []
    stats.value = {}
    filters.value = { category: '', search: '' }
  }

  return {
    materials,
    stats,
    loading,
    filters,
    totalMaterials,
    totalFiles,
    categoryStats,
    fetchMaterials,
    downloadFile,
    setFilter,
    reset,
  }
})
