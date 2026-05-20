<!-- src/views/students/materials/MaterialsListPage.vue -->
<template>
  <div class="max-w-5xl mx-auto p-6">
    <!-- Header -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-stone-800">📚 Tài liệu học tập</h2>
      <p class="text-sm text-stone-500 mt-1">Chọn lớp để xem tài liệu giảng viên đã chia sẻ</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-20">
      <div
        class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"
      />
    </div>

    <!-- Empty -->
    <div v-else-if="!classes.length" class="bg-white rounded-2xl border p-12 text-center">
      <p class="text-5xl mb-3">🎓</p>
      <p class="text-stone-500 font-medium">Bạn chưa có lớp nào</p>
    </div>

    <!-- Class grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <router-link
        v-for="cls in classes"
        :key="cls.id"
        :to="`/student/classes/${cls.id}/materials`"
        class="bg-white rounded-2xl border border-stone-200 p-5 hover:shadow-lg hover:border-indigo-300 transition group"
      >
        <!-- Header -->
        <div class="flex items-start justify-between mb-3">
          <div
            class="w-10 h-10 rounded-xl bg-indigo-100 group-hover:bg-indigo-600 flex items-center justify-center transition"
          >
            <svg
              class="w-5 h-5 text-indigo-600 group-hover:text-white transition"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
              />
            </svg>
          </div>

          <span
            v-if="materialCounts[cls.id] > 0"
            class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs font-bold rounded-full"
          >
            📄 {{ materialCounts[cls.id] }}
          </span>
          <span
            v-else
            class="px-2 py-0.5 bg-stone-100 text-stone-400 text-[10px] font-bold rounded-full"
          >
            Chưa có
          </span>
        </div>

        <h3 class="font-semibold text-stone-800 group-hover:text-indigo-700 transition">
          {{ cls.name }}
        </h3>
        <p class="text-xs text-stone-400 font-mono mt-1">{{ cls.code }}</p>

        <div class="flex items-center gap-3 mt-2 text-xs text-stone-500 flex-wrap">
          <span v-if="cls.lecturer?.name"> 👤 {{ cls.lecturer.name }} </span>
          <span v-if="cls.semester?.name"> · {{ cls.semester.name }} </span>
        </div>

        <p
          class="text-xs text-indigo-600 mt-3 opacity-0 group-hover:opacity-100 transition flex items-center gap-1"
        >
          Xem tài liệu
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5l7 7-7 7"
            />
          </svg>
        </p>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axiosClient from '@/api/axiosClient'
import { studentMaterialApi } from '@/api/students/materialApi'

const classes = ref([])
const materialCounts = ref({})
const loading = ref(false)

onMounted(loadData)

async function loadData() {
  loading.value = true
  try {
    const { data: response } = await axiosClient.get('/student/my-classes')

    // ✅ Response: { status, message, data: { student, classes: [...] } }
    const rawClasses = response.data?.classes ?? response.classes ?? []

    // ✅ Mỗi item có format: { class: {...}, semester: {...}, lecturer: {...}, my_group: {...} }
    // → Flatten để dễ dùng
    classes.value = rawClasses
      .map((item) => ({
        id: item.class?.id,
        name: item.class?.name,
        code: item.class?.code,
        semester: item.semester,
        lecturer: item.lecturer,
        subjects: item.subjects,
        my_group: item.my_group,
        has_group: item.has_group,
      }))
      .filter((c) => c.id) // Lọc bỏ item không có id

    console.log('Parsed classes:', classes.value) // Debug

    // Load số tài liệu cho từng lớp
    await Promise.all(
      classes.value.map(async (cls) => {
        try {
          const { data: mData } = await studentMaterialApi.getMaterials(cls.id)
          materialCounts.value[cls.id] = mData.total ?? mData.materials?.length ?? 0
        } catch (e) {
          console.warn(`Load materials for class ${cls.id} failed:`, e.response?.data)
          materialCounts.value[cls.id] = 0
        }
      }),
    )
  } catch (e) {
    console.error('Load classes error:', e.response?.data ?? e.message)
  } finally {
    loading.value = false
  }
}
</script>
