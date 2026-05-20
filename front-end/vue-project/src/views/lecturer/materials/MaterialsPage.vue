<!-- src/views/lecturer/materials/MaterialsPage.vue
     Wrapper page — đọc selectedClassId từ store, truyền xuống ClassMaterialsView -->
<template>
  <div>
    <!-- Empty state khi chưa chọn lớp -->
    <div v-if="!classId" class="bg-white rounded-2xl border p-12 text-center">
      <p class="text-5xl mb-3">📚</p>
      <p class="text-stone-500 font-medium mb-2">Vui lòng chọn lớp học</p>
      <p class="text-xs text-stone-400 mb-4">
        Chọn lớp ở dropdown phía trên hoặc bên dưới để xem tài liệu
      </p>

      <!-- Dropdown chọn lớp -->
      <select
        v-if="lecturerStore.classes.length"
        @change="onSelectClass($event.target.value)"
        class="mt-3 px-4 py-2 border border-stone-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 outline-none"
      >
        <option value="">-- Chọn lớp --</option>
        <option v-for="c in lecturerStore.classes" :key="c.id" :value="c.id">
          {{ c.code }} - {{ c.name }}
        </option>
      </select>
    </div>

    <!-- Materials view -->
    <ClassMaterialsView v-else :class-id="classId" :key="classId" />
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useLecturerStore } from '@/stores/lecturer/lecturerStore'
import ClassMaterialsView from './ClassMaterialsView.vue'
import axiosClient from '@/api/axiosClient'

const lecturerStore = useLecturerStore()

const classId = computed(() => lecturerStore.selectedClassId ?? null)

onMounted(async () => {
  // Đảm bảo có danh sách lớp
  if (!lecturerStore.classes.length) {
    try {
      const { data } = await axiosClient.get('/lecturer/classes')
      lecturerStore.setClasses(data)

      // Tự chọn lớp đầu tiên nếu chưa có
      if (!lecturerStore.selectedClassId && data.length) {
        lecturerStore.setSelectedClassId(data[0].id)
      }
    } catch (e) {
      console.error('Load classes error:', e)
    }
  }
})

function onSelectClass(val) {
  if (val) {
    lecturerStore.setSelectedClassId(Number(val))
  }
}
</script>
