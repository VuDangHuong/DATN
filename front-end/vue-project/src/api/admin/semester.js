// src/api/userApi.js
import axiosClient from '../axiosClient'

export const semesterApi = {
  getAll(params) {
    return axiosClient.get('/general/semesters', { params })
  },

  // 2. Lấy chi tiết
  get(id) {
    return axiosClient.get(`/admin/semesters/${id}`)
  },

  // 3. Tạo mới
  create(data) {
    return axiosClient.post('/admin/semesters', data)
  },

  // 4. Cập nhật
  update(id, data) {
    return axiosClient.put(`/admin/semesters/${id}`, data)
  },

  // 5. Xóa mềm (Ẩn/Hủy kích hoạt)
  delete(id) {
    return axiosClient.delete(`/admin/semesters/${id}`)
  },
}
