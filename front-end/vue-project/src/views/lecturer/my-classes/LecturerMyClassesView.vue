<!-- src/views/lecturer/my-classes/LecturerMyClassesView.vue
     Danh sách lớp tôi phụ trách - card grid
     Route: /lecturer/my-classes
-->
<template>
  <div class="p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
      <div>
        <h1 class="text-2xl font-bold text-stone-800">Lớp của tôi</h1>
        <p class="text-sm text-stone-500 mt-1">Bạn đang phụ trách {{ store.classes.length }} lớp</p>
      </div>

      <div class="relative">
        <svg
          class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"
          />
        </svg>
        <input
          v-model="search"
          type="text"
          placeholder="Tìm lớp..."
          class="pl-9 pr-3 py-2 border border-stone-200 rounded-xl text-sm w-64 focus:ring-2 focus:ring-teal-500 outline-none"
        />
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
      <div class="bg-white rounded-2xl border border-stone-200 p-4">
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 rounded-xl bg-teal-100 flex items-center justify-center">
            <SvgIcon name="building" class="w-5 h-5 text-teal-600" />
          </div>
          <div>
            <p class="text-xs text-stone-500">Tổng lớp</p>
            <p class="text-xl font-bold text-stone-800">{{ store.classes.length }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-stone-200 p-4">
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 rounded-xl bg-blue-100 flex items-center justify-center">
            <SvgIcon name="users" class="w-5 h-5 text-blue-600" />
          </div>
          <div>
            <p class="text-xs text-stone-500">Tổng SV</p>
            <p class="text-xl font-bold text-blue-600">{{ totalStudents }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-stone-200 p-4">
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 rounded-xl bg-emerald-100 flex items-center justify-center">
            <SvgIcon name="class-book" class="w-5 h-5 text-emerald-600" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-stone-500">Học kỳ hiện tại</p>
            <p class="text-sm font-bold text-emerald-600 truncate">
              {{ currentSemesterName ?? '—' }}
            </p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-stone-200 p-4">
        <div class="flex items-center gap-3">
          <div class="w-11 h-11 rounded-xl bg-purple-100 flex items-center justify-center">
            <SvgIcon name="class-book-open" class="w-5 h-5 text-purple-600" />
          </div>
          <div>
            <p class="text-xs text-stone-500">Môn dạy</p>
            <p class="text-xl font-bold text-purple-600">{{ totalSubjects }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="store.loading && !store.classes.length" class="flex justify-center py-20">
      <div class="w-10 h-10 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin" />
    </div>

    <!-- Empty -->
    <div v-else-if="!filteredClasses.length" class="bg-white rounded-2xl border p-12 text-center">
      <SvgIcon name="class-book-open" class="w-12 h-12 text-stone-300 mx-auto mb-3" />
      <p class="text-stone-500">
        {{ search ? 'Không tìm thấy lớp phù hợp' : 'Bạn chưa được phân công lớp nào' }}
      </p>
    </div>

    <!-- Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="cls in filteredClasses"
        :key="cls.id"
        class="bg-white rounded-2xl border border-stone-200 p-5 hover:border-teal-300 hover:shadow-md transition cursor-pointer group"
        @click="goToManage(cls)"
      >
        <div class="flex items-start justify-between mb-3">
          <div class="flex-1 min-w-0">
            <h3
              class="text-base font-bold text-stone-800 truncate group-hover:text-teal-700 transition"
            >
              {{ cls.name }}
            </h3>
            <p class="text-xs text-stone-500 font-mono mt-0.5">{{ cls.code }}</p>
          </div>
          <span
            class="px-2 py-1 rounded-full text-[10px] font-bold flex-shrink-0"
            :class="
              cls.semester?.is_active
                ? 'bg-emerald-100 text-emerald-700'
                : 'bg-stone-100 text-stone-500'
            "
          >
            {{ cls.semester?.is_active ? 'Đang dạy' : 'Đã đóng' }}
          </span>
        </div>

        <div class="space-y-1.5 text-xs text-stone-600 mb-3">
          <div class="flex items-center gap-2">
            <SvgIcon name="class-book" class="w-3.5 h-3.5 text-stone-400 flex-shrink-0" />
            <span class="truncate">{{ cls.semester?.name ?? '—' }}</span>
            <span v-if="cls.semester?.year" class="text-stone-400">· {{ cls.semester.year }}</span>
          </div>
          <div class="flex items-center gap-2">
            <SvgIcon name="document" class="w-3.5 h-3.5 text-stone-400 flex-shrink-0" />
            <span class="truncate">
              {{ cls.subjects?.map((s) => s.name).join(', ') || '—' }}
            </span>
          </div>
        </div>

        <div v-if="cls.subjects?.length" class="flex flex-wrap gap-1 mb-3">
          <span
            v-for="s in cls.subjects"
            :key="s.id"
            class="inline-block px-2 py-0.5 bg-purple-50 text-purple-700 rounded-lg text-[10px] font-mono"
          >
            {{ s.code }}
          </span>
        </div>

        <div class="mb-3">
          <div class="flex justify-between text-xs mb-1">
            <span class="text-stone-500">Sĩ số</span>
            <span class="font-semibold text-stone-700">
              {{ cls.students_count ?? cls.current_count ?? 0 }} / {{ getMaxMembers(cls) }}
            </span>
          </div>
          <div class="w-full h-1.5 bg-stone-100 rounded-full overflow-hidden">
            <div
              class="h-full rounded-full transition-all"
              :class="
                progressColor(cls.students_count ?? cls.current_count ?? 0, getMaxMembers(cls))
              "
              :style="{
                width: progressWidth(
                  cls.students_count ?? cls.current_count ?? 0,
                  getMaxMembers(cls),
                ),
              }"
            />
          </div>
        </div>

        <div class="pt-3 border-t border-stone-100 flex items-center justify-between text-xs">
          <span
            class="text-stone-400 group-hover:text-teal-600 font-medium transition flex items-center gap-1"
          >
            Quản lý sinh viên
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5l7 7-7 7"
              />
            </svg>
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useLecturerClassStore } from '@/stores/lecturer/lecturerClass'
import SvgIcon from '@/components/icons/SVG.vue'

const router = useRouter()
const store = useLecturerClassStore()

const search = ref('')

const filteredClasses = computed(() => {
  if (!search.value) return store.classes
  const q = search.value.toLowerCase()
  return store.classes.filter(
    (c) => c.name?.toLowerCase().includes(q) || c.code?.toLowerCase().includes(q),
  )
})

const totalStudents = computed(() =>
  store.classes.reduce((sum, c) => sum + (c.students_count ?? c.current_count ?? 0), 0),
)

const currentSemesterName = computed(() => {
  const active = store.classes.find((c) => c.semester?.is_active)
  return active?.semester?.name
})

const totalSubjects = computed(() => {
  const codes = new Set()
  store.classes.forEach((c) => {
    c.subjects?.forEach((s) => codes.add(s.code))
  })
  return codes.size
})

onMounted(() => store.fetchClasses())

function goToManage(cls) {
  router.push(`/lecturer/my-classes/${cls.id}/students`)
}

function getMaxMembers(cls) {
  return cls.subjects?.[0]?.pivot?.max_members ?? cls.max_members ?? 0
}

function progressWidth(current, max) {
  if (!max) return '0%'
  return Math.min((current / max) * 100, 100) + '%'
}

function progressColor(current, max) {
  const pct = max ? current / max : 0
  if (pct >= 0.9) return 'bg-red-500'
  if (pct >= 0.7) return 'bg-amber-500'
  return 'bg-emerald-500'
}
</script>
