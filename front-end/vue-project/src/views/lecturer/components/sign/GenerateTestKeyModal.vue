<!-- src/components/lecturer/sign/GenerateTestKeyModal.vue -->
<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="$emit('close')" />
      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6">
        <div class="flex items-start justify-between mb-4">
          <div>
            <h3 class="text-lg font-bold text-stone-800 mb-1">TẠO CHỮ KÝ SỐ DEMO</h3>
            <p class="text-sm text-stone-500">Tạo cặp khóa RSA + chứng thư self-signed để test</p>
          </div>
          <button @click="$emit('close')" class="p-1 hover:bg-stone-100 rounded">
            <svg
              class="w-5 h-5 text-stone-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>

        <!-- Form trước khi generate -->
        <div v-if="!generated">
          <div
            class="p-3 bg-amber-50 border border-amber-200 rounded-xl mb-4 text-base text-amber-800"
          >
            ⚠️ Chữ ký test self-signed chỉ dùng cho mục đích demo, không có giá trị pháp lý.
          </div>

          <div class="space-y-3 mb-4">
            <div>
              <label class="block text-base font-medium text-stone-600 mb-1">Họ tên (CN)</label>
              <input
                v-model="form.cn"
                type="text"
                class="w-full px-3 py-2 border border-stone-200 rounded-lg text-sm"
              />
            </div>
            <div>
              <label class="block text-base font-medium text-stone-600 mb-1">Tổ chức (O)</label>
              <input
                v-model="form.o"
                type="text"
                class="w-full px-3 py-2 border border-stone-200 rounded-lg text-sm"
              />
            </div>
            <div>
              <label class="block text-base font-medium text-stone-600 mb-1"
                >Có hiệu lực (ngày)</label
              >
              <input
                v-model.number="form.valid_days"
                type="number"
                min="30"
                max="3650"
                class="w-full px-3 py-2 border border-stone-200 rounded-lg text-sm"
              />
            </div>
          </div>

          <button
            @click="handleGenerate"
            :disabled="generating"
            class="w-full py-2.5 bg-teal-600 text-white rounded-xl text-sm font-semibold hover:bg-teal-700 disabled:opacity-50 flex items-center justify-center gap-2"
          >
            <div
              v-if="generating"
              class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
            />
            {{ generating ? 'Đang tạo...' : 'Tạo cặp khóa' }}
          </button>
        </div>

        <!-- Sau khi generate xong -->
        <div v-else>
          <div
            class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl mb-4 text-sm text-emerald-800"
          >
            ✅ Đã tạo cặp khóa thành công. Tải về 2 file và upload lại ở form đăng ký.
          </div>

          <div class="space-y-2 mb-4">
            <div class="flex items-center justify-between p-3 border border-stone-200 rounded-xl">
              <div class="flex items-center gap-2">
                <span class="text-lg">📄</span>
                <div>
                  <p class="text-sm font-medium text-stone-800">test_certificate.crt</p>
                  <p class="text-[10px] text-stone-400">Chứng thư công khai</p>
                </div>
              </div>
              <button
                @click="downloadFile(generated.certificate, 'test_certificate.crt')"
                class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-base font-medium hover:bg-blue-700"
              >
                Tải về
              </button>
            </div>
            <div class="flex items-center justify-between p-3 border border-stone-200 rounded-xl">
              <div class="flex items-center gap-2">
                <span class="text-lg">🔐</span>
                <div>
                  <p class="text-sm font-medium text-stone-800">test_private.key</p>
                  <p class="text-[10px] text-stone-400">Private key — bảo mật cao</p>
                </div>
              </div>
              <button
                @click="downloadFile(generated.private_key, 'test_private.key')"
                class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-base font-medium hover:bg-blue-700"
              >
                Tải về
              </button>
            </div>
          </div>

          <div class="p-3 bg-stone-50 rounded-xl text-base text-stone-600 mb-4">
            <p class="font-semibold mb-1">Thông tin chữ ký:</p>
            <p>
              Serial: <span class="font-mono">{{ generated.serial?.substring(0, 30) }}...</span>
            </p>
            <p>Subject CN: {{ generated.subject_cn }}</p>
            <p>Có hiệu lực đến: {{ formatDate(generated.valid_to) }}</p>
          </div>

          <button
            @click="$emit('close')"
            class="w-full py-2.5 bg-stone-100 text-stone-700 rounded-xl text-sm font-medium hover:bg-stone-200"
          >
            Đóng
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue'
import axiosClient from '@/api/axiosClient'
import { useToastStore } from '@/stores/toast'
import { useAuthStore } from '@/stores/auth'

defineEmits(['close'])

const toast = useToastStore()
const authStore = useAuthStore()

const form = ref({
  cn: authStore.user?.name ?? '',
  o: 'EduGroup',
  valid_days: 365,
})

const generating = ref(false)
const generated = ref(null)

async function handleGenerate() {
  generating.value = true
  try {
    const { data } = await axiosClient.post('/lecturer/sign-profile/generate-test', form.value)
    generated.value = data.data
    toast.success('Đã tạo cặp khóa demo')
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Tạo khóa thất bại')
  } finally {
    generating.value = false
  }
}

function downloadFile(content, filename) {
  const blob = new Blob([content], { type: 'application/x-pem-file' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('vi-VN')
}
</script>
