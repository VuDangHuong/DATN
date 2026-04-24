<template>
  <div class="min-h-screen bg-stone-50 flex">
    <!-- ── Sidebar ── -->
    <aside
      class="w-64 bg-white border-r border-stone-200 flex flex-col fixed h-full z-30 transition-transform duration-300"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    >
      <!-- Logo -->
      <div class="h-16 flex items-center gap-3 px-5 border-b border-stone-100">
        <div class="w-8 h-8 rounded-lg bg-teal-600 flex items-center justify-center flex-shrink-0">
          <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
            />
          </svg>
        </div>
        <div>
          <p class="text-sm font-bold text-stone-800 tracking-tight">EduGroup</p>
          <p class="text-[10px] text-teal-600 font-semibold uppercase tracking-widest">
            Giảng viên
          </p>
        </div>
      </div>

      <!-- User info -->
      <div v-if="lecturer" class="px-4 py-3.5 border-b border-stone-100">
        <div class="flex items-center gap-3">
          <div
            class="w-9 h-9 rounded-full bg-teal-100 flex items-center justify-center text-sm font-bold text-teal-700 flex-shrink-0"
          >
            {{ lecturer.name?.charAt(0) }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-stone-800 truncate">{{ lecturer.name }}</p>
            <p class="text-xs text-stone-400 truncate">{{ lecturer.email }}</p>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5">
        <p class="px-3 pt-1 pb-2 text-[10px] font-bold text-stone-400 uppercase tracking-widest">
          Tổng quan
        </p>

        <router-link to="/lecturer/dashboard" class="nav-link" active-class="nav-active">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
            />
          </svg>
          <span>Dashboard</span>
        </router-link>

        <p class="px-3 pt-4 pb-2 text-[10px] font-bold text-stone-400 uppercase tracking-widest">
          Lớp học
        </p>

        <router-link to="/lecturer/classes" class="nav-link" active-class="nav-active">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
            />
          </svg>
          <span>Quản lý lớp</span>
        </router-link>

        <p class="px-3 pt-4 pb-2 text-[10px] font-bold text-stone-400 uppercase tracking-widest">
          Bài tập
        </p>

        <router-link to="/lecturer/assignments" class="nav-link" active-class="nav-active">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
          </svg>
          <span>Đợt nộp bài</span>
        </router-link>

        <router-link to="/lecturer/reviews" class="nav-link" active-class="nav-active">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
            />
          </svg>
          <span>Duyệt bài nộp</span>
          <span
            v-if="pendingCount > 0"
            class="ml-auto px-1.5 py-0.5 bg-amber-500 text-white text-[10px] font-bold rounded-full min-w-[18px] text-center"
          >
            {{ pendingCount > 99 ? '99+' : pendingCount }}
          </span>
        </router-link>

        <p class="px-3 pt-4 pb-2 text-[10px] font-bold text-stone-400 uppercase tracking-widest">
          Ký số
        </p>

        <router-link to="/lecturer/sign-requests" class="nav-link" active-class="nav-active">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
            />
          </svg>
          <span>Yêu cầu ký số</span>
        </router-link>

        <router-link to="/lecturer/sign-profile" class="nav-link" active-class="nav-active">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
            />
          </svg>
          <span>Hồ sơ chữ ký</span>
        </router-link>
      </nav>

      <!-- Logout -->
      <div class="p-3 border-t border-stone-100">
        <button
          @click="logout"
          class="nav-link w-full text-red-500 hover:bg-red-50 hover:text-red-600"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
            />
          </svg>
          <span>Đăng xuất</span>
        </button>
      </div>
    </aside>

    <!-- Mobile overlay -->
    <div
      v-if="sidebarOpen"
      @click="sidebarOpen = false"
      class="fixed inset-0 bg-black/20 z-20 lg:hidden"
    />

    <!-- ── Main content ── -->
    <div class="flex-1 lg:ml-64 flex flex-col min-h-screen">
      <!-- Topbar -->
      <header
        class="h-16 bg-white border-b border-stone-200 flex items-center justify-between px-6 sticky top-0 z-10"
      >
        <button
          @click="sidebarOpen = !sidebarOpen"
          class="lg:hidden p-2 hover:bg-stone-100 rounded-lg"
        >
          <svg class="w-5 h-5 text-stone-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"
            />
          </svg>
        </button>

        <div class="hidden lg:flex items-center gap-2 text-sm text-stone-500">
          <span class="text-stone-800 font-medium">{{ currentPageTitle }}</span>
        </div>

        <div class="flex items-center gap-3">
          <!-- ✅ Dùng lecturerStore.classes, bỏ option null, auto-select lớp đầu -->
          <select
            v-if="lecturerStore.classes.length > 0"
            v-model="selectedClassId"
            class="text-sm border border-stone-200 rounded-lg px-3 py-1.5 bg-white text-stone-700 focus:ring-2 focus:ring-teal-500 focus:border-transparent"
          >
            <option v-for="c in lecturerStore.classes" :key="c.id" :value="c.id">
              {{ c.code }} - {{ c.name }}
            </option>
          </select>

          <div
            class="w-8 h-8 rounded-full bg-teal-100 flex items-center justify-center text-sm font-bold text-teal-700"
          >
            {{ lecturer?.name?.charAt(0) }}
          </div>
        </div>
      </header>

      <!-- ✅ Chỉ 1 router-view duy nhất, không có select thừa bên dưới -->
      <main class="flex-1 p-6">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useLecturerStore } from '@/stores/lecturer/lecturerStore'
import axiosClient from '@/api/axiosClient'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const lecturerStore = useLecturerStore()

const sidebarOpen = ref(false)
const pendingCount = ref(0)

const lecturer = computed(() => authStore.user)

const pageMap = {
  '/lecturer/dashboard': 'Dashboard',
  '/lecturer/classes': 'Quản lý lớp',
  '/lecturer/assignments': 'Đợt nộp bài',
  '/lecturer/reviews': 'Duyệt bài nộp',
  '/lecturer/sign-requests': 'Yêu cầu ký số',
  '/lecturer/sign-profile': 'Hồ sơ chữ ký',
}

const currentPageTitle = computed(() => {
  for (const [path, title] of Object.entries(pageMap)) {
    if (route.path.startsWith(path)) return title
  }
  return 'Giảng viên'
})

// ✅ Chỉ 1 khai báo selectedClassId — computed dùng store
const selectedClassId = computed({
  get: () => lecturerStore.selectedClassId,
  set: (val) => lecturerStore.setSelectedClassId(val),
})

onMounted(async () => {
  await loadClasses()
  loadPendingCount()
})

watch(() => route.path, loadPendingCount)

async function loadClasses() {
  try {
    const { data } = await axiosClient.get('/lecturer/classes')
    lecturerStore.setClasses(data)
  } catch (e) {
    console.error('loadClasses error:', e.response?.data)
  }
}

async function loadPendingCount() {
  try {
    const { data } = await axiosClient.get('/lecturer/assignments/pending-count')
    pendingCount.value = data.count ?? 0
  } catch {
    pendingCount.value = 0
  }
}

async function logout() {
  await authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.nav-link {
  @apply flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-stone-600
         hover:bg-stone-100 hover:text-stone-800 transition-all duration-150 font-medium w-full;
}
.nav-active {
  @apply bg-teal-50 text-teal-700 font-semibold;
}
</style>
