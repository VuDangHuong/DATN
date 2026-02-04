// src/api/userApi.js
import axiosClient from '../axiosClient'

export const semesterApi = {
  getAll(params) {
    return axiosClient.get('/semesters', { params })
  },

  // 2. Lấy chi tiết
  get(id) {
    return axiosClient.get(`/semesters/${id}`)
  },

  // 3. Tạo mới
  create(data) {
    return axiosClient.post('/semesters', data)
  },

  // 4. Cập nhật
  update(id, data) {
    return axiosClient.put(`/semesters/${id}`, data)
  },

  // 5. Xóa mềm (Ẩn/Hủy kích hoạt)
  delete(id) {
    return axiosClient.delete(`/semesters/${id}`)
  },
}
