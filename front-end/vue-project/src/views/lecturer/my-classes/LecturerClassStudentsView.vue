<!-- src/views/lecturer/my-classes/LecturerClassStudentsView.vue
     Quản lý SV trong lớp - CRUD đầy đủ + import Excel
     Route: /lecturer/my-classes/:classId/students
-->
<template>
  <div class="p-6">
    <!-- Breadcrumb -->
    <div class="mb-6">
      <button
        @click="$router.push('/lecturer/my-classes')"
        class="text-sm text-stone-500 hover:text-stone-700 mb-3 flex items-center gap-1 transition"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 19l-7-7 7-7"
          />
        </svg>
        Quay lại Lớp của tôi
      </button>

      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 class="text-2xl font-bold text-stone-800">
            Sinh viên — {{ classInfo?.name ?? 'Đang tải...' }}
          </h1>
          <p class="text-sm text-stone-500 mt-1 font-mono">
            {{ classInfo?.code }}
          </p>
        </div>

        <div class="flex gap-2">
          <button
            @click="showImport = true"
            class="flex items-center gap-1.5 px-4 py-2 border border-stone-300 rounded-xl text-sm font-medium text-stone-700 hover:bg-stone-50 transition"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"
              />
            </svg>
            Import Excel
          </button>
          <button
            @click="showAddStudent = true"
            class="flex items-center gap-1.5 px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 transition"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              />
            </svg>
            Thêm SV
          </button>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
      <div class="bg-white rounded-2xl border border-stone-200 p-4">
        <p class="text-xs text-stone-500">Tổng SV</p>
        <div class="flex items-end gap-1 mt-1">
          <p class="text-2xl font-bold text-stone-800">{{ totalCount }}</p>
          <p class="text-sm text-stone-400 mb-1">/ {{ maxMembers }}</p>
        </div>
        <div class="w-full h-1 bg-stone-100 rounded-full mt-2 overflow-hidden">
          <div
            class="h-full rounded-full transition-all"
            :class="progressColor"
            :style="{ width: progressWidth }"
          />
        </div>
      </div>

      <div class="bg-white rounded-2xl border border-stone-200 p-4">
        <p class="text-xs text-stone-500">Đã có nhóm</p>
        <p class="text-2xl font-bold text-emerald-600 mt-1">{{ withGroupCount }}</p>
        <p class="text-xs text-stone-400 mt-2">
          {{ totalCount ? Math.round((withGroupCount / totalCount) * 100) : 0 }}%
        </p>
      </div>

      <div class="bg-white rounded-2xl border border-stone-200 p-4">
        <p class="text-xs text-stone-500">Chưa có nhóm</p>
        <p class="text-2xl font-bold text-amber-600 mt-1">{{ withoutGroupCount }}</p>
        <p class="text-xs text-stone-400 mt-2">
          {{ totalCount ? Math.round((withoutGroupCount / totalCount) * 100) : 0 }}%
        </p>
      </div>

      <div class="bg-white rounded-2xl border border-stone-200 p-4">
        <p class="text-xs text-stone-500">Còn trống</p>
        <p class="text-2xl font-bold text-stone-800 mt-1">
          {{ Math.max(maxMembers - totalCount, 0) }}
        </p>
        <p class="text-xs text-stone-400 mt-2">slot</p>
      </div>
    </div>

    <!-- Filter -->
    <div
      class="bg-white rounded-2xl border border-stone-200 p-4 mb-4 flex flex-wrap gap-3 items-center"
    >
      <div class="relative flex-1 min-w-[200px]">
        <svg
          class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400"
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
          v-model="searchInput"
          type="text"
          placeholder="Tìm theo tên, mã, email..."
          class="w-full pl-9 pr-3 py-2 border border-stone-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none"
        />
      </div>

      <div class="flex gap-1 bg-stone-100 p-1 rounded-xl">
        <button
          v-for="opt in groupFilterOptions"
          :key="opt.value"
          @click="groupFilter = opt.value"
          class="px-3 py-1.5 rounded-lg text-xs font-medium transition"
          :class="
            groupFilter === opt.value
              ? 'bg-white text-blue-600 shadow-sm'
              : 'text-stone-600 hover:text-stone-800'
          "
        >
          {{ opt.label }}
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="store.loading && !store.students.length" class="flex justify-center py-20">
      <div class="w-10 h-10 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin" />
    </div>

    <!-- Empty -->
    <div v-else-if="!filteredStudents.length" class="bg-white rounded-2xl border p-12 text-center">
      <svg
        class="w-12 h-12 text-stone-300 mx-auto mb-3"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1.5"
          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
        />
      </svg>
      <p class="text-stone-500 mb-3">
        {{
          searchInput || groupFilter !== 'all'
            ? 'Không có sinh viên phù hợp'
            : 'Lớp chưa có sinh viên nào'
        }}
      </p>
      <button
        v-if="!searchInput && groupFilter === 'all'"
        @click="showAddStudent = true"
        class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700"
      >
        Thêm sinh viên đầu tiên
      </button>
    </div>

    <!-- Table SV -->
    <div v-else class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
      <table class="w-full">
        <thead class="bg-stone-50 border-b border-stone-200">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-stone-500 uppercase">#</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-stone-500 uppercase">
              Sinh viên
            </th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-stone-500 uppercase">
              Liên hệ
            </th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-stone-500 uppercase">
              Nhóm
            </th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-stone-500 uppercase">
              Tham gia
            </th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-stone-500 uppercase">
              Hành động
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-stone-100">
          <tr
            v-for="(sv, idx) in filteredStudents"
            :key="sv.id"
            class="hover:bg-stone-50 transition"
          >
            <td class="px-4 py-3 text-sm text-stone-500">{{ idx + 1 }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <div
                  class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-sm font-bold text-blue-700 flex-shrink-0"
                >
                  {{ sv.name?.charAt(0) }}
                </div>
                <div class="min-w-0">
                  <p class="text-sm font-medium text-stone-800 truncate">{{ sv.name }}</p>
                  <p class="text-xs text-stone-400 font-mono">{{ sv.code }}</p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3">
              <p class="text-sm text-stone-700">{{ sv.email }}</p>
              <p class="text-xs text-stone-400">{{ sv.phone || '—' }}</p>
            </td>
            <td class="px-4 py-3 text-center">
              <button
                class="px-2.5 py-1 rounded-lg text-xs font-medium transition"
                :class="
                  sv.has_group
                    ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200'
                    : 'bg-stone-100 text-stone-500 hover:bg-stone-200'
                "
              >
                {{ sv.has_group ? '✓ Có nhóm' : 'Chưa có' }}
              </button>
            </td>
            <td class="px-4 py-3 text-sm text-stone-500">
              {{ formatDate(sv.joined_at) }}
            </td>
            <td class="px-4 py-3 text-right">
              <button
                @click="handleRemove(sv)"
                class="text-red-600 hover:bg-red-50 px-2 py-1 rounded-lg text-xs font-medium inline-flex items-center gap-1 transition"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                  />
                </svg>
                Xóa
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modals -->
    <ModalAddStudent
      :show="showAddStudent"
      :loading="store.loading"
      @close="showAddStudent = false"
      @submit="handleAddStudent"
    />
    <ModalImportStudent
      ref="importModal"
      :show="showImport"
      :importing="store.importing"
      @close="showImport = false"
      @import="handleImport"
    />
    <ConfirmModal
      v-model="confirmState.show"
      :title="confirmState.title"
      :message="confirmState.message"
      :item-name="confirmState.itemName"
      :warning-text="confirmState.warningText"
      :confirm-text="confirmState.confirmText"
      :cancel-text="confirmState.cancelText"
      :variant="confirmState.variant"
      :loading="confirmState.loading"
      :require-type-confirm="confirmState.requireTypeConfirm"
      @confirm="_handleConfirm"
      @cancel="_handleCancel"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useLecturerClassStore } from '@/stores/lecturer/lecturerClass'
