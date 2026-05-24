<!-- src/components/lecturer/class/MaxMembersPerGroupModal.vue -->
<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="$emit('close')" />
      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-start gap-3 mb-4">
          <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center">
            <span class="text-xl">👥</span>
          </div>
          <div>
            <h3 class="text-lg font-bold text-stone-800">Định mức thành viên mỗi nhóm</h3>
            <p class="text-base text-stone-500 mt-1">Áp dụng cho tất cả nhóm trong lớp này</p>
          </div>
        </div>

        <div class="mb-4">
          <label class="flex items-center gap-2 cursor-pointer mb-3">
            <input
              type="checkbox"
              v-model="noLimit"
              class="rounded text-indigo-600 focus:ring-indigo-500"
            />
            <span class="text-sm text-stone-700">Không giới hạn (default 5)</span>
          </label>

          <div v-if="!noLimit">
            <label class="block text-sm font-medium text-stone-700 mb-1">
              Số thành viên tối đa <span class="text-red-500">*</span>
            </label>
            <input
              v-model.number="maxPerGroup"
              type="number"
              min="1"
              max="100"
              placeholder="5"
              class="w-full px-3 py-2 border border-stone-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
            />
            <p class="text-base text-stone-400 mt-1">1-100 thành viên/nhóm</p>
          </div>
        </div>

        <div v-if="warning" class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-xl">
          <p class="text-base text-amber-800">⚠️ {{ warning }}</p>
        </div>

        <div class="flex gap-3">
          <button
            @click="$emit('close')"
            class="flex-1 py-2.5 border border-stone-200 rounded-xl text-sm font-medium text-stone-600 hover:bg-stone-50"
          >
            Hủy
          </button>
          <button
            @click="handleSubmit"
            :disabled="submitting"
            class="flex-1 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50"
          >
            {{ submitting ? 'Đang lưu...' : 'Cập nhật' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useToastStore } from '@/stores/toast'
import { useLecturerClassStore } from '@/stores/lecturer/classStore'

const props = defineProps({
  show: { type: Boolean, default: false },
  classId: { type: Number, required: true },
  current: { type: [Number, null], default: 5 },
})

const emit = defineEmits(['close', 'updated'])

const classStore = useLecturerClassStore()
const toast = useToastStore()

const maxPerGroup = ref(5)
const noLimit = ref(false)
const submitting = ref(false)
const warning = ref('')

watch(
  () => props.show,
  (v) => {
    if (v) {
      maxPerGroup.value = props.current ?? 5
      noLimit.value = props.current === null
      warning.value = ''
    }
  },
)

async function handleSubmit() {
  if (!noLimit.value) {
    if (!maxPerGroup.value || maxPerGroup.value < 1 || maxPerGroup.value > 100) {
      toast.error('Số thành viên phải từ 1-100')
      return
    }
  }

  submitting.value = true
  const value = noLimit.value ? null : maxPerGroup.value
  const result = await classStore.updateMaxMembersPerGroup(props.classId, value)
  submitting.value = false

  if (result.success) {
    toast.success(result.message)
    if (result.warning) {
      warning.value = result.warning
      toast.warning(result.warning)
    }
    emit('updated', value)
    if (!result.warning) emit('close')
  } else {
    toast.error(result.message)
  }
}
</script>
