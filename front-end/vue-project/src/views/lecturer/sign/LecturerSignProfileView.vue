<!-- src/views/lecturer/sign/LecturerSignProfileView.vue -->
<template>
  <div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-stone-800">Chữ ký số của tôi</h2>
      <p class="text-sm text-stone-500 mt-1">
        Đăng ký và quản lý chữ ký số cá nhân để ký xác nhận tài liệu.
      </p>
    </div>

    <!-- Loading -->
    <div v-if="store.loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin" />
    </div>

    <template v-else>
      <!-- ── Card chữ ký hiện tại ── -->
      <div v-if="store.hasProfile" class="mb-6">
        <div
          class="bg-white rounded-2xl border-2 p-6"
          :class="store.isValid ? 'border-emerald-200' : 'border-red-200'"
        >
          <div class="flex items-start justify-between gap-4 mb-4">
            <div class="flex items-center gap-3">
              <!-- Icon trạng thái -->
              <div
                class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                :class="store.isValid ? 'bg-emerald-100' : 'bg-red-100'"
              >
                <svg
                  v-if="store.isValid"
                  class="w-6 h-6 text-emerald-600"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd"
                  />
                </svg>
                <svg
                  v-else
                  class="w-6 h-6 text-red-600"
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
              </div>
              <div>
                <div class="flex items-center gap-2 mb-1">
                  <h3 class="text-lg font-bold text-stone-800">Chữ ký số đang hoạt động</h3>
                  <span
                    class="px-2 py-0.5 text-[10px] font-bold rounded-full"
                    :class="
                      store.isValid
                        ? 'bg-emerald-100 text-emerald-700'
                        : store.isExpired
                          ? 'bg-red-100 text-red-700'
                          : 'bg-amber-100 text-amber-700'
                    "
                  >
                    {{ store.isValid ? 'Hợp lệ' : store.isExpired ? 'Đã hết hạn' : 'Sắp hết hạn' }}
                  </span>
                </div>
                <p class="text-sm text-stone-500">
                  Đăng ký lúc {{ formatDate(store.profile.created_at) }}
                </p>
              </div>
            </div>

            <button
              @click="confirmDeactivate"
              class="px-3 py-1.5 border border-red-200 text-red-600 rounded-lg text-xs font-medium hover:bg-red-50 transition"
            >
              Vô hiệu hóa
            </button>
          </div>

          <!-- Cảnh báo sắp hết hạn / đã hết hạn -->
          <div v-if="store.isExpired" class="mb-4 p-3 bg-red-50 rounded-xl border border-red-200">
            <p class="text-sm text-red-700">
              ⚠️ Chữ ký số đã hết hạn ngày
              <strong>{{ formatDate(store.profile.cert_expires_at) }}</strong
              >. Vui lòng đăng ký chữ ký mới để tiếp tục ký tài liệu.
            </p>
          </div>
          <div
            v-else-if="store.isExpiringSoon"
            class="mb-4 p-3 bg-amber-50 rounded-xl border border-amber-200"
          >
            <p class="text-sm text-amber-700">
              ⏳ Chữ ký số sẽ hết hạn sau <strong>{{ store.daysUntilExpire }} ngày</strong> ({{
                formatDate(store.profile.cert_expires_at)
              }}). Bạn nên cập nhật chữ ký mới sớm.
            </p>
          </div>

          <!-- Info -->
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div class="p-3 bg-stone-50 rounded-xl">
              <p class="text-xs text-stone-400 mb-1">Serial chứng thư</p>
              <p class="font-mono font-medium text-stone-700 break-all">
                {{ store.profile.certificate_serial }}
              </p>
            </div>
            <div class="p-3 bg-stone-50 rounded-xl">
              <p class="text-xs text-stone-400 mb-1">Ngày hết hạn</p>
              <p class="font-medium" :class="store.isExpired ? 'text-red-600' : 'text-stone-700'">
                {{ formatDate(store.profile.cert_expires_at) }}
              </p>
            </div>
            <div v-if="store.profile.certificate_meta?.provider" class="p-3 bg-stone-50 rounded-xl">
              <p class="text-xs text-stone-400 mb-1">Nhà cung cấp</p>
              <p class="font-medium text-stone-700">
                {{ providerLabel(store.profile.certificate_meta.provider) }}
              </p>
            </div>
            <div
              v-if="store.profile.certificate_meta?.cert_type"
              class="p-3 bg-stone-50 rounded-xl"
            >
              <p class="text-xs text-stone-400 mb-1">Loại chứng thư</p>
              <p class="font-medium text-stone-700">
                {{
                  store.profile.certificate_meta.cert_type === 'personal' ? 'Cá nhân' : 'Tổ chức'
                }}
              </p>
            </div>
          </div>

          <!-- Public key preview -->
          <div class="mt-3 p-3 bg-stone-50 rounded-xl">
            <p class="text-xs text-stone-400 mb-1">Public key (rút gọn)</p>
            <p class="font-mono text-[11px] text-stone-600 break-all">
              {{ store.profile.public_key_preview }}
            </p>
          </div>
        </div>

        <!-- Nút cập nhật -->
        <button
          @click="showForm = !showForm"
          class="mt-4 w-full py-3 border-2 border-dashed border-teal-300 text-teal-700 rounded-xl text-sm font-medium hover:bg-teal-50 transition flex items-center justify-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
            />
          </svg>
          {{ showForm ? 'Hủy cập nhật' : 'Cập nhật chữ ký số mới' }}
        </button>
      </div>

      <!-- ── Empty state ── -->
      <div v-else class="bg-white rounded-2xl border border-stone-200 p-8 text-center mb-6">
        <div
          class="w-16 h-16 rounded-2xl bg-stone-100 mx-auto mb-4 flex items-center justify-center"
        >
          <svg class="w-8 h-8 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
            />
          </svg>
        </div>
        <h3 class="text-lg font-bold text-stone-800 mb-1">Chưa đăng ký chữ ký số</h3>
        <p class="text-sm text-stone-500 mb-4">
          Đăng ký chữ ký số cá nhân để có thể ký xác nhận tài liệu cho sinh viên.
        </p>
        <button
          @click="showForm = true"
          class="px-5 py-2.5 bg-teal-600 text-white rounded-xl text-sm font-semibold hover:bg-teal-700 transition"
        >
          + Đăng ký chữ ký số
        </button>
      </div>

      <!-- ── Form đăng ký / cập nhật ── -->
      <SignProfileRegisterForm
        v-if="showForm"
        @cancel="showForm = false"
        @success="onRegisterSuccess"
      />

      <!-- ── Lịch sử ── -->
      <div v-if="store.history.length > 1" class="mt-8">
        <h3 class="text-sm font-semibold text-stone-700 mb-3">Lịch sử chữ ký</h3>
        <div class="space-y-2">
          <div
            v-for="p in store.history.filter((p) => !p.is_active)"
            :key="p.id"
            class="bg-white rounded-xl border border-stone-200 p-3 flex items-center justify-between"
          >
            <div class="flex items-center gap-3 min-w-0">
              <div
                class="w-8 h-8 rounded-lg bg-stone-100 flex items-center justify-center flex-shrink-0"
              >
                <svg
                  class="w-4 h-4 text-stone-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
              </div>
              <div class="min-w-0">
                <p class="text-sm font-mono font-medium text-stone-700 truncate">
                  {{ p.certificate_serial }}
                </p>
                <p class="text-xs text-stone-400">
                  {{ formatDate(p.created_at) }} · Đã hết hạn / Đã vô hiệu hóa
                </p>
              </div>
            </div>
            <span
              class="px-2 py-0.5 bg-stone-100 text-stone-500 text-[10px] font-bold rounded-full flex-shrink-0"
            >
              Cũ
            </span>
          </div>
        </div>
      </div>
    </template>

    <!-- Modal xác nhận deactivate -->
    <Teleport to="body">
      <div
        v-if="showDeactivateModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
      >
        <div
          class="absolute inset-0 bg-black/30 backdrop-blur-sm"
          @click="showDeactivateModal = false"
        />
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
          <h3 class="text-lg font-bold text-stone-800 mb-1">Vô hiệu hóa chữ ký số</h3>
          <p class="text-sm text-stone-500 mb-4">
            Bạn sẽ không thể ký tài liệu cho đến khi đăng ký chữ ký mới.
          </p>
          <div class="mb-4">
            <label class="block text-sm font-medium text-stone-700 mb-1"
              >Nhập mật khẩu để xác nhận</label
            >
            <input
              v-model="deactivatePassword"
              type="password"
              placeholder="••••••••"
              class="w-full px-4 py-2.5 border border-stone-200 rounded-xl text-sm focus:ring-2 focus:ring-red-500 outline-none"
            />
          </div>
          <div class="flex gap-3">
            <button
              @click="showDeactivateModal = false"
              class="flex-1 py-2.5 border border-stone-200 rounded-xl text-sm font-medium text-stone-600 hover:bg-stone-50"
            >
              Hủy
            </button>
            <button
              @click="handleDeactivate"
              :disabled="!deactivatePassword || store.submitting"
              class="flex-1 py-2.5 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 disabled:opacity-50 flex items-center justify-center gap-2"
            >
              <div
                v-if="store.submitting"
                class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
              />
              Vô hiệu hóa
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useSignProfileStore } from '@/stores/lecturer/signProfileStore'
import { useToastStore } from '@/stores/toast'
import SignProfileRegisterForm from '../components/sign/SignProfileRegisterForm.vue'

const store = useSignProfileStore()
const toast = useToastStore()

const showForm = ref(false)
const showDeactivateModal = ref(false)
const deactivatePassword = ref('')

onMounted(async () => {
  await Promise.all([store.fetchProfile(), store.fetchHistory(), store.fetchCategories()])
})

function onRegisterSuccess() {
  showForm.value = false
  toast.success('Đăng ký chữ ký số thành công')
}

function confirmDeactivate() {
  deactivatePassword.value = ''
  showDeactivateModal.value = true
}

async function handleDeactivate() {
  const result = await store.deactivate(deactivatePassword.value)
  if (result.success) {
    toast.success('Đã vô hiệu hóa chữ ký số')
    showDeactivateModal.value = false
    deactivatePassword.value = ''
  } else {
    toast.error(result.message)
  }
}

function providerLabel(value) {
  return store.categories.providers.find((p) => p.value === value)?.label ?? value
}

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}
</script>
