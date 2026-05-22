<!-- src/views/lecturer/LecturerTaskView.vue -->
<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <!-- Breadcrumb -->
        <div
          v-if="mode !== 'groups'"
          class="flex items-center gap-2 text-xs text-stone-500 mb-1.5 flex-wrap"
        >
          <button @click="backToGroups" class="hover:text-teal-600 transition">
            Công việc nhóm
          </button>
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5l7 7-7 7"
            />
          </svg>
          <button
            v-if="mode === 'detail'"
            @click="backToMembers"
            class="hover:text-teal-600 transition"
          >
            {{ selectedGroup?.name }}
          </button>
          <span v-else class="text-stone-800 font-medium">{{ selectedGroup?.name }}</span>

          <template v-if="mode === 'detail'">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5l7 7-7 7"
              />
            </svg>
            <span class="text-stone-800 font-medium">{{ selectedMember?.name }}</span>
          </template>
        </div>

        <h2 class="text-2xl font-bold text-stone-800">
          {{ pageTitle }}
        </h2>
        <p class="text-sm text-stone-500 mt-1">{{ pageSubtitle }}</p>
      </div>

      <button
        v-if="mode !== 'groups'"
        @click="handleBack"
        class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm hover:shadow-md transition-all duration-200"
      >
        <SvgIcon name="back-arrow" class="w-4 h-4" />
        Quay lại
      </button>
    </div>

    <!-- ── Chọn lớp (luôn hiện) ── -->
    <div
      class="bg-white rounded-xl border border-stone-200 p-4 mb-5 flex flex-wrap gap-3 items-center"
    >
      <!-- ✅ Chọn lớp - dùng SearchableSelect -->
      <div class="flex items-center gap-2 flex-1 min-w-[200px] max-w-md">
        <label class="text-xs font-medium text-stone-500 whitespace-nowrap">Lớp học:</label>
        <SearchableSelect
          v-model="selectedClassId"
          :options="classOptions"
          label-key="label"
          value-key="id"
          placeholder="-- Chọn lớp --"
          search-placeholder="Tìm lớp theo mã / tên..."
          empty-text="Bạn chưa có lớp nào"
          class="flex-1"
          @change="onClassChange"
        />
      </div>

      <!-- Search nhóm khi ở mode groups (giữ nguyên) -->
      <div
        v-if="mode === 'groups' && selectedClassId"
        class="flex items-center gap-2 flex-1 min-w-[200px]"
      >
        <div class="relative flex-1">
          <svg
            class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-stone-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"
            />
          </svg>
          <input
            v-model="groupSearch"
            type="text"
            placeholder="Tìm nhóm theo tên..."
            class="w-full pl-8 pr-2 py-1.5 border border-stone-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-teal-500"
          />
        </div>
      </div>
    </div>

    <!-- ── Empty state: chưa chọn lớp ── -->
    <div
      v-if="!selectedClassId"
      class="bg-white rounded-xl border border-stone-200 p-12 text-center"
    >
      <svg
        class="w-12 h-12 mx-auto text-stone-300 mb-3"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1.5"
          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
        />
      </svg>
      <p class="text-stone-400 font-medium">Chọn lớp để xem các nhóm</p>
    </div>

    <!-- ── Loading ── -->
    <div v-else-if="loadingGroups || loadingTasks" class="flex justify-center py-20">
      <div class="w-8 h-8 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin" />
    </div>

    <!-- ════════════════════════════════════════════ -->
    <!-- ✅ MODE 1: GROUPS LIST (mới)                  -->
    <!-- ════════════════════════════════════════════ -->
    <template v-else-if="mode === 'groups'">
      <!-- Class overview -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-xl border border-stone-200 p-4 text-center">
          <p class="text-2xl font-bold text-stone-700">{{ groups.length }}</p>
          <p class="text-xs text-stone-400 mt-1">Tổng nhóm</p>
        </div>
        <div class="bg-white rounded-xl border border-stone-200 p-4 text-center">
          <p class="text-2xl font-bold text-stone-700">{{ classOverallStats.total }}</p>
          <p class="text-xs text-stone-400 mt-1">Tổng công việc</p>
        </div>
        <div class="bg-white rounded-xl border border-stone-200 p-4 text-center">
          <p class="text-2xl font-bold text-emerald-600">{{ classOverallStats.done }}</p>
          <p class="text-xs text-stone-400 mt-1">Đã hoàn thành</p>
        </div>
        <div class="bg-white rounded-xl border border-stone-200 p-4 text-center">
          <p class="text-2xl font-bold text-red-600">{{ classOverallStats.late }}</p>
          <p class="text-xs text-stone-400 mt-1">Trễ hạn</p>
        </div>
      </div>

      <div class="flex items-center justify-between mb-4">
        <h3
          class="text-sm flex items-center gap-1.5 font-bold text-stone-700 uppercase tracking-wide"
        >
          <SvgIcon name="group-users" class="w-5 h-5" /> Danh sách nhóm ({{
            filteredGroups.length
          }})
        </h3>
      </div>

      <!-- Empty groups -->
      <div
        v-if="!filteredGroups.length"
        class="bg-white rounded-xl border border-stone-200 p-12 text-center"
      >
        <p class="text-stone-400">
          {{ groupSearch ? 'Không tìm thấy nhóm phù hợp' : 'Lớp chưa có nhóm nào' }}
        </p>
      </div>

      <!-- Groups grid -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <GroupProgressCard
          v-for="g in filteredGroups"
          :key="g.id"
          :group="g"
          :stats="groupStatsMap[g.id] ?? emptyStats"
          @click="openGroupDetail"
        />
      </div>
    </template>

    <!-- ════════════════════════════════════════════ -->
    <!-- MODE 2: MEMBERS LIST (giữ nguyên logic)    -->
    <!-- ════════════════════════════════════════════ -->
    <template v-else-if="mode === 'members'">
      <!-- Group overview -->
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
        <div class="bg-white rounded-xl border border-stone-200 p-4 text-center">
          <p class="text-2xl font-bold text-stone-700">{{ overallStats.total }}</p>
          <p class="text-xs text-stone-400 mt-1">Tổng nhiệm vụ</p>
        </div>
        <div class="bg-white rounded-xl border border-stone-200 p-4 text-center">
          <p class="text-2xl font-bold text-stone-500">{{ overallStats.todo }}</p>
          <p class="text-xs text-stone-400 mt-1">Cần làm</p>
        </div>
        <div class="bg-white rounded-xl border border-stone-200 p-4 text-center">
          <p class="text-2xl font-bold text-blue-600">{{ overallStats.doing }}</p>
          <p class="text-xs text-stone-400 mt-1">Đang làm</p>
        </div>
        <div class="bg-white rounded-xl border border-stone-200 p-4 text-center">
          <p class="text-2xl font-bold text-emerald-600">{{ overallStats.done }}</p>
          <p class="text-xs text-stone-400 mt-1">Hoàn thành</p>
        </div>
        <div class="bg-white rounded-xl border border-stone-200 p-4 text-center">
          <p class="text-2xl font-bold text-red-600">{{ overallStats.late }}</p>
          <p class="text-xs text-stone-400 mt-1">Trễ hạn</p>
        </div>
      </div>

      <div class="flex items-center justify-between mb-4">
        <h3
          class="flex items-center gap-1.5 text-sm font-bold text-stone-700 uppercase tracking-wide"
        >
          <SvgIcon name="group-users" class="w-3.5 h-3.5" /> Thành viên nhóm ({{
            sortedMembers.length
          }})
        </h3>
        <p class="text-xs text-stone-400">
          Tổng tiến độ nhóm: <strong :class="groupProgressColor">{{ groupProgress }}%</strong>
        </p>
      </div>

      <!-- Empty members -->
      <div
        v-if="!sortedMembers.length"
        class="bg-white rounded-xl border border-stone-200 p-12 text-center"
      >
        <p class="text-stone-400">Nhóm chưa có thành viên</p>
      </div>

      <!-- Member cards grid -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <MemberProgressCard
          v-for="m in sortedMembers"
          :key="m.id"
          :member="m"
          :stats="memberStats(m.id)"
          @click="openMemberDetail"
        />
      </div>
    </template>

    <!-- ════════════════════════════════════════════ -->
    <!--MODE 3: DETAIL VIEW (Kanban)               -->
    <!-- ════════════════════════════════════════════ -->
    <template v-else-if="mode === 'detail' && selectedMember">
      <!-- Member info card -->
      <div class="bg-white rounded-2xl border border-stone-200 p-5 mb-5 flex items-center gap-4">
        <div class="relative flex-shrink-0">
          <img
            v-if="selectedMember.avatar_url || selectedMember.avatar"
            :src="selectedMember.avatar_url || selectedMember.avatar"
            class="w-16 h-16 rounded-full object-cover border-2"
            :class="selectedMember.role === 'leader' ? 'border-amber-400' : 'border-stone-100'"
          />
          <div
            v-else
            class="w-16 h-16 rounded-full flex items-center justify-center text-xl font-bold text-white border-2"
            :class="
              selectedMember.role === 'leader'
                ? 'bg-gradient-to-br from-amber-400 to-orange-500 border-amber-400'
                : 'bg-gradient-to-br from-teal-400 to-cyan-500 border-stone-100'
            "
          >
            {{ selectedMember.name?.charAt(0) }}
          </div>
          <span
            v-if="selectedMember.role === 'leader'"
            class="absolute -top-1 -right-1 w-7 h-7 rounded-full bg-amber-400 flex items-center justify-center shadow"
          >
            <SvgIcon name="crown" class="w-3.5 h-3.5 text-amber-500" />
          </span>
        </div>

        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 flex-wrap">
            <h3 class="text-lg font-bold text-stone-800">{{ selectedMember.name }}</h3>
            <span
              v-if="selectedMember.role === 'leader'"
              class="px-2 py-0.5 bg-amber-100 text-amber-700 text-[10px] font-bold rounded uppercase"
            >
              Trưởng nhóm
            </span>
          </div>
          <p class="text-xs text-stone-400 font-mono mt-1">{{ selectedMember.code }}</p>

          <div class="mt-3 flex items-center gap-3">
            <div class="flex-1 h-2 bg-stone-100 rounded-full overflow-hidden max-w-xs">
              <div
                class="h-full rounded-full transition-all duration-500"
                :class="memberProgressBarClass"
                :style="{ width: `${memberPercentage}%` }"
              />
            </div>
            <span class="text-sm font-bold" :class="memberProgressColor"
              >{{ memberPercentage }}%</span
            >
          </div>
        </div>

        <div class="hidden sm:flex gap-2 text-center flex-shrink-0">
          <div class="px-3 py-2 bg-stone-50 rounded-lg">
            <p class="text-lg font-bold text-stone-700">{{ memberCurrentStats.total }}</p>
            <p class="text-[10px] text-stone-400">Tổng</p>
          </div>
          <div class="px-3 py-2 bg-stone-50 rounded-lg">
            <p class="text-lg font-bold text-emerald-600">{{ memberCurrentStats.done }}</p>
            <p class="text-[10px] text-stone-400">Xong</p>
          </div>
          <div class="px-3 py-2 bg-stone-50 rounded-lg">
            <p
              class="text-lg font-bold"
              :class="memberCurrentStats.late > 0 ? 'text-red-600' : 'text-stone-400'"
            >
              {{ memberCurrentStats.late }}
            </p>
            <p class="text-[10px] text-stone-400">Trễ</p>
          </div>
        </div>
      </div>

      <!-- Status filter -->
      <div class="flex justify-end mb-4">
        <div class="flex gap-1 bg-stone-100 rounded-lg p-1">
          <button
            v-for="f in statusFilters"
            :key="f.value"
            @click="filterStatus = f.value"
            class="px-3 py-1.5 rounded-md text-xs font-medium transition"
            :class="
              filterStatus === f.value
                ? 'bg-white text-stone-800 shadow-sm'
                : 'text-stone-500 hover:text-stone-700'
            "
          >
            {{ f.label }}
          </button>
        </div>
      </div>

      <!-- Empty -->
      <div
        v-if="!memberFilteredTasks.length"
        class="bg-white rounded-xl border border-stone-200 p-12 text-center"
      >
        <p class="text-stone-400">
          {{
            filterStatus
              ? 'Không có công việc ở trạng thái này'
              : 'Thành viên chưa có công việc nào'
          }}
        </p>
      </div>

      <!-- Kanban -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div
          v-for="col in columns"
          :key="col.status"
          class="bg-stone-50 rounded-xl border border-stone-200 p-4"
        >
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
              <span class="text-sm font-semibold text-stone-700">{{ col.label }}</span>
              <span class="px-2 py-0.5 text-xs font-bold rounded-full" :class="col.badgeClass">
                {{ getColumnTasks(col.status).length }}
              </span>
            </div>
            <div class="w-2 h-2 rounded-full" :class="col.dotClass" />
          </div>

          <div class="space-y-3">
            <div
              v-if="!getColumnTasks(col.status).length"
              class="text-center py-6 text-xs text-stone-400 border-2 border-dashed border-stone-200 rounded-xl"
            >
              Không có công việc
            </div>

            <div
              v-for="task in getColumnTasks(col.status)"
              :key="task.id"
              class="bg-white rounded-xl border border-stone-200 p-4 cursor-pointer hover:shadow-sm hover:border-teal-300 transition"
              @click="openTaskDetail(task)"
            >
              <div class="flex items-center justify-between mb-2">
                <span
                  class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase"
                  :class="priorityClass(task.priority)"
                >
                  {{ priorityLabel(task.priority) }}
                </span>
                <span
                  v-if="task.is_overdue"
                  class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-red-100 text-red-700"
                >
                  Trễ hạn
                </span>
              </div>
              <p class="text-sm font-semibold text-stone-800 mb-2 leading-snug">{{ task.title }}</p>
              <div class="flex items-center justify-between mt-3">
                <span class="text-xs text-stone-500">{{ task.assignee?.name ?? 'Chưa giao' }}</span>
                <span v-if="task.deadline" class="text-[10px] text-stone-400">
                  {{ formatDate(task.deadline) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Late tasks -->
      <div v-if="getColumnTasks('late').length" class="mt-4">
        <div class="bg-red-50 rounded-xl border border-red-200 p-4">
          <div class="flex items-center gap-2 mb-3">
            <span class="text-sm font-semibold text-red-700">⚠️ Trễ hạn</span>
            <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-red-100 text-red-700">
              {{ getColumnTasks('late').length }}
            </span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div
              v-for="task in getColumnTasks('late')"
              :key="task.id"
              class="bg-white rounded-xl border border-red-200 p-3 cursor-pointer hover:shadow-sm transition"
              @click="openTaskDetail(task)"
            >
              <p class="text-sm font-semibold text-stone-800 mb-1">{{ task.title }}</p>
              <div class="flex items-center justify-between">
                <span class="text-xs text-stone-500">{{ task.assignee?.name ?? 'Chưa giao' }}</span>
                <span class="text-[10px] text-red-600 font-medium">{{
                  formatDate(task.deadline)
                }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- ── Task detail modal ── -->
    <Teleport to="body">
      <div
        v-if="showDetailModal && currentTask"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
      >
        <div
          class="absolute inset-0 bg-black/30 backdrop-blur-sm"
          @click="showDetailModal = false"
        />
        <div
          class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col"
        >
          <div class="p-6 border-b border-stone-100 flex items-start justify-between flex-shrink-0">
            <div>
              <div class="flex items-center gap-2 mb-1">
                <span
                  class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase"
                  :class="priorityClass(currentTask.priority)"
                >
                  {{ priorityLabel(currentTask.priority) }}
                </span>
                <span
                  class="px-2 py-0.5 text-[10px] font-bold rounded-full"
                  :class="statusClass(currentTask.status)"
                >
                  {{ statusLabel(currentTask.status) }}
                </span>
              </div>
              <h3 class="text-lg font-bold text-stone-800">{{ currentTask.title }}</h3>
            </div>
            <button @click="showDetailModal = false" class="p-1.5 hover:bg-stone-100 rounded-lg">
              <svg
                class="w-5 h-5 text-stone-400"
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

          <div class="flex-1 overflow-y-auto p-6 space-y-5">
            <p
              v-if="currentTask.description"
              class="text-sm text-stone-600 leading-relaxed bg-stone-50 rounded-xl p-4"
            >
              {{ currentTask.description }}
            </p>

            <div class="grid grid-cols-2 gap-3 text-sm">
              <div class="p-3 bg-stone-50 rounded-xl">
                <p class="text-stone-400 text-xs mb-1">Giao cho</p>
                <p class="font-medium text-stone-700">
                  {{ currentTask.assignee?.name || 'Chưa giao' }}
                </p>
              </div>
              <div class="p-3 bg-stone-50 rounded-xl">
                <p class="text-stone-400 text-xs mb-1">Người tạo</p>
                <p class="font-medium text-stone-700">{{ currentTask.creator?.name }}</p>
              </div>
              <div class="p-3 bg-stone-50 rounded-xl">
                <p class="text-stone-400 text-xs mb-1">Bắt đầu</p>
                <p class="font-medium text-stone-700">
                  {{ formatDate(currentTask.start_date) || '—' }}
                </p>
              </div>
              <div class="p-3 bg-stone-50 rounded-xl">
                <p class="text-stone-400 text-xs mb-1">Deadline</p>
                <p
                  class="font-medium"
                  :class="currentTask.is_overdue ? 'text-red-600' : 'text-stone-700'"
                >
                  {{ formatDate(currentTask.deadline) }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axiosClient from '@/api/axiosClient'
import { useLecturerStore } from '@/stores/lecturer/lecturerStore'
import { useToastStore } from '@/stores/toast'
import GroupProgressCard from '../components/task/GroupProgressCard.vue'
import MemberProgressCard from './MemberProgressCard.vue'
import SearchableSelect from '@/components/common/SearchableSelect.vue'
import SvgIcon from '@/components/icons/SVG.vue'

const lecturerStore = useLecturerStore()
const toast = useToastStore()

// ─── State ──────────────────────────────────
const mode = ref('groups') // 'groups' | 'members' | 'detail'

const selectedClassId = ref(lecturerStore.selectedClassId || '')
const selectedGroup = ref(null)
const selectedMember = ref(null)
const filterStatus = ref('')

const groupSearch = ref('')
const groups = ref([])
const groupStatsMap = ref({}) // { groupId: { total, todo, doing, done, late } }
const members = ref([])
const allTasks = ref([])
const currentTask = ref(null)

const loadingGroups = ref(false)
const loadingTasks = ref(false)
const showDetailModal = ref(false)

const emptyStats = { total: 0, todo: 0, doing: 0, done: 0, late: 0 }
const classOptions = computed(() =>
  lecturerStore.classes.map((c) => ({
    id: c.id,
    label: `${c.code} - ${c.name}`, // Hiển thị "K01 - Đại số tuyến tính 1"
  })),
)
// ─── Config ─────────────────────────────────
const statusFilters = [
  { value: '', label: 'Tất cả' },
  { value: 'todo', label: 'Cần làm' },
  { value: 'doing', label: 'Đang làm' },
  { value: 'done', label: 'Hoàn thành' },
  { value: 'late', label: 'Trễ hạn' },
]

const columns = [
  {
    status: 'todo',
    label: 'Cần làm',
    badgeClass: 'bg-stone-100 text-stone-600',
    dotClass: 'bg-stone-400',
  },
  {
    status: 'doing',
    label: 'Đang làm',
    badgeClass: 'bg-blue-100 text-blue-700',
    dotClass: 'bg-blue-500',
  },
  {
    status: 'done',
    label: 'Hoàn thành',
    badgeClass: 'bg-emerald-100 text-emerald-700',
    dotClass: 'bg-emerald-500',
  },
]

// ─── Computed ───────────────────────────────
const pageTitle = computed(() => {
  if (mode.value === 'detail') return `Công việc của ${selectedMember.value?.name ?? ''}`
  if (mode.value === 'members') return `Thành viên ${selectedGroup.value?.name ?? ''}`
  return 'Công việc nhóm'
})

const pageSubtitle = computed(() => {
  if (mode.value === 'detail') return 'Xem chi tiết các nhiệm vụ thành viên'
  if (mode.value === 'members') return 'Click vào thành viên để xem chi tiết công việc'
  return 'Chọn nhóm để xem chi tiết công việc'
})

// Filter groups theo search
const filteredGroups = computed(() => {
  if (!groupSearch.value.trim()) return groups.value
  const q = groupSearch.value.toLowerCase()
  return groups.value.filter(
    (g) =>
      g.name?.toLowerCase().includes(q) ||
      g.invitation_code?.toLowerCase().includes(q) ||
      g.leader?.name?.toLowerCase().includes(q),
  )
})

// Stats tổng cả lớp
const classOverallStats = computed(() => {
  let total = 0,
    done = 0,
    late = 0
  Object.values(groupStatsMap.value).forEach((s) => {
    total += s.total ?? 0
    done += s.done ?? 0
    late += s.late ?? 0
  })
  return { total, done, late }
})

// Members sorted (leader đầu)
const sortedMembers = computed(() => {
  return [...members.value].sort((a, b) => {
    if (a.role === 'leader' && b.role !== 'leader') return -1
    if (a.role !== 'leader' && b.role === 'leader') return 1
    return (a.name ?? '').localeCompare(b.name ?? '', 'vi')
  })
})

// Stats nhóm hiện tại
const overallStats = computed(() => calcStats(allTasks.value))

const groupProgress = computed(() => {
  if (!overallStats.value.total) return 0
  return Math.round((overallStats.value.done / overallStats.value.total) * 100)
})

const groupProgressColor = computed(() => {
  const p = groupProgress.value
  if (p >= 80) return 'text-emerald-600'
  if (p >= 50) return 'text-blue-600'
  if (p >= 30) return 'text-amber-600'
  return 'text-stone-500'
})

// Detail
const memberFilteredTasks = computed(() => {
  if (!selectedMember.value) return []
  let list = allTasks.value.filter((t) => t.assignee?.id === selectedMember.value.id)
  if (filterStatus.value) {
    list = list.filter((t) => t.status === filterStatus.value)
  }
  return list
})

const memberCurrentStats = computed(() => {
  if (!selectedMember.value) return emptyStats
  return memberStats(selectedMember.value.id)
})

const memberPercentage = computed(() => {
  if (!memberCurrentStats.value.total) return 0
  return Math.round((memberCurrentStats.value.done / memberCurrentStats.value.total) * 100)
})

const memberProgressColor = computed(() => {
  const p = memberPercentage.value
  if (p >= 80) return 'text-emerald-600'
  if (p >= 50) return 'text-blue-600'
  if (p >= 30) return 'text-amber-600'
  return 'text-stone-500'
})

const memberProgressBarClass = computed(() => {
  const p = memberPercentage.value
  if (p >= 80) return 'bg-gradient-to-r from-emerald-400 to-emerald-600'
  if (p >= 50) return 'bg-gradient-to-r from-blue-400 to-blue-600'
  if (p >= 30) return 'bg-gradient-to-r from-amber-400 to-amber-600'
  return 'bg-stone-300'
})

// ─── Helpers ────────────────────────────────
function memberStats(memberId) {
  const list = allTasks.value.filter((t) => t.assignee?.id === memberId)
  return calcStats(list)
}

function calcStats(list) {
  return {
    total: list.length,
    todo: list.filter((t) => t.status === 'todo').length,
    doing: list.filter((t) => t.status === 'doing').length,
    done: list.filter((t) => t.status === 'done').length,
    late: list.filter((t) => t.status === 'late').length,
  }
}

function getColumnTasks(status) {
  return memberFilteredTasks.value.filter((t) => t.status === status)
}

// ─── Lifecycle ──────────────────────────────
onMounted(() => {
  if (selectedClassId.value) loadGroups(selectedClassId.value)
})

watch(
  () => lecturerStore.selectedClassId,
  (id) => {
    if (id && id !== selectedClassId.value) {
      selectedClassId.value = id
      onClassChange()
    }
  },
)

// ─── Actions ────────────────────────────────
async function onClassChange() {
  // Reset toàn bộ
  selectedGroup.value = null
  selectedMember.value = null
  filterStatus.value = ''
  groupSearch.value = ''
  mode.value = 'groups'
  groups.value = []
  groupStatsMap.value = {}
  members.value = []
  allTasks.value = []

  if (selectedClassId.value) {
    await loadGroups(selectedClassId.value)
  }
}

async function loadGroups(classId) {
  groups.value = []
  loadingGroups.value = true

  try {
    const { data } = await axiosClient.get(`/lecturer/classes/${classId}/groups`)
    const list = data.groups ?? data.data ?? data ?? []
    groups.value = list

    //Load tasks song song để tính stats cho từng nhóm
    await Promise.all(list.map((g) => loadGroupStats(g.id)))
  } catch (e) {
    console.error('loadGroups error:', e.response?.data)
    groups.value = []
    toast.error('Không thể tải danh sách nhóm')
  } finally {
    loadingGroups.value = false
  }
}

async function loadGroupStats(groupId) {
  try {
    const { data } = await axiosClient.get(`/lecturer/groups/${groupId}/tasks`)
    const tasks = data.tasks ?? data.data?.tasks ?? data.data ?? []
    groupStatsMap.value[groupId] = calcStats(tasks)
  } catch {
    groupStatsMap.value[groupId] = { ...emptyStats }
  }
}

async function loadGroupMembers(groupId) {
  try {
    const { data } = await axiosClient.get(`/lecturer/groups/${groupId}/members`)
    members.value = data.members ?? data.data ?? data ?? []
  } catch {
    members.value = []
  }
}

async function loadTasks(groupId) {
  loadingTasks.value = true
  try {
    const { data } = await axiosClient.get(`/lecturer/groups/${groupId}/tasks`)
    allTasks.value = data.tasks ?? data.data?.tasks ?? data.data ?? []
  } catch {
    allTasks.value = []
    toast.error('Không thể tải công việc nhóm')
  } finally {
    loadingTasks.value = false
  }
}

// ── Mode switching ──
async function openGroupDetail(group) {
  selectedGroup.value = group
  selectedMember.value = null
  filterStatus.value = ''
  mode.value = 'members'
  members.value = []
  allTasks.value = []
  await Promise.all([loadGroupMembers(group.id), loadTasks(group.id)])
}

function openMemberDetail(member) {
  selectedMember.value = member
  filterStatus.value = ''
  mode.value = 'detail'
}

function backToGroups() {
  mode.value = 'groups'
  selectedGroup.value = null
  selectedMember.value = null
  filterStatus.value = ''
}

function backToMembers() {
  mode.value = 'members'
  selectedMember.value = null
  filterStatus.value = ''
}

function handleBack() {
  if (mode.value === 'detail') backToMembers()
  else if (mode.value === 'members') backToGroups()
}

async function openTaskDetail(task) {
  try {
    const { data } = await axiosClient.get(`/tasks/${task.id}`)
    currentTask.value = data.task ?? data.data ?? task
  } catch {
    currentTask.value = task
  }
  showDetailModal.value = true
}

// ─── Formatters ─────────────────────────────
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

function priorityClass(p) {
  return (
    {
      urgent: 'bg-red-100 text-red-700',
      high: 'bg-orange-100 text-orange-700',
      medium: 'bg-blue-100 text-blue-700',
      low: 'bg-stone-100 text-stone-600',
    }[p] ?? 'bg-stone-100 text-stone-600'
  )
}

function priorityLabel(p) {
  return { urgent: 'Khẩn cấp', high: 'Cao', medium: 'Trung bình', low: 'Thấp' }[p] ?? p
}

function statusClass(s) {
  return (
    {
      todo: 'bg-stone-100 text-stone-600',
      doing: 'bg-blue-100 text-blue-700',
      done: 'bg-emerald-100 text-emerald-700',
      late: 'bg-red-100 text-red-700',
    }[s] ?? 'bg-stone-100 text-stone-600'
  )
}

function statusLabel(s) {
  return { todo: 'Cần làm', doing: 'Đang làm', done: 'Hoàn thành', late: 'Trễ hạn' }[s] ?? s
}
</script>
