<!-- src/components/lecturer/sign/SignProfileRegisterForm.vue -->
<template>
  <div class="bg-white rounded-2xl border border-stone-200 p-6">
    <div class="flex items-center justify-between mb-1">
      <h3 class="text-lg font-bold text-stone-800">
        {{ store.hasProfile ? 'CẬP NHẬT CHỮ KÝ SỐ' : 'ĐĂNG KÝ CHỮ KÝ SỐ' }}
      </h3>
      <button
        @click="showGenerateTest = true"
        class="text-sm px-3 py-1.5 border border-teal-200 text-teal-700 rounded-lg hover:bg-teal-50 font-medium transition"
      >
        Tạo chữ ký test
      </button>
    </div>
    <p class="text-sm text-stone-500 mb-5">Upload file .crt và .key được cấp bởi CA.</p>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <!-- ── Step 1: Upload .crt ────────────────── -->
      <div>
        <label class="block text-sm font-medium text-stone-700 mb-1">
          File chứng thư (.crt / .pem) <span class="text-red-500">*</span>
        </label>
        <div
          class="border-2 border-dashed rounded-xl p-4 transition"
          :class="
            form.certificate_file
              ? 'border-emerald-300 bg-emerald-50'
              : 'border-stone-200 hover:border-teal-300'
          "
        >
          <input
            ref="certInput"
            type="file"
            accept=".cer,.crt,.pem"
            @change="onCertSelected"
            class="hidden"
          />

          <div
            v-if="!form.certificate_file"
            @click="$refs.certInput.click()"
            class="cursor-pointer text-center py-2"
          >
            <svg
              class="w-8 h-8 mx-auto text-stone-300 mb-1"
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
            <p class="text-sm font-medium text-stone-700">Click chọn file chứng thư</p>
          </div>

          <div v-else class="flex items-center gap-3">
            <div
              class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0"
            >
              <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                <path
                  fill-rule="evenodd"
                  d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-stone-800 truncate">
                {{ form.certificate_file.name }}
              </p>
              <p class="text-base text-stone-400">{{ formatBytes(form.certificate_file.size) }}</p>
            </div>
            <button
              type="button"
              @click="clearCert"
              class="p-1 hover:bg-red-50 rounded text-stone-400 hover:text-red-500"
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
        <p v-if="errors.certificate_file" class="text-base text-red-500 mt-1">
          {{ errors.certificate_file[0] }}
        </p>
      </div>

      <!-- ── Preview cert info ────────────────── -->
      <div v-if="certPreview" class="p-4 bg-blue-50 border border-blue-200 rounded-xl">
        <div class="flex items-center gap-2 mb-3">
          <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
            <path
              fill-rule="evenodd"
              d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001z"
              clip-rule="evenodd"
            />
          </svg>
          <span class="text-sm font-semibold text-blue-800">Thông tin chứng thư</span>
        </div>
        <div class="grid grid-cols-2 gap-2 text-base">
          <div>
            <span class="text-stone-500">Chủ sở hữu:</span>
            <span class="font-medium text-stone-800">{{ certPreview.subject_cn }}</span>
          </div>
          <div>
            <span class="text-stone-500">Cấp bởi:</span>
            <span class="font-medium text-stone-800">{{ certPreview.issuer_cn }}</span>
          </div>
          <div>
            <span class="text-stone-500">Serial:</span>
            <span class="font-mono text-[11px] text-stone-700"
              >{{ certPreview.serial?.substring(0, 20) }}...</span
            >
          </div>
          <div>
            <span class="text-stone-500">Thuật toán:</span>
            <span class="font-medium text-stone-800">{{ certPreview.algorithm }}</span>
          </div>
          <div>
            <span class="text-stone-500">Hiệu lực từ:</span>
            <span class="font-medium text-stone-800">{{ formatDate(certPreview.valid_from) }}</span>
          </div>
          <div>
            <span class="text-stone-500">Hết hạn:</span>
            <span
              class="font-medium"
              :class="certPreview.is_expired ? 'text-red-600' : 'text-stone-800'"
            >
              {{ formatDate(certPreview.valid_to) }}
            </span>
          </div>
        </div>
        <div v-if="certPreview.is_expired" class="mt-2 text-base text-red-700 font-medium">
          ⚠️ Chứng thư đã hết hạn — không thể đăng ký
        </div>
      </div>

      <!-- ── Step 2: Upload .key ────────────────── -->
      <div>
        <label class="block text-sm font-medium text-stone-700 mb-1">
          File private key (.key / .pem) <span class="text-red-500">*</span>
        </label>
        <div
          class="border-2 border-dashed rounded-xl p-4 transition"
          :class="
            form.private_key_file
              ? 'border-amber-300 bg-amber-50'
              : 'border-stone-200 hover:border-teal-300'
          "
        >
          <input
            ref="keyInput"
            type="file"
            accept=".key,.pem,.txt"
            @change="onKeySelected"
            class="hidden"
          />

          <div
            v-if="!form.private_key_file"
            @click="$refs.keyInput.click()"
            class="cursor-pointer text-center py-2"
          >
            <svg
              class="w-8 h-8 mx-auto text-stone-300 mb-1"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
              />
            </svg>
            <p class="text-sm font-medium text-stone-700">Click chọn file private key</p>
          </div>

          <div v-else class="flex items-center gap-3">
            <div
              class="w-9 h-9 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0"
            >
              <svg class="w-4 h-4 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                <path
                  fill-rule="evenodd"
                  d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-stone-800 truncate">
                {{ form.private_key_file.name }}
              </p>
              <p class="text-base text-stone-400">
                {{ formatBytes(form.private_key_file.size) }} · 🔐 Sẽ được mã hóa
              </p>
            </div>
            <button
              type="button"
              @click="clearKey"
              class="p-1 hover:bg-red-50 rounded text-stone-400 hover:text-red-500"
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

      <!-- Cảnh báo bảo mật -->
      <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-2">
        <svg
          class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path
            fill-rule="evenodd"
            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
            clip-rule="evenodd"
          />
        </svg>
        <p class="text-base text-amber-800">
          Private key được mã hóa bằng AES-256 với mật khẩu ký số của bạn và chỉ được giải mã
          runtime khi ký tài liệu.
        </p>
      </div>

      <!-- ── Step 3: Signing password ────────────────── -->
      <div class="pt-2 border-t border-stone-100">
        <label class="block text-sm font-medium text-stone-700 mb-1">
          Mật khẩu ký số <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.signing_password"
          type="password"
          placeholder="Tối thiểu 8 ký tự"
          class="w-full px-4 py-2.5 border border-stone-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 outline-none"
        />
        <p v-if="errors.signing_password" class="text-base text-red-500 mt-1">
          {{ errors.signing_password[0] }}
        </p>

        <label class="block text-sm font-medium text-stone-700 mb-1 mt-3">
          Xác nhận mật khẩu ký số <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.signing_password_confirmation"
          type="password"
          placeholder="Nhập lại mật khẩu ký số"
          class="w-full px-4 py-2.5 border border-stone-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 outline-none"
        />
        <p class="text-base text-stone-400 mt-1">
          Mật khẩu này riêng với mật khẩu đăng nhập. Mỗi lần ký tài liệu bạn sẽ nhập mật khẩu này.
        </p>
      </div>

      <!-- ── Step 4: Account password ────────────────── -->
      <div>
        <label class="block text-sm font-medium text-stone-700 mb-1">
          Mật khẩu đăng nhập (xác nhận quyền) <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.account_password"
          type="password"
          placeholder="••••••••"
          class="w-full px-4 py-2.5 border border-stone-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 outline-none"
          :class="errors.account_password ? 'border-red-300' : ''"
        />
        <p v-if="errors.account_password" class="text-base text-red-500 mt-1">
          {{ errors.account_password[0] }}
        </p>
      </div>

      <!-- Buttons -->
      <div class="flex gap-3 pt-4">
        <button
          type="button"
          @click="$emit('cancel')"
          class="flex-1 py-2.5 border border-stone-200 rounded-xl text-sm font-medium text-stone-600 hover:bg-stone-50"
        >
          Hủy
        </button>
        <button
          type="submit"
          :disabled="!canSubmit || submitting"
          class="flex-1 py-2.5 bg-teal-600 text-white rounded-xl text-sm font-semibold hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition"
        >
          <div
            v-if="submitting"
            class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
          />
          {{ submitting ? 'Đang xử lý...' : 'Đăng ký chữ ký' }}
        </button>
      </div>
    </form>

    <!-- Modal tạo test key -->
    <GenerateTestKeyModal v-if="showGenerateTest" @close="showGenerateTest = false" />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useSignProfileStore } from '@/stores/lecturer/signProfileStore'
