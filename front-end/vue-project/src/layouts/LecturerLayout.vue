<!-- src/layouts/LecturerLayout.vue -->
<template>
  <div class="min-h-screen bg-stone-50 flex">
    <!-- Overlay đóng dropdown — đặt TRƯỚC aside, z-40 -->
    <div v-if="showUserMenu" class="fixed inset-0 z-40" @click="showUserMenu = false" />

    <!-- ── Sidebar — z-50 cao hơn overlay ── -->
    <aside
      class="w-64 bg-white border-r border-stone-200 flex flex-col fixed h-full z-50 transition-transform duration-300"
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

      <!-- User info với dropdown -->
      <div v-if="lecturer" class="px-3 py-3.5 border-b border-stone-100 relative">
        <SearchableSelect
          v-if="lecturerStore.classes.length > 0"
          v-model="selectedClassId"
          :options="classOptions"
          label-key="label"
          value-key="id"
          placeholder="-- Lớp đang xem --"
          search-placeholder="Tìm lớp..."
          class="w-full"
        />
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5">
        <p class="px-3 pt-1 pb-2 text-[10px] font-bold text-stone-400 uppercase tracking-widest">
          Tổng quan
        </p>

        <router-link to="/lecturer/dashboard" class="nav-link" active-class="nav-active">
          <SvgIcon name="dashboard" class="w-4 h-4" />
          <span>Dashboard</span>
        </router-link>

        <p class="px-3 pt-4 pb-2 text-[10px] font-bold text-stone-400 uppercase tracking-widest">
          Lớp học
        </p>

        <router-link to="/lecturer/classes" class="nav-link" active-class="nav-active">
          <SvgIcon name="building" class="w-4 h-4 text-gray-600" />
          <span>Quản lý lớp</span>
        </router-link>

        <router-link to="/lecturer/materials" class="nav-link" active-class="nav-active">
          <SvgIcon name="document" class="w-4 h-4" />
          <span>Tài liệu lớp học</span>
        </router-link>

        <router-link to="/lecturer/tasks" class="nav-link" active-class="nav-active">
          <SvgIcon name="tasks" class="w-4 h-4" />
          <span>Công việc nhóm</span>
        </router-link>
        <router-link to="/lecturer/my-classes" class="nav-link" active-class="nav-active">
          <SvgIcon name="users" class="w-4 h-4" />
          <span>Sinh viên lớp học</span>
        </router-link>
        <p class="px-3 pt-4 pb-2 text-[10px] font-bold text-stone-400 uppercase tracking-widest">
          Bài tập
        </p>

        <router-link to="/lecturer/assignments" class="nav-link" active-class="nav-active">
          <SvgIcon name="class-book-open" class="w-4 h-4" />
          <span>Đợt nộp bài</span>
        </router-link>

        <router-link to="/lecturer/reviews" class="nav-link" active-class="nav-active">
          <SvgIcon name="check-circle" class="w-4 h-4" />
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
          <SvgIcon name="edit-pencil" class="w-4 h-4" />
          <span>Yêu cầu ký số</span>
        </router-link>

        <router-link to="/lecturer/sign-history" class="nav-link" active-class="nav-active">
          <SvgIcon name="clipboard" class="w-4 h-4" />
          <span>Lịch sử ký số</span>
        </router-link>

        <router-link to="/lecturer/sign-profile" class="nav-link" active-class="nav-active">
          <SvgIcon name="shield-check" class="w-4 h-4" />
          <span>Chữ ký số của tôi</span>
        </router-link>
      </nav>

      <!-- Logout -->
      <div class="p-3 border-t border-stone-100">
        <button
          @click="logout"
          class="nav-link w-full text-red-500 hover:bg-red-50 hover:text-red-600"
        >
          <SvgIcon name="logout" class="w-4 h-4" />
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
      <header
        class="h-16 bg-white border-b border-stone-200 flex items-center justify-between px-6 sticky top-0 z-50"
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

        <div class="flex items-center gap-3 relative">
          <div
            class="flex items-center gap-3 cursor-pointer rounded-xl hover:bg-stone-50 p-1 -m-1 transition"
            @click.stop="showUserMenu = !showUserMenu"
          >
            <img
              v-if="lecturer.avatar_url"
              :src="getAvatarUrl(lecturer)"
              class="w-9 h-9 rounded-full object-cover border-2 border-stone-100 flex-shrink-0"
              alt="avatar"
            />
            <div
              v-else
              class="w-9 h-9 rounded-full bg-teal-100 flex items-center justify-center text-sm font-bold text-teal-700 flex-shrink-0"
            >
              {{ lecturer.name?.charAt(0) }}
            </div>

            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-stone-800 truncate">{{ lecturer.name }}</p>
              <p class="text-base text-stone-400 truncate">{{ lecturer.email }}</p>
            </div>

            <svg
              class="w-3.5 h-3.5 text-stone-400 transition-transform flex-shrink-0"
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
            class="absolute right-0 top-full mt-2 w-56 bg-white rounded-xl border border-stone-200 shadow-lg overflow-hidden"
          >
            <button
              @click="openAvatarModal"
              class="w-full flex items-center gap-3 px-4 py-3 text-sm text-stone-700 hover:bg-stone-50 transition text-left"
            >
              <svg
                class="w-4 h-4 text-stone-400 flex-shrink-0"
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
              to="/lecturer/profile"
              @click="showUserMenu = false"
              class="flex items-center gap-3 px-4 py-3 text-sm text-stone-700 hover:bg-stone-50 transition border-t border-stone-100"
            >
              <svg
                class="w-4 h-4 text-stone-400 flex-shrink-0"
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
              to="/lecturer/change-password"
              @click="showUserMenu = false"
              class="flex items-center gap-3 px-4 py-3 text-sm text-stone-700 hover:bg-stone-50 transition border-t border-stone-100"
            >
              <svg
                class="w-4 h-4 text-stone-400 flex-shrink-0"
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

      <main class="flex-1 p-6">
        <router-view />
      </main>
    </div>

    <!-- AvatarModal — ngoài aside tránh z-index clip -->
    <AvatarModal :show="showAvatarModal" @close="onAvatarModalClose" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useLecturerStore } from '@/stores/lecturer/lecturerStore'
