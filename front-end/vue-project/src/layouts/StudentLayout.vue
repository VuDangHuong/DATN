<!-- src/layouts/StudentLayout.vue -->
<template>
  <div class="min-h-screen bg-slate-50 flex">
    <!-- ✅ Overlay đóng dropdown — đặt TRƯỚC aside, z-40 -->
    <div v-if="showUserMenu" class="fixed inset-0 z-40" @click="showUserMenu = false" />

    <!-- Sidebar -->
    <aside
      class="w-72 bg-white border-r border-slate-200 flex flex-col fixed h-full z-50 transition-transform duration-300"
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

      <!-- User info với dropdown -->
      <div v-if="student" class="px-5 py-4 border-b border-slate-100 relative">
        <!-- Click vào card → mở dropdown -->
        <div
          class="flex items-center gap-3 cursor-pointer rounded-xl hover:bg-slate-50 p-1 -m-1 transition"
          @click.stop="showUserMenu = !showUserMenu"
        >
          <img
            v-if="authStore.user?.avatar_url"
            :src="getAvatarUrl(authStore.user)"
            class="w-10 h-10 rounded-full object-cover border-2 border-slate-100 flex-shrink-0"
            alt="avatar"
          />
          <div
            v-else
            class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
          >
            {{ authStore.user?.name?.charAt(0) }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-slate-800 truncate">{{ student.name }}</p>
            <p class="text-xs text-slate-400">{{ student.code }}</p>
          </div>
          <svg
            class="w-3.5 h-3.5 text-slate-400 transition-transform flex-shrink-0"
            :class="showUserMenu ? 'rotate-180' : ''"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M19 9l-7 7-7-7"
            />
          </svg>
        </div>

        <!-- Dropdown menu — z-[60] cao hơn sidebar z-50 -->
        <div
          v-if="showUserMenu"
          class="absolute left-3 right-3 top-full mt-1 z-[60] bg-white rounded-xl border border-slate-200 shadow-lg overflow-hidden"
        >
          <!-- Đổi avatar -->
          <button
            @click="openAvatarModal"
            class="w-full flex items-center gap-3 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 transition text-left"
          >
            <svg
              class="w-4 h-4 text-slate-400 flex-shrink-0"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
              />
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
              />
            </svg>
            Đổi ảnh đại diện
          </button>

          <!-- Hồ sơ cá nhân -->
          <router-link
            to="/student/profile"
            @click="showUserMenu = false"
            class="flex items-center gap-3 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 transition border-t border-slate-100"
          >
            <svg
              class="w-4 h-4 text-slate-400 flex-shrink-0"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
              />
            </svg>
            Hồ sơ cá nhân
          </router-link>

          <!-- Đổi mật khẩu -->
          <router-link
            to="/student/change-password"
            @click="showUserMenu = false"
            class="flex items-center gap-3 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 transition border-t border-slate-100"
          >
            <svg
              class="w-4 h-4 text-slate-400 flex-shrink-0"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
              />
            </svg>
            Đổi mật khẩu
          </router-link>
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

        <router-link to="/student/assignments" class="nav-link" active-class="nav-link-active">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            />
          </svg>
          <span>Bài tập nộp</span>
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

    <!-- Overlay mobile sidebar -->
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

      <main class="p-6">
        <router-view />
      </main>
    </div>

    <!-- AvatarModal — ngoài aside để tránh z-index bị clip -->
    <AvatarModal :show="showAvatarModal" @close="showAvatarModal = false" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useDashboardStore } from '@/stores/students/dashboardStore'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import AvatarModal from '@/components/modal/AvatarModal.vue'
import { getAvatarUrl } from '@/utils/imageHelper'
const router = useRouter()
const dashboardStore = useDashboardStore()
const authStore = useAuthStore()

const { classes, selectedClassId, selectedClass } = storeToRefs(dashboardStore)

const sidebarOpen = ref(false)
const showUserMenu = ref(false)
const showAvatarModal = ref(false)
const student = computed(() => authStore.user)

watch(showAvatarModal, async (val) => {
  if (!val) {
    // Modal vừa đóng → refresh để cập nhật avatar mới
    await dashboardStore.fetchMyClasses()
    // Hoặc nếu có authStore.fetchUser():
    await authStore.fetchUser()
  }
})
onMounted(() => {
  dashboardStore.fetchMyClasses()
})

function selectClass(id) {
  dashboardStore.selectClass(id)
}

function openAvatarModal() {
  showUserMenu.value = false
  showAvatarModal.value = true
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
