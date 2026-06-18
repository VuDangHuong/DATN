<!-- src/components/common/ConfirmModal.vue
     Modal xác nhận hành động - hỗ trợ nhiều variant (danger / warning / info / success) -->
<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="modelValue" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="handleCancel" />

        <!-- Modal -->
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div
            v-if="modelValue"
            class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-auto overflow-hidden"
          >
            <!-- Icon header -->
            <div class="p-6 text-center">
              <div
                class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4"
                :class="iconBgClass"
              >
                <!-- Danger icon -->
                <svg
                  v-if="variant === 'danger'"
                  class="w-8 h-8"
                  :class="iconColorClass"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                  />
                </svg>

                <!-- Warning icon -->
                <svg
                  v-else-if="variant === 'warning'"
                  class="w-8 h-8"
                  :class="iconColorClass"
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

                <!-- Success icon -->
                <svg
                  v-else-if="variant === 'success'"
                  class="w-8 h-8"
                  :class="iconColorClass"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>

                <!-- Info icon (default) -->
                <svg
                  v-else
                  class="w-8 h-8"
                  :class="iconColorClass"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
              </div>

              <!-- Title -->
              <h3 class="text-lg font-bold text-stone-800 mb-2">{{ title }}</h3>

              <!-- Message -->
              <p class="text-sm text-stone-600 leading-relaxed">
                <slot>{{ message }}</slot>
              </p>

              <!-- Highlight name (nếu có) -->
              <p
                v-if="itemName"
                class="mt-3 text-sm font-semibold text-stone-800 bg-stone-50 px-3 py-2 rounded-lg break-words"
              >
                "{{ itemName }}"
              </p>

              <!-- Warning text bổ sung -->
              <p v-if="warningText" class="mt-3 text-base text-red-600 font-medium">
                ⚠️ {{ warningText }}
              </p>
            </div>

            <!-- Optional: input confirmation (gõ đúng tên để xóa) -->
            <div v-if="requireTypeConfirm" class="px-6 pb-4">
              <p class="text-base text-stone-500 mb-2">
                Gõ <strong class="text-red-600">{{ requireTypeConfirm }}</strong> để xác nhận:
              </p>
              <input
                v-model="typeConfirmInput"
                type="text"
                :placeholder="requireTypeConfirm"
                class="w-full px-3 py-2 border border-stone-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-red-500"
                @keyup.enter="canConfirm && handleConfirm()"
              />
            </div>

            <!-- Buttons -->
            <div class="flex gap-3 px-6 py-4 bg-stone-50 border-t border-stone-100">
              <button
                @click="handleCancel"
                :disabled="loading"
                class="flex-1 px-4 py-2.5 border border-stone-200 hover:bg-stone-100 rounded-xl text-sm font-medium text-stone-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ cancelText }}
              </button>
              <button
                @click="handleConfirm"
                :disabled="loading || !canConfirm"
                class="flex-1 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                :class="confirmButtonClass"
              >
                <div
                  v-if="loading"
                  class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
                />
                {{ confirmText }}
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  // v-model
  modelValue: { type: Boolean, default: false },

  // Content
  title: { type: String, default: 'Xác nhận' },
  message: { type: String, default: 'Bạn có chắc chắn muốn thực hiện hành động này?' },
  itemName: { type: String, default: '' }, // Tên item được làm nổi bật
  warningText: { type: String, default: '' }, // Cảnh báo phụ ở dưới message

  // Buttons
  confirmText: { type: String, default: 'Xác nhận' },
  cancelText: { type: String, default: 'Hủy' },

  // Variant: 'danger' | 'warning' | 'info' | 'success'
  variant: { type: String, default: 'danger' },

  // Loading state (khi đang call API)
  loading: { type: Boolean, default: false },

  // Yêu cầu gõ đúng text mới được confirm (vd: gõ tên lớp để xóa)
  requireTypeConfirm: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue', 'confirm', 'cancel'])

const typeConfirmInput = ref('')

// Reset input khi mở lại
watch(
  () => props.modelValue,
  (val) => {
    if (val) typeConfirmInput.value = ''
  },
)

// ─── Validation ──
const canConfirm = computed(() => {
  if (!props.requireTypeConfirm) return true
  return typeConfirmInput.value === props.requireTypeConfirm
})

// ─── Styling theo variant ──
const iconBgClass = computed(
  () =>
    ({
      danger: 'bg-red-100',
      warning: 'bg-amber-100',
      success: 'bg-emerald-100',
      info: 'bg-blue-100',
    })[props.variant] ?? 'bg-stone-100',
)

const iconColorClass = computed(
  () =>
    ({
      danger: 'text-red-600',
      warning: 'text-amber-600',
      success: 'text-emerald-600',
      info: 'text-blue-600',
    })[props.variant] ?? 'text-stone-600',
)

const confirmButtonClass = computed(
  () =>
    ({
      danger: 'bg-red-600 hover:bg-red-700',
      warning: 'bg-amber-500 hover:bg-amber-600',
      success: 'bg-emerald-600 hover:bg-emerald-700',
      info: 'bg-blue-600 hover:bg-blue-700',
    })[props.variant] ?? 'bg-stone-700 hover:bg-stone-800',
)

// ─── Actions ──
function handleConfirm() {
  if (!canConfirm.value || props.loading) return
  emit('confirm')
}

function handleCancel() {
  if (props.loading) return
  emit('update:modelValue', false)
  emit('cancel')
}
</script>