import { useToastStore } from '@/stores/toast'
import { useConfirm } from '@/composables/useConfirm'

// Reuse 2 modal có sẵn của admin
import ModalAddStudent from '@/components/admin/classes/ModalAddStudent.vue'
import ModalImportStudent from '@/components/admin/classes/ModalImportStudent.vue'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const route = useRoute()
const store = useLecturerClassStore()
const toast = useToastStore()

const {
  state: confirmState,
  confirmDelete,
  setLoading: setConfirmLoading,
  close: closeConfirm,
  _handleConfirm,
  _handleCancel,
} = useConfirm()

const classId = Number(route.params.classId)

// ── UI State ──
const searchInput = ref('')
const groupFilter = ref('all')
const showAddStudent = ref(false)
const showImport = ref(false)
const importModal = ref(null)

const groupFilterOptions = [
  { value: 'all', label: 'Tất cả' },
  { value: 'yes', label: 'Có nhóm' },
  { value: 'no', label: 'Chưa có' },
]

// ── Computed ──
const classInfo = computed(() => store.classInfo)
const totalCount = computed(() => store.students.length)
const withGroupCount = computed(() => store.students.filter((s) => s.has_group).length)
const withoutGroupCount = computed(() => store.students.filter((s) => !s.has_group).length)
const maxMembers = computed(() => classInfo.value?.max_members ?? 0)

