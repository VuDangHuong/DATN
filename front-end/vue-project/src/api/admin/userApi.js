// src/api/userApi.js
import axiosClient from '../axiosClient'

export const userApi = {
  // Lấy danh sách (có lọc và phân trang)
  getAll(params) {
    return axiosClient.get('/admin/users', { params })
  },

  // Xem chi tiết
  get(id) {
    return axiosClient.get(`/admin/users/${id}`)
  },

  // Tạo mới
  create(data) {
    return axiosClient.post('/admin/users', data)
  },

  // Cập nhật
  update(id, data) {
    return axiosClient.put(`/admin/users/${id}`, data)
  },

  // Xóa
  delete(id) {
    return axiosClient.delete(`/admin/users/${id}`)
  },

  // Import Excel
  importExcel(formData) {
    return axiosClient.post('/admin/users/import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
  },
}
