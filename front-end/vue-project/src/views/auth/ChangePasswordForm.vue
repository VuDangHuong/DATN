<script setup>
import { ref, reactive } from 'vue'
import { authApi } from '@/api/authApi'
import { useToastStore } from '@/stores/toast'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import SvgIcon from '@/components/icons/SVG.vue'
// const emit = defineEmits(['close'])
const toast = useToastStore()

// STATE
const loading = ref(false)
const showPassword = ref(false)

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})

// Lưu lỗi trả về từ server
const errors = reactive({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})

// Reset lỗi khi người dùng bắt đầu gõ lại
const clearError = (field) => {
  errors[field] = ''
}

const handleSubmit = async () => {
  Object.keys(errors).forEach((key) => (errors[key] = ''))

  if (form.new_password !== form.new_password_confirmation) {
    errors.new_password_confirmation = 'Mật khẩu nhập lại không khớp.'
    return
  }

  if (form.new_password.length < 8) {
    errors.new_password = 'Mật khẩu mới phải có ít nhất 8 ký tự.'
    return
  }

  loading.value = true

  try {
    await authStore.changePassword(form)
    // 4. Thành công
    toast.success('Đổi mật khẩu thành công! Vui lòng đăng nhập lại.')

    await authStore.logout()
    router.push('/login')
  } catch (error) {
    if (error.response && error.response.status === 422) {
      const apiErrors = error.response.data.errors

      if (apiErrors.current_password) errors.current_password = apiErrors.current_password[0]
      if (apiErrors.new_password) errors.new_password = apiErrors.new_password[0]

      toast.error(error.response.data.message || 'Dữ liệu không hợp lệ.')
    } else {
      toast.error('Lỗi hệ thống, vui lòng thử lại sau.')
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex items-center justify-center min-h-[80vh]">
    <div class="bg-white p-6 rounded-lg shadow-md max-w-md w-full mx-auto">
      <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2 text-center">Đổi mật khẩu</h2>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Mật khẩu hiện tại <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input
              v-model="form.current_password"
              @input="clearError('current_password')"
              :type="showPassword ? 'text' : 'password'"
              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-colors"
              :class="
                errors.current_password ? 'border-red-500 focus:ring-red-200' : 'border-gray-300'
              "
              placeholder="Nhập mật khẩu đang dùng"
              required
            />
            <button
              type="button"
              @click="showPassword = !showPassword"
              class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600 focus:outline-none"
            >
              <SvgIcon :name="showPassword ? 'eye' : 'eye-off'" className="h-5 w-5" />
            </button>
          </div>
          <p v-if="errors.current_password" class="text-red-500 text-base mt-1">
            {{ errors.current_password }}
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Mật khẩu mới <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.new_password"
            @input="clearError('new_password')"
            :type="showPassword ? 'text' : 'password'"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-colors"
            :class="errors.new_password ? 'border-red-500 focus:ring-red-200' : 'border-gray-300'"
            placeholder="Nhập mật khẩu mới"
            required
          />
          <p v-if="errors.new_password" class="text-red-500 text-base mt-1">
            {{ errors.new_password }}
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Xác nhận mật khẩu mới <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.new_password_confirmation"
            @input="clearError('new_password_confirmation')"
            :type="showPassword ? 'text' : 'password'"
            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-colors"
            :class="
              errors.new_password_confirmation
                ? 'border-red-500 focus:ring-red-200'
                : 'border-gray-300'
            "
            placeholder="Nhập lại mật khẩu mới"
            required
          />
          <p v-if="errors.new_password_confirmation" class="text-red-500 text-base mt-1">
            {{ errors.new_password_confirmation }}
          </p>
        </div>

        <div class="pt-2">
          <button
            type="submit"
            :disabled="loading"
            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
          >
            <SvgIcon
              v-if="loading"
              name="spinner"
              :spin="true"
              className="-ml-1 mr-2 h-4 w-4 text-white"
            />
            {{ loading ? 'Đang lưu...' : 'Lưu thay đổi' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
