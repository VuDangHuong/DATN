<!-- src/components/students/task/BulkCreateTaskModal.vue -->
<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="$emit('close')" />

      <div
        class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col"
      >
        <!-- Header -->
        <div
          class="px-6 py-4 border-b border-slate-100 flex items-center justify-between flex-shrink-0"
        >
          <div>
            <h3 class="text-lg font-bold text-slate-800">Tạo nhiều công việc</h3>
            <p class="text-base text-slate-400 mt-0.5">
              Đã thêm {{ tasks.length }} công việc — Tối đa 20
            </p>
          </div>
          <button @click="$emit('close')" class="p-1.5 hover:bg-slate-100 rounded-lg">
            <svg
              class="w-5 h-5 text-slate-400"
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

        <!-- Body — scroll được -->
        <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
          <!-- Mặc định chung -->
          <div class="p-3 bg-indigo-50 border border-indigo-200 rounded-xl">
            <p class="text-base font-semibold text-indigo-700 mb-2">
              Áp dụng cho tất cả (tùy chọn)
            </p>
            <div class="grid grid-cols-3 gap-2">
              <div>
                <label class="block text-[10px] text-indigo-600 mb-1">Deadline mặc định</label>
                <input
                  v-model="defaults.deadline"
                  type="datetime-local"
                  class="w-full px-2 py-1.5 border border-indigo-200 rounded-lg text-base"
                />
              </div>
              <div>
                <label class="block text-[10px] text-indigo-600 mb-1">Ưu tiên mặc định</label>
                <select
                  v-model="defaults.priority"
                  class="w-full px-2 py-1.5 border border-indigo-200 rounded-lg text-base"
                >
                  <option value="low">Thấp</option>
                  <option value="medium">Trung bình</option>
                  <option value="high">Cao</option>
                  <option value="urgent">Khẩn cấp</option>
                </select>
              </div>
              <div class="flex items-end">
                <button
                  @click="applyDefaults"
                  class="w-full py-1.5 bg-indigo-600 text-white rounded-lg text-base font-medium hover:bg-indigo-700 transition"
                >
                  Áp dụng
                </button>
              </div>
            </div>
          </div>

          <!-- Danh sách task -->
          <div
            v-for="(task, index) in tasks"
            :key="index"
            class="border border-slate-200 rounded-xl p-4 relative transition"
            :class="errors[index] ? 'border-red-300 bg-red-50' : 'bg-white'"
          >
            <!-- Header task -->
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <div
                  class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-base font-bold"
                >
                  {{ index + 1 }}
                </div>
                <span class="text-sm font-semibold text-slate-700">Công việc #{{ index + 1 }}</span>
              </div>
              <button
                v-if="tasks.length > 1"
                @click="removeTask(index)"
                class="text-slate-400 hover:text-red-500 transition"
              >
                <SvgIcon name="trash" />
              </button>
            </div>

            <!-- Form -->
            <div class="space-y-2">
              <!-- Tiêu đề + Giao cho -->
              <div class="grid grid-cols-3 gap-2">
                <div class="col-span-2">
                  <input
                    v-model="task.title"
                    type="text"
                    placeholder="Tiêu đề công việc *"
                    class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  />
                </div>
                <select
                  v-model="task.assignee_id"
                  class="w-full px-2 py-2 border border-slate-200 rounded-lg text-sm bg-white"
                >
                  <option value="">— Giao cho —</option>
                  <option v-for="m in members" :key="m.id" :value="m.id">
                    {{ m.name }}
                  </option>
                </select>
              </div>

              <!-- Mô tả -->
              <textarea
                v-model="task.description"
                rows="2"
                placeholder="Mô tả (tùy chọn)..."
                class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm resize-none focus:ring-2 focus:ring-indigo-500"
              />

              <!-- Deadline + Priority + Weight -->
              <div class="grid grid-cols-3 gap-2">
                <div>
                  <label class="block text-[10px] text-slate-500 mb-0.5">Deadline *</label>
                  <input
                    v-model="task.deadline"
                    type="datetime-local"
                    class="w-full px-2 py-1.5 border border-slate-200 rounded-lg text-base"
                  />
                </div>
                <div>
                  <label class="block text-[10px] text-slate-500 mb-0.5">Ưu tiên</label>
                  <select
                    v-model="task.priority"
                    class="w-full px-2 py-1.5 border border-slate-200 rounded-lg text-base bg-white"
                  >
                    <option value="low">Thấp</option>
                    <option value="medium">Trung bình</option>
                    <option value="high">Cao</option>
                    <option value="urgent">Khẩn cấp</option>
                  </select>
                </div>
                <div>
                  <label class="block text-[10px] text-slate-500 mb-0.5">Trọng số (1-10)</label>
                  <input
                    v-model.number="task.weight"
                    type="number"
                    min="1"
                    max="10"
                    class="w-full px-2 py-1.5 border border-slate-200 rounded-lg text-base"
                  />
                </div>
              </div>
            </div>

            <!-- Error message -->
            <p v-if="errors[index]" class="text-base text-red-600 mt-2">⚠️ {{ errors[index] }}</p>
          </div>

          <!-- Nút thêm task -->
          <button
            @click="addTask"
            :disabled="tasks.length >= 20"
            class="w-full py-3 border-2 border-dashed border-indigo-300 rounded-xl text-sm text-indigo-700 hover:bg-indigo-50 disabled:opacity-50 disabled:cursor-not-allowed transition font-medium flex items-center justify-center gap-2"
          >
            <SvgIcon name="plus" class="w-4 h-4" />
            Thêm công việc {{ tasks.length >= 20 ? '(đã đạt tối đa)' : '' }}
          </button>
        </div>

        <!-- Footer buttons -->
        <div class="px-6 py-4 border-t border-slate-100 flex gap-3 flex-shrink-0">
          <button
            @click="$emit('close')"
            class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-50"
          >
            Hủy
          </button>
          <button
            @click="handleSubmit"
            :disabled="!canSubmit || submitting"
            class="flex-1 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <div
              v-if="submitting"
              class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
            />
            {{ submitting ? 'Đang tạo...' : `Tạo ${tasks.length} công việc` }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useTaskStore } from '@/stores/students/taskStore'
