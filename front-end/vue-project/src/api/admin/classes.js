import axiosClient from '../axiosClient'

//Lớp học phần
export const getClasses = (params = {}) => axiosClient.get('/admin/classes', { params })

export const getClass = (id) => axiosClient.get(`/admin/classes/${id}`)

export const createClass = (data) => axiosClient.post('/admin/classes', data)

export const updateClass = (id, data) => axiosClient.put(`/admin/classes/${id}`, data)

export const deleteClass = (id) => axiosClient.delete(`/admin/classes/${id}`)

//Sinh viên trong lớp
export const getStudents = (classId) => axiosClient.get(`/admin/classes/${classId}/students`)

export const addStudent = (classId, studentCode) =>
  axiosClient.post(`/admin/classes/${classId}/students`, {
    student_code: studentCode,
  })

export const updateStudent = (classId, studentId, data) =>
  axiosClient.patch(`/admin/classes/${classId}/students/${studentId}`, data)

export const removeStudent = (classId, studentId) =>
  axiosClient.delete(`/admin/classes/${classId}/students/${studentId}`)

export const importStudents = (classId, file) => {
  const form = new FormData()
  form.append('file', file)

  return axiosClient.post(`/admin/classes/${classId}/students/import`, form, {
    headers: { 'Content-Type': 'multipart/form-data' },
  })
}
