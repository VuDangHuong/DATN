<template>
  <div class="min-h-screen bg-slate-50 flex">
    <div v-if="showUserMenu" class="fixed inset-0 z-40" @click="showUserMenu = false" />

    <aside
      class="w-64 bg-white border-r border-slate-200 flex flex-col fixed h-full z-50 transition-transform duration-300"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    >
      <div class="h-16 flex items-center gap-3 px-5 border-b border-slate-100">
        <div
          class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center flex-shrink-0"
        >
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
          <h1 class="text-sm font-bold text-slate-800 tracking-tight">EduGroup</h1>
          <p class="text-[10px] text-slate-400 uppercase tracking-widest">Student Portal</p>
        </div>
      </div>

      <div v-if="student" class="px-3 py-3.5 border-b border-slate-100 relative">
        <SearchableSelect
          v-if="classes.length > 0"
          v-model="selectedClassId"
          :options="classOptions"
          label-key="label"
          value-key="id"
          placeholder="Tất cả lớp"
          search-placeholder="Tìm lớp..."
          class="w-full"
        />
      </div>

      <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5">
        <p class="px-3 pt-1 pb-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
          Tổng quan
        </p>

        <router-link to="/student/dashboard" class="nav-link" active-class="nav-active">
          <SvgIcon name="dashboard" class="w-4 h-4" />
          <span>Dashboard</span>
        </router-link>

        <p class="px-3 pt-4 pb-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
          Nhóm
        </p>

        <router-link to="/student/groups" class="nav-link" active-class="nav-active">
          <SvgIcon name="group-users" class="w-4 h-4" />
          <span>Quản lý nhóm</span>
        </router-link>

        <router-link to="/student/chat" class="nav-link" active-class="nav-active">
          <SvgIcon name="chat" class="w-4 h-4" />
          <span>Chat nhóm</span>
        </router-link>

        <p class="px-3 pt-4 pb-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
          Công việc
        </p>

        <router-link to="/student/tasks" class="nav-link" active-class="nav-active">
          <SvgIcon name="clipboard" class="w-4 h-4" />
          <span>Quản lý công việc</span>
        </router-link>

        <router-link to="/student/assignments" class="nav-link" active-class="nav-active">
          <SvgIcon name="document" class="w-4 h-4" />
          <span>Bài tập nộp</span>
        </router-link>

        <router-link to="/student/materials" class="nav-link" active-class="nav-active">
          <SvgIcon name="class-book" class="w-4 h-4" />
          <span>Tài liệu học tập</span>
        </router-link>
      </nav>

      <div class="p-3 border-t border-slate-100">
        <button
          @click="logout"
          class="nav-link w-full text-red-500 hover:bg-red-50 hover:text-red-600"
        >
          <SvgIcon name="logout" class="w-4 h-4" />
          <span>Đăng xuất</span>
        </button>
      </div>
    </aside>

    <div
      v-if="sidebarOpen"
      @click="sidebarOpen = false"
      class="fixed inset-0 bg-black/20 z-20 lg:hidden"
    />

    <div class="flex-1 lg:ml-64 flex flex-col min-h-screen">
      <header
        class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 sticky top-0 z-50"
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

        <div class="hidden lg:flex items-center gap-2 text-sm text-slate-500">
          <span v-if="selectedClass" class="text-slate-800 font-medium">
            {{ selectedClass?.class?.name }}
            <span class="mx-1 text-slate-300">·</span>
            <span class="text-sm text-slate-500 font-normal">{{
              selectedClass?.subjects?.[0]?.name
            }}</span>
          </span>
          <span v-else class="text-slate-800 font-medium">Tất cả lớp</span>
        </div>

        <div class="flex items-center gap-3 relative">
          <div
            class="flex items-center gap-3 cursor-pointer rounded-xl hover:bg-slate-50 p-1 -m-1 transition"
            @click.stop="showUserMenu = !showUserMenu"
          >
            <img
              v-if="authStore.user?.avatar_url"
              :src="getAvatarUrl(authStore.user)"
              class="w-9 h-9 rounded-full object-cover border-2 border-slate-100 flex-shrink-0"
              alt="avatar"
            />
            <div
              v-else
              class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-400 to-teal-500 flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
            >
              {{ authStore.user?.name?.charAt(0) }}
            </div>

            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-slate-800 truncate">{{ student.name }}</p>
              <p class="text-xs text-slate-400 truncate">{{ student.code }}</p>
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

          <div
            v-if="showUserMenu"
            @click.stop
            class="absolute right-0 top-full mt-2 w-56 bg-white rounded-xl border border-slate-200 shadow-lg overflow-hidden"
          >
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
      </header>

      <main class="p-6 flex-1">
        <router-view />
      </main>
    </div>

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
import SearchableSelect from '@/components/common/SearchableSelect.vue'
import SvgIcon from '@/components/icons/SVG.vue'
const router = useRouter()
const dashboardStore = useDashboardStore()
const authStore = useAuthStore()

const { classes, selectedClassId, selectedClass } = storeToRefs(dashboardStore)

const sidebarOpen = ref(false)
const showUserMenu = ref(false)
const showAvatarModal = ref(false)
const student = computed(() => authStore.user)

// ✅ Tạo Computed classOptions chuyển đổi cấu trúc dữ liệu của Student cho khớp component
const classOptions = computed(() =>
  classes.value.map((c) => ({
    id: c.class.id,
    label: `${c.class.code} - ${c.class.name}`,
  })),
)

watch(showAvatarModal, async (val) => {
  if (!val) {
    await dashboardStore.fetchMyClasses()
    await authStore.fetchUser()
  }
})

onMounted(() => {
  dashboardStore.fetchMyClasses()
})

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
  @apply flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm text-slate-600
         hover:bg-slate-100 hover:text-slate-800 transition-all duration-150 font-medium w-full;
}
.nav-active {
  @apply bg-indigo-50 text-indigo-700 font-semibold;
}
</style>
