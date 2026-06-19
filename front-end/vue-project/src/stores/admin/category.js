import { defineStore } from 'pinia'
import { ref } from 'vue'
import { categoryApi } from '@/api/admin/category'

export const useCategoryStore = defineStore('category', () => {
  const faculties = ref([])
  const facultyPagination = ref({ current_page: 1, last_page: 1, per_page: 5, total: 0 })
  const majors = ref([])
  const loading = ref(false)

  // 1. Load danh sách Khoa
  async function fetchFaculties(params = {}) {
    const res = await categoryApi.getFaculties(params)
    const data = res.data
    if (Array.isArray(data)) {
      // trả full (không paginate)
      faculties.value = data
    } else {
      // object phân trang
      faculties.value = data.data ?? []
      facultyPagination.value = {
        current_page: data.current_page ?? 1,
        last_page: data.last_page ?? 1,
        per_page: data.per_page ?? 5,
        total: data.total ?? 0,
      }
    }
  }

  // 2. Load Ngành (Có thể lọc theo khoa)
  async function fetchMajors(facultyId = null) {
    try {
      const params = facultyId ? { faculty_id: facultyId } : {}
      const res = await categoryApi.getMajors(params)
      // chịu được cả mảng full lẫn object phân trang
      const list = Array.isArray(res.data) ? res.data : (res.data.data ?? [])
      majors.value = list
      return list
    } catch (error) {
      console.error(error)
      return []
    }
  }

  async function fetchSubjects(majorId = null) {
    try {
      const params = majorId ? { major_id: majorId } : {}
      const res = await categoryApi.getSubjects(params)
      const list = Array.isArray(res.data) ? res.data : (res.data.data ?? [])
      return list
    } catch (error) {
      console.error(error)
      return []
    }
  }
  return {
    faculties,
    majors,
    loading,
    facultyPagination,
    fetchFaculties,
    fetchMajors,
    fetchSubjects,
  }
})
