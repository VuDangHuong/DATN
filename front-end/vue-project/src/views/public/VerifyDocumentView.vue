<!-- src/views/public/VerifyDocumentView.vue -->
<template>
  <div class="min-h-screen bg-gradient-to-br from-teal-50 via-white to-blue-50 py-12 px-4">
    <div class="max-w-2xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-8">
        <div
          class="inline-flex w-16 h-16 rounded-2xl bg-teal-600 items-center justify-center mb-4 shadow-lg shadow-teal-200"
        >
          <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
            />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-stone-800">Xác thực tài liệu ký số</h1>
        <p class="text-sm text-stone-500 mt-2">EduGroup PKI Verification</p>
      </div>

      <!-- Form -->
      <div class="bg-white rounded-2xl shadow-sm border border-stone-200 p-6">
        <form @submit.prevent="handleVerify" class="space-y-4">
          <!-- File upload -->
          <div>
            <label class="block text-sm font-medium text-stone-700 mb-1">
              Tài liệu PDF cần xác thực <span class="text-red-500">*</span>
            </label>
            <div
              class="border-2 border-dashed rounded-xl p-6 text-center"
              :class="
                file ? 'border-emerald-300 bg-emerald-50' : 'border-stone-200 hover:border-teal-300'
              "
            >
              <input
                ref="fileInput"
                type="file"
                accept=".pdf"
                @change="onFileSelected"
                class="hidden"
              />

              <div v-if="!file" @click="$refs.fileInput.click()" class="cursor-pointer">
                <svg
                  class="w-10 h-10 mx-auto text-stone-300 mb-2"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                  />
                </svg>
                <p class="text-sm font-medium text-stone-700">Click chọn file PDF</p>
                <p class="text-base text-stone-400 mt-1">Tối đa 20MB</p>
              </div>

              <div v-else class="flex items-center gap-3 text-left">
                <span class="text-2xl">📄</span>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-stone-800 truncate">{{ file.name }}</p>
                  <p class="text-base text-stone-400">{{ formatBytes(file.size) }}</p>
                </div>
                <button
                  type="button"
                  @click="file = null"
                  class="text-stone-400 hover:text-red-500"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Serial -->
          <div>
            <label class="block text-sm font-medium text-stone-700 mb-1">
              Serial chứng thư <span class="text-red-500">*</span>
            </label>
            <input
              v-model="serial"
              type="text"
              placeholder="VD: A1B2C3D4E5F6..."
              class="w-full px-4 py-2.5 border border-stone-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-teal-500 outline-none"
            />
            <p class="text-base text-stone-400 mt-1">Lấy từ thông tin trên tài liệu đã ký</p>
          </div>

          <button
            type="submit"
            :disabled="!canSubmit || verifying"
            class="w-full py-3 bg-teal-600 text-white rounded-xl text-sm font-semibold hover:bg-teal-700 disabled:opacity-50 flex items-center justify-center gap-2"
          >
            <div
              v-if="verifying"
              class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
            />
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
              />
            </svg>
            {{ verifying ? 'Đang xác thực...' : 'Xác thực tài liệu' }}
          </button>
        </form>

        <!-- Kết quả -->
        <div v-if="result" class="mt-6">
          <!-- Hợp lệ -->
          <div
            v-if="result.verified"
            class="p-5 bg-emerald-50 border-2 border-emerald-300 rounded-xl"
          >
            <div class="flex items-center gap-3 mb-3">
              <svg class="w-8 h-8 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                <path
                  fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                  clip-rule="evenodd"
                />
              </svg>
              <div>
                <h3 class="text-lg font-bold text-emerald-800">Tài liệu hợp lệ</h3>
                <p class="text-base text-emerald-700">Chữ ký được xác thực, file chưa bị sửa đổi</p>
              </div>
            </div>

            <div class="space-y-2 text-sm pt-3 border-t border-emerald-200">
              <div class="flex justify-between">
                <span class="text-stone-500">Người ký:</span>
                <span class="font-semibold text-stone-800">{{ result.data.signer }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-stone-500">Email:</span>
                <span class="text-stone-700">{{ result.data.signer_email }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-stone-500">Yêu cầu từ:</span>
                <span class="font-semibold text-stone-800">
                  {{ result.data.requester }} ({{ result.data.requester_code }})
                </span>
              </div>
              <div class="flex justify-between">
                <span class="text-stone-500">Loại tài liệu:</span>
                <span class="text-stone-700">{{ result.data.category }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-stone-500">Ký lúc:</span>
                <span class="text-stone-700">{{ formatDate(result.data.signed_at) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-stone-500">Thuật toán:</span>
                <span class="font-mono text-base text-stone-700">{{ result.data.algorithm }}</span>
              </div>
              <div class="pt-2 border-t border-emerald-200">
                <p class="text-stone-500 mb-1">File hash (SHA-256):</p>
                <p class="font-mono text-[10px] text-stone-600 break-all">
                  {{ result.data.file_hash }}
                </p>
              </div>
            </div>
          </div>

          <!-- Không hợp lệ -->
          <div v-else class="p-5 bg-red-50 border-2 border-red-300 rounded-xl">
            <div class="flex items-center gap-3 mb-3">
              <svg class="w-8 h-8 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                <path
                  fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                  clip-rule="evenodd"
                />
              </svg>
              <div>
                <h3 class="text-lg font-bold text-red-800">Tài liệu không hợp lệ</h3>
                <p class="text-base text-red-700">{{ result.message }}</p>
              </div>
            </div>

            <div
              v-if="result.reason === 'HASH_MISMATCH'"
              class="text-base text-red-700 pt-3 border-t border-red-200"
            >
              <p class="font-semibold mb-1">File đã bị thay đổi sau khi ký.</p>
              <p class="font-mono text-[10px] mt-2">
                Hash mong đợi: {{ result.data?.expected_hash?.substring(0, 32) }}...
              </p>
              <p class="font-mono text-[10px]">
                Hash thực tế: {{ result.data?.actual_hash?.substring(0, 32) }}...
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer info -->
      <div class="text-center mt-6">
        <p class="text-base text-stone-400">
          Hệ thống dùng RSA-SHA256 + SHA-256 hash để xác thực · Không cần đăng nhập
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'

const file = ref(null)
const serial = ref('')
const verifying = ref(false)
const result = ref(null)

const canSubmit = computed(() => file.value && serial.value.trim().length > 0)

function onFileSelected(e) {
  const f = e.target.files?.[0]
  if (!f) return
  if (f.size > 20 * 1024 * 1024) {
    alert('File vượt quá 20MB')
    return
  }
  file.value = f
}

async function handleVerify() {
  if (!canSubmit.value) return
  verifying.value = true
  result.value = null

  const fd = new FormData()
  fd.append('file', file.value)
  fd.append('serial', serial.value.trim())

  try {
    // Public endpoint, không cần auth header
    const { data } = await axios.post(
      `${import.meta.env.VITE_API_URL ?? 'http://127.0.0.1:8000/api'}/public/verify/file`,
      fd,
      { headers: { 'Content-Type': 'multipart/form-data' } },
    )
    result.value = data
  } catch (e) {
    result.value = e.response?.data ?? {
      verified: false,
      message: 'Có lỗi xảy ra khi xác thực',
    }
  } finally {
    verifying.value = false
  }
}

function formatBytes(b) {
  if (b < 1024) return b + ' B'
  if (b < 1048576) return (b / 1024).toFixed(1) + ' KB'
  return (b / 1048576).toFixed(1) + ' MB'
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('vi-VN')
}
</script>
