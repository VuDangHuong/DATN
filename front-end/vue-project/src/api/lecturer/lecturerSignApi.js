// src/api/lecturer/lecturerSignApi.js
import axiosClient from '@/api/axiosClient'

export const lecturerSignApi = {
  // Danh sách yêu cầu ký số
  getRequests: (params = {}) => axiosClient.get('/lecturer/sign-requests', { params }),

  // Chi tiết yêu cầu
  getDetail: (id) => axiosClient.get(`/lecturer/sign-requests/${id}`),

  // Preview file gốc
  preview: (id) => axiosClient.get(`/lecturer/sign-requests/${id}/preview`),

  // Xác nhận ký số (không upload file)
  sign: (id) => axiosClient.post(`/lecturer/sign-requests/${id}/sign`),

  // Từ chối ký
  reject: (id, reason) => axiosClient.post(`/lecturer/sign-requests/${id}/reject`, { reason }),
}
