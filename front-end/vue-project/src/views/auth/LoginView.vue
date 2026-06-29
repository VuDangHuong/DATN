<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import logoTlu from '@/assets/images/logo-dai-hoc-thuy-loi.jpg'
import ForgotPasswordModal from './ForgotPasswordModal.vue'

const router = useRouter()
const authStore = useAuthStore()
const rememberMe = ref(false)
const email = ref('')
const password = ref('')
const isLoading = ref(false)
const errorMessage = ref('')
const showPassword = ref(false) // UX: Trạng thái ẩn/hiện mật khẩu

const showForgotModal = ref(false)
const REMEMBER_KEY = 'remembered_email'

onMounted(() => {
  const savedEmail = localStorage.getItem(REMEMBER_KEY)
  if (savedEmail) {
    email.value = savedEmail
    rememberMe.value = true
  }
})

const handleLogin = async () => {
  errorMessage.value = ''

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
    localStorage.setItem('user', JSON.stringify(userData))

    if (rememberMe.value) {
      localStorage.setItem(REMEMBER_KEY, email.value)
    } else {
      localStorage.removeItem(REMEMBER_KEY)
    }

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
        case 401:
          errorMessage.value = 'Email hoặc mật khẩu không chính xác.'
          break
        case 403:
          errorMessage.value = 'Tài khoản của bạn đã bị khóa. Vui lòng liên hệ Admin.'
          break
        case 422:
          const errors = error.response.data.errors
          errorMessage.value = errors ? Object.values(errors)[0][0] : 'Dữ liệu nhập không hợp lệ.'
          break
        default:
          errorMessage.value = `Lỗi hệ thống (${status}). Vui lòng thử lại sau.`
      }
    } else if (error.request) {
      errorMessage.value = 'Không thể kết nối đến máy chủ. Vui lòng kiểm tra đường truyền.'
    } else {
      errorMessage.value = 'Đã có lỗi không xác định xảy ra.'
    }
    console.error('Chi tiết lỗi:', error)
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div
    class="min-h-screen flex bg-slate-50 font-sans antialiased selection:bg-blue-500 selection:text-white"
  >
    <div
      class="hidden md:flex md:w-2/5 relative overflow-hidden items-center justify-center bg-blue-950"
    >
      <img
        src="/src/assets/images/tlucampus.png"
        alt="TLU Campus"
        class="absolute inset-0 w-full h-full object-cover transition-transform duration-[10000ms] hover:scale-105"
      />
      <div
        class="absolute inset-0 bg-gradient-to-tr from-blue-950/90 via-blue-900/60 to-transparent"
      ></div>

      <div class="relative z-10 p-12 text-white max-w-lg drop-shadow-md">
        <span
          class="bg-blue-500/30 text-blue-200 text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wider backdrop-blur-sm"
        >
          Chào mừng bạn trở lại
        </span>
        <h1 class="text-3xl lg:text-4xl font-black mt-4 tracking-tight leading-tight">
          Cổng Thông Tin <br /><span class="text-blue-400">Đào Tạo TLU</span>
        </h1>
        <p class="mt-4 text-slate-200 text-sm leading-relaxed font-light">
          Hệ thống quản lý học tập, kết nối giảng viên và sinh viên Trường Đại học Thủy Lợi. Kiên
          định - Sáng tạo - Phục vụ.
        </p>
      </div>
    </div>

    <div
      class="w-full md:w-3/5 flex flex-col justify-between p-8 sm:p-16 lg:p-20 bg-white shadow-[0_0_60px_rgba(0,0,0,0.04)] z-10"
    >
      <!-- <div class="flex justify-end text-sm text-gray-400 font-medium">
        <span class="text-gray-600 font-semibold cursor-default">VN</span>
        <span class="mx-2.5">|</span>
        <span class="hover:text-gray-700 cursor-pointer transition-colors">EN</span>
      </div> -->

      <div class="w-full max-w-xl mx-auto my-auto py-10">
        <div class="text-center mb-10">
          <div class="inline-flex p-4">
            <img :src="logoTlu" alt="Logo TLU" class="h-24 w-auto object-contain" />
          </div>
          <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">Đăng Nhập Tài Khoản</h2>
          <p class="text-slate-600 text-base mt-2.5">
            Sử dụng tài khoản nội bộ được cấp bởi nhà trường để bắt đầu.
          </p>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-6">
          <div>
            <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">
              TÀI KHOẢN (EMAIL)
            </label>
            <div class="relative flex items-center group">
              <div
                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-600 transition-colors"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-5 w-5"
                  viewBox="0 0 20 20"
                  fill="currentColor"
                >
                  <path
                    d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"
                  />
                  <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                </svg>
              </div>
              <input
                v-model="email"
                id="email"
                type="email"
                placeholder="Nhập mã sinh viên hoặc email..."
                required
                class="pl-12 pr-4 block w-full h-12 bg-slate-50 border border-slate-200 rounded-xl placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600/15 focus:border-blue-600 focus:bg-white text-base transition-all duration-200"
              />
            </div>
          </div>

          <div>
            <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">
              MẬT KHẨU
            </label>
            <div class="relative flex items-center group">
              <div
                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-600 transition-colors"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-5 w-5"
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
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••••••"
                required
                class="pl-12 pr-12 block w-full h-12 bg-slate-50 border border-slate-200 rounded-xl placeholder-slate-400 tracking-wider focus:outline-none focus:ring-2 focus:ring-blue-600/15 focus:border-blue-600 focus:bg-white text-base transition-all duration-200"
              />

              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-4 p-1 text-slate-400 hover:text-slate-600 focus:outline-none transition-colors"
                title="Hiển thị/Ẩn mật khẩu"
              >
                <svg
                  v-if="showPassword"
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                  />
                </svg>
                <svg
                  v-else
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"
                  />
                </svg>
              </button>
            </div>
          </div>

          <div
            v-if="errorMessage"
            class="text-base bg-red-50 text-red-700 p-4 rounded-xl border border-red-100 flex items-start gap-3 animate-[fadeIn_0.25s_ease-out]"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-6 w-6 flex-shrink-0 text-red-500 mt-0.5"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fill-rule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                clip-rule="evenodd"
              />
            </svg>
            <span class="font-medium leading-normal">{{ errorMessage }}</span>
          </div>

          <div class="flex items-center justify-between pt-1">
            <label class="flex items-center select-none cursor-pointer group">
              <input
                id="remember-me"
                v-model="rememberMe"
                type="checkbox"
                class="h-4.5 w-4.5 text-blue-600 focus:ring-blue-500/15 border-slate-300 rounded transition-all cursor-pointer"
              />
              <span
                class="ml-3 text-sm text-slate-600 group-hover:text-slate-900 transition-colors"
              >
                Ghi nhớ đăng nhập
              </span>
            </label>

            <a
              href="#"
              @click.prevent="showForgotModal = true"
              class="text-sm font-semibold text-blue-600 hover:text-blue-700 hover:underline transition-colors"
            >
              Quên mật khẩu?
            </a>
          </div>

          <div class="pt-4">
            <button
              type="submit"
              :disabled="isLoading"
              class="w-full flex justify-center py-4 px-6 border border-transparent rounded-xl shadow-md text-base font-bold text-white bg-blue-900 hover:bg-blue-950 focus:outline-none focus:ring-4 focus:ring-blue-900/15 active:scale-[0.99] transition-all duration-150 disabled:opacity-65 disabled:pointer-events-none"
            >
              <span v-if="isLoading" class="flex items-center gap-2.5">
                <svg
                  class="animate-spin h-5 w-5 text-white"
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
                Đang xử lý đăng nhập...
              </span>
              <span v-else>Đăng Nhập</span>
            </button>
          </div>

          <!-- Xác nhận tài liệu ký số -->
          <div class="text-center pt-2">
            <a
              href="/verify"
              class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 hover:text-blue-700 hover:underline transition-colors"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                ư
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                />
              </svg>
              Xác nhận tài liệu ký số
            </a>
          </div>
        </form>

        <ForgotPasswordModal :show="showForgotModal" @close="showForgotModal = false" />
      </div>

      <div class="text-center text-sm text-slate-400 font-medium">
        &copy; 2026 Trường Đại học Thủy Lợi.
        <span class="block mt-1 text-slate-300"
          >Phát triển bởi Trung tâm Tin học & phần mềm quản lý đào tạo.</span
        >
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