const progressWidth = computed(() => {
  if (!maxMembers.value) return '0%'
  return Math.min((totalCount.value / maxMembers.value) * 100, 100) + '%'
})

const progressColor = computed(() => {
  const pct = maxMembers.value ? totalCount.value / maxMembers.value : 0
  if (pct >= 0.9) return 'bg-red-500'
  if (pct >= 0.7) return 'bg-amber-500'
  return 'bg-emerald-500'
})

const filteredStudents = computed(() => {
  let result = store.students

  if (groupFilter.value === 'yes') result = result.filter((s) => s.has_group)
  else if (groupFilter.value === 'no') result = result.filter((s) => !s.has_group)

  if (searchInput.value.trim()) {
    const q = searchInput.value.toLowerCase()
    result = result.filter(
      (s) =>
        s.name?.toLowerCase().includes(q) ||
        s.code?.toLowerCase().includes(q) ||
        s.email?.toLowerCase().includes(q),
    )
  }

  return result
})

// ── Lifecycle ──
onMounted(() => store.fetchStudents(classId))

// ── Actions ──
async function handleAddStudent(code) {
  try {
    await store.addStudent(classId, code)
    showAddStudent.value = false
    toast.success('Đã thêm sinh viên')
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Lỗi thêm sinh viên')
  }
}

// async function handleToggleGroup(student) {
//   try {
//     await store.toggleHasGroup(classId, student.id, !student.has_group)
//     toast.success('Đã cập nhật trạng thái nhóm')
//   } catch (e) {
//     toast.error(e.response?.data?.message ?? 'Không thể cập nhật')
//   }
// }

async function handleRemove(student) {
  const ok = await confirmDelete(student.name, {
    title: 'Xóa sinh viên khỏi lớp',
    message: 'Sinh viên sẽ bị xóa khỏi lớp. Nếu đang trong nhóm, hãy xử lý nhóm trước.',
    warningText: `Mã SV: ${student.code}${student.email ? ` · ${student.email}` : ''}`,
    confirmText: 'Xóa khỏi lớp',
  })
  if (!ok) return

  setConfirmLoading(true)
  try {
    await store.removeStudent(classId, student.id)
    toast.success('Đã xóa sinh viên')
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Lỗi khi xóa')
  } finally {
    closeConfirm()
  }
}

async function handleImport(file) {
  try {
    const result = await store.importStudents(classId, file)
    importModal.value?.setResult(result)
    toast.success(`Đã thêm ${result.added ?? 0} sinh viên`)
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Lỗi import')
  }
}

// ── Helpers ──
function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}
</script>
