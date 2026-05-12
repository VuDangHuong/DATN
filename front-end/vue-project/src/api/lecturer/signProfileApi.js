// src/api/lecturer/signProfileApi.js
import axiosClient from '@/api/axiosClient'

export const signProfileApi = {
  // GET chữ ký hiện tại (active)
  show() {
    return axiosClient.get('/lecturer/sign-profile')
  },

  // GET lịch sử các chữ ký đã đăng ký
  history() {
    return axiosClient.get('/lecturer/sign-profile/history')
  },

  // GET danh sách nhà cung cấp + loại chứng thư
  categories() {
    return axiosClient.get('/lecturer/sign-profile/categories')
  },

  // POST đăng ký / cập nhật chữ ký (multipart cho upload file)
  upsert(formData) {
    return axiosClient.post('/lecturer/sign-profile', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  // DELETE vô hiệu hóa chữ ký
  deactivate(currentPassword) {
    return axiosClient.delete('/lecturer/sign-profile', {
      data: { current_password: currentPassword },
    })
  },
}
