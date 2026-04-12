<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- ── Header ── -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Quản lý lớp học phần</h1>
          <p class="text-sm text-gray-500 mt-1">{{ store.classes.length }} lớp đang hoạt động</p>
        </div>
        <button
          @click="openCreate"
          class="flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 transition shadow-sm"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4v16m8-8H4"
            />
          </svg>
          Tạo lớp mới
        </button>
      </div>

      <!-- ── Filters ── -->
      <div class="bg-white rounded-2xl border border-gray-200 p-4 mb-6 flex flex-wrap gap-3">
        <input
          v-model="search"
          type="text"
          placeholder="Tìm theo tên, mã lớp..."
          class="flex-1 min-w-48 px-4 py-2 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer select-none">
          <input
            v-model="includeInactive"
            type="checkbox"
            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            @change="loadClasses"
          />
          Hiện học kỳ không active
        </label>
      </div>

      <!-- ── Loading ── -->
      <div
        v-if="store.loading && !store.classes.length"
        class="flex items-center justify-center py-20"
      >
        <svg class="w-8 h-8 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
        </svg>
      </div>

      <!-- ── Table ── -->
      <div v-else class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-100 bg-gray-50">
              <th class="text-left px-5 py-3.5 font-medium text-gray-600">Lớp</th>
              <th class="text-left px-5 py-3.5 font-medium text-gray-600">Học kỳ</th>
              <th class="text-left px-5 py-3.5 font-medium text-gray-600">Giảng viên</th>
              <th class="text-left px-5 py-3.5 font-medium text-gray-600">Môn học</th>
              <th class="text-center px-5 py-3.5 font-medium text-gray-600">Sĩ số</th>
              <th class="text-right px-5 py-3.5 font-medium text-gray-600">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="cls in filteredClasses"
              :key="cls.id"
              class="hover:bg-gray-50 transition group"
            >
              <!-- Tên lớp -->
              <td class="px-5 py-4">
                <p class="font-medium text-gray-900">{{ cls.name }}</p>
                <p class="text-xs text-gray-400 mt-0.5 font-mono">{{ cls.code }}</p>
              </td>

              <!-- Học kỳ -->
              <td class="px-5 py-4">
                <p class="text-gray-700">{{ cls.semester?.name }}</p>
                <p class="text-xs text-gray-400">{{ cls.semester?.year }}</p>
              </td>

              <!-- Giảng viên -->
              <td class="px-5 py-4">
                <div class="flex items-center gap-2">
                  <div
                    class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center text-xs font-medium text-blue-700"
                  >
                    {{ cls.teacher?.name?.charAt(0) ?? '?' }}
                  </div>
                  <span class="text-gray-700 text-xs">{{ cls.teacher?.name }}</span>
                </div>
              </td>

              <!-- Môn học -->
              <td class="px-5 py-4">
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="s in cls.subjects"
                    :key="s.id"
                    class="inline-block px-2 py-0.5 bg-purple-50 text-purple-700 rounded-lg text-xs font-mono"
                  >
                    {{ s.code }}
                  </span>
                </div>
              </td>

              <!-- Sĩ số -->
              <td class="px-5 py-4 text-center">
                <div class="inline-flex flex-col items-center">
                  <span class="text-base font-bold text-gray-900">{{ cls.current_count }}</span>
                  <span class="text-xs text-gray-400">/ {{ getMaxMembers(cls) }}</span>
                  <!-- Progress bar -->
                  <div class="w-16 h-1 bg-gray-200 rounded-full mt-1">
                    <div
                      class="h-1 rounded-full transition-all"
                      :class="progressColor(cls.current_count, getMaxMembers(cls))"
                      :style="{ width: progressWidth(cls.current_count, getMaxMembers(cls)) }"
                    />
                  </div>
                </div>
              </td>

              <!-- Actions -->
              <td class="px-5 py-4">
                <div
                  class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition"
                >
                  <!-- Quản lý SV -->
                  <button
                    @click="openStudents(cls)"
                    class="p-1.5 rounded-lg hover:bg-blue-50 text-gray-400 hover:text-blue-600 transition"
                    title="Quản lý sinh viên"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                      />
                    </svg>
                  </button>
                  <!-- Sửa -->
                  <button
                    @click="openEdit(cls)"
                    class="p-1.5 rounded-lg hover:bg-amber-50 text-gray-400 hover:text-amber-600 transition"
                    title="Chỉnh sửa"
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
                  <!-- Xóa -->
                  <button
                    @click="confirmDelete(cls)"
                    class="p-1.5 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-600 transition"
                    title="Xóa lớp"
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
              </td>
            </tr>

            <tr v-if="!filteredClasses.length">
              <td colspan="6" class="text-center py-16 text-gray-400 text-sm">
                Không có lớp nào phù hợp
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ── Student Panel (slide in từ phải) ── -->
      <transition name="slide">
        <div
          v-if="selectedClass"
          class="fixed inset-y-0 right-0 w-full sm:w-[520px] bg-white shadow-2xl z-40 flex flex-col"
        >
          <!-- Panel header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div>
              <h2 class="font-semibold text-gray-900">{{ selectedClass.name }}</h2>
              <p class="text-xs text-gray-500 mt-0.5">
                Sĩ số: <span class="font-medium text-gray-700">{{ store.students.length }}</span> /
                {{ selectedClass.max_members }}
              </p>
            </div>
            <div class="flex items-center gap-2">
              <button
                @click="showImport = true"
                class="flex items-center gap-1.5 px-3 py-2 border border-gray-300 rounded-xl text-xs font-medium text-gray-700 hover:bg-gray-50 transition"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                class="flex items-center gap-1.5 px-3 py-2 bg-blue-600 text-white rounded-xl text-xs font-medium hover:bg-blue-700 transition"
              >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 4v16m8-8H4"
                  />
                </svg>
                Thêm SV
              </button>
              <button
                @click="selectedClass = null"
                class="p-2 rounded-lg hover:bg-gray-100 text-gray-400 transition"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>
          </div>

          <!-- Student list -->
          <div class="flex-1 overflow-y-auto px-6 py-4">
            <div v-if="store.loading" class="flex justify-center py-10">
              <svg class="w-6 h-6 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
              </svg>
            </div>

            <div v-else-if="!store.students.length" class="text-center py-10 text-gray-400 text-sm">
              Chưa có sinh viên nào trong lớp
            </div>

            <div v-else class="space-y-2">
              <div
                v-for="sv in store.students"
                :key="sv.id"
                class="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:bg-gray-50 transition"
              >
                <div class="flex items-center gap-3">
                  <div
                    class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-xs font-semibold text-blue-700"
                  >
                    {{ sv.name?.charAt(0) }}
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ sv.name }}</p>
                    <p class="text-xs text-gray-400 font-mono">{{ sv.code }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <!-- Toggle has_group -->
                  <button
                    @click="store.toggleHasGroup(selectedClass.id, sv.id, !sv.has_group)"
                    class="px-2.5 py-1 rounded-lg text-xs font-medium transition"
                    :class="
                      sv.has_group
                        ? 'bg-green-100 text-green-700 hover:bg-green-200'
                        : 'bg-gray-100 text-gray-500 hover:bg-gray-200'
                    "
                  >
                    {{ sv.has_group ? 'Có nhóm' : 'Chưa có nhóm' }}
                  </button>
                  <!-- Xóa -->
                  <button
                    @click="handleRemoveStudent(sv.id)"
                    class="p-1.5 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-500 transition"
                  >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                      />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </transition>

      <!-- Overlay khi panel mở -->
      <div
        v-if="selectedClass"
        class="fixed inset-0 bg-black/20 z-30"
        @click="selectedClass = null"
      />
    </div>

    <!-- ── Modals ── -->
    <ClassModal
      :show="showClassForm"
      :editing-item="editingClass"
      :semesters="semesters"
      :lecturers="lecturers"
      :subjects="subjects"
      @close="showClassForm = false"
      @save="handleClassSubmit"
    />

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

    <!-- Toast notification -->
    <transition name="toast">
      <div
        v-if="toast.show"
        class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-5 py-3.5 rounded-2xl shadow-lg text-white text-sm font-medium"
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
import { ref, computed, onMounted } from 'vue'
import { useAdminClassStore } from '@/stores/admin/classes'
import ClassModal from './components/ClassModal.vue'
import ModalAddStudent from '@/components/admin/classes/ModalAddStudent.vue'
import ModalImportStudent from '@/components/admin/classes/ModalImportStudent.vue'
import axiosClient from '@/api/axiosClient'

const store = useAdminClassStore()

// ── UI State ──────────────────────────────────────────────
const search = ref('')
const includeInactive = ref(false)
const selectedClass = ref(null)
const showClassForm = ref(false)
const showAddStudent = ref(false)
const showImport = ref(false)
const editingClass = ref(null)
const importModal = ref(null)

// Data cho dropdowns trong form
const semesters = ref([])
const lecturers = ref([])
const subjects = ref([])

const toast = ref({ show: false, type: 'success', message: '' })

// ── Computed ──────────────────────────────────────────────
const filteredClasses = computed(() => {
  if (!search.value) return store.classes
  const q = search.value.toLowerCase()
  return store.classes.filter(
    (c) => c.name.toLowerCase().includes(q) || c.code.toLowerCase().includes(q),
  )
})

// ── Lifecycle ─────────────────────────────────────────────
onMounted(async () => {
  await loadClasses()
  await loadDropdowns()
})

async function loadClasses() {
  const params = includeInactive.value ? { include_inactive: 1 } : {}
  await store.fetchClasses(params)
}

async function loadDropdowns() {
  const [semRes, lecRes, subRes] = await Promise.all([
    axiosClient.get('/general/semesters'),
    axiosClient.get('/admin/users?role=lecturer'),
    axiosClient.get('/admin/subjects'),
  ])
  semesters.value = semRes.data
  lecturers.value = lecRes.data?.data ?? lecRes.data
  subjects.value = subRes.data?.data ?? subRes.data
}

// ── Class CRUD ────────────────────────────────────────────
function openCreate() {
  editingClass.value = null
  showClassForm.value = true
}

function openEdit(cls) {
  editingClass.value = cls
  showClassForm.value = true
}

function getMaxMembers(cls) {
  return cls.subjects?.[0]?.pivot?.max_members ?? cls.max_members ?? 0
}

async function handleClassSubmit(payload) {
  try {
    // Modal mới emit { id, code, name, ... } — dùng payload.id để phân biệt create/edit
    if (payload.id) {
      await store.editClass(payload.id, payload)
      showToast('Cập nhật lớp thành công')
    } else {
      await store.addClass(payload)
      showToast('Tạo lớp thành công')
    }
    showClassForm.value = false
  } catch (e) {
    showToast(e.response?.data?.message ?? 'Có lỗi xảy ra', 'error')
  }
}

async function confirmDelete(cls) {
  if (!confirm(`Xóa lớp "${cls.name}"?`)) return
  try {
    await store.removeClass(cls.id)
    if (selectedClass.value?.id === cls.id) selectedClass.value = null
    showToast('Đã xóa lớp')
  } catch (e) {
    showToast('Lỗi khi xóa lớp', 'error')
  }
}

// ── Student management ────────────────────────────────────
async function openStudents(cls) {
  selectedClass.value = cls
  await store.fetchStudents(cls.id)
}

async function handleAddStudent(code) {
  try {
    await store.addStudent(selectedClass.value.id, code)
    showAddStudent.value = false
    showToast('Đã thêm sinh viên')
  } catch (e) {
    showToast(e.response?.data?.message ?? 'Lỗi thêm sinh viên', 'error')
  }
}

async function handleRemoveStudent(studentId) {
  if (!confirm('Xóa sinh viên khỏi lớp?')) return
  try {
    await store.removeStudent(selectedClass.value.id, studentId)
    showToast('Đã xóa sinh viên')
  } catch {
    showToast('Lỗi khi xóa', 'error')
  }
}

async function handleImport(file) {
  try {
    const result = await store.importStudents(selectedClass.value.id, file)
    importModal.value?.setResult(result)
    showToast(`Đã thêm ${result.added} sinh viên`)
  } catch (e) {
    showToast(e.response?.data?.message ?? 'Lỗi import', 'error')
  }
}

// ── Helpers ───────────────────────────────────────────────
function progressWidth(current, max) {
  if (!max) return '0%'
  return Math.min((current / max) * 100, 100) + '%'
}

function progressColor(current, max) {
  const pct = max ? current / max : 0
  if (pct >= 0.9) return 'bg-red-500'
  if (pct >= 0.7) return 'bg-amber-500'
  return 'bg-green-500'
}

function showToast(message, type = 'success') {
  toast.value = { show: true, type, message }
  setTimeout(() => {
    toast.value.show = false
  }, 3000)
}
</script>

<style scoped>
.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s ease;
}
.slide-enter-from,
.slide-leave-to {
  transform: translateX(100%);
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(12px);
}
</style>
