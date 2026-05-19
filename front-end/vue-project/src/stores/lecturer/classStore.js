import { lecturerClassApi } from '@/api/lecturer/classesApi'
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
// import * as classApi from '@/api/lecturer/classes'
// export const useLecturerClassStore = defineStore('lecturerClass', () => {
//   const classes = ref([])
//   const students = ref([])
//   const loading = ref(false)
//   const importing = ref(false)
//   const error = ref(null)

//   async function fetchMyClasses(params = {}) {
//     loading.value = true
//     error.value = null
//     try {
//       const { data } = await classApi.getMyClasses(params)
//       classes.value = data
//     } catch (e) {
//       error.value = e.response?.data?.message ?? 'Lỗi tải danh sách lớp'
//     } finally {
//       loading.value = false
//     }
//   }

//   async function fetchStudents(classId) {
//     loading.value = true
//     error.value = null
//     try {
//       const { data } = await classApi.getStudents(classId)
//       students.value = data.students
//       const idx = classes.value.findIndex((c) => c.id === classId)
//       if (idx !== -1) classes.value[idx].current_count = data.current_count
//       return data
//     } catch (e) {
//       error.value = e.response?.data?.message ?? 'Lỗi tải sinh viên'
//     } finally {
//       loading.value = false
//     }
//   }

//   async function addStudent(classId, studentCode) {
//     const { data } = await classApi.addStudent(classId, studentCode)
//     await fetchStudents(classId)
//     return data
//   }

//   async function toggleHasGroup(classId, studentId, hasGroup) {
//     await classApi.updateStudent(classId, studentId, { has_group: hasGroup })
//     const s = students.value.find((s) => s.id === studentId)
//     if (s) s.has_group = hasGroup
//   }

//   async function removeStudent(classId, studentId) {
//     const { data } = await classApi.removeStudent(classId, studentId)
//     students.value = students.value.filter((s) => s.id !== studentId)
//     const idx = classes.value.findIndex((c) => c.id === classId)
//     if (idx !== -1) classes.value[idx].current_count = data.current_count
//   }

//   async function importStudents(classId, file) {
//     importing.value = true
//     error.value = null
//     try {
//       const { data } = await classApi.importStudents(classId, file)
//       await fetchStudents(classId)
//       return data
//     } catch (e) {
//       error.value = e.response?.data?.message ?? 'Lỗi import'
//       throw e
//     } finally {
//       importing.value = false
//     }
//   }

//   return {
//     classes,
//     students,
//     loading,
//     importing,
//     error,
//     fetchMyClasses,
//     fetchStudents,
//     addStudent,
//     toggleHasGroup,
//     removeStudent,
//     importStudents,
//   }
// })
export const useLecturerClassStore = defineStore('lecturerClass', () => {
  // ─── State ──────────────────────────────────
  const classes = ref([]) // Danh sách lớp
  const currentClass = ref(null) // Lớp đang xem
  const groups = ref([]) // Nhóm trong lớp đang xem
  const loading = ref(false)
  const submitting = ref(false)

  // ─── Getters ────────────────────────────────
  const classesCount = computed(() => classes.value.length)

  const currentMaxPerGroup = computed(() => currentClass.value?.max_members_per_group ?? 5)

  // ─── Actions: Classes ───────────────────────
  async function fetchMyClasses() {
    loading.value = true
    try {
      const { data } = await lecturerClassApi.getMyClasses()
      classes.value = Array.isArray(data) ? data : (data.classes ?? data.data ?? [])
      return { success: true }
    } catch (e) {
      return {
        success: false,
        message: e.response?.data?.message ?? 'Không thể tải danh sách lớp',
      }
    } finally {
      loading.value = false
    }
  }

  async function fetchClassGroups(classId) {
    loading.value = true
    try {
      const { data } = await lecturerClassApi.getClassGroups(classId)
      groups.value = data.groups ?? []

      // Lưu max_members_per_group từ response
      if (data.max_members_per_group !== undefined) {
        const idx = classes.value.findIndex((c) => c.id === classId)
        if (idx >= 0) {
          classes.value[idx].max_members_per_group = data.max_members_per_group
        }
        if (!currentClass.value || currentClass.value.id !== classId) {
          currentClass.value = {
            id: classId,
            max_members_per_group: data.max_members_per_group,
          }
        } else {
          currentClass.value.max_members_per_group = data.max_members_per_group
        }
      }

      return { success: true, groups: data.groups, maxPerGroup: data.max_members_per_group }
    } catch (e) {
      return {
        success: false,
        message: e.response?.data?.message ?? 'Không thể tải danh sách nhóm',
      }
    } finally {
      loading.value = false
    }
  }

  // ─── Actions: Định mức TV/nhóm ──────────────
  async function updateMaxMembersPerGroup(classId, maxPerGroup) {
    submitting.value = true
    try {
      const { data } = await lecturerClassApi.updateMaxMembersPerGroup(classId, maxPerGroup)

      // Update local
      const idx = classes.value.findIndex((c) => c.id === classId)
      if (idx >= 0) {
        classes.value[idx].max_members_per_group = maxPerGroup
      }
      if (currentClass.value?.id === classId) {
        currentClass.value.max_members_per_group = maxPerGroup
      }

      return {
        success: true,
        message: data.message,
        warning: data.warning,
      }
    } catch (e) {
      return {
        success: false,
        message: e.response?.data?.message ?? 'Cập nhật thất bại',
      }
    } finally {
      submitting.value = false
    }
  }

  // ─── Actions: Thêm SV vào nhóm (bypass max) ──
  async function addMemberToGroup(groupId, studentCode) {
    submitting.value = true
    try {
      const { data } = await lecturerClassApi.addMemberToGroup(groupId, studentCode)
      return {
        success: true,
        message: data.message,
        member: data.member,
        memberCount: data.member_count,
        isOverLimit: data.is_over_limit ?? false,
      }
    } catch (e) {
      return {
        success: false,
        message: e.response?.data?.message ?? 'Thêm thất bại',
      }
    } finally {
      submitting.value = false
    }
  }

  // ─── Reset ───────────────────────────────────
  function reset() {
    classes.value = []
    currentClass.value = null
    groups.value = []
  }

  return {
    // state
    classes,
    currentClass,
    groups,
    loading,
    submitting,
    // getters
    classesCount,
    currentMaxPerGroup,
    // actions
    fetchMyClasses,
    fetchClassGroups,
    updateMaxMembersPerGroup,
    addMemberToGroup,
    reset,
  }
})
