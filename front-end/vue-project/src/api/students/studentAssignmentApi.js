import axiosClient from '../axiosClient'

// ── SINH VIÊN ─────────────────────────────────────────────────────
export const studentAssignmentApi = {
  // Danh sách đợt nộp của lớp
  getByClass: (classId) => axiosClient.get(`/student/classes/${classId}/assignments`),

  // Nộp bài cá nhân
  submitIndividual: (assignmentId, file, note = '') => {
    const form = new FormData()
    form.append('file', file)
    if (note) form.append('note', note)
    return axiosClient.post(`/student/assignments/${assignmentId}/submit`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  // Nộp bài nhóm (leader only)
  submitGroup: (assignmentId, file, note = '') => {
    const form = new FormData()
    form.append('file', file)
    if (note) form.append('note', note)
    return axiosClient.post(`/student/assignments/${assignmentId}/submit-group`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  // Lịch sử nộp
  getHistory: (assignmentId) =>
    axiosClient.get(`/student/assignments/${assignmentId}/submission/history`),
}
