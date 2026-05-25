// src/stores/lecturer/lecturerClass.js
//
// LƯU Ý: Đổi tên export từ useAdminClassStore → useLecturerClassStore (đúng nghĩa hơn)
//        Nếu có code đang import useAdminClassStore từ file này → cập nhật chỗ đó

import { defineStore } from 'pinia'
import { ref } from 'vue'
import * as classApi from '@/api/lecturer/classesLecturer'

export const useLecturerClassStore = defineStore('lecturerClass', () => {
  // ── State ──
  const classes = ref([])
  const students = ref([])
  const classInfo = ref(null) // ← THÊM: info lớp khi vào trang chi tiết SV

  const loading = ref(false)
  const importing = ref(false)
  const error = ref(null)

  // ─── Danh sách lớp ──
  async function fetchClasses(params = {}) {
    loading.value = true
    error.value = null
    try {
      const { data } = await classApi.getMyClasses(params)
      // Backend trả { message, data: [...] }
      classes.value = Array.isArray(data) ? data : (data.data ?? [])
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Lỗi tải danh sách lớp'
    } finally {
      loading.value = false
    }
  }

  // ─── Lấy SV trong lớp ──
  async function fetchStudents(classId) {
    loading.value = true
    error.value = null
    try {
      const { data } = await classApi.getStudents(classId)
      // Service trả: { class_id, class_code, class_name, max_members, current_count, students: [...] }
      students.value = data.students ?? []
      classInfo.value = {
        id: data.class_id,
        code: data.class_code,
        name: data.class_name,
        max_members: data.max_members,
        current_count: data.current_count,
      }
      // Cập nhật current_count vào list lớp
      const idx = classes.value.findIndex((c) => c.id === classId)
      if (idx !== -1) classes.value[idx].current_count = data.current_count
      return data
    } catch (e) {
      error.value = e.response?.data?.message ?? 'Lỗi tải sinh viên'
      return null
    } finally {
      loading.value = false
    }
  }

  // ─── Thêm SV ──
  async function addStudent(classId, studentCode) {
    const { data } = await classApi.addStudent(classId, studentCode)
    await fetchStudents(classId)
    return data
  }

  // ─── Toggle has_group ──
  // async function toggleHasGroup(classId, studentId, hasGroup) {
  //   await classApi.updateStudent(classId, studentId, { has_group: hasGroup })
  //   const s = students.value.find((s) => s.id === studentId)
  //   if (s) s.has_group = hasGroup
  // }

  // ─── Xóa SV ──
  async function removeStudent(classId, studentId) {
    const { data } = await classApi.removeStudent(classId, studentId)
    students.value = students.value.filter((s) => s.id !== studentId)
    if (classInfo.value) classInfo.value.current_count = data.current_count
    const idx = classes.value.findIndex((c) => c.id === classId)
    if (idx !== -1) classes.value[idx].current_count = data.current_count
    return data
  }

  // ─── Import Excel ──
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
    classInfo,
    loading,
    importing,
    error,
    fetchClasses,
    fetchStudents,
    addStudent,
    // toggleHasGroup,
    removeStudent,
    importStudents,
  }
})