import { useToastStore } from '@/stores/toast'
import axiosClient from '@/api/axiosClient'
import GenerateTestKeyModal from './GenerateTestKeyModal.vue'

const emit = defineEmits(['cancel', 'success'])

const store = useSignProfileStore()
const toast = useToastStore()

const form = ref({
  certificate_file: null,
  private_key_file: null,
  signing_password: '',
  signing_password_confirmation: '',
  account_password: '',
})

const errors = ref({})
const certPreview = ref(null)
const submitting = ref(false)
const showGenerateTest = ref(false)

const canSubmit = computed(() => {
  return (
    form.value.certificate_file &&
    form.value.private_key_file &&
    form.value.signing_password.length >= 8 &&
    form.value.signing_password === form.value.signing_password_confirmation &&
    form.value.account_password &&
    !certPreview.value?.is_expired
  )
})

// Auto parse cert khi upload
watch(
  () => form.value.certificate_file,
  async (file) => {
    if (!file) {
      certPreview.value = null
      return
    }

    const fd = new FormData()
    fd.append('certificate_file', file)
    try {
      const { data } = await axiosClient.post('/lecturer/sign-profile/parse-cert', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      certPreview.value = data.data
    } catch (e) {
      toast.error(e.response?.data?.message ?? 'Không đọc được chứng thư')
      form.value.certificate_file = null
    }
  },
)

function onCertSelected(e) {
  const file = e.target.files?.[0]
  if (!file) return
  if (file.size > 5 * 1024 * 1024) {
    toast.error('File vượt quá 5MB')
    return
  }
  form.value.certificate_file = file
}

function onKeySelected(e) {
  const file = e.target.files?.[0]
  if (!file) return
  if (file.size > 5 * 1024 * 1024) {
    toast.error('File vượt quá 5MB')
    return
  }
  form.value.private_key_file = file
}

function clearCert() {
  form.value.certificate_file = null
  certPreview.value = null
}
function clearKey() {
  form.value.private_key_file = null
}

async function handleSubmit() {
  if (!canSubmit.value) return

  errors.value = {}
  submitting.value = true

  const fd = new FormData()
  fd.append('certificate_file', form.value.certificate_file)
  fd.append('private_key_file', form.value.private_key_file)
  fd.append('signing_password', form.value.signing_password)
  fd.append('signing_password_confirmation', form.value.signing_password_confirmation)
  fd.append('account_password', form.value.account_password)

  try {
    const { data } = await axiosClient.post('/lecturer/sign-profile', fd, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    toast.success(data.message ?? 'Đăng ký thành công')
    await store.fetchProfile()
    await store.fetchHistory()
    emit('success')
  } catch (e) {
    errors.value = e.response?.data?.errors ?? {}
    const code = e.response?.data?.error_code
    if (code === 'KEY_PAIR_MISMATCH') {
      toast.error('Private key không khớp với chứng thư')
    } else {
      toast.error(e.response?.data?.message ?? 'Đăng ký thất bại')
    }
  } finally {
    submitting.value = false
  }
}

function formatBytes(bytes) {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / 1024 / 1024).toFixed(1) + ' MB'
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('vi-VN')
}
</script>
