// src/stores/lecturer/lecturerStore.js
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useLecturerStore = defineStore('lecturer', () => {
  const selectedClassId = ref(null)
  const classes = ref([])

  function setSelectedClassId(id) {
    selectedClassId.value = id
  }

  function setClasses(list) {
    classes.value = list
    // ✅ Auto-select lớp đầu tiên nếu chưa chọn lớp nào
    if (list.length > 0 && !selectedClassId.value) {
      selectedClassId.value = list[0].id
    }
  }

  return { selectedClassId, classes, setSelectedClassId, setClasses }
})
