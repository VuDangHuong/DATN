<script setup>
import { ref, onMounted } from 'vue'
import { useCategoryStore } from '@/stores/admin/category'
import FacultyTab from './components/FacultyTab.vue'
import MajorTab from './components/MajorTab.vue'
import ClassTab from './components/ClassTab.vue'
import SubjectTab from './components/SubjectTab.vue'
const categoryStore = useCategoryStore()
const activeTab = ref('faculty')

// Load dữ liệu nền tảng ngay khi vào trang
onMounted(() => {
  categoryStore.fetchFaculties()
})
</script>

<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-2">Quản lý Cấu trúc Đào tạo</h1>
    <p class="text-gray-500 mb-6">Quản lý phân cấp Khoa - Ngành - Lớp học phần</p>

    <div class="bg-white rounded-t-lg shadow-sm border-b border-gray-200">
      <div class="flex">
        <button
          @click="activeTab = 'faculty'"
          :class="[
            'px-6 py-3 font-medium text-sm focus:outline-none transition-colors',
            activeTab === 'faculty'
              ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50'
              : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50',
          ]"
        >
          1. Khoa / Viện
        </button>
        <button
          @click="activeTab = 'major'"
          :class="[
            'px-6 py-3 font-medium text-sm focus:outline-none transition-colors',
            activeTab === 'major'
              ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50'
              : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50',
          ]"
        >
          2. Ngành Đào tạo
        </button>
        <button
          @click="activeTab = 'subject'"
          :class="[
            'px-6 py-3 font-medium text-sm focus:outline-none transition-colors',
            activeTab === 'subject'
              ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50'
              : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50',
          ]"
        >
          3. Môn học
        </button>
        <button
          @click="activeTab = 'class'"
          :class="[
            'px-6 py-3 font-medium text-sm focus:outline-none transition-colors',
            activeTab === 'class'
              ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50'
              : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50',
          ]"
        >
          4. Lớp Học phần
        </button>
      </div>
    </div>

    <div class="bg-white p-6 rounded-b-lg shadow min-h-[500px]">
      <Transition name="fade" mode="out-in">
        <FacultyTab v-if="activeTab === 'faculty'" />
        <MajorTab v-else-if="activeTab === 'major'" />
        <SubjectTab v-else-if="activeTab === 'subject'" />
        <ClassTab v-else-if="activeTab === 'class'" />
      </Transition>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
