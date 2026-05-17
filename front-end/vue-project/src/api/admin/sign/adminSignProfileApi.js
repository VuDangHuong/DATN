import axiosClient from '@/api/axiosClient'

export const adminSignProfileApi = {
  // Danh sách chữ ký số
  list: (params = {}) => axiosClient.get('/admin/sign-profiles', { params }),

  // Thống kê
  stats: () => axiosClient.get('/admin/sign-profiles/stats'),

  // Deactivation requests
  listRequests: (params = {}) => axiosClient.get('/admin/deactivation-requests', { params }),

  showRequest: (id) => axiosClient.get(`/admin/deactivation-requests/${id}`),

  approveRequest: (id) => axiosClient.post(`/admin/deactivation-requests/${id}/approve`),

  rejectRequest: (id, note) =>
    axiosClient.post(`/admin/deactivation-requests/${id}/reject`, { note }),
}
