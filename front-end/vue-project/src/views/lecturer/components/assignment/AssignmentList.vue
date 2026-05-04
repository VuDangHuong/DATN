<!-- src/components/lecturer/assignment/AssignmentList.vue -->
<template>
  <div class="space-y-4">
    <div v-if="store.loading" class="flex justify-center py-20">
      <div
        class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"
      />
    </div>

    <div v-else-if="!store.assignments.length" class="bg-white rounded-2xl border p-12 text-center">
      <p class="text-slate-400">Chưa có đợt nộp bài nào</p>
    </div>

    <div
      v-for="a in store.assignments"
      :key="a.id"
      class="bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md transition cursor-pointer group"
      @click="$emit('select', a)"
    >
      <div class="flex items-start justify-between gap-4">
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-1.5 flex-wrap">
            <h3 class="font-semibold text-slate-800 group-hover:text-indigo-700 transition">
              {{ a.title }}
            </h3>
            <span
              v-if="!a.is_active"
              class="px-2 py-0.5 bg-slate-100 text-slate-500 text-xs font-bold rounded-full"
              >Đã đóng</span
            >
            <span
              v-else-if="a.is_expired"
              class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-bold rounded-full"
              >Hết hạn</span
            >
            <span
              v-else
              class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs font-bold rounded-full"
              >Đang mở</span
            >
            <!-- Badge ký số -->
            <span
              v-if="a.document_category_label"
              class="px-2 py-0.5 bg-violet-100 text-violet-700 text-xs font-bold rounded-full flex items-center gap-1"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                />
              </svg>
              {{ a.document_category_label }}
            </span>
          </div>
          <p class="text-xs text-slate-500 mb-3">
            Hạn: {{ formatDate(a.deadline) }} · {{ submissionTypeLabel(a.submission_type) }}
          </p>
          <div class="flex items-center gap-4 text-xs">
            <div class="flex items-center gap-1.5">
              <div class="w-2 h-2 rounded-full bg-emerald-500" />
              <span class="text-slate-600"
                >{{ (a.group_count || 0) + (a.individual_count || 0) }} đã nộp</span
              >
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div
          class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition"
          @click.stop
        >
          <button
            @click.stop="$emit('edit', a)"
            class="p-1.5 rounded-lg hover:bg-amber-50 text-slate-400 hover:text-amber-600 transition"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
              />
            </svg>
          </button>
          <button
            @click.stop="$emit('delete', a.id)"
            class="p-1.5 rounded-lg hover:bg-red-50 text-slate-400 hover:text-red-500 transition"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
              />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useLecturerAssignmentStore } from '@/stores/lecturer/lecturerAssignmentStore'

const store = useLecturerAssignmentStore()

defineEmits(['select', 'edit', 'delete'])

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function submissionTypeLabel(t) {
  return { both: 'Nhóm + Cá nhân', group: 'Theo nhóm', individual: 'Cá nhân' }[t] || t
}
</script>
