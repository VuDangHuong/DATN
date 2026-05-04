<!-- src/components/lecturer/assignment/AssignmentDetail.vue -->
<template>
  <div>
    <!-- Back button -->
    <button
      @click="$emit('back')"
      class="flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700 mb-5 transition"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
      Quay lại
    </button>

    <!-- Assignment info + stats -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-5">
      <div class="flex items-start justify-between">
        <div>
          <h3 class="text-lg font-bold text-slate-800">{{ store.currentAssignment?.title }}</h3>
          <p class="text-sm text-slate-500 mt-1">
            Hạn: {{ formatDate(store.currentAssignment?.deadline) }}
          </p>
        </div>
        <div class="flex gap-4 text-center">
          <div>
            <p class="text-2xl font-bold text-emerald-600">{{ store.stats.submitted || 0 }}</p>
            <p class="text-xs text-slate-400">Đã nộp</p>
          </div>
          <div>
            <p class="text-2xl font-bold text-red-500">{{ store.stats.missing || 0 }}</p>
            <p class="text-xs text-slate-400">Chưa nộp</p>
          </div>
          <div>
            <p class="text-2xl font-bold text-amber-500">{{ store.stats.late || 0 }}</p>
            <p class="text-xs text-slate-400">Trễ hạn</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 mb-4 bg-slate-100 rounded-xl p-1 w-fit">
      <button
        v-for="tab in ['submitted', 'missing']"
        :key="tab"
        @click="activeTab = tab"
        class="px-4 py-1.5 rounded-lg text-sm font-medium transition"
        :class="
          activeTab === tab
            ? 'bg-white text-slate-800 shadow-sm'
            : 'text-slate-500 hover:text-slate-700'
        "
      >
        {{
          tab === 'submitted'
            ? `Đã nộp (${store.submissions.length})`
            : `Chưa nộp (${store.missing.length})`
        }}
      </button>
    </div>

    <!-- Submitted list -->
    <div v-if="activeTab === 'submitted'" class="space-y-3">
      <div v-if="store.loading" class="flex justify-center py-10">
        <div
          class="w-6 h-6 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"
        />
      </div>
      <div
        v-for="sub in store.submissions"
        :key="sub.id"
        class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-4"
      >
        <div
          class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-sm font-bold text-indigo-700 flex-shrink-0"
        >
          {{ (sub.group?.name || sub.student?.name)?.charAt(0) }}
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2">
            <p class="text-sm font-medium text-slate-800">
              {{ sub.group?.name || sub.student?.name }}
            </p>
            <span v-if="sub.student?.code" class="text-xs text-slate-400 font-mono">{{
              sub.student.code
            }}</span>
            <span
              v-if="sub.is_late"
              class="px-1.5 py-0.5 bg-amber-100 text-amber-700 text-[10px] font-bold rounded"
              >Trễ</span
            >
          </div>
          <p class="text-xs text-slate-400 mt-0.5">
            {{ sub.file_name }} · {{ formatDate(sub.submitted_at) }}
          </p>
        </div>
        <a
          :href="downloadUrl(sub.id)"
          target="_blank"
          class="flex items-center gap-1.5 px-3 py-1.5 border border-slate-300 rounded-lg text-xs font-medium text-slate-600 hover:bg-slate-50 transition"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
            />
          </svg>
          Tải về
        </a>
      </div>
      <div
        v-if="!store.loading && !store.submissions.length"
        class="text-center py-8 text-slate-400 text-sm"
      >
        Chưa có bài nộp nào
      </div>
    </div>

    <!-- Missing list -->
    <div v-else class="space-y-2">
      <div
        v-for="m in store.missing"
        :key="m.id"
        class="bg-white rounded-xl border border-slate-200 p-4 flex items-center gap-3"
      >
        <div
          class="w-9 h-9 rounded-full bg-red-100 flex items-center justify-center text-sm font-bold text-red-500 flex-shrink-0"
        >
          {{ m.name?.charAt(0) }}
        </div>
        <div>
          <p class="text-sm font-medium text-slate-700">{{ m.name }}</p>
          <p v-if="m.code" class="text-xs text-slate-400 font-mono">{{ m.code }}</p>
        </div>
        <span class="ml-auto px-2 py-0.5 bg-red-50 text-red-600 text-xs font-medium rounded-full"
          >Chưa nộp</span
        >
      </div>
      <div v-if="!store.missing.length" class="text-center py-8 text-slate-400 text-sm">
        Tất cả đã nộp bài
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useLecturerAssignmentStore } from '@/stores/lecturer/lecturerAssignmentStore'
import { lecturerAssignmentApi } from '@/api/lecturer/lecturerAssignmentApi'

const store = useLecturerAssignmentStore()
const activeTab = ref('submitted')

defineEmits(['back'])

function downloadUrl(submissionId) {
  return lecturerAssignmentApi.downloadUrl(submissionId)
}

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
</script>
