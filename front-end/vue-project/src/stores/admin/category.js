import { defineStore } from 'pinia'
import { ref } from 'vue'
import { categoryApi } from '@/api/admin/category'

export const useCategoryStore = defineStore('category', () => {
  const faculties = ref([])
  const majors = ref([])
  const loading = ref(false)

  // 1. Load danh sách Khoa
  async function fetchFaculties() {
    try {
      const res = await categoryApi.getFaculties()
      faculties.value = res.data
    } catch (error) {
      console.error(error)
    }
  }

  // 2. Load Ngành (Có thể lọc theo khoa)
  async function fetchMajors(facultyId = null) {
    try {
      const params = facultyId ? { faculty_id: facultyId } : {}
      const res = await categoryApi.getMajors(params)
      majors.value = res.data
      return res.data
    } catch (error) {
      console.error(error)
      return []
    }
  }

  async function fetchSubjects(majorId = null) {
    try {
      const params = majorId ? { major_id: majorId } : {}
      const res = await categoryApi.getSubjects(params)
      return res.data // Trả về list subjects
    } catch (error) {
      console.error(error)
      return []
    }
  }
  return {
    faculties,
    majors,
    loading,
    fetchFaculties,
    fetchMajors,
    fetchSubjects,
  }
})
