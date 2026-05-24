<!-- src/components/lecturer/sign/DeactivationRequestModal.vue -->
<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="$emit('close')" />
      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-start gap-3 mb-4">
          <div
            class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0"
          >
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
              />
            </svg>
          </div>
          <div>
            <h3 class="text-lg font-bold text-stone-800">Yêu cầu vô hiệu hóa chữ ký số</h3>
            <p class="text-base text-stone-500 mt-1">Yêu cầu sẽ được gửi cho Admin duyệt</p>
          </div>
        </div>

        <!-- Warning -->
        <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl mb-4">
          <p class="text-base text-amber-800">
            ⚠️ <strong>Lưu ý:</strong> Trong thời gian chờ duyệt, bạn sẽ
            <strong>không thể ký tài liệu</strong>. Nếu Admin từ chối, chữ ký sẽ tiếp tục hoạt động
            bình thường.
          </p>
        </div>

        <form @submit.prevent="handleSubmit" class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-stone-700 mb-1">
              Lý do vô hiệu hóa <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="reason"
              rows="4"
              maxlength="1000"
              placeholder="Vui lòng nêu rõ lý do (vd: chứng chỉ bị lộ, đổi sang chứng chỉ mới...)"
              class="w-full px-3 py-2 border border-stone-200 rounded-xl text-sm resize-none focus:ring-2 focus:ring-red-500 outline-none"
              :class="error ? 'border-red-300' : ''"
            />
            <div class="flex justify-between mt-1">
              <p v-if="error" class="text-base text-red-500">{{ error }}</p>
              <p v-else class="text-base text-stone-400">Tối thiểu 10 ký tự</p>
              <p class="text-base text-stone-400">{{ reason.length }}/1000</p>
            </div>
          </div>

          <div class="flex gap-3 pt-2">
            <button
              type="button"
              @click="$emit('close')"
              class="flex-1 py-2.5 border border-stone-200 rounded-xl text-sm font-medium text-stone-600 hover:bg-stone-50"
            >
              Hủy
            </button>
            <button
              type="submit"
              :disabled="reason.length < 10 || submitting"
              class="flex-1 py-2.5 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 disabled:opacity-50 flex items-center justify-center gap-2"
            >
              <div
                v-if="submitting"
                class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
              />
              {{ submitting ? 'Đang gửi...' : 'Gửi yêu cầu' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { signProfileApi } from '@/api/lecturer/signProfileApi'
import { useToastStore } from '@/stores/toast'

const props = defineProps({
  show: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'success'])

const toast = useToastStore()
const reason = ref('')
const error = ref('')
const submitting = ref(false)

watch(
  () => props.show,
  (v) => {
    if (v) {
      reason.value = ''
      error.value = ''
    }
  },
)

async function handleSubmit() {
  if (reason.value.length < 10) {
    error.value = 'Lý do phải có ít nhất 10 ký tự'
    return
  }

  submitting.value = true
  try {
    const { data } = await signProfileApi.requestDeactivation(reason.value)
    toast.success(data.message ?? 'Đã gửi yêu cầu')
    emit('success')
    emit('close')
  } catch (e) {
    const msg = e.response?.data?.message ?? 'Gửi yêu cầu thất bại'
    error.value = msg
    toast.error(msg)
  } finally {
    submitting.value = false
  }
}
</script>
