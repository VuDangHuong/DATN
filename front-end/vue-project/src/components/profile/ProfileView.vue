<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { getAvatarUrl } from '@/utils/imageHelper'
import SvgIcon from '@/components/icons/SVG.vue'
import AvatarModal from '@/components/modal/AvatarModal.vue'
import ChangePasswordForm from '@/views/auth/ChangePasswordForm.vue'
import { useToastStore } from '@/stores/toast'
const authStore = useAuthStore()
const loading = ref(false)
// --- STATE ---
const showAvatarModal = ref(false)
const activeTab = ref('general')
const toast = useToastStore()
//Khởi tạo form rỗng
const form = reactive({
  name: '',
  email: '',
  phone: '',
})

// Hàm đồng bộ dữ liệu từ Store vào Form
const syncFormData = () => {
  if (authStore.user) {
    form.name = authStore.user.name || ''
    form.email = authStore.user.email || ''
    form.phone = authStore.user.phone || ''
  }
}

// Format vai trò
const roleName = computed(() => {
  const role = authStore.user?.role
  if (role === 'admin') return 'Quản trị viên'
  if (role === 'lecturer') return 'Giảng viên'
  return 'Sinh viên'
})

// --- ACTION ---
const handleUpdateProfile = async () => {
  loading.value = true
  try {
    await authStore.updateProfile(form)
    toast.success('Cập nhật thông tin thành công!')
  } catch (error) {
    if (error.response && error.response.status === 422) {
      const errors = error.response.data.errors
      const firstError = Object.values(errors)[0][0]
      toast.error(firstError)
    } else {
      toast.error(error.response?.data?.message || 'Lỗi cập nhật hồ sơ.')
    }
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  syncFormData()
  try {
    await authStore.fetchUser()
    syncFormData()
  } catch (error) {
    console.error('Lỗi tải thông tin người dùng:', error)
  }
})
</script>

<template>
  <div class="max-w-5xl mx-auto">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-800">Hồ sơ cá nhân</h1>
      <p class="text-gray-500 text-sm mt-1">Quản lý thông tin và bảo mật tài khoản của bạn.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="col-span-1">
        <div
          class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col items-center text-center"
        >
          <div class="relative group mb-4">
            <img
              :src="getAvatarUrl(authStore.user)"
              class="w-32 h-32 rounded-full object-cover border-4 border-gray-50 shadow-md"
              alt="Avatar"
            />
            <button
              @click="showAvatarModal = true"
              class="absolute bottom-0 right-0 bg-blue-600 text-white p-2 rounded-full shadow hover:bg-blue-700 transition-all transform hover:scale-105"
              title="Đổi ảnh đại diện"
            >
              <SvgIcon name="camera" class="w-4 h-4" />
            </button>
          </div>

          <h2 class="text-xl font-bold text-gray-800">{{ authStore.user?.name }}</h2>
          <span
            class="inline-block mt-1 px-3 py-1 bg-blue-50 text-blue-700 text-base font-semibold rounded-full"
          >
            {{ roleName }}
          </span>
          <p class="text-gray-500 text-sm mt-3">{{ authStore.user?.email }}</p>

          <div class="w-full border-t border-gray-100 my-4"></div>

          <div class="w-full flex justify-between text-sm text-gray-600 px-4">
            <span>Mã số:</span>
            <span class="font-medium text-gray-800">{{ authStore.user?.code || '---' }}</span>
          </div>
          <div class="w-full flex justify-between text-sm text-gray-600 px-4 mt-2">
            <span>Tham gia:</span>
            <span class="font-medium text-gray-800">
              {{
                authStore.user?.created_at
                  ? new Date(authStore.user.created_at).toLocaleDateString('vi-VN')
                  : '---'
              }}
            </span>
          </div>
        </div>
      </div>

      <div class="col-span-1 md:col-span-2">
        <div
          class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden min-h-[400px]"
        >
          <div class="flex border-b border-gray-200">
            <button
              @click="activeTab = 'general'"
              class="flex-1 py-4 text-sm font-medium text-center transition-colors border-b-2"
              :class="
                activeTab === 'general'
                  ? 'border-blue-600 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700'
              "
            >
              <div class="flex items-center justify-center gap-2">
                <SvgIcon name="profile" class="w-4 h-4" />
                Thông tin chung
              </div>
            </button>
            <button
              @click="activeTab = 'security'"
              class="flex-1 py-4 text-sm font-medium text-center transition-colors border-b-2"
              :class="
                activeTab === 'security'
                  ? 'border-blue-600 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700'
              "
            >
              <div class="flex items-center justify-center gap-2">
                <SvgIcon name="key" class="w-4 h-4" />
                Bảo mật
              </div>
            </button>
          </div>

          <div class="p-6">
            <div v-if="activeTab === 'general'" class="space-y-4 animate-fade-in">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Họ và tên</label>
                  <input
                    v-model="form.name"
                    type="text"
                    class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                  <input
                    v-model="form.email"
                    type="email"
                    disabled
                    class="w-full border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed text-gray-500"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Số điện thoại</label>
                  <input
                    v-model="form.phone"
                    type="text"
                    class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Chưa cập nhật"
                  />
                </div>
              </div>

              <div class="pt-4 text-right">
                <button
                  @click="handleUpdateProfile"
                  class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition shadow-sm"
                >
                  Lưu thay đổi
                </button>
              </div>
            </div>

            <div v-else-if="activeTab === 'security'" class="animate-fade-in">
              <h3 class="text-lg font-medium text-gray-800 mb-4">Đổi mật khẩu</h3>
              <div class="max-w-md mx-auto">
                <ChangePasswordForm />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <AvatarModal :show="showAvatarModal" @close="showAvatarModal = false" />
  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(5px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
