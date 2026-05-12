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

  // POST đăng ký / cập nhật chữ ký (multipart cho upload file)
  parseCertificate(formData) {
    return axiosClient.post('/lecturer/sign-profile/parse-cert', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  generateTest(payload) {
    return axiosClient.post('/lecturer/sign-profile/generate-test', payload)
  },
  register(formData) {
    return axiosClient.post('/lecturer/sign-profile', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  // DELETE vô hiệu hóa chữ ký
  deactivate(accountPassword) {
    return axiosClient.delete('/lecturer/sign-profile', {
      data: { account_password: accountPassword },
    })
  },
}
