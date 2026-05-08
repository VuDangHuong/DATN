<!-- src/components/lecturer/review/ReviewModal.vue -->
<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="$emit('close')" />
      <div
        class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col"
      >
        <!-- Header -->
        <div class="p-6 border-b border-slate-100 flex-shrink-0">
          <h3 class="text-lg font-bold text-slate-800">
            {{ form.status === 'approved' ? '✅ Chấp nhận bài nộp' : '❌ Từ chối bài nộp' }}
          </h3>
          <p class="text-sm text-slate-500 mt-0.5">{{ submission?.submitter_name }}</p>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-6 space-y-5">
          <!-- ── Chấm điểm NHÓM — từng thành viên ── -->
          <div v-if="isGroup">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">
              Điểm từng thành viên
            </p>

            <div v-if="loadingGrades" class="flex items-center gap-2 text-xs text-slate-400 py-2">
              <div
                class="w-3.5 h-3.5 border-2 border-slate-300 border-t-slate-600 rounded-full animate-spin"
              />
              Đang tải danh sách thành viên...
            </div>

            <div v-else class="space-y-3">
              <div
                v-for="(member, idx) in form.member_grades"
                :key="member.student_id"
                class="bg-slate-50 rounded-xl p-3 flex items-center gap-3"
              >
                <!-- Avatar + tên -->
                <div
                  class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-bold text-indigo-700 flex-shrink-0"
                >
                  {{ member.student_name?.charAt(0) }}
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-1.5">
                    <p class="text-sm font-medium text-slate-800 truncate">
                      {{ member.student_name }}
                    </p>
                    <span
                      v-if="member.role === 'leader'"
                      class="px-1.5 py-0.5 bg-amber-100 text-amber-700 text-[9px] font-bold rounded"
                    >
                      Trưởng nhóm
                    </span>
                  </div>
                  <p class="text-xs text-slate-400 font-mono">{{ member.student_code }}</p>
                </div>
                <!-- Điểm -->
                <div class="flex items-center gap-2 flex-shrink-0">
                  <input
                    v-model.number="form.member_grades[idx].score"
                    type="number"
                    min="0"
                    max="10"
                    step="0.5"
                    placeholder="Điểm"
                    class="w-20 px-2 py-1.5 border border-slate-200 rounded-lg text-sm text-center focus:ring-2 focus:ring-indigo-500 outline-none"
                  />
                  <span class="text-xs text-slate-400">/10</span>
                </div>
              </div>

              <!-- Ghi chú chung cho nhóm -->
              <div class="mt-2">
                <label class="block text-xs font-medium text-slate-600 mb-1">
                  Nhận xét chung cho nhóm
                </label>
                <textarea
                  v-model="form.feedback"
                  rows="3"
                  placeholder="Nhận xét về bài nộp của nhóm..."
                  class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm resize-none focus:ring-2 focus:ring-indigo-500 outline-none"
                />
              </div>

              <!-- Ghi chú riêng từng thành viên (toggle) -->
              <details class="mt-1">
                <summary
                  class="text-xs text-slate-500 cursor-pointer hover:text-slate-700 select-none"
                >
                  Thêm ghi chú riêng cho từng thành viên...
                </summary>
                <div class="mt-3 space-y-2">
                  <div
                    v-for="(member, idx) in form.member_grades"
                    :key="`note-${member.student_id}`"
                  >
                    <label class="block text-xs text-slate-500 mb-1">{{
                      member.student_name
                    }}</label>
                    <input
                      v-model="form.member_grades[idx].note"
                      type="text"
                      placeholder="Ghi chú riêng..."
                      class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-indigo-500 outline-none"
                    />
                  </div>
                </div>
              </details>
            </div>
          </div>

          <!-- ── Chấm điểm CÁ NHÂN ── -->
          <div v-else class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-600 mb-1">
                Điểm số <span class="text-slate-400">(0–10, tuỳ chọn)</span>
              </label>
              <input
                v-model.number="form.score"
                type="number"
                min="0"
                max="10"
                step="0.5"
                placeholder="VD: 8.5"
                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-600 mb-1">
                Nhận xét <span class="text-slate-400">(tuỳ chọn)</span>
              </label>
              <textarea
                v-model="form.feedback"
                rows="4"
                placeholder="Nhận xét chi tiết..."
                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm resize-none focus:ring-2 focus:ring-indigo-500 outline-none"
              />
            </div>
          </div>

          <!-- Notification note -->
          <div
            class="flex items-center gap-2 text-xs text-slate-400 bg-slate-50 rounded-xl px-3 py-2"
          >
            <svg
              class="w-4 h-4 flex-shrink-0"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
              />
            </svg>
            Sinh viên sẽ nhận email thông báo kết quả
          </div>
        </div>

        <!-- Footer -->
        <div class="p-6 border-t border-slate-100 flex gap-3 flex-shrink-0">
          <button
            @click="$emit('close')"
            class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-50"
          >
            Hủy
          </button>
          <button
            @click="handleSubmit"
            :disabled="reviewing || loadingGrades"
            class="flex-1 py-2.5 rounded-xl text-sm font-semibold text-white disabled:opacity-50 flex items-center justify-center gap-2 transition"
            :class="
              form.status === 'approved'
                ? 'bg-emerald-600 hover:bg-emerald-700'
                : 'bg-red-600 hover:bg-red-700'
            "
          >
            <div
              v-if="reviewing"
              class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
            />
            {{
              reviewing
                ? 'Đang xử lý...'
                : form.status === 'approved'
                  ? 'Xác nhận chấp nhận'
                  : 'Xác nhận từ chối'
            }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import axiosClient from '@/api/axiosClient'
import { useToastStore } from '@/stores/toast'

const props = defineProps({
  show: { type: Boolean, required: true },
  submission: { type: Object, default: null },
  status: { type: String, default: 'approved' }, // 'approved' | 'rejected'
})

const emit = defineEmits(['close', 'saved'])

const toast = useToastStore()
const reviewing = ref(false)
const loadingGrades = ref(false)

const isGroup = computed(() => props.submission?.submitter_type === 'group')

const form = ref({
  status: 'approved',
  score: null,
  feedback: '',
  member_grades: [],
})

// Khi mở modal → reset form + load member grades nếu là nhóm
watch(
  () => props.show,
  async (val) => {
    if (!val || !props.submission) return

    form.value = {
      status: props.status,
      score: props.submission.score ?? null,
      feedback: props.submission.feedback ?? '',
      member_grades: [],
    }

    if (isGroup.value) {
      await loadMemberGrades()
    }
  },
)

async function loadMemberGrades() {
  loadingGrades.value = true
  try {
    const { data } = await axiosClient.get(
      `/lecturer/submissions/${props.submission.id}/member-grades`,
    )
    // Pre-fill điểm cũ nếu đã chấm trước đó
    form.value.member_grades = data.grades.map((g) => ({
      student_id: g.student_id,
      student_name: g.student_name,
      student_code: g.student_code,
      role: g.role,
      score: g.score ?? null,
      note: g.note ?? '',
    }))
  } catch {
    toast.error('Không thể tải danh sách thành viên')
  } finally {
    loadingGrades.value = false
  }
}

async function handleSubmit() {
  reviewing.value = true
  try {
    const payload = {
      status: form.value.status,
      feedback: form.value.feedback || null,
    }

    if (isGroup.value) {
      // Chỉ gửi thành viên có điểm
      payload.member_grades = form.value.member_grades.map((m) => ({
        student_id: m.student_id,
        score: m.score ?? null,
        note: m.note || null,
      }))
    } else {
      payload.score = form.value.score ?? null
    }

    const { data } = await axiosClient.patch(
      `/lecturer/submissions/${props.submission.id}/review`,
      payload,
    )

    toast.success(form.value.status === 'approved' ? 'Đã chấp nhận bài nộp' : 'Đã từ chối bài nộp')
    emit('saved', data.submission)
    emit('close')
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Có lỗi xảy ra')
  } finally {
    reviewing.value = false
  }
}
</script>
