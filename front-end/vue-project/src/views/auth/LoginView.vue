<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import logoTlu from '@/assets/images/logo-dai-hoc-thuy-loi.jpg'
import ForgotPasswordModal from './ForgotPasswordModal.vue'
const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const isLoading = ref(false)
const errorMessage = ref('')

const showForgotModal = ref(false)

const handleLogin = async () => {
  errorMessage.value = ''
  // 2. Validate form
  if (!email.value || !password.value) {
    errorMessage.value = 'Vui lòng nhập đầy đủ Email và Mật khẩu.'
    return
  }

  isLoading.value = true

  try {
    const userData = await authStore.login({
      email: email.value,
      password: password.value,
    })
    if (userData.role === 'admin') {
      router.push('/admin/dashboard')
    } else if (userData.role === 'lecturer') {
      router.push('/lecturer/dashboard')
    } else {
      router.push('/student/dashboard')
    }
  } catch (error) {
    if (error.response) {
      const status = error.response.status

      switch (status) {
        case 401: // Unauthorized (Sai mật khẩu hoặc email)
          errorMessage.value = 'Email hoặc mật khẩu không chính xác.'
          break
        case 403: // Forbidden (Tài khoản bị khóa - is_active = false)
          errorMessage.value = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ Admin.'
          break
        case 422: // Unprocessable Entity (Lỗi validate dữ liệu)
          // Lấy thông báo lỗi đầu tiên từ mảng errors của Laravel
          const errors = error.response.data.errors
          errorMessage.value = errors ? Object.values(errors)[0][0] : 'Dữ liệu nhập không hợp lệ.'
          break
        default:
          errorMessage.value = `Lỗi hệ thống (${status}). Vui lòng thử lại sau.`
      }
    } else if (error.request) {
      // Lỗi không nhận được phản hồi (mất mạng, server down)
      errorMessage.value = 'Không thể kết nối đến máy chủ. Vui lòng kiểm tra đường truyền.'
    } else {
      // Lỗi khác trong quá trình setup request
      errorMessage.value = 'Đã có lỗi không xác định xảy ra.'
    }

    console.error('Chi tiết lỗi:', error)
  } finally {
    // 6. Kết thúc loading
    isLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 relative">
    <div class="absolute inset-0 z-0">
      <img
        src="https://upload.wikimedia.org/wikipedia/commons/thumb/6/6f/Thuyloi_University_campus.jpg/1200px-Thuyloi_University_campus.jpg"
        alt="TLU Background"
        class="w-full h-full object-cover filter blur-sm brightness-50"
      />
    </div>

    <div
      class="relative z-10 bg-white p-8 rounded-xl shadow-2xl w-full max-w-md mx-4 border-t-4 border-blue-800"
    >
      <div class="text-center mb-8">
        <div class="flex justify-center mb-4">
          <img :src="logoTlu" alt="Logo TLU" class="h-24 w-auto" />
        </div>
        <h2 class="text-2xl font-bold text-blue-900 uppercase">Cổng Thông Tin Đào Tạo</h2>
        <p class="text-gray-500 text-sm mt-1">Trường Đại Học Thủy Lợi</p>
      </div>

      <form @submit.prevent="handleLogin" class="space-y-6">
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
            Email sinh viên / Giảng viên
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 text-gray-400"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
              </svg>
            </div>
            <input
              v-model="email"
              id="email"
              type="email"
              placeholder="msv@thuyloi.edu.vn"
              required
              class="pl-10 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-200"
            />
          </div>
        </div>

        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
            Mật khẩu
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 text-gray-400"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fill-rule="evenodd"
                  d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
            <input
              v-model="password"
              id="password"
              type="password"
              placeholder="••••••••"
              required
              class="pl-10 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-200"
            />
          </div>
        </div>

        <div
          v-if="errorMessage"
          class="text-red-600 text-sm bg-red-50 p-2 rounded border border-red-200 flex items-center animate-pulse"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-4 w-4 mr-1 flex-shrink-0"
            viewBox="0 0 20 20"
            fill="currentColor"
          >
            <path
              fill-rule="evenodd"
              d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
              clip-rule="evenodd"
            />
          </svg>
          <span>{{ errorMessage }}</span>
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input
              id="remember-me"
              type="checkbox"
              class="h-4 w-4 text-blue-800 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label for="remember-me" class="ml-2 block text-sm text-gray-900">
              Ghi nhớ đăng nhập
            </label>
          </div>
          <div class="text-sm">
            <a
              href="#"
              @click.prevent="showForgotModal = true"
              class="font-medium text-blue-800 hover:text-blue-600 hover:underline"
            >
              Quên mật khẩu?
            </a>
          </div>
        </div>
        <ForgotPasswordModal :show="showForgotModal" @close="showForgotModal = false" />
        <div>
          <button
            type="submit"
            :disabled="isLoading"
            class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-900 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200 disabled:opacity-70 disabled:cursor-not-allowed"
          >
            <span v-if="isLoading" class="flex items-center">
              <svg
                class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
              Đang xử lý...
            </span>
            <span v-else>Đăng Nhập</span>
          </button>
        </div>
      </form>

      <div class="mt-6 text-center text-xs text-gray-400">
        &copy; 2024 Trường Đại học Thủy Lợi. <br />Hệ thống quản lý đào tạo.
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>
