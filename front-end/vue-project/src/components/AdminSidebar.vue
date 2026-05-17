<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import SvgIcon from '@/components/icons/SVG.vue'
import { useAdminSignProfileStore } from '@/stores/admin/sign/adminSignProfileStore'

const props = defineProps({
  isMobileOpen: Boolean,
})

const emit = defineEmits(['close-mobile'])

const route = useRoute()
const isCollapsed = ref(false)

//Load stats để hiện badge số pending requests
const adminSignStore = useAdminSignProfileStore()
const { stats } = storeToRefs(adminSignStore)

const pendingCount = computed(() => stats.value?.pending_requests ?? 0)

onMounted(async () => {
  try {
    await adminSignStore.fetchStats()
  } catch {
    /* ignore */
  }
})

const toggleSidebar = () => {
  isCollapsed.value = !isCollapsed.value
}

const handleLinkClick = () => {
  emit('close-mobile')
}

const menuItems = [
  { name: 'Dashboard', path: '/admin/dashboard', icon: 'dashboard' },
  { name: 'Quản lý người dùng', path: '/admin/users', icon: 'users' },
  { name: 'Quản lý niên khóa', path: '/admin/semesters', icon: 'semesters' },
  { name: 'Quản lý lớp học phần', path: '/admin/classes', icon: 'classes' },
  { name: 'Quản lý danh mục', path: '/admin/master-data', icon: 'master-data' },
  { name: 'Quản lý Chat', path: '/admin/chatBot', icon: 'master-data' },
  { name: 'Quản lý chữ ký số', path: '/admin/sign-profiles', icon: 'sign' },
  {
    name: 'Yêu cầu vô hiệu hóa',
    path: '/admin/deactivation-requests',
    icon: 'sign',
    badge: 'pending',
  },
  { name: 'Ký số tài liệu', path: '/admin/sign-requests', icon: 'sign' },
  { name: 'Cấu hình hệ thống', path: '/admin/settings', icon: 'settings' },
  { name: 'Giám sát & Báo cáo', path: '/admin/reports', icon: 'reports' },
]
</script>

<template>
  <aside
    class="fixed inset-y-0 left-0 z-30 bg-blue-900 text-white shadow-lg transition-all duration-300 ease-in-out lg:static lg:translate-x-0"
    :class="[
      isMobileOpen ? 'translate-x-0 w-64' : '-translate-x-full',
      isCollapsed ? 'lg:w-20' : 'lg:w-64',
    ]"
  >
    <div
      class="h-16 flex items-center justify-center border-b border-blue-800 bg-blue-950 overflow-hidden whitespace-nowrap relative"
    >
      <button
        @click="$emit('close-mobile')"
        class="absolute right-4 lg:hidden text-blue-300 hover:text-white"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-6 w-6"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"
          />
        </svg>
      </button>

      <transition name="fade" mode="out-in">
        <span v-if="!isCollapsed" class="text-xl font-bold tracking-wider">TLU ADMIN</span>
        <span v-else class="text-xl font-bold">TLU</span>
      </transition>
    </div>

    <button
      @click="toggleSidebar"
      class="hidden lg:block absolute -right-3 top-20 bg-yellow-400 text-blue-900 rounded-full p-1 shadow-md hover:bg-yellow-300 transition-colors z-50 border-2 border-white"
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-4 w-4 transition-transform duration-300"
        :class="isCollapsed ? 'rotate-180' : ''"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
    </button>

    <nav class="flex-1 py-4 overflow-x-hidden overflow-y-auto">
      <ul>
        <li v-for="item in menuItems" :key="item.path" class="mb-1">
          <router-link
            :to="item.path"
            @click="handleLinkClick"
            class="flex items-center py-3 hover:bg-blue-800 transition-colors duration-200 group relative"
            :class="[
              isCollapsed && !isMobileOpen ? 'justify-center px-0' : 'px-6',
              route.path.startsWith(item.path)
                ? 'bg-blue-800 border-l-4 border-yellow-400'
                : 'border-l-4 border-transparent',
            ]"
          >
            <div class="relative flex items-center justify-center">
              <SvgIcon
                :name="item.icon"
                class="transition-all duration-300"
                :class="isCollapsed && !isMobileOpen ? 'h-6 w-6' : 'h-5 w-5 mr-3'"
              />
              <!-- Dot báo có pending khi sidebar collapsed -->
              <span
                v-if="item.badge === 'pending' && pendingCount > 0 && isCollapsed && !isMobileOpen"
                class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-blue-900 lg:block hidden"
              />
            </div>

            <span
              class="whitespace-nowrap transition-all duration-300 origin-left"
              :class="
                isCollapsed && !isMobileOpen
                  ? 'lg:opacity-0 lg:w-0 lg:hidden'
                  : 'opacity-100 w-auto'
              "
            >
              {{ item.name }}
            </span>

            <!-- Badge số lượng pending (chỉ hiện khi sidebar expanded) -->
            <span
              v-if="item.badge === 'pending' && pendingCount > 0"
              class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"
              :class="isCollapsed && !isMobileOpen ? 'lg:hidden' : ''"
            >
              {{ pendingCount }}
            </span>

            <!-- Tooltip khi collapsed -->
            <div
              v-if="isCollapsed"
              class="hidden lg:block absolute left-full top-1/2 transform -translate-y-1/2 ml-2 bg-gray-900 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-50 whitespace-nowrap shadow-lg"
            >
              {{ item.name }}
              <span
                v-if="item.badge === 'pending' && pendingCount > 0"
                class="ml-1 bg-red-500 text-white px-1.5 rounded-full"
              >
                {{ pendingCount }}
              </span>
            </div>
          </router-link>
        </li>
      </ul>
    </nav>
  </aside>
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
