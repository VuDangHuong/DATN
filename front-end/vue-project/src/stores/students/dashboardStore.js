// src/stores/dashboardStore.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { dashboardApi } from '@/api/students/studentApi'

export const useDashboardStore = defineStore('dashboard', () => {
  const classes = ref([])
  const student = ref(null)
  const loading = ref(false)
  const error = ref(null)
  const selectedClassId = ref(null)

  function selectClass(id) {
    selectedClassId.value = id
  }
  const filteredClasses = computed(() =>
    selectedClassId.value
      ? classes.value.filter((c) => c.class.id == selectedClassId.value) // == thay vì ===
      : classes.value,
  )
  // Lớp đang chọn
  const selectedClass = computed(
    () => classes.value.find((c) => c.class.id === selectedClassId.value) || null,
  )

  // Nhóm của mình trong lớp đang chọn
  const myGroup = computed(() => selectedClass.value?.my_group || null)

  // Load dashboard
  async function fetchMyClasses() {
    loading.value = true
    error.value = null
    try {
      const { data } = await dashboardApi.getMyClasses()
      console.log('API response:', data)
      student.value = data.data.student
      classes.value = data.data.classes
      console.log('classes sau khi set:', classes.value)
      console.log('classes[0].class.id:', classes.value[0]?.class?.id)

      // Tự chọn lớp đầu tiên nếu chưa chọn
      if (!selectedClassId.value && classes.value.length > 0) {
        selectedClassId.value = classes.value[0].class.id
        console.log('selectedClassId sau khi set:', selectedClassId.value)
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Không thể tải dữ liệu'
    } finally {
      loading.value = false
    }
  }

  return {
    classes,
    student,
    loading,
    error,
    selectedClassId,
    selectedClass,
    myGroup,
    fetchMyClasses,
    filteredClasses,
    selectClass,
  }
})
