// src/api/admin/adminSignApi.js
import axiosClient from '@/api/axiosClient'

export const adminSignApi = {
  // Danh sách yêu cầu ký số
  getRequests: (params = {}) => axiosClient.get('/admin/sign-requests', { params }),

  // Chi tiết yêu cầu
  getDetail: (id) => axiosClient.get(`/admin/sign-requests/${id}`),

  // Thống kê
  getStats: () => axiosClient.get('/admin/sign-requests/stats'),

  // Forward cho GV
  forward: (id, payload) => axiosClient.post(`/admin/sign-requests/${id}/forward`, payload),

  // Từ chối
  reject: (id, reason) => axiosClient.post(`/admin/sign-requests/${id}/reject`, { reason }),

  // Phát hành cho SV
  complete: (id) => axiosClient.post(`/admin/sign-requests/${id}/complete`),

  // Preview file gốc
  preview: (id) => axiosClient.get(`/admin/sign-requests/${id}/preview`),

  // Danh sách GV
  getLecturers: () => axiosClient.get('/admin/users', { params: { role: 'lecturer' } }),

  // Danh sách loại tài liệu
  getDocumentCategories: () => axiosClient.get('/general/document-categories'),
}
