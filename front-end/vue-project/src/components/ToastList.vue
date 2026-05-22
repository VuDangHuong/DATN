<!-- src/components/common/Toast.vue
     Toast container với:
     - z-index cao nhất (z-[9999]) - không bị che bởi modal, sidebar, header
     - Position prop linh hoạt (4 góc + center)
     - Mặc định: top-right đẩy xuống dưới header (top-20 thay vì top-5)
     - Teleport ra ngoài để không bị parent overflow:hidden cắt
-->
<template>
  <Teleport to="body">
    <div class="fixed z-[9999] flex flex-col gap-3 pointer-events-none" :class="positionClass">
      <TransitionGroup :name="transitionName">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="pointer-events-auto min-w-[300px] max-w-md p-4 rounded-lg shadow-2xl text-white flex items-center justify-between cursor-pointer"
          :class="[
            toast.type === 'success'
              ? 'bg-emerald-500 border-l-4 border-emerald-700'
              : toast.type === 'error'
                ? 'bg-red-500 border-l-4 border-red-700'
                : toast.type === 'warning'
                  ? 'bg-amber-500 border-l-4 border-amber-700'
                  : 'bg-blue-500 border-l-4 border-blue-700',
          ]"
          @click="toastStore.removeToast(toast.id)"
        >
          <div class="flex items-center">
            <!-- Icon Success -->
            <svg
              v-if="toast.type === 'success'"
              xmlns="http://www.w3.org/2000/svg"
              class="h-6 w-6 mr-2 flex-shrink-0"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>

            <!-- Icon Error -->
            <svg
              v-else-if="toast.type === 'error'"
              xmlns="http://www.w3.org/2000/svg"
              class="h-6 w-6 mr-2 flex-shrink-0"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>

            <!-- Icon Warning -->
            <svg
              v-else-if="toast.type === 'warning'"
              xmlns="http://www.w3.org/2000/svg"
              class="h-6 w-6 mr-2 flex-shrink-0"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
              />
            </svg>

            <!-- Icon Info (default) -->
            <svg
              v-else
              xmlns="http://www.w3.org/2000/svg"
              class="h-6 w-6 mr-2 flex-shrink-0"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>

            <span class="font-medium text-sm">{{ toast.message }}</span>
          </div>

          <button class="ml-4 opacity-70 hover:opacity-100 flex-shrink-0">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-4 w-4"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
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
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import { useToastStore } from '@/stores/toast'
import { storeToRefs } from 'pinia'

const props = defineProps({
  // 'top-right' | 'top-left' | 'top-center' | 'bottom-right' | 'bottom-left' | 'bottom-center'
  position: { type: String, default: 'top-right' },
})

const toastStore = useToastStore()
const { toasts } = storeToRefs(toastStore)

// ✅ Tính class theo vị trí
const positionClass = computed(() => {
  const map = {
    'top-right': 'top-20 right-5 items-end', // ⬅ Đẩy xuống dưới header (top-20 ~ 80px)
    'top-left': 'top-20 left-5 items-start',
    'top-center': 'top-20 left-1/2 -translate-x-1/2 items-center',
    'bottom-right': 'bottom-5 right-5 items-end',
    'bottom-left': 'bottom-5 left-5 items-start',
    'bottom-center': 'bottom-5 left-1/2 -translate-x-1/2 items-center',
  }
  return map[props.position] ?? map['top-right']
})

// ✅ Animation tương ứng với position
const transitionName = computed(() => {
  if (props.position.includes('left')) return 'toast-left'
  if (props.position.includes('center')) return 'toast-top'
  return 'toast-right'
})
</script>

<style scoped>
/* ── Trượt vào từ bên phải ── */
.toast-right-enter-from {
  opacity: 0;
  transform: translateX(100%);
}
.toast-right-enter-active,
.toast-right-leave-active {
  transition: all 0.3s ease;
}
.toast-right-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

/* ── Trượt vào từ bên trái ── */
.toast-left-enter-from {
  opacity: 0;
  transform: translateX(-100%);
}
.toast-left-enter-active,
.toast-left-leave-active {
  transition: all 0.3s ease;
}
.toast-left-leave-to {
  opacity: 0;
  transform: translateX(-100%);
}

/* ── Trượt vào từ trên ── */
.toast-top-enter-from {
  opacity: 0;
  transform: translateY(-30px);
}
.toast-top-enter-active,
.toast-top-leave-active {
  transition: all 0.3s ease;
}
.toast-top-leave-to {
  opacity: 0;
  transform: translateY(-30px);
}
</style>
