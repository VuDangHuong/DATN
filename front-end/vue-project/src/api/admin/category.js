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
  // ===== IMPORT =====
  importFaculties(formData) {
    return axiosClient.post('/admin/faculties/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  downloadFacultyTemplate() {
    return axiosClient.get('/admin/faculties/template', {
      responseType: 'blob',
    })
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
  importMajors(formData) {
    return axiosClient.post('/admin/majors/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  downloadMajorTemplate() {
    return axiosClient.get('/admin/majors/template', {
      responseType: 'blob',
    })
  },
  //Classes
  importClasses(formData) {
    return axiosClient.post('/admin/classes/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  downloadClassTemplate() {
    return axiosClient.get('/admin/classes/template', {
      responseType: 'blob',
    })
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
  //Subject
  importSubjects(formData) {
    return axiosClient.post('/admin/subjects/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
  downloadSubjectTemplate() {
    return axiosClient.get('/admin/subjects/template', {
      responseType: 'blob',
    })
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
