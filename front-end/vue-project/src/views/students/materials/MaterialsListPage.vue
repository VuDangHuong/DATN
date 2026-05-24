<template>
  <div>
    <!-- Header -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-slate-800">Tài liệu học tập</h2>
      <p class="text-sm text-slate-500 mt-1">Chọn lớp để xem tài liệu giảng viên đã chia sẻ</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-20 gap-3">
      <SvgICon name="spinner" class="w-8 h-8 text-indigo-600" spin />
      <p class="text-base text-slate-400 font-medium animate-pulse">
        Đang tải danh sách lớp học...
      </p>
    </div>

    <!-- Empty -->
    <div
      v-else-if="!classes.length"
      class="bg-white rounded-2xl border border-slate-200 p-12 text-center"
    >
      <div class="w-14 h-14 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <SvgICon name="class-book" class="w-7 h-7 text-slate-400" />
      </div>

      <h3 class="text-base font-bold text-slate-700 mb-1">Bạn chưa có lớp học nào</h3>

      <p class="text-sm text-slate-400">
        Danh sách tài liệu sẽ xuất hiện khi bạn được xếp vào lớp học mới.
      </p>
    </div>

    <!-- List -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <router-link
        v-for="cls in classes"
        :key="cls.id"
        :to="`/student/classes/${cls.id}/materials`"
        class="bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-lg hover:border-indigo-300 transition-all duration-300 group"
      >
        <!-- Top -->
        <div class="flex items-start justify-between mb-4">
          <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center">
            <SvgICon name="class-book" class="w-5 h-5 text-slate-500 group-hover:text-indigo-600" />
          </div>

          <span
            v-if="materialCounts[cls.id] > 0"
            class="px-2 py-1 bg-emerald-100 text-emerald-700 text-base font-bold rounded-full flex items-center gap-1"
          >
            <SvgICon name="clipboard" class="w-4 h-4" />
            {{ materialCounts[cls.id] }}
          </span>

          <span
            v-else
            class="px-2 py-1 bg-slate-100 text-slate-500 text-base font-bold rounded-full"
          >
            Chưa có
          </span>
        </div>

        <!-- Content -->
        <h3
          class="font-semibold text-slate-800 group-hover:text-indigo-600 transition-colors line-clamp-1"
        >
          {{ cls.name }}
        </h3>

        <p
          class="text-base text-indigo-600 font-mono mt-1 bg-indigo-50/50 px-2 py-0.5 rounded w-fit"
        >
          {{ cls.code }}
        </p>

        <div
          class="flex items-center gap-4 text-base text-slate-500 flex-wrap mt-4 pt-4 border-t border-slate-100"
        >
          <span v-if="cls.lecturer?.name" class="flex items-center gap-1.5">
            <SvgICon name="user-profile" class="w-4 h-4 text-slate-400 flex-shrink-0" />
            <span class="font-medium text-slate-700 leading-none">
              {{ cls.lecturer.name }}
            </span>
          </span>

          <span v-if="cls.semester?.name" class="flex items-center gap-1.5">
            <SvgICon name="calendar" class="w-4 h-4 text-slate-400 flex-shrink-0" />
            <span class="font-medium text-slate-700 leading-none">{{ cls.semester.name }}</span>
          </span>
        </div>

        <!-- Footer -->
        <div
          class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-base font-semibold text-indigo-600"
        >
          <span>Xem tài liệu</span>

          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5l7 7-7 7"
            />
          </svg>
        </div>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axiosClient from '@/api/axiosClient'
import { studentMaterialApi } from '@/api/students/materialApi'
import SvgICon from '@/components/icons/SVG.vue' // ✅ Sử dụng đúng component SVG đồng bộ hệ thống

const classes = ref([])
const materialCounts = ref({})
const loading = ref(false)

onMounted(loadData)

async function loadData() {
  loading.value = true
  try {
    const { data: response } = await axiosClient.get('/student/my-classes')

    const rawClasses = response.data?.classes ?? response.classes ?? []

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
      .filter((c) => c.id)

    // Load song song số tài liệu cho từng lớp học
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
