import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as classApi from '@/api/lecturer/classes'

export const useLecturerClassStore = defineStore('lecturerClass', () => {
  const classes = ref([])
  const students = ref([])
  const loading = ref(false)
  const importing = ref(false)
  const error = ref(null)

  async function fetchMyClasses(params = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await classApi.getMyClasses(params)
      classes.value = data
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Lỗi tải danh sách lớp'
    } finally {
      loading.value = false
    }
  }

  async function fetchStudents(classId) {
    loading.value = true
    error.value = null
    try {
      const { data } = await classApi.getStudents(classId)
      students.value = data.students
      const idx = classes.value.findIndex((c) => c.id === classId)
      if (idx !== -1) classes.value[idx].current_count = data.current_count
      return data
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Lỗi tải sinh viên'
    } finally {
      loading.value = false
    }
  }

  async function addStudent(classId, studentCode) {
    const { data } = await classApi.addStudent(classId, studentCode)
    await fetchStudents(classId)
    return data
  }

  async function toggleHasGroup(classId, studentId, hasGroup) {
    await classApi.updateStudent(classId, studentId, { has_group: hasGroup })
    const s = students.value.find((s) => s.id === studentId)
    if (s) s.has_group = hasGroup
  }

  async function removeStudent(classId, studentId) {
    const { data } = await classApi.removeStudent(classId, studentId)
    students.value = students.value.filter((s) => s.id !== studentId)
    const idx = classes.value.findIndex((c) => c.id === classId)
    if (idx !== -1) classes.value[idx].current_count = data.current_count
  }

  async function importStudents(classId, file) {
    importing.value = true
    error.value = null
    try {
      const { data } = await classApi.importStudents(classId, file)
      await fetchStudents(classId)
      return data
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Lỗi import'
      throw e
    } finally {
      importing.value = false
    }
  }

  return {
    classes,
    students,
    loading,
    importing,
    error,
    fetchMyClasses,
    fetchStudents,
    addStudent,
    toggleHasGroup,
    removeStudent,
    importStudents,
  }
})
