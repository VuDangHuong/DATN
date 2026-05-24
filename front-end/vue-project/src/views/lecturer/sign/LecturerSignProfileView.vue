<!-- src/views/lecturer/sign/LecturerSignProfileView.vue -->
<template>
  <div class="max-w-full mx-auto">
    <!-- Header -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-stone-800">Chữ ký số của tôi</h2>
      <p class="text-base text-stone-500 mt-1">
        Đăng ký và quản lý chữ ký số cá nhân để ký xác nhận tài liệu.
      </p>
    </div>

    <!-- Loading -->
    <div v-if="store.loading" class="flex justify-center py-12">
      <div class="w-8 h-8 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin" />
    </div>

    <template v-else>
      <!--Banner: đang chờ admin duyệt vô hiệu hóa -->
      <div
        v-if="store.pendingRequest"
        class="mb-6 bg-amber-50 border-2 border-amber-300 rounded-2xl p-5"
      >
        <div class="flex items-start gap-3">
          <div
            class="w-10 h-10 rounded-xl bg-amber-200 flex items-center justify-center flex-shrink-0"
          >
            <svg
              class="w-5 h-5 text-amber-700"
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
          <div class="flex-1 min-w-0">
            <h4 class="text-base font-bold text-amber-900">
              Yêu cầu vô hiệu hóa đang chờ Admin duyệt
            </h4>
            <p class="text-base text-amber-800 mt-2">
              <strong>Lý do bạn đã gửi:</strong> {{ store.pendingRequest.reason }}
            </p>
            <p class="text-base text-amber-700 mt-2">
              ⚠️ Bạn không thể ký tài liệu trong thời gian chờ duyệt.<br />
              Gửi lúc {{ formatDateTime(store.pendingRequest.created_at) }}.
            </p>
          </div>
        </div>
      </div>

      <!-- ── Card chữ ký hiện tại ── -->
      <div v-if="store.hasProfile" class="mb-6">
        <div class="bg-white rounded-2xl border-2 p-6" :class="cardBorderClass">
          <div class="flex items-start justify-between gap-4 mb-4">
            <div class="flex items-center gap-3">
              <!-- Icon trạng thái -->
              <div
                class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                :class="iconBgClass"
              >
                <SvgICon
                  v-if="store.isValid && !store.isPendingDeactivation"
                  name="shield-check"
                  class="w-6 h-6 text-emerald-600"
                />

                <SvgICon
                  v-else
                  name="warning"
                  class="w-6 h-6"
                  :class="store.isPendingDeactivation ? 'text-amber-600' : 'text-red-600'"
                />
              </div>
              <div>
                <div class="flex items-center gap-2 mb-1">
                  <h3 class="text-lg font-bold text-stone-800">Chữ ký số</h3>
                  <span
                    class="px-2 py-0.5 text-[12px] font-bold rounded-full"
                    :class="statusBadgeClass"
                  >
                    {{ statusLabel }}
                  </span>
                </div>
                <p class="text-base text-stone-500">
                  Đăng ký lúc {{ formatDate(store.profile.created_at) }}
                </p>
              </div>
            </div>

            <!-- Nút Yêu cầu vô hiệu — chỉ hiện khi chưa có pending -->
            <button
              v-if="!store.isPendingDeactivation"
              @click="showDeactivateModal = true"
              class="px-4 py-2 border border-red-200 text-red-600 rounded-lg text-base font-medium hover:bg-red-50 transition"
            >
              Yêu cầu vô hiệu hóa
            </button>
            <span v-else class="text-base text-amber-600 font-medium"> ⏳ Đang chờ duyệt </span>
          </div>

          <!-- Cảnh báo sắp hết hạn / đã hết hạn -->
          <div v-if="store.isExpired" class="mb-4 p-3 bg-red-50 rounded-xl border border-red-200">
            <p class="text-base text-red-700">
              ⚠️ Chữ ký số đã hết hạn ngày
              <strong>{{ formatDate(store.profile.cert_expires_at) }}</strong
              >. Vui lòng đăng ký chữ ký mới để tiếp tục ký tài liệu.
            </p>
          </div>
          <div
            v-else-if="store.isExpiringSoon"
            class="mb-4 p-3 bg-amber-50 rounded-xl border border-amber-200"
          >
            <p class="text-base text-amber-700">
              ⏳ Chữ ký số sẽ hết hạn sau <strong>{{ store.daysUntilExpire }} ngày</strong> ({{
                formatDate(store.profile.cert_expires_at)
              }}). Bạn nên cập nhật chữ ký mới sớm.
            </p>
          </div>

          <!-- Info -->
          <div class="grid grid-cols-2 gap-3 text-base">
            <div class="p-3 bg-stone-50 rounded-xl">
              <p class="text-base text-stone-400 mb-1">Serial chứng thư</p>
              <p class="font-mono font-medium text-stone-700 break-all">
                {{ store.profile.certificate_serial || store.profile.serial_number }}
              </p>
            </div>
            <div class="p-3 bg-stone-50 rounded-xl">
              <p class="text-base text-stone-400 mb-1">Ngày hết hạn</p>
              <p class="font-medium" :class="store.isExpired ? 'text-red-600' : 'text-stone-700'">
                {{ formatDate(store.profile.cert_expires_at || store.profile.cert_valid_to) }}
              </p>
            </div>
            <div v-if="store.profile.subject_cn" class="p-3 bg-stone-50 rounded-xl">
              <p class="text-base text-stone-400 mb-1">Chủ sở hữu</p>
              <p class="font-medium text-stone-700 truncate">{{ store.profile.subject_cn }}</p>
            </div>
            <div v-if="store.profile.issuer_cn" class="p-3 bg-stone-50 rounded-xl">
              <p class="text-base text-stone-400 mb-1">Cấp bởi</p>
              <p class="font-medium text-stone-700 truncate">{{ store.profile.issuer_cn }}</p>
            </div>
          </div>

          <!-- Public key preview -->
          <div v-if="store.profile.public_key_preview" class="mt-3 p-3 bg-stone-50 rounded-xl">
            <p class="text-base text-stone-400 mb-1">Public key (rút gọn)</p>
            <p class="font-mono text-[11px] text-stone-600 break-all">
              {{ store.profile.public_key_preview }}
            </p>
          </div>
        </div>

        <!-- Nút cập nhật -->
        <button
          @click="showForm = !showForm"
          class="mt-4 w-full py-3 border-2 border-dashed border-teal-300 text-teal-700 rounded-xl text-base font-medium hover:bg-teal-50 transition flex items-center justify-center gap-2"
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
        <p class="text-base text-stone-500 mb-4">
          Đăng ký chữ ký số cá nhân để có thể ký xác nhận tài liệu cho sinh viên.
        </p>
        <button
          @click="showForm = true"
          class="px-5 py-2.5 bg-teal-600 text-white rounded-xl text-base font-semibold hover:bg-teal-700 transition"
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

      <!-- ── Lịch sử chữ ký ── -->
      <div v-if="store.history.length > 1" class="mt-8">
        <h3 class="text-base font-semibold text-stone-700 mb-3">Lịch sử chữ ký</h3>
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
                <SvgICon name="clock" class="w-4 h-4" />
              </div>
              <div class="min-w-0">
                <p class="text-base font-mono font-medium text-stone-700 truncate">
                  {{ p.certificate_serial || p.serial_number }}
                </p>
                <p class="text-base text-stone-400">
                  {{ formatDate(p.created_at) }} · Đã hết hạn / Đã vô hiệu hóa
                </p>
              </div>
            </div>
            <span
              class="px-2 py-0.5 bg-stone-100 text-stone-500 text-[12px] font-bold rounded-full flex-shrink-0"
            >
              Cũ
            </span>
          </div>
        </div>
      </div>

      <!-- ── Lịch sử yêu cầu vô hiệu hóa ── -->
      <div v-if="store.deactivationRequests.length" class="mt-8">
        <h3 class="text-base font-semibold text-stone-700 mb-3">Lịch sử yêu cầu vô hiệu hóa</h3>
        <div class="space-y-2">
          <div
            v-for="req in store.deactivationRequests"
            :key="req.id"
            class="bg-white rounded-xl border border-stone-200 p-3"
          >
            <div class="flex items-start justify-between gap-3 mb-1">
              <p class="text-base text-stone-500">Gửi lúc {{ formatDateTime(req.created_at) }}</p>
              <span
                class="px-2 py-0.5 rounded-full text-[12px] font-bold uppercase flex-shrink-0"
                :class="reqStatusClass(req.status)"
              >
                {{ reqStatusLabel(req.status) }}
              </span>
            </div>
            <p class="text-base text-stone-700 mb-1">
              <span class="text-base text-stone-500">Lý do:</span> {{ req.reason }}
            </p>
            <p v-if="req.admin_note" class="text-base text-red-600 mt-1">
              <span class="font-semibold">Admin từ chối:</span> {{ req.admin_note }}
            </p>
            <p v-if="req.resolved_at" class="text-[12px] text-stone-400 mt-1">
              Xử lý lúc {{ formatDateTime(req.resolved_at) }}
              <span v-if="req.admin">· bởi {{ req.admin.name }}</span>
            </p>
          </div>
        </div>
      </div>
    </template>

    <!-- Modal yêu cầu vô hiệu hóa (nhập lý do, không phải password) -->
    <Teleport to="body">
      <div
        v-if="showDeactivateModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
      >
        <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="closeDeactivateModal" />
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
          <div class="flex items-start gap-3 mb-4">
            <div
              class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0"
            >
              <svg
                class="w-5 h-5 text-red-600"
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
            </div>
            <div>
              <h3 class="text-lg font-bold text-stone-800">Yêu cầu vô hiệu hóa</h3>
              <p class="text-base text-stone-500 mt-1">Yêu cầu sẽ được gửi cho Admin duyệt</p>
            </div>
          </div>

          <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl mb-4">
            <p class="text-base text-amber-800">
              ⚠️ <strong>Lưu ý:</strong> Trong thời gian chờ duyệt, bạn sẽ
              <strong>không thể ký tài liệu</strong>. Nếu Admin từ chối, chữ ký sẽ tiếp tục hoạt
              động bình thường.
            </p>
          </div>

          <div class="mb-4">
            <label class="block text-base font-medium text-stone-700 mb-1">
              Lý do vô hiệu hóa <span class="text-red-500">*</span>
            </label>
            <textarea
              v-model="deactivateReason"
              rows="4"
              maxlength="1000"
              placeholder="Vui lòng nêu rõ lý do (vd: chứng chỉ bị lộ, đổi sang chứng chỉ mới...)"
              class="w-full px-3 py-2 border border-stone-200 rounded-xl text-base resize-none focus:ring-2 focus:ring-red-500 outline-none"
            />
            <div class="flex justify-between mt-1">
              <p class="text-base text-stone-400">Tối thiểu 10 ký tự</p>
              <p class="text-base text-stone-400">{{ deactivateReason.length }}/1000</p>
            </div>
          </div>

          <div class="flex gap-3">
            <button
              @click="closeDeactivateModal"
              class="flex-1 py-2.5 border border-stone-200 rounded-xl text-base font-medium text-stone-600 hover:bg-stone-50"
            >
              Hủy
            </button>
            <button
              @click="handleDeactivate"
              :disabled="deactivateReason.length < 10 || store.submitting"
              class="flex-1 py-2.5 bg-red-600 text-white rounded-xl text-base font-semibold hover:bg-red-700 disabled:opacity-50 flex items-center justify-center gap-2"
            >
              <div
                v-if="store.submitting"
                class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
              />
              {{ store.submitting ? 'Đang gửi...' : 'Gửi yêu cầu' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useSignProfileStore } from '@/stores/lecturer/signProfileStore'
import { useToastStore } from '@/stores/toast'
import SignProfileRegisterForm from '../components/sign/SignProfileRegisterForm.vue'
import SvgICon from '@/components/icons/SVG.vue'

const store = useSignProfileStore()
const toast = useToastStore()

const showForm = ref(false)
const showDeactivateModal = ref(false)
const deactivateReason = ref('')

// ── Computed cho status display ──────────────
const cardBorderClass = computed(() => {
  if (store.isPendingDeactivation) return 'border-amber-300'
  if (!store.isValid) return 'border-red-200'
  return 'border-emerald-200'
})

const iconBgClass = computed(() => {
  if (store.isPendingDeactivation) return 'bg-amber-100'
  if (!store.isValid) return 'bg-red-100'
  return 'bg-emerald-100'
})

const statusLabel = computed(() => {
  if (store.isPendingDeactivation) return 'Chờ duyệt vô hiệu'
  if (store.isExpired) return 'Đã hết hạn'
  if (store.isExpiringSoon) return 'Sắp hết hạn'
  if (store.isValid) return 'Hợp lệ'
  return 'Không hoạt động'
})

const statusBadgeClass = computed(() => {
  if (store.isPendingDeactivation) return 'bg-amber-100 text-amber-700'
  if (store.isExpired) return 'bg-red-100 text-red-700'
  if (store.isExpiringSoon) return 'bg-amber-100 text-amber-700'
  if (store.isValid) return 'bg-emerald-100 text-emerald-700'
  return 'bg-stone-100 text-stone-600'
})

// ── Lifecycle ────────────────────────────────
onMounted(async () => {
  await Promise.all([
    store.fetchProfile(),
    store.fetchHistory(),
    store.fetchPendingRequest(),
    store.fetchDeactivationRequests(), //Load lịch sử yêu cầu
  ])
})

// ── Handlers ─────────────────────────────────
function onRegisterSuccess() {
  showForm.value = false
  toast.success('Đăng ký chữ ký số thành công')
}

function closeDeactivateModal() {
  showDeactivateModal.value = false
  deactivateReason.value = ''
}

//Gửi yêu cầu vô hiệu hóa (không phải deactivate trực tiếp)
async function handleDeactivate() {
  if (deactivateReason.value.length < 10) return

  const result = await store.requestDeactivation(deactivateReason.value)
  if (result.success) {
    toast.success(result.message ?? 'Đã gửi yêu cầu, đang chờ Admin duyệt')
    closeDeactivateModal()
    // Reload để lấy lịch sử mới
    await store.fetchDeactivationRequests()
  } else {
    toast.error(result.message ?? 'Có lỗi xảy ra')
  }
}

// ── Formatters ───────────────────────────────
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

function formatDateTime(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function reqStatusClass(s) {
  return (
    {
      pending: 'bg-amber-100 text-amber-700',
      approved: 'bg-emerald-100 text-emerald-700',
      rejected: 'bg-red-100 text-red-700',
    }[s] || 'bg-stone-100 text-stone-600'
  )
}

function reqStatusLabel(s) {
  return (
    {
      pending: 'Chờ duyệt',
      approved: 'Đã chấp thuận',
      rejected: 'Bị từ chối',
    }[s] || s
  )
}
</script>
