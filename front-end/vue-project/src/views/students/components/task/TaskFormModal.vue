<!-- src/components/students/task/TaskFormModal.vue -->
<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="$emit('close')" />
      <div
        class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto"
      >
        <h3 class="text-lg font-bold text-slate-800 mb-5">
          {{ editingTask ? 'CHỈNH SỬA CÔNG VIỆC' : 'Tạo công việc mới' }}
        </h3>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Tiêu đề *</label>
            <input
              v-model="form.title"
              type="text"
              class="input-field"
              placeholder="Nhập tiêu đề..."
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-600 mb-1">Mô tả</label>
            <textarea
              v-model="form.description"
              rows="3"
              class="input-field"
              placeholder="Mô tả chi tiết..."
            />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-600 mb-1">Giao cho</label>
              <select v-model="form.assignee_id" class="input-field">
                <option value="">-- Chọn --</option>
                <option v-for="m in members" :key="m.id" :value="m.id">
                  {{ m.name }} ({{ m.code }})
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-600 mb-1">Ưu tiên</label>
              <select v-model="form.priority" class="input-field">
                <option value="low">Thấp</option>
                <option value="medium">Trung bình</option>
                <option value="high">Cao</option>
                <option value="urgent">Khẩn cấp</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-600 mb-1">Deadline *</label>
              <input v-model="form.deadline" type="datetime-local" class="input-field" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-600 mb-1">Trọng số (1-10)</label>
              <input
                v-model.number="form.weight"
                type="number"
                min="1"
                max="10"
                class="input-field"
              />
            </div>
          </div>
        </div>
        <div class="flex gap-3 mt-6">
          <button
            @click="$emit('close')"
            class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-50"
          >
            Hủy
          </button>
          <button
            @click="handleSave"
            :disabled="!form.title || !form.deadline || loading"
            class="flex-1 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50"
          >
            {{ loading ? 'Đang lưu...' : editingTask ? 'Cập nhật' : 'Tạo' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  editingTask: { type: Object, default: null },
  members: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'save'])

function emptyForm() {
  return {
    title: '',
    description: '',
    assignee_id: '',
    priority: 'medium',
    start_date: '',
    deadline: '',
    weight: 1,
  }
}

const form = ref(emptyForm())

// Reset form khi modal mở/đóng hoặc đổi task edit
watch(
  [() => props.show, () => props.editingTask],
  () => {
    if (!props.show) return
    if (props.editingTask) {
      const t = props.editingTask
      form.value = {
        title: t.title,
        description: t.description || '',
        assignee_id: t.assignee?.id || '',
        priority: t.priority,
        start_date: t.start_date ? t.start_date.slice(0, 16) : '',
        deadline: t.deadline ? t.deadline.slice(0, 16) : '',
        weight: t.weight || 1,
      }
    } else {
      form.value = emptyForm()
    }
  },
  { immediate: true },
)

function handleSave() {
  const data = { ...form.value }
  if (!data.assignee_id) delete data.assignee_id
  if (!data.start_date) delete data.start_date
  emit('save', data)
}
</script>

<style scoped>
.input-field {
  @apply w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent;
}
</style>
