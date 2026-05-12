<!--
src/components/lecturer/sign/LecturerSignConfirmForm.vue
Form xác nhận ký - check sign-profile trước khi cho phép ký
-->
<template>
  <div class="p-6">
    <h3 class="text-lg font-bold text-stone-800 mb-1">🔏 Xác nhận ký số tài liệu</h3>
    <p class="text-sm text-stone-500 mb-5">
      {{ request?.requester?.name }} - {{ request?.document_category_label }}
    </p>

    <!-- ✅ E2: Chưa có sign-profile -->
    <div
      v-if="!signProfile && !checkingProfile"
      class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-xl"
    >
      <div class="flex items-start gap-3">
        <svg
          class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
        <div class="flex-1">
          <p class="text-sm font-semibold text-amber-800 mb-1">Chưa đăng ký chữ ký số</p>
          <p class="text-xs text-amber-700 mb-3">
            Bạn cần đăng ký chữ ký số cá nhân trước khi có thể ký tài liệu.
          </p>
          <router-link
            to="/lecturer/sign-profile"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-600 text-white rounded-lg text-xs font-medium hover:bg-amber-700 transition"
          >
            Đăng ký ngay →
          </router-link>
        </div>
      </div>
    </div>

    <!-- ✅ E3: Chữ ký hết hạn -->
    <div v-else-if="isExpired" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl">
      <div class="flex items-start gap-3">
        <svg
          class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <div class="flex-1">
          <p class="text-sm font-semibold text-red-800 mb-1">Chữ ký số đã hết hạn</p>
          <p class="text-xs text-red-700 mb-3">
            Chứng thư của bạn đã hết hạn ngày
            <strong>{{ formatDate(signProfile?.cert_expires_at) }}</strong
            >. Vui lòng cập nhật chữ ký số mới.
          </p>
          <router-link
            to="/lecturer/sign-profile"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 text-white rounded-lg text-xs font-medium hover:bg-red-700 transition"
          >
            Cập nhật chữ ký →
          </router-link>
        </div>
      </div>
    </div>

    <!-- ✅ Hợp lệ → form xác nhận -->
    <template v-else-if="signProfile && !isExpired">
      <!-- Info chữ ký số -->
      <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl">
        <div class="flex items-center gap-2 mb-1">
          <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
            <path
              fill-rule="evenodd"
              d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
              clip-rule="evenodd"
            />
          </svg>
          <span class="text-xs font-semibold text-emerald-800">Chữ ký số hợp lệ</span>
        </div>
        <p class="text-[11px] text-emerald-700">
          Serial: <span class="font-mono">{{ signProfile.certificate_serial }}</span>
          <span class="ml-2">· Hết hạn: {{ formatDate(signProfile.cert_expires_at) }}</span>
        </p>
      </div>

      <!-- Checkbox xác nhận -->
      <label class="flex items-start gap-2 mb-4 cursor-pointer">
        <input
          type="checkbox"
          v-model="confirmed"
          class="mt-0.5 rounded text-teal-600 focus:ring-teal-500"
        />
        <span class="text-sm text-stone-700">
          Tôi xác nhận đã xem xét nội dung tài liệu và đồng ý ký số bằng chứng thư của mình. Hệ
          thống sẽ tự động tạo phiếu xác nhận và gửi cho sinh viên.
        </span>
      </label>

      <div
        class="flex items-center gap-2 text-xs text-stone-400 bg-stone-50 rounded-xl px-3 py-2 mb-4"
      >
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
          />
        </svg>
        Sinh viên sẽ nhận email thông báo và mã xác thực SHA-256
      </div>
    </template>

    <!-- Loading check profile -->
    <div v-else class="flex items-center justify-center py-8 text-stone-400 text-sm">
      <div
        class="w-5 h-5 border-2 border-stone-300 border-t-stone-600 rounded-full animate-spin mr-2"
      />
      Đang kiểm tra chữ ký số...
    </div>

    <!-- Buttons -->
    <div class="flex gap-3">
      <button
        @click="$emit('cancel')"
        class="flex-1 py-2.5 border border-stone-200 rounded-xl text-sm font-medium text-stone-600 hover:bg-stone-50"
      >
        Hủy
      </button>
      <button
        @click="handleSubmit"
        :disabled="!canSign || submitting"
        class="flex-1 py-2.5 bg-teal-600 text-white rounded-xl text-sm font-semibold hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center justify-center gap-2"
      >
        <div
          v-if="submitting"
          class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
        />
        {{ submitting ? 'Đang ký...' : 'Xác nhận ký số' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axiosClient from '@/api/axiosClient'
import { useToastStore } from '@/stores/toast'

const props = defineProps({
  request: { type: Object, required: true },
})

const emit = defineEmits(['cancel', 'success'])

const toast = useToastStore()
const signProfile = ref(null)
const checkingProfile = ref(true)
const confirmed = ref(false)
const submitting = ref(false)

const isExpired = computed(() => {
  if (!signProfile.value?.cert_expires_at) return false
  return new Date(signProfile.value.cert_expires_at) < new Date()
})

const canSign = computed(() => signProfile.value && !isExpired.value && confirmed.value)

onMounted(loadSignProfile)

async function loadSignProfile() {
  checkingProfile.value = true
  try {
    const { data } = await axiosClient.get('/lecturer/sign-profile')
    signProfile.value = data.profile ?? data.data ?? data
  } catch (e) {
    if (e.response?.status === 404) {
      signProfile.value = null // Chưa đăng ký
    } else {
      toast.error('Không thể tải thông tin chữ ký số')
    }
  } finally {
    checkingProfile.value = false
  }
}

async function handleSubmit() {
  if (!canSign.value) return
  submitting.value = true
  try {
    const { data } = await axiosClient.post(`/lecturer/sign-requests/${props.request.id}/sign`)
    toast.success(data.message ?? 'Đã ký số tài liệu thành công')
    emit('success', data.data)
  } catch (e) {
    // ✅ Log đầy đủ để debug
    console.error('Sign error:', {
      status: e.response?.status,
      data: e.response?.data,
      message: e.message,
    })

    const errCode = e.response?.data?.error_code
    if (errCode === 'NO_SIGN_PROFILE') {
      toast.error('Bạn chưa đăng ký chữ ký số')
      signProfile.value = null
    } else if (errCode === 'SIGN_PROFILE_EXPIRED') {
      toast.error('Chữ ký số đã hết hạn')
      await loadSignProfile()
    } else {
      // ✅ Hiện cụ thể lỗi server
      toast.error(e.response?.data?.message ?? `Lỗi ${e.response?.status}: ${e.message}`)
    }
  } finally {
    submitting.value = false
  }
}

function formatDate(d) {
  if (!d) return 'Không xác định'
  return new Date(d).toLocaleDateString('vi-VN')
}
</script>
