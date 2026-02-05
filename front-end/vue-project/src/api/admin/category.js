import axiosClient from '../axiosClient'

export const categoryApi = {
  getFaculties() {
    return axiosClient.get('/admin/faculties')
  },
  createFaculty(data) {
    return axiosClient.post('/admin/faculties', data)
  },
  updateFaculty(id, data) {
    return axiosClient.put(`/admin/faculties/${id}`, data)
  },
  deleteFaculty(id) {
    return axiosClient.delete(`/admin/faculties/${id}`)
  },
  getMajors(params) {
    return axiosClient.get('/admin/majors', { params })
  },
  createMajor(data) {
    return axiosClient.post('/admin/majors', data)
  },
  updateMajor(id, data) {
    return axiosClient.put(`/admin/majors/${id}`, data)
  },
  deleteMajor(id) {
    return axiosClient.delete(`/admin/majors/${id}`)
  },

  getClasses(params) {
    return axiosClient.get('/admin/classes', { params })
  },
  createClass(data) {
    return axiosClient.post('/admin/classes', data)
  },
  updateClass(id, data) {
    return axiosClient.put(`/admin/classes/${id}`, data)
  },
  deleteClass(id) {
    return axiosClient.delete(`/admin/classes/${id}`)
  },

  getSubjects(params) {
    return axiosClient.get('/admin/subjects', { params })
  },
  createSubject(data) {
    return axiosClient.post('/admin/subjects', data)
  },
  updateSubject(id, data) {
    return axiosClient.put(`/admin/subjects/${id}`, data)
  },
  deleteSubject(id) {
    return axiosClient.delete(`/admin/subjects/${id}`)
  },
  getSemesters(params = { status: 'active' }) {
    return axiosClient.get('/admin/semesters', { params })
  },

  getLecturers() {
    return axiosClient.get('/admin/users', {
      params: {
        role: 'lecturer',
        all: true,
      },
    })
  },
}
