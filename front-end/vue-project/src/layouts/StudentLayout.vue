<!-- src/layouts/StudentLayout.vue -->
<template>
  <div class="min-h-screen bg-slate-50 flex">
    <!-- Sidebar -->
    <aside
      class="w-72 bg-white border-r border-slate-200 flex flex-col fixed h-full z-30 transition-transform duration-300"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    >
      <!-- Logo -->
      <div class="h-16 flex items-center gap-3 px-6 border-b border-slate-100">
        <div
          class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center"
        >
          <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
            />
          </svg>
        </div>
        <div>
          <h1 class="text-sm font-bold text-slate-800 tracking-tight">EduGroup</h1>
          <p class="text-[10px] text-slate-400 uppercase tracking-widest">Student Portal</p>
        </div>
      </div>

      <!-- User info -->
      <div v-if="student" class="px-5 py-4 border-b border-slate-100">
        <div class="flex items-center gap-3">
          <div
            class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center text-white font-bold text-sm"
          >
            {{ student.name?.charAt(0) }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-slate-800 truncate">{{ student.name }}</p>
            <p class="text-xs text-slate-400">{{ student.code }}</p>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        <p class="px-3 mb-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
          Tổng quan
        </p>

        <router-link to="/student/dashboard" class="nav-link" active-class="nav-link-active">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
            />
          </svg>
          <span>Dashboard</span>
        </router-link>

        <p class="px-3 mt-5 mb-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
          Nhóm
        </p>

        <router-link to="/student/groups" class="nav-link" active-class="nav-link-active">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
            />
          </svg>
          <span>Quản lý nhóm</span>
        </router-link>

        <router-link to="/student/chat" class="nav-link" active-class="nav-link-active">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
            />
          </svg>
          <span>Chat nhóm</span>
        </router-link>

        <p class="px-3 mt-5 mb-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
          Công việc
        </p>

        <router-link to="/student/tasks" class="nav-link" active-class="nav-link-active">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
            />
          </svg>
          <span>Quản lý công việc</span>
        </router-link>
      </nav>

      <!-- Logout -->
      <div class="p-3 border-t border-slate-100">
        <button
          @click="logout"
          class="nav-link w-full text-red-500 hover:bg-red-50 hover:text-red-600"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

    <!-- Overlay mobile -->
    <div
      v-if="sidebarOpen"
      @click="sidebarOpen = false"
      class="fixed inset-0 bg-black/20 backdrop-blur-sm z-20 lg:hidden"
    />

    <!-- Main content -->
    <div class="flex-1 lg:ml-72">
      <!-- Top bar -->
      <header
        class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-10"
      >
        <button
          @click="sidebarOpen = !sidebarOpen"
          class="lg:hidden p-2 hover:bg-slate-100 rounded-lg"
        >
          <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"
            />
          </svg>
        </button>

        <div class="flex items-center gap-2 text-sm text-slate-500">
          <span v-if="selectedClass" class="hidden sm:inline">
            <span class="font-medium text-slate-700">{{ selectedClass?.class?.name }}</span>
            <span class="mx-1">·</span>
            {{ selectedClass?.subjects?.[0]?.name }}
          </span>
        </div>

        <div class="flex items-center gap-3">
          <!-- Class selector -->
          <select
            v-if="classes.length > 1"
            :value="selectedClassId ?? ''"
            @change="selectClass($event.target.value === '' ? null : Number($event.target.value))"
            class="text-sm border border-slate-200 rounded-lg px-3 py-1.5 bg-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
          >
            <option value="">Tất cả lớp</option>
            <option v-for="c in classes" :key="c.class.id" :value="c.class.id">
              {{ c.class.code }} - {{ c.class.name }}
            </option>
          </select>
        </div>
      </header>

      <!-- Page content -->
      <main class="p-6">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useDashboardStore } from '@/stores/students/dashboardStore'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'
const router = useRouter()
const dashboardStore = useDashboardStore()
const { student, classes, selectedClassId, selectedClass } = storeToRefs(dashboardStore)
const authStore = useAuthStore()
const sidebarOpen = ref(false)

onMounted(() => {
  dashboardStore.fetchMyClasses()
  console.log(
    'selectedClassId type:',
    typeof selectedClassId.value,
    '| value:',
    selectedClassId.value,
  )
  console.log(
    'class.id type:',
    typeof classes.value[0]?.class?.id,
    '| value:',
    classes.value[0]?.class?.id,
  )
  console.log('So sánh ===:', selectedClassId.value === classes.value[0]?.class?.id)
  console.log('So sánh ==:', selectedClassId.value == classes.value[0]?.class?.id)
})

function selectClass(id) {
  dashboardStore.selectClass(id)
}

async function logout() {
  await authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.nav-link {
  @apply flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm text-slate-600
         hover:bg-slate-50 transition-all duration-150 font-medium;
}
.nav-link-active {
  @apply bg-indigo-50 text-indigo-700 font-semibold shadow-sm shadow-indigo-100;
}
</style>
