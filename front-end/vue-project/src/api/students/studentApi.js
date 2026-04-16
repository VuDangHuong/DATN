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
  // Lấy danh sách tin nhắn (phân trang)
  getMessages: (groupId, { page = 1, perPage = 30 } = {}) =>
    axiosClient.get(`${BASE}/groups/${groupId}/messages`, {
      params: {
        page,
        per_page: perPage,
      },
    }),

  // Gửi tin nhắn
  send: (groupId, content) =>
    axiosClient.post(`${BASE}/groups/${groupId}/messages`, {
      content,
    }),
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

  // Cập nhật task
  update: (taskId, data) => axiosClient.put(`${BASE}/tasks/${taskId}`, data),

  // Đổi trạng thái task
  updateStatus: (taskId, status) =>
    axiosClient.patch(`${BASE}/tasks/${taskId}/status`, {
      status,
    }),

  // Xóa task
  delete: (taskId) => axiosClient.delete(`${BASE}/tasks/${taskId}`),
}
export const commentApi = {
  // Lấy DS bình luận trong task
  getByTask: (taskId) => axiosClient.get(`/student/tasks/${taskId}/comments`),

  // Thêm bình luận
  create: (taskId, data) => axiosClient.post(`/student/tasks/${taskId}/comments`, data),

  // Sửa bình luận
  update: (commentId, data) => axiosClient.put(`/student/comments/${commentId}`, data),

  // Xóa bình luận
  delete: (commentId) => axiosClient.delete(`/student/comments/${commentId}`),
}
