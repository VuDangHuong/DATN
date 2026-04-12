import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as classApi from '@/api/admin/classes'

export const useAdminClassStore = defineStore('adminClass', () => {
  // ── State ────────────────────────────────────────────────
  const classes = ref([])
  const currentClass = ref(null)
  const students = ref([])
  const loading = ref(false)
  const importing = ref(false)
  const error = ref(null)

  //Actions: Lớp học phần
  async function fetchClasses(params = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await classApi.getClasses(params)
      classes.value = data
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Lỗi tải danh sách lớp'
    } finally {
      loading.value = false
    }
  }

  async function fetchClass(id) {
    loading.value = true
    error.value = null
    try {
      const { data } = await classApi.getClass(id)
      currentClass.value = data
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Lỗi tải lớp'
    } finally {
      loading.value = false
    }
  }

  async function addClass(payload) {
    const { data } = await classApi.createClass(payload)
    await fetchClasses()
    return data
  }

  async function editClass(id, payload) {
    const { data } = await classApi.updateClass(id, payload)
    await fetchClasses()
    return data
  }

  async function removeClass(id) {
    await classApi.deleteClass(id)
    classes.value = classes.value.filter((c) => c.id !== id)
  }

  //Actions: Sinh viên
  async function fetchStudents(classId) {
    loading.value = true
    error.value = null
    try {
      const { data } = await classApi.getStudents(classId)
      students.value = data.students
      // Cập nhật current_count trong danh sách lớp luôn
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
    // Cập nhật current_count
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
    currentClass,
    students,
    loading,
    importing,
    error,
    fetchClasses,
    fetchClass,
    addClass,
    editClass,
    removeClass,
    fetchStudents,
    addStudent,
    toggleHasGroup,
    removeStudent,
    importStudents,
  }
})
