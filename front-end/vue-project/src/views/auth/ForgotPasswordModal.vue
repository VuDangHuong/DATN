<script setup>
import { ref, reactive } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import SvgIcon from '@/components/icons/SVG.vue'

const props = defineProps({
  show: Boolean,
})

const emit = defineEmits(['close'])

const authStore = useAuthStore()
const toast = useToastStore()

// --- STATE ---
const step = ref(1)
const isLoading = ref(false)
const errorMessage = ref('')

// Dữ liệu Form
const email = ref('')
const formReset = reactive({
  otp: '',
  password: '',
  password_confirmation: '',
})

// --- ACTIONS ---

// Đóng modal và reset trạng thái
const closeModal = () => {
  emit('close')
  // Reset lại sau 300ms để tránh giật giao diện khi đóng
  setTimeout(() => {
    step.value = 1
    email.value = ''
    formReset.otp = ''
    formReset.password = ''
    formReset.password_confirmation = ''
    errorMessage.value = ''
  }, 300)
}

// BƯỚC 1: Gửi yêu cầu lấy OTP
const handleSendOtp = async () => {
  errorMessage.value = ''
  if (!email.value) {
    errorMessage.value = 'Vui lòng nhập email.'
    return
  }

  isLoading.value = true
  try {
    const res = await authStore.forgotPassword(email.value)
    toast.success(res.message || 'Mã OTP đã được gửi!')

    // Chuyển sang bước 2
    step.value = 2
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Không thể gửi mã OTP. Vui lòng thử lại.'
  } finally {
    isLoading.value = false
  }
}

// BƯỚC 2: Đặt lại mật khẩu
const handleResetPassword = async () => {
  errorMessage.value = ''
  if (formReset.password !== formReset.password_confirmation) {
    errorMessage.value = 'Mật khẩu xác nhận không khớp.'
    return
  }

  isLoading.value = true
  try {
    const res = await authStore.resetPassword({
      email: email.value,
      otp: formReset.otp,
      password: formReset.password,
      password_confirmation: formReset.password_confirmation,
    })

    toast.success('Đổi mật khẩu thành công! Hãy đăng nhập ngay.')
    closeModal()
  } catch (error) {
    errorMessage.value = error.response?.data?.message || 'Lỗi đặt lại mật khẩu.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[9999] flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm px-4"
    >
      <div class="absolute inset-0" @click="closeModal"></div>

      <div
        class="relative bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden animate-fade-in-up"
      >
        <div class="flex justify-between items-center px-6 py-4 border-b bg-gray-50">
          <h3 class="text-lg font-bold text-gray-800">
            {{ step === 1 ? 'Quên mật khẩu' : 'Đặt lại mật khẩu' }}
          </h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <div class="p-6">
          <div
            v-if="errorMessage"
            class="mb-4 p-3 bg-red-50 text-red-600 text-sm rounded border border-red-200"
          >
            {{ errorMessage }}
          </div>

          <div v-if="step === 1" class="space-y-4">
            <p class="text-sm text-gray-600">Nhập email của bạn để nhận mã xác thực (OTP).</p>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <input
                v-model="email"
                type="email"
                placeholder="email@example.com"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                @keyup.enter="handleSendOtp"
              />
            </div>
            <button
              @click="handleSendOtp"
              :disabled="isLoading"
              class="w-full py-2 bg-blue-900 text-white rounded-md hover:bg-blue-800 transition disabled:opacity-50 flex justify-center"
            >
              <span v-if="isLoading">Đang gửi...</span>
              <span v-else>Gửi mã xác nhận</span>
            </button>
          </div>

          <div v-else class="space-y-4">
            <p class="text-sm text-green-600 font-medium">Mã OTP đã được gửi đến: {{ email }}</p>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Mã OTP (6 số)</label>
              <input
                v-model="formReset.otp"
                type="text"
                placeholder="123456"
                maxlength="6"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-center tracking-widest font-bold"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Mật khẩu mới</label>
              <input
                v-model="formReset.password"
                type="password"
                placeholder="••••••••"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Xác nhận mật khẩu</label>
              <input
                v-model="formReset.password_confirmation"
                type="password"
                placeholder="••••••••"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                @keyup.enter="handleResetPassword"
              />
            </div>

            <div class="flex gap-2 pt-2">
              <button
                @click="step = 1"
                class="flex-1 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50"
              >
                Quay lại
              </button>
              <button
                @click="handleResetPassword"
                :disabled="isLoading"
                class="flex-1 py-2 bg-blue-900 text-white rounded-md hover:bg-blue-800 transition disabled:opacity-50 flex justify-center"
              >
                <span v-if="isLoading">Đang xử lý...</span>
                <span v-else>Đổi mật khẩu</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.animate-fade-in-up {
  animation: fadeInUp 0.3s ease-out;
}
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
