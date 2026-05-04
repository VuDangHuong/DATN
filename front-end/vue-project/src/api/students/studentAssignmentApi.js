import axiosClient from '../axiosClient'

// ── SINH VIÊN ─────────────────────────────────────────────────────
export const studentAssignmentApi = {
  // Danh sách đợt nộp của lớp
  getByClass: (classId) => axiosClient.get(`/student/classes/${classId}/assignments`),

  // Nộp bài cá nhân
  submitIndividual: (assignmentId, file, note = '', documentCategory = '') => {
    const form = new FormData()
    form.append('file', file)
    if (note) form.append('note', note)
    if (documentCategory) form.append('document_category', documentCategory) // ← thêm
    return axiosClient.post(`/student/assignments/${assignmentId}/submit`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  submitGroup: (assignmentId, file, note = '', documentCategory = '') => {
    const form = new FormData()
    form.append('file', file)
    if (note) form.append('note', note)
    if (documentCategory) form.append('document_category', documentCategory) // ← thêm
    return axiosClient.post(`/student/assignments/${assignmentId}/submit-group`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  // Lịch sử nộp
  getHistory: (assignmentId) =>
    axiosClient.get(`/student/assignments/${assignmentId}/submission/history`),

  // Tạo yêu cầu ký số
  createSignRequest: (submissionId) =>
    axiosClient.post('/student/sign-requests', { submission_id: submissionId }),

  // Danh sách yêu cầu của SV (có thể filter ?status=pending)
  getSignRequests: (params = {}) => axiosClient.get('/student/sign-requests', { params }),

  // Chi tiết yêu cầu
  getSignRequest: (id) => axiosClient.get(`/student/sign-requests/${id}`),

  // Tải file đã ký
  downloadSigned: (id) =>
    axiosClient.get(`/student/sign-requests/${id}/download`, { responseType: 'blob' }),
}