import { useToastStore } from '@/stores/toast'
import SvgIcon from '@/components/icons/SVG.vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  groupId: { type: Number, required: true },
  members: { type: Array, default: () => [] },
})

const emit = defineEmits(['close', 'success'])

const taskStore = useTaskStore()
const toast = useToastStore()

const submitting = ref(false)
const errors = ref({})

const defaults = ref({
  deadline: '',
  priority: 'medium',
})

function makeEmptyTask() {
  return {
    title: '',
    description: '',
    assignee_id: '',
    deadline: defaults.value.deadline || '',
    priority: defaults.value.priority || 'medium',
    weight: 1,
  }
}

const tasks = ref([makeEmptyTask()])

// Reset khi đóng modal
watch(
  () => props.show,
  (val) => {
    if (val) {
      tasks.value = [makeEmptyTask()]
      errors.value = {}
      defaults.value = { deadline: '', priority: 'medium' }
    }
  },
)

const canSubmit = computed(() => {
  return tasks.value.length > 0 && tasks.value.every((t) => t.title.trim() && t.deadline)
})

function addTask() {
  if (tasks.value.length >= 20) return
  tasks.value.push(makeEmptyTask())
}

function removeTask(index) {
  tasks.value.splice(index, 1)
}

function applyDefaults() {
  let applied = 0
  tasks.value.forEach((t) => {
    if (defaults.value.deadline && !t.deadline) {
      t.deadline = defaults.value.deadline
      applied++
    }
    if (defaults.value.priority) {
      t.priority = defaults.value.priority
    }
  })
  toast.success(`Đã áp dụng cho ${tasks.value.length} công việc`)
}

async function handleSubmit() {
  if (!canSubmit.value) return

  // Validate basic
  errors.value = {}
  tasks.value.forEach((t, i) => {
    if (!t.title.trim()) errors.value[i] = 'Thiếu tiêu đề'
    else if (!t.deadline) errors.value[i] = 'Thiếu deadline'
  })
  if (Object.keys(errors.value).length > 0) {
    toast.error('Vui lòng điền đầy đủ thông tin các công việc được đánh dấu đỏ')
    return
  }

  // Build payload — clean empty fields
  const payload = tasks.value.map((t) => {
    const item = { ...t }
    if (!item.assignee_id) delete item.assignee_id
    if (!item.description) delete item.description
    return item
  })

  submitting.value = true
  const result = await taskStore.bulkCreateTasks(props.groupId, payload)
  submitting.value = false

  if (result.success) {
    toast.success(result.message || `Đã tạo ${result.total} công việc`)
    emit('success', result.data)
    emit('close')
  } else {
    toast.error(result.message)
  }
}
</script>
