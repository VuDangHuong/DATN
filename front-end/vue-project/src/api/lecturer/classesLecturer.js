// src/api/lecturer/classes.js
//
// CẢNH BÁO: Nếu file này đã có với endpoint /lecturer/classes cho module nhóm cũ
//   → TẠO FILE MỚI ở src/api/lecturer/myClasses.js thay vì replace file này
//
// Nếu file này CHỈ phục vụ cho phần "Lớp của tôi" mới → replace như dưới

import axiosClient from '../axiosClient'

// ─── Lớp (đã có) ──
export const getMyClasses = (params = {}) => axiosClient.get('/lecturer/my-classes', { params })

// ─── SV trong lớp (CRUD + import - THÊM MỚI) ──
export const getStudents = (classId) => axiosClient.get(`/lecturer/my-classes/${classId}/students`)

export const addStudent = (classId, studentCode) =>
  axiosClient.post(`/lecturer/my-classes/${classId}/students`, {
    student_code: studentCode,
  })

// export const updateStudent = (classId, studentId, data) =>
//   axiosClient.patch(`/lecturer/my-classes/${classId}/students/${studentId}`, data)

export const removeStudent = (classId, studentId) =>
  axiosClient.delete(`/lecturer/my-classes/${classId}/students/${studentId}`)

export const importStudents = (classId, file) => {
  const form = new FormData()
  form.append('file', file)

  return axiosClient.post(`/lecturer/my-classes/${classId}/students/import`, form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
}
