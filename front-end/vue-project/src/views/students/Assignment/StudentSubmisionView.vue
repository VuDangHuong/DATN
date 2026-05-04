<template>
  <div>
    <!-- Header -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-slate-800">Nộp bài</h2>
      <p class="text-sm text-slate-500 mt-1">Danh sách đợt nộp bài của lớp</p>
    </div>

    <!-- Loading -->
    <div v-if="store.loading" class="flex justify-center py-20">
      <div
        class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"
      />
    </div>

    <!-- Empty -->
    <div v-else-if="!store.assignments.length" class="bg-white rounded-2xl border p-12 text-center">
      <svg
        class="w-12 h-12 mx-auto text-slate-300 mb-3"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1.5"
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
        />
      </svg>
      <p class="text-slate-500">Chưa có đợt nộp bài nào</p>
    </div>

    <!-- Assignment list -->
    <div v-else class="space-y-4">
      <div
        v-for="assignment in store.assignments"
        :key="assignment.id"
        class="bg-white rounded-2xl border border-slate-200 overflow-hidden"
      >
        <!-- Assignment header -->
        <div class="p-5 border-b border-slate-100">
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                <h3 class="font-semibold text-slate-800">{{ assignment.title }}</h3>
                <!-- Status badges -->
                <span
                  v-if="assignment.is_expired && !assignment.allow_late"
                  class="px-2 py-0.5 bg-red-100 text-red-700 text-xs font-bold rounded-full"
                  >Hết hạn</span
                >
                <span
                  v-else-if="assignment.is_expired && assignment.allow_late"
                  class="px-2 py-0.5 bg-amber-100 text-amber-700 text-xs font-bold rounded-full"
                  >Quá hạn — vẫn nhận</span
                >
                <span
                  v-else
                  class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-xs font-bold rounded-full"
                  >Đang mở</span
                >
              </div>
              <p v-if="assignment.description" class="text-sm text-slate-500 mb-2">
                {{ assignment.description }}
              </p>
              <div class="flex items-center gap-4 text-xs text-slate-500">
                <span class="flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                  </svg>
                  Hạn: {{ formatDate(assignment.deadline) }}
                </span>
                <span>Max: {{ assignment.max_file_size }}MB</span>
                <span v-if="assignment.allowed_extensions?.length">
                  Định dạng: {{ assignment.allowed_extensions.join(', ') }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Submission areas -->
        <div class="p-5 space-y-4">
          <!-- Nộp cá nhân -->
          <div v-if="['individual', 'both'].includes(assignment.submission_type)">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">
              Nộp cá nhân
            </p>
            <SubmissionCard
              :submission="assignment.my_submission"
              :assignment="assignment"
              type="individual"
              :submitting="store.submitting"
              @submit="(file, note) => handleSubmit(assignment, 'individual', file, note)"
              @show-history="showHistory(assignment.id)"
            />
          </div>

          <!-- Nộp nhóm -->
          <div
            v-if="
              ['group', 'both'].includes(assignment.submission_type) &&
              assignment.is_leader !== undefined
            "
          >
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">
              Nộp nhóm
            </p>
            <SubmissionCard
              :submission="assignment.group_submission"
              :assignment="assignment"
              type="group"
              :is-leader="assignment.is_leader"
              :submitting="store.submitting"
              @submit="(file, note) => handleSubmit(assignment, 'group', file, note)"
              @show-history="showHistory(assignment.id)"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Modal: Lịch sử nộp -->
    <Teleport to="body">
      <div v-if="showHistoryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div
          class="absolute inset-0 bg-black/30 backdrop-blur-sm"
          @click="showHistoryModal = false"
        />
        <div
          class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[80vh] flex flex-col"
        >
          <div class="p-5 border-b flex items-center justify-between">
            <h3 class="font-bold text-slate-800">Lịch sử nộp bài</h3>
            <button @click="showHistoryModal = false" class="p-1.5 hover:bg-slate-100 rounded-lg">
              <svg
                class="w-4 h-4 text-slate-400"
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
          <div class="flex-1 overflow-y-auto p-5">
            <div v-if="!store.history.length" class="text-center py-8 text-slate-400 text-sm">
              Chưa có lịch sử nộp
            </div>
            <div v-else class="space-y-3">
              <div
                v-for="(h, idx) in store.history"
                :key="h.id"
                class="flex items-start gap-3 p-3 rounded-xl border border-slate-100 bg-slate-50"
              >
                <div
                  class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center flex-shrink-0 mt-0.5"
                >
                  {{ store.history.length - idx }}
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 mb-1">
                    <span class="text-sm font-medium text-slate-700 truncate">{{
                      h.file_name
                    }}</span>
                    <span
                      v-if="h.is_late"
                      class="px-1.5 py-0.5 bg-amber-100 text-amber-700 text-[10px] font-bold rounded flex-shrink-0"
                      >Trễ</span
                    >
                  </div>
                  <p class="text-xs text-slate-400">
                    {{ h.submitted_by }} · {{ formatDate(h.submitted_at) }}
                  </p>
                  <p v-if="h.note" class="text-xs text-slate-500 mt-1 italic">{{ h.note }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Toast -->
    <transition name="toast">
      <div
        v-if="toast.show"
        class="fixed bottom-6 right-6 z-50 px-5 py-3.5 rounded-2xl shadow-lg text-white text-sm font-medium flex items-center gap-2"
        :class="toast.type === 'success' ? 'bg-gray-900' : 'bg-red-600'"
      >
        <svg
          v-if="toast.type === 'success'"
          class="w-4 h-4 text-green-400"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path
            fill-rule="evenodd"
            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
            clip-rule="evenodd"
          />
        </svg>
        {{ toast.message }}
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useDashboardStore } from '@/stores/students/dashboardStore'
import SubmissionCard from '@/components/students/SubmissionCard.vue'
import { useStudentAssignmentStore } from '@/stores/students/studentAssignmentStore'
import { useToastStore } from '@/stores/toast'
const dashboardStore = useDashboardStore()
const store = useStudentAssignmentStore()
const toast = useToastStore()
const showHistoryModal = ref(false)

onMounted(async () => {
  const classId = dashboardStore.selectedClass?.class?.id
  if (classId) await store.fetchByClass(classId)
})

async function handleSubmit(assignment, type, file, note, documentCategory) {
  const result =
    type === 'group'
      ? await store.submitGroup(assignment.id, file, note, documentCategory)
      : await store.submitIndividual(assignment.id, file, note, documentCategory)

  if (result.success) {
    toast.success(result.data.message, 'success')
  } else {
    toast.error(result.message, 'error')
  }
}

async function showHistory(assignmentId) {
  await store.fetchHistory(assignmentId)
  showHistoryModal.value = true
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

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>
