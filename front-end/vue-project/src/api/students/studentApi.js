import axiosClient from '../axiosClient'

// Base path
const BASE = '/student'

// ── Dashboard ──────────────────────────────────
export const dashboardApi = {
  getMyClasses: () => axiosClient.get(`${BASE}/my-classes`),
}

// ── Groups ─────────────────────────────────────
export const groupApi = {
  // Danh sách nhóm theo lớp
  getByClass: (classId) => axiosClient.get(`${BASE}/classes/${classId}/groups`),

  // Chi tiết nhóm
  getDetail: (groupId) => axiosClient.get(`${BASE}/groups/${groupId}`),

  // Tạo nhóm
  create: (data) => axiosClient.post(`${BASE}/groups`, data),

  update: (groupId, data) => axiosClient.put(`${BASE}/groups/${groupId}`, data),

  // Xóa nhóm
  delete: (groupId) => axiosClient.delete(`${BASE}/groups/${groupId}`),
  // Thêm thành viên
  addMember: (groupId, studentCode) =>
    axiosClient.post(`${BASE}/groups/${groupId}/members`, {
      student_code: studentCode,
    }),

  // Xóa thành viên
  removeMember: (groupId, memberId) =>
    axiosClient.delete(`${BASE}/groups/${groupId}/members/${memberId}`),

  leave: (groupId) => axiosClient.post(`${BASE}/groups/${groupId}/leave`),

  // Chuyển quyền trưởng nhóm
  transferLeader: (groupId, newLeaderId) =>
    axiosClient.post(`${BASE}/groups/${groupId}/transfer-leader`, { new_leader_id: newLeaderId }),
}

// ── Messages (Chat) ────────────────────────────
export const messageApi = {
  getMessages: (groupId, { page = 1, perPage = 30 } = {}) =>
    axiosClient.get(`${BASE}/groups/${groupId}/messages`, {
      params: { page, per_page: perPage },
    }),

  //Gửi tin nhắn với mentions + files (multipart)
  send: (groupId, { content = '', mentions = [], files = [] }) => {
    const fd = new FormData()
    fd.append('content', content)

    mentions.forEach((userId) => fd.append('mentions[]', userId))
    files.forEach((file) => fd.append('files[]', file))

    return axiosClient.post(`${BASE}/groups/${groupId}/messages`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  // Xóa tin nhắn
  delete: (messageId) => axiosClient.delete(`${BASE}/messages/${messageId}`),

  // Xóa 1 attachment
  deleteAttachment: (attachmentId) =>
    axiosClient.delete(`${BASE}/messages/attachments/${attachmentId}`),
}

// ── Tasks ──────────────────────────────────────
export const taskApi = {
  // Danh sách task (có filter)
  getByGroup: (groupId, filters = {}) =>
    axiosClient.get(`${BASE}/groups/${groupId}/tasks`, {
      params: filters,
    }),

  // Chi tiết task
  getDetail: (taskId) => axiosClient.get(`${BASE}/tasks/${taskId}`),

  // Tạo task
  create: (groupId, data) => axiosClient.post(`${BASE}/groups/${groupId}/tasks`, data),

  bulkCreate: (groupId, tasks) =>
    axiosClient.post(`${BASE}/groups/${groupId}/tasks/bulk`, { tasks }),

  // Cập nhật task
  update: (taskId, data) => axiosClient.put(`${BASE}/tasks/${taskId}`, data),

  // Đổi trạng thái task
  updateStatus: (taskId, status) =>
    axiosClient.patch(`${BASE}/tasks/${taskId}/status`, {
      status,
    }),

  // Xóa task
  delete: (taskId) => axiosClient.delete(`${BASE}/tasks/${taskId}`),

  //SV báo hoàn thành
  submitForReview: (taskId, note = '') =>
    axiosClient.post(`/student/tasks/${taskId}/submit-review`, { note }),

  // Trưởng duyệt
  approve: (taskId, note = '') => axiosClient.post(`/student/tasks/${taskId}/approve`, { note }),

  // Trưởng từ chối
  reject: (taskId, reason) => axiosClient.post(`/student/tasks/${taskId}/reject`, { reason }),
}
export const commentApi = {
  // Lấy DS bình luận trong task
  getByTask: (taskId) => axiosClient.get(`/student/tasks/${taskId}/comments`),

  // Thêm bình luận
  create: (taskId, { content, files = [] }) => {
    const fd = new FormData()
    fd.append('content', content)
    files.forEach((file) => fd.append('files[]', file))

    return axiosClient.post(`/student/tasks/${taskId}/comments`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  // Sửa bình luận
  update: (commentId, { content, files = [], removedAttachmentIds = [] }) => {
    const fd = new FormData()
    fd.append('content', content)

    // Method override để Laravel nhận POST nhưng route là PUT
    fd.append('_method', 'PUT')

    files.forEach((file) => fd.append('files[]', file))
    removedAttachmentIds.forEach((id) => fd.append('removed_attachment_ids[]', id))

    return axiosClient.post(`/student/comments/${commentId}`, fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  // Xóa bình luận
  delete: (commentId) => axiosClient.delete(`/student/comments/${commentId}`),

  deleteAttachment: (attachmentId) =>
    axiosClient.delete(`/student/comments/attachments/${attachmentId}`),
}
