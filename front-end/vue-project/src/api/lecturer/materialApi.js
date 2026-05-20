import axiosClient from '../axiosClient'

export const materialApi = {
  // List
  getMaterials: (classId, params = {}) =>
    axiosClient.get(`/lecturer/classes/${classId}/materials`, { params }),

  //Tạo title MỚI với nhiều file
  create: (classId, formData) =>
    axiosClient.post(`/lecturer/classes/${classId}/materials`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),

  // Thêm file vào title đã có
  addFiles: (materialId, formData) =>
    axiosClient.post(`/lecturer/materials/${materialId}/files`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),

  // Update title/desc/category
  update: (materialId, data) => axiosClient.patch(`/lecturer/materials/${materialId}`, data),

  // Delete title (kéo theo files)
  deleteMaterial: (materialId) => axiosClient.delete(`/lecturer/materials/${materialId}`),

  // Delete 1 file con
  deleteFile: (fileId) => axiosClient.delete(`/lecturer/material-files/${fileId}`),

  // Copy
  copy: (materialIds, targetClassIds) =>
    axiosClient.post('/lecturer/materials/copy', {
      material_ids: materialIds,
      target_class_ids: targetClassIds,
    }),

  getCopyTargets: (classId) => axiosClient.get(`/lecturer/classes/${classId}/copy-targets`),

  // Download
  downloadFile: (fileId) => axiosClient.get(`/lecturer/material-files/${fileId}/download`),
}
