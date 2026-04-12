<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
    <!-- Overlay -->
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="$emit('close')" />

    <!-- Modal -->
    <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl p-6 mx-4">
      <!-- Header -->
      <div class="flex items-center justify-between mb-5">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Thêm sinh viên</h3>
          <p class="text-sm text-gray-500 mt-0.5">Nhập mã sinh viên có trong hệ thống</p>
        </div>
        <button
          @click="$emit('close')"
          class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleSubmit">
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Mã sinh viên <span class="text-red-500">*</span>
          </label>
          <input
            v-model="studentCode"
            type="text"
            placeholder="VD: 2251172367"
            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
            :class="{ 'border-red-400 bg-red-50': errorMsg }"
            autofocus
          />
          <p v-if="errorMsg" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
              <path
                fill-rule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                clip-rule="evenodd"
              />
            </svg>
            {{ errorMsg }}
          </p>
        </div>

        <!-- Info box -->
        <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 mb-5 text-xs text-blue-700">
          Sinh viên phải có tài khoản trong hệ thống với vai trò <strong>Student</strong>.
        </div>

        <!-- Actions -->
        <div class="flex gap-3">
          <button
            type="button"
            @click="$emit('close')"
            class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-50 transition"
          >
            Hủy
          </button>
          <button
            type="submit"
            :disabled="loading || !studentCode.trim()"
            class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center justify-center gap-2"
          >
            <svg v-if="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
            </svg>
            {{ loading ? 'Đang thêm...' : 'Thêm sinh viên' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'submit'])

const studentCode = ref('')
const errorMsg = ref('')

watch(
  () => props.show,
  (val) => {
    if (val) {
      studentCode.value = ''
      errorMsg.value = ''
    }
  },
)

function handleSubmit() {
  errorMsg.value = ''
  if (!studentCode.value.trim()) {
    errorMsg.value = 'Vui lòng nhập mã sinh viên'
    return
  }
  emit('submit', studentCode.value.trim())
}
</script>
