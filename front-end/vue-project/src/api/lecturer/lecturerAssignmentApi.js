import axiosClient from '../axiosClient'
export const lecturerAssignmentApi = {
  // Danh sách đợt nộp của lớp
  getByClass: (classId) => axiosClient.get(`/lecturer/classes/${classId}/assignments`),

  // Tạo đợt nộp mới
  create: (classId, data) => axiosClient.post(`/lecturer/classes/${classId}/assignments`, data),

  // Chi tiết + danh sách bài nộp
  getDetail: (id) => axiosClient.get(`/lecturer/assignments/${id}`),

  // Cập nhật
  update: (id, data) => axiosClient.patch(`/lecturer/assignments/${id}`, data),

  // Xóa
  delete: (id) => axiosClient.delete(`/lecturer/assignments/${id}`),

  // URL download file
  downloadUrl: (submissionId) =>
    `${import.meta.env.VITE_API_URL}/lecturer/submissions/${submissionId}/download`,

  getDocumentCategories: () => axiosClient.get('/general/document-categories'),
}