import AvatarModal from '@/components/modal/AvatarModal.vue'
import axiosClient from '@/api/axiosClient'
import { getAvatarUrl } from '@/utils/imageHelper'
import SearchableSelect from '@/components/common/SearchableSelect.vue'
import SvgIcon from '@/components/icons/SVG.vue'
const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const lecturerStore = useLecturerStore()

const sidebarOpen = ref(false)
const pendingCount = ref(0)
const showUserMenu = ref(false)
const showAvatarModal = ref(false)
const classOptions = computed(() =>
  lecturerStore.classes.map((c) => ({
    id: c.id,
    label: `${c.code} - ${c.name}`,
  })),
)
// ✅ lecturer luôn reactive với authStore.user
const lecturer = computed(() => authStore.user)

const pageMap = {
  '/lecturer/dashboard': 'Dashboard',
  '/lecturer/classes': 'Quản lý lớp',
  '/lecturer/tasks': 'Công việc nhóm',
  '/lecturer/my-classes': 'Sinh viên lớp học',
  '/lecturer/materials': 'Tài liệu lớp học',
  '/lecturer/assignments': 'Đợt nộp bài',
  '/lecturer/reviews': 'Duyệt bài nộp',
  '/lecturer/sign-requests': 'Yêu cầu ký số',
  '/lecturer/sign-history': 'Lịch sử ký số',
  '/lecturer/profile': 'Hồ sơ cá nhân',
  '/lecturer/change-password': 'Đổi mật khẩu',
}

const currentPageTitle = computed(() => {
  for (const [path, title] of Object.entries(pageMap)) {
    if (route.path.startsWith(path)) return title
  }
  return 'Giảng viên'
})

const selectedClassId = computed({
  get: () => lecturerStore.selectedClassId,
  set: (val) => lecturerStore.setSelectedClassId(val),
})

// Trong onMounted
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

function openAvatarModal() {
  showUserMenu.value = false
  showAvatarModal.value = true
}

// ✅ Khi modal đóng → fetchUser để cập nhật avatar mới
async function onAvatarModalClose() {
  showAvatarModal.value = false
  try {
    await authStore.fetchUser()
  } catch {}
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
