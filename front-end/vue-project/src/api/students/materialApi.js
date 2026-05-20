import axiosClient from '../axiosClient'

export const studentMaterialApi = {
  // Danh sách tài liệu trong 1 lớp
  getMaterials: (classId, params = {}) =>
    axiosClient.get(`/student/classes/${classId}/materials`, { params }),

  // Tải 1 file con (track download_count)
  downloadFile: (fileId) => axiosClient.get(`/student/material-files/${fileId}/download`),
}
