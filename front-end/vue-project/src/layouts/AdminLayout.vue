<script setup>
import AdminSidebar from '@/components/AdminSidebar.vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { ref, onMounted, onUnmounted } from 'vue'
import SvgIcon from '@/components/icons/SVG.vue'
import { getAvatarUrl, handleImageError } from '@/utils/imageHelper'
import { useUserStore } from '@/stores/admin/user'
import { storeToRefs } from 'pinia'
import AvatarModal from '@/components/modal/AvatarModal.vue'
const authStore = useAuthStore()
const router = useRouter()
const userStore = useUserStore()
const { users } = storeToRefs(userStore)
// --- STATE ---
const showUserMenu = ref(false)
const dropdownRef = ref(null)
const isMobileMenuOpen = ref(false)
const showAvatarModal = ref(false)

// --- LOGIC MOBILE ---
const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

const closeMobileMenu = () => {
  isMobileMenuOpen.value = false
}

// --- LOGIC USER MENU ---
const toggleUserMenu = () => {
  showUserMenu.value = !showUserMenu.value
}

const handleClickOutside = (event) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    showUserMenu.value = false
  }
}

// --- LOGIC AUTH & NAV ---
const handleLogout = async () => {
  // if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
  try {
    await authStore.logout()
  } finally {
    window.location.href = '/login'
  }
  // }
}

const navigateTo = (path) => {
  router.push(path)
  showUserMenu.value = false
}

// Hooks
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div class="flex h-screen bg-gray-100 font-sans overflow-hidden">
    <div
      v-if="isMobileMenuOpen"
      @click="closeMobileMenu"
      class="fixed inset-0 z-20 bg-black bg-opacity-50 transition-opacity lg:hidden"
    ></div>

    <AdminSidebar :is-mobile-open="isMobileMenuOpen" @close-mobile="closeMobileMenu" />

    <div class="flex-1 flex flex-col overflow-hidden relative">
      <header
        class="h-16 bg-white shadow-sm flex items-center justify-between px-4 sm:px-6 z-10 relative"
      >
        <div class="flex items-center gap-4">
          <button
            @click="toggleMobileMenu"
            class="text-gray-500 focus:outline-none lg:hidden hover:bg-gray-100 p-2 rounded-md"
          >
            <SvgIcon name="menu" class="h-6 w-6" />
          </button>

          <h2 class="text-xl font-semibold text-gray-800 truncate">
            Hệ thống quản lý học phần nhóm
          </h2>
        </div>

        <div class="flex items-center gap-3">
          <div class="relative" ref="dropdownRef">
            <button
              @click="toggleUserMenu"
              class="flex items-center gap-3 focus:outline-none hover:bg-gray-50 px-2 py-1.5 rounded-lg transition duration-200 border border-transparent hover:border-gray-200"
            >
              <img
                class="h-10 w-10 rounded-full object-cover"
                :src="getAvatarUrl(authStore.user)"
                :alt="authStore.user?.name"
                @error="(e) => handleImageError(e, authStore.user?.name)"
              />

              <div class="text-left hidden md:block">
                <p class="text-sm font-semibold text-gray-700 leading-none">Admin</p>
                <p class="text-xs text-gray-500 mt-1">Quản trị viên</p>
              </div>

              <SvgIcon
                name="chevron-down"
                class="h-4 w-4 text-gray-400 transition-transform duration-200"
                :class="{ 'rotate-180': showUserMenu }"
              />
            </button>

            <transition
              enter-active-class="transition ease-out duration-100"
              enter-from-class="transform opacity-0 scale-95"
              enter-to-class="transform opacity-100 scale-100"
              leave-active-class="transition ease-in duration-75"
              leave-from-class="transform opacity-100 scale-100"
              leave-to-class="transform opacity-0 scale-95"
            >
              <div
                v-if="showUserMenu"
                class="absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg py-1 border border-gray-100 ring-1 ring-black ring-opacity-5 focus:outline-none"
              >
                <button
                  @click="navigateTo('/admin/profile')"
                  class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-blue-600 flex items-center"
                >
                  <SvgIcon name="profile" class="h-4 w-4 mr-3" />
                  Thông tin cá nhân
                </button>

                <button
                  @click="((showAvatarModal = true), (showUserMenu = false))"
                  class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-blue-600 flex items-center"
                >
                  <SvgIcon name="camera" class="h-4 w-4 mr-3" />
                  Cập nhật ảnh đại diện
                </button>

                <button
                  @click="navigateTo('/admin/change-password')"
                  class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-blue-600 flex items-center"
                >
                  <SvgIcon name="key" class="h-4 w-4 mr-3" />
                  Đổi mật khẩu
                </button>
              </div>
            </transition>
          </div>

          <button
            @click="handleLogout"
            class="flex items-center px-3 py-2 text-sm font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors duration-200"
            title="Đăng xuất"
          >
            <SvgIcon name="logout" class="h-4 w-4" />
            <span class="ml-2 hidden sm:inline">Đăng xuất</span>
          </button>
        </div>
      </header>

      <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>

      <AvatarModal :show="showAvatarModal" @close="showAvatarModal = false" />
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
