<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="$emit('close')" />
      <div
        class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto"
      >
        <div class="flex items-center justify-between mb-5">
          <h3 class="text-lg font-bold text-gray-900">
            {{ editingClass ? 'Chỉnh sửa lớp' : 'Tạo lớp mới' }}
          </h3>
          <button @click="$emit('close')" class="p-1.5 hover:bg-gray-100 rounded-lg transition">
            <svg
              class="w-4 h-4 text-gray-400"
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

        <div class="space-y-4">
          <!-- Tên lớp -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Tên lớp *</label>
            <input
              v-model="form.name"
              type="text"
              class="input-field"
              placeholder="VD: 64KTPM3-NMLP"
            />
          </div>

          <!-- Mã lớp -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Mã lớp *</label>
            <input v-model="form.code" type="text" class="input-field" placeholder="VD: L01" />
          </div>

          <!-- Học kỳ -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Học kỳ</label>
            <select v-model="form.semester_id" class="input-field">
              <option value="">-- Chọn học kỳ --</option>
              <option v-for="s in semesters" :key="s.id" :value="s.id">
                {{ s.name }} - {{ s.year }}
              </option>
            </select>
          </div>

          <!-- Giảng viên -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Giảng viên</label>
            <select v-model="form.lecturer_id" class="input-field">
              <option value="">-- Chọn giảng viên --</option>
              <option v-for="l in lecturers" :key="l.id" :value="l.id">
                {{ l.name }}
              </option>
            </select>
          </div>
          <!-- Môn học -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Môn học *</label>
            <div v-if="!subjects?.length" class="text-xs text-gray-400">Không có môn học</div>
            <div v-else class="space-y-2">
              <div
                v-for="s in subjects"
                :key="s.id"
                class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl"
              >
                <input
                  type="checkbox"
                  :id="`sub-${s.id}`"
                  :value="s.id"
                  :checked="isSubjectSelected(s.id)"
                  @change="toggleSubject(s.id)"
                  class="rounded border-gray-300 text-blue-600"
                />
                <label :for="`sub-${s.id}`" class="flex-1 text-sm text-gray-700 cursor-pointer">
                  {{ s.name }} <span class="font-mono text-gray-400 text-xs">({{ s.code }})</span>
                </label>
                <!-- max_members khi chọn -->
                <input
                  v-if="isSubjectSelected(s.id)"
                  v-model.number="getSubjectDetail(s.id).max_members"
                  type="number"
                  min="1"
                  placeholder="Sĩ số"
                  class="w-20 px-2 py-1 border border-gray-200 rounded-lg text-xs text-center"
                />
              </div>
            </div>
          </div>
          <!-- Số SV tối đa -->
          <div>
            <label class="block text-sm font-medium text-gray-600 mb-1">Số SV tối đa</label>
            <input
              v-model.number="form.max_members"
              type="number"
              min="1"
              max="200"
              class="input-field"
            />
          </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-3 mt-6">
          <button
            @click="$emit('close')"
            class="flex-1 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition"
          >
            Hủy
          </button>
          <button
            @click="handleSubmit"
            :disabled="!form.name || !form.code || loading"
            class="flex-1 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700 disabled:opacity-50 transition flex items-center justify-center gap-2"
          >
            <div
              v-if="loading"
              class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
            />
            {{ loading ? 'Đang lưu...' : editingClass ? 'Cập nhật' : 'Tạo lớp' }}
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
  editingClass: { type: Object, default: null }, // null = tạo mới
  semesters: { type: Array, default: () => [] },
  lecturers: { type: Array, default: () => [] },
  subjects: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'submit'])

const form = ref({
  name: '',
  code: '',
  semester_id: '',
  lecturer_id: '',
  max_members: 50,
  subject_details: [],
})

// Sync form khi editingClass thay đổi
watch(
  () => props.editingClass,
  (cls) => {
    if (cls) {
      form.value = {
        id: cls.id,
        name: cls.name ?? '',
        code: cls.code ?? '',
        semester_id: cls.semester?.id ?? cls.semester_id ?? '',
        lecturer_id: cls.lecturer?.id ?? cls.lecturer_id ?? '',
        max_members: cls.subjects?.[0]?.pivot?.max_members ?? cls.max_members ?? 50,
        subject_details:
          cls.subjects?.map((s) => ({
            subject_id: s.id,
            max_members: s.pivot?.max_members ?? 50,
            max_groups: s.pivot?.max_groups ?? 10,
          })) ?? [], // ← thêm
      }
    }
  },
)
function isSubjectSelected(subjectId) {
  return form.value.subject_details.some((s) => s.subject_id === subjectId)
}

function getSubjectDetail(subjectId) {
  return form.value.subject_details.find((s) => s.subject_id === subjectId)
}

function toggleSubject(subjectId) {
  const idx = form.value.subject_details.findIndex((s) => s.subject_id === subjectId)
  if (idx === -1) {
    form.value.subject_details.push({ subject_id: subjectId, max_members: 50, max_groups: 10 })
  } else {
    form.value.subject_details.splice(idx, 1)
  }
}
function handleSubmit() {
  if (!form.value.name || !form.value.code) return
  emit('submit', { ...form.value })
}
</script>

<style scoped>
.input-field {
  @apply w-full px-4 py-2.5 border border-gray-200 rounded-xl text-sm
         focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none;
}
</style>
