<template>
  <div class="bg-white rounded-2xl border border-stone-200 p-6">
    <h3 class="text-lg font-bold text-stone-800 mb-1">
      {{ store.hasProfile ? 'Cập nhật chữ ký số' : 'Đăng ký chữ ký số' }}
    </h3>
    <p class="text-sm text-stone-500 mb-5">
      {{
        store.hasProfile
          ? 'Chữ ký cũ sẽ tự động vô hiệu hóa khi đăng ký mới.'
          : 'Cung cấp thông tin chứng thư số do tổ chức CA cấp.'
      }}
    </p>

    <!-- Toggle mode -->
    <div class="flex gap-1 bg-stone-100 rounded-xl p-1 mb-5">
      <button
        v-for="m in modes"
        :key="m.value"
        @click="form.mode = m.value"
        class="flex-1 py-2 rounded-lg text-sm font-medium transition"
        :class="
          form.mode === m.value
            ? 'bg-white text-stone-800 shadow-sm'
            : 'text-stone-500 hover:text-stone-700'
        "
      >
        <span class="mr-1.5">{{ m.icon }}</span>
        {{ m.label }}
      </button>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-4">
      <!-- ── Mode: Upload file ── -->
      <div v-if="form.mode === 'upload'">
        <label class="block text-sm font-medium text-stone-700 mb-1">
          File chứng thư <span class="text-red-500">*</span>
        </label>
        <div
          class="border-2 border-dashed rounded-xl p-6 text-center transition"
          :class="
            form.certificate_file
              ? 'border-emerald-300 bg-emerald-50'
              : 'border-stone-200 hover:border-teal-300'
          "
        >
          <input
            ref="fileInput"
            type="file"
            accept=".cer,.crt,.pem,.key,.txt"
            @change="onFileSelected"
            class="hidden"
          />

          <div
            v-if="!form.certificate_file"
            @click="$refs.fileInput.click()"
            class="cursor-pointer"
          >
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
            <p class="text-sm font-medium text-stone-700">Click để chọn file chứng thư</p>
            <p class="text-xs text-stone-400 mt-1">Định dạng: .cer, .crt, .pem (tối đa 5MB)</p>
          </div>

          <div v-else class="flex items-center gap-3 text-left">
            <div
              class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0"
            >
              <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                <path
                  fill-rule="evenodd"
                  d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-stone-800 truncate">
                {{ form.certificate_file.name }}
              </p>
              <p class="text-xs text-stone-400">{{ formatBytes(form.certificate_file.size) }}</p>
            </div>
            <button
              type="button"
              @click="clearFile"
              class="p-1.5 hover:bg-red-50 rounded-lg text-stone-400 hover:text-red-500"
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
        <p v-if="errors.certificate_file" class="text-xs text-red-500 mt-1">
          {{ errors.certificate_file[0] }}
        </p>
      </div>

      <!-- ── Mode: Nhập tay (paste public key) ── -->
      <div v-else>
        <label class="block text-sm font-medium text-stone-700 mb-1">
          Public Key (PEM format) <span class="text-red-500">*</span>
        </label>
        <textarea
          v-model="form.public_key"
          rows="6"
          placeholder="-----BEGIN PUBLIC KEY-----&#10;MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA...&#10;-----END PUBLIC KEY-----"
          class="w-full px-3 py-2 border border-stone-200 rounded-xl text-xs font-mono resize-none focus:ring-2 focus:ring-teal-500 outline-none"
        />
        <p v-if="errors.public_key" class="text-xs text-red-500 mt-1">{{ errors.public_key[0] }}</p>
        <p class="text-xs text-stone-400 mt-1">
          Dán toàn bộ nội dung public key bao gồm cả dòng BEGIN/END.
        </p>
      </div>

      <!-- ── Serial chứng thư ── -->
      <div>
        <label class="block text-sm font-medium text-stone-700 mb-1">
          Serial chứng thư <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.certificate_serial"
          type="text"
          placeholder="VD: 54000A3F2B91D7C8E5F1234567890ABCDEF"
          class="w-full px-4 py-2.5 border border-stone-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-teal-500 outline-none"
        />
        <p v-if="errors.certificate_serial" class="text-xs text-red-500 mt-1">
          {{ errors.certificate_serial[0] }}
        </p>
      </div>

      <!-- ── Ngày hết hạn ── -->
      <div>
        <label class="block text-sm font-medium text-stone-700 mb-1">
          Ngày hết hạn <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.cert_expires_at"
          type="date"
          :min="tomorrow"
          class="w-full px-4 py-2.5 border border-stone-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 outline-none"
        />
        <p v-if="errors.cert_expires_at" class="text-xs text-red-500 mt-1">
          {{ errors.cert_expires_at[0] }}
        </p>
      </div>

      <!-- ── Nhà cung cấp + loại ── -->
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-sm font-medium text-stone-700 mb-1">Nhà cung cấp</label>
          <select
            v-model="form.provider"
            class="w-full px-3 py-2.5 border border-stone-200 rounded-xl text-sm bg-white focus:ring-2 focus:ring-teal-500 outline-none"
          >
            <option value="">— Chọn —</option>
            <option v-for="p in store.categories.providers" :key="p.value" :value="p.value">
              {{ p.label }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-stone-700 mb-1">Loại chứng thư</label>
          <select
            v-model="form.cert_type"
            class="w-full px-3 py-2.5 border border-stone-200 rounded-xl text-sm bg-white focus:ring-2 focus:ring-teal-500 outline-none"
          >
            <option value="">— Chọn —</option>
            <option v-for="t in store.categories.cert_types" :key="t.value" :value="t.value">
              {{ t.label }}
            </option>
          </select>
        </div>
      </div>

      <!-- ── Xác nhận mật khẩu ── -->
      <div class="pt-2 border-t border-stone-100">
        <label class="block text-sm font-medium text-stone-700 mb-1">
          Nhập lại mật khẩu để xác nhận <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.current_password"
          type="password"
          placeholder="••••••••"
          class="w-full px-4 py-2.5 border border-stone-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 outline-none"
          :class="errors.current_password ? 'border-red-300' : ''"
        />
        <p v-if="errors.current_password" class="text-xs text-red-500 mt-1">
          {{ errors.current_password[0] }}
        </p>
        <p class="text-xs text-stone-400 mt-1">
          Để đảm bảo bảo mật, vui lòng nhập lại mật khẩu đăng nhập của bạn.
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
          :disabled="!canSubmit || store.submitting"
          class="flex-1 py-2.5 bg-teal-600 text-white rounded-xl text-sm font-semibold hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition"
        >
          <div
            v-if="store.submitting"
            class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
          />
          {{
            store.submitting
              ? 'Đang đăng ký...'
              : store.hasProfile
                ? 'Cập nhật chữ ký'
                : 'Đăng ký chữ ký'
          }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useSignProfileStore } from '@/stores/lecturer/signProfileStore'
import { useToastStore } from '@/stores/toast'

const emit = defineEmits(['cancel', 'success'])

const store = useSignProfileStore()
const toast = useToastStore()

const modes = [
  { value: 'upload', label: 'Upload file chứng thư', icon: '📂' },
  { value: 'manual', label: 'Nhập public key', icon: '✏️' },
]

const form = ref({
  mode: 'upload',
  certificate_file: null,
  public_key: '',
  certificate_serial: '',
  cert_expires_at: '',
  provider: '',
  cert_type: 'personal',
  current_password: '',
})

const errors = ref({})

const tomorrow = computed(() => {
  const d = new Date()
  d.setDate(d.getDate() + 1)
  return d.toISOString().split('T')[0]
})

const canSubmit = computed(() => {
  if (!form.value.certificate_serial.trim()) return false
  if (!form.value.cert_expires_at) return false
  if (!form.value.current_password) return false
  if (form.value.mode === 'upload' && !form.value.certificate_file) return false
  if (form.value.mode === 'manual' && !form.value.public_key.trim()) return false
  return true
})

function onFileSelected(e) {
  const file = e.target.files?.[0]
  if (!file) return
  if (file.size > 5 * 1024 * 1024) {
    toast.error('File vượt quá 5MB')
    return
  }
  form.value.certificate_file = file
  errors.value.certificate_file = null
}

function clearFile() {
  form.value.certificate_file = null
}

async function handleSubmit() {
  errors.value = {}

  // Build FormData (cần cho file upload)
  const fd = new FormData()
  fd.append('mode', form.value.mode)
  fd.append('current_password', form.value.current_password)
  fd.append('certificate_serial', form.value.certificate_serial.trim())
  fd.append('cert_expires_at', form.value.cert_expires_at)
  if (form.value.provider) fd.append('provider', form.value.provider)
  if (form.value.cert_type) fd.append('cert_type', form.value.cert_type)

  if (form.value.mode === 'upload') {
    fd.append('certificate_file', form.value.certificate_file)
  } else {
    fd.append('public_key', form.value.public_key.trim())
  }

  const result = await store.register(fd)

  if (result.success) {
    emit('success')
  } else {
    errors.value = result.errors ?? {}
    toast.error(result.message)
  }
}

function formatBytes(bytes) {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / 1024 / 1024).toFixed(1) + ' MB'
}
</script>
