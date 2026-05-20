import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { materialApi } from '@/api/lecturer/materialApi'

export const useMaterialStore = defineStore('material', () => {
  const materials = ref([])
  const stats = ref({})
  const loading = ref(false)
  const uploading = ref(false)
  const copying = ref(false)
  const copyTargets = ref([])

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
      const { data } = await materialApi.getMaterials(classId, filters.value)
      materials.value = data.materials ?? []
      stats.value = data.stats ?? {}
      return { success: true }
    } catch (e) {
      return { success: false, message: e.response?.data?.message ?? 'Lỗi tải' }
    } finally {
      loading.value = false
    }
  }

  // ✅ Tạo title MỚI với nhiều file
  async function createMaterial(classId, formData) {
    uploading.value = true
    try {
      const { data } = await materialApi.create(classId, formData)
      if (data.material) {
        materials.value.unshift(data.material)
      }
      return { success: true, message: data.message, material: data.material }
    } catch (e) {
      return { success: false, message: e.response?.data?.message ?? 'Upload thất bại' }
    } finally {
      uploading.value = false
    }
  }

  // ✅ Thêm file vào title đã có
  async function addFilesToMaterial(materialId, formData) {
    uploading.value = true
    try {
      const { data } = await materialApi.addFiles(materialId, formData)
      // Update local
      const idx = materials.value.findIndex((m) => m.id === materialId)
      if (idx >= 0 && data.material) {
        materials.value[idx] = data.material
      }
      return { success: true, message: data.message, material: data.material }
    } catch (e) {
      return { success: false, message: e.response?.data?.message ?? 'Thêm file thất bại' }
    } finally {
      uploading.value = false
    }
  }

  async function updateMaterial(materialId, data) {
    try {
      const { data: res } = await materialApi.update(materialId, data)
      const idx = materials.value.findIndex((m) => m.id === materialId)
      if (idx >= 0 && res.material) {
        materials.value[idx] = res.material
      }
      return { success: true, message: res.message }
    } catch (e) {
      return { success: false, message: e.response?.data?.message ?? 'Cập nhật thất bại' }
    }
  }

  async function deleteMaterial(materialId) {
    try {
      const { data } = await materialApi.deleteMaterial(materialId)
      materials.value = materials.value.filter((m) => m.id !== materialId)
      return { success: true, message: data.message }
    } catch (e) {
      return { success: false, message: e.response?.data?.message ?? 'Xóa thất bại' }
    }
  }

  async function deleteFile(fileId, materialId) {
    try {
      const { data } = await materialApi.deleteFile(fileId)
      const m = materials.value.find((m) => m.id === materialId)
      if (m) {
        m.files = m.files.filter((f) => f.id !== fileId)
        m.file_count = m.files.length
      }
      return { success: true, message: data.message }
    } catch (e) {
      return { success: false, message: e.response?.data?.message ?? 'Xóa thất bại' }
    }
  }

  async function copyMaterials(materialIds, targetClassIds) {
    copying.value = true
    try {
      const { data } = await materialApi.copy(materialIds, targetClassIds)
      return {
        success: true,
        message: data.message,
        copiedCount: data.copied_count,
        skippedCount: data.skipped_count,
        skippedReasons: data.skipped_reasons,
      }
    } catch (e) {
      return { success: false, message: e.response?.data?.message ?? 'Lỗi sao chép' }
    } finally {
      copying.value = false
    }
  }

  async function fetchCopyTargets(classId) {
    try {
      const { data } = await materialApi.getCopyTargets(classId)
      copyTargets.value = data.classes ?? []
      return { success: true, classes: data.classes, source: data.source }
    } catch (e) {
      return { success: false, message: e.response?.data?.message }
    }
  }

  async function downloadFile(fileId, materialId) {
    try {
      const { data } = await materialApi.downloadFile(fileId)
      if (data.download_url) {
        window.open(data.download_url, '_blank')
      }
      const m = materials.value.find((m) => m.id === materialId)
      if (m) {
        const f = m.files?.find((f) => f.id === fileId)
        if (f) f.download_count = (f.download_count ?? 0) + 1
      }
      return { success: true }
    } catch (e) {
      return { success: false, message: e.response?.data?.message ?? 'Không tải được' }
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
    uploading,
    copying,
    copyTargets,
    filters,
    totalMaterials,
    totalFiles,
    categoryStats,
    fetchMaterials,
    createMaterial,
    addFilesToMaterial,
    updateMaterial,
    deleteMaterial,
    deleteFile,
    copyMaterials,
    fetchCopyTargets,
    downloadFile,
    setFilter,
    reset,
  }
})
