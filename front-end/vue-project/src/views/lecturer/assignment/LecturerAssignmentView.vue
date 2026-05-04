<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-slate-800">Quản lý nộp bài</h2>
        <p class="text-sm text-slate-500 mt-1">Tạo và theo dõi đợt nộp bài của lớp</p>
      </div>
      <button
        @click="openCreate"
        class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 transition flex items-center gap-2"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 4v16m8-8H4"
          />
        </svg>
        Tạo đợt nộp
      </button>
    </div>

    <!-- Assignment list -->
    <div v-if="!selectedAssignment" class="space-y-4">
      <div v-if="store.loading" class="flex justify-center py-20">
        <div
          class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"
        />
      </div>

      <div
        v-else-if="!store.assignments.length"
        class="bg-white rounded-2xl border p-12 text-center"
      >
        <p class="text-slate-400">Chưa có đợt nộp bài nào</p>
      </div>

      <div
        v-for="a in store.assignments"
        :key="a.id"
        class="bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md transition cursor-pointer group"
        @click="openDetail(a)"
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
              <!-- ✅ Badge loại tài liệu ký số -->
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
          <div
            class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition"
            @click.stop
          >
            <button
              @click.stop="openEdit(a)"
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
              @click.stop="handleDelete(a.id)"
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

    <!-- Detail view -->
    <div v-else>
      <button
        @click="selectedAssignment = null"
        class="flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700 mb-5 transition"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 19l-7-7 7-7"
          />
        </svg>
        Quay lại
      </button>

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

    <!-- Modal: Tạo / Sửa đợt nộp -->
    <Teleport to="body">
      <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="showForm = false" />
        <div
          class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto"
        >
          <h3 class="text-lg font-bold text-slate-800 mb-5">
            {{ editingId ? 'Cập nhật đợt nộp' : 'Tạo đợt nộp mới' }}
          </h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-600 mb-1">Tiêu đề *</label>
              <input
                v-model="form.title"
                type="text"
                class="input-field"
                placeholder="VD: Đồ án cuối kỳ"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-600 mb-1">Mô tả</label>
              <textarea
                v-model="form.description"
                rows="3"
                class="input-field"
                placeholder="Hướng dẫn nộp bài..."
              />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-600 mb-1">Deadline *</label>
                <input v-model="form.deadline" type="datetime-local" class="input-field" />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-600 mb-1">Loại nộp</label>
                <select v-model="form.submission_type" class="input-field">
                  <option value="both">Cả nhóm và cá nhân</option>
                  <option value="group">Chỉ nhóm</option>
                  <option value="individual">Chỉ cá nhân</option>
                </select>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-slate-600 mb-1"
                  >Dung lượng tối đa (MB)</label
                >
                <input
                  v-model.number="form.max_file_size"
                  type="number"
                  min="1"
                  max="500"
                  class="input-field"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-600 mb-1"
                  >Định dạng cho phép</label
                >
                <input
                  v-model="extensionsInput"
                  type="text"
                  class="input-field"
                  placeholder="pdf,docx,zip"
                />
              </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
              <input
                v-model="form.allow_late"
                type="checkbox"
                class="rounded border-slate-300 text-indigo-600"
              />
              Cho phép nộp trễ hạn
            </label>

            <!-- ✅ Loại tài liệu cần ký số -->
            <div class="border border-slate-200 rounded-xl p-4 space-y-3">
              <p class="text-sm font-semibold text-slate-700 flex items-center gap-2">
                <svg
                  class="w-4 h-4 text-violet-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                  />
                </svg>
                Phân loại tài liệu
              </p>

              <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">
                  Loại tài liệu
                  <span class="text-slate-400">(để trống nếu không cần ký số)</span>
                </label>

                <!-- Loading categories -->
                <div
                  v-if="loadingCategories"
                  class="flex items-center gap-2 text-xs text-slate-400 py-2"
                >
                  <div
                    class="w-3.5 h-3.5 border-2 border-slate-300 border-t-slate-500 rounded-full animate-spin"
                  />
                  Đang tải danh mục...
                </div>

                <select v-else v-model="form.document_category" class="input-field">
                  <option value="">-- Tài liệu thông thường (không cần ký số) --</option>
                  <option v-for="cat in documentCategories" :key="cat.value" :value="cat.value">
                    {{ cat.label }}
                  </option>
                </select>

                <!-- Thông báo khi chọn loại cần ký số -->
                <div
                  v-if="form.document_category"
                  class="mt-2 flex items-center gap-2 text-xs text-violet-600 bg-violet-50 px-3 py-2 rounded-lg"
                >
                  <svg
                    class="w-3.5 h-3.5 flex-shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                  </svg>
                  Sinh viên nộp bài sẽ có tùy chọn gửi yêu cầu ký số tài liệu này lên Admin
                </div>
              </div>
            </div>
          </div>

          <div class="flex gap-3 mt-6">
            <button
              @click="showForm = false"
              class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-50"
            >
              Hủy
            </button>
            <button
              @click="handleSave"
              :disabled="!form.title || !form.deadline"
              class="flex-1 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50"
            >
              {{ editingId ? 'Cập nhật' : 'Tạo' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useLecturerAssignmentStore } from '@/stores/lecturer/lecturerAssignmentStore'
import { lecturerAssignmentApi } from '@/api/lecturer/lecturerAssignmentApi'
import { useLecturerStore } from '@/stores/lecturer/lecturerStore'
import { useToastStore } from '@/stores/toast'
import axiosClient from '@/api/axiosClient'

const store = useLecturerAssignmentStore()
const lecturerStore = useLecturerStore()
const toast = useToastStore()

const selectedAssignment = ref(null)
const activeTab = ref('submitted')
const showForm = ref(false)
const editingId = ref(null)
const extensionsInput = ref('pdf,docx,zip')

//Document categories từ API
const documentCategories = ref([])
const loadingCategories = ref(false)

const classId = computed(() => lecturerStore.selectedClassId)

const form = ref({
  title: '',
  description: '',
  deadline: '',
  allow_late: true,
  submission_type: 'both',
  max_file_size: 50,
  allowed_extensions: [],
  document_category: '', // ← thêm
})

onMounted(async () => {
  if (classId.value) store.fetchByClass(classId.value)
  await loadDocumentCategories()
})

watch(classId, (id) => {
  if (id) store.fetchByClass(id)
})

// Load categories từ API /general/document-categories
async function loadDocumentCategories() {
  loadingCategories.value = true
  try {
    const { data } = await axiosClient.get('/general/document-categories')
    documentCategories.value = data
  } catch {
    // Fallback hardcode nếu API lỗi
    documentCategories.value = [
      { value: 'bao_cao_thuc_tap', label: 'Báo cáo thực tập' },
      { value: 'nckh', label: 'Nghiên cứu khoa học' },
      { value: 'do_an_tot_nghiep', label: 'Đồ án tốt nghiệp' },
      { value: 'bao_cao_du_an', label: 'Báo cáo dự án' },
      { value: 'khoa_luan', label: 'Khóa luận' },
    ]
  } finally {
    loadingCategories.value = false
  }
}

function openCreate() {
  editingId.value = null
  form.value = {
    title: '',
    description: '',
    deadline: '',
    allow_late: true,
    submission_type: 'both',
    max_file_size: 50,
    document_category: '',
  }
  extensionsInput.value = 'pdf,docx,zip'
  showForm.value = true
}

function openEdit(a) {
  editingId.value = a.id
  form.value = {
    title: a.title,
    description: a.description,
    deadline: a.deadline?.slice(0, 16),
    allow_late: a.allow_late,
    submission_type: a.submission_type,
    max_file_size: a.max_file_size,
    document_category: a.document_category ?? '', // ← thêm
  }
  extensionsInput.value = a.allowed_extensions?.join(',') || ''
  showForm.value = true
}

async function openDetail(a) {
  selectedAssignment.value = a
  activeTab.value = 'submitted'
  await store.fetchDetail(a.id)
}

async function handleSave() {
  // Resolve label từ categories
  const selectedCat = documentCategories.value.find((c) => c.value === form.value.document_category)

  const payload = {
    ...form.value,
    allowed_extensions: extensionsInput.value
      .split(',')
      .map((e) => e.trim())
      .filter(Boolean),
    document_category: form.value.document_category || null,
    document_category_label: selectedCat?.label ?? null, // ← resolve label
  }

  const result = editingId.value
    ? await store.updateAssignment(editingId.value, payload)
    : await store.createAssignment(classId.value, payload)

  if (result.success) {
    showForm.value = false
    toast.success(editingId.value ? 'Cập nhật đợt nộp thành công' : 'Tạo đợt nộp thành công')
  } else {
    toast.error(result.message ?? 'Có lỗi xảy ra')
  }
}

async function handleDelete(id) {
  if (!confirm('Xóa đợt nộp này?')) return
  const result = await store.deleteAssignment(id)
  if (result.success) {
    toast.success('Đã xóa đợt nộp bài')
  } else {
    toast.error(result.message ?? 'Lỗi khi xóa')
  }
}

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

function submissionTypeLabel(t) {
  return { both: 'Nhóm + Cá nhân', group: 'Theo nhóm', individual: 'Cá nhân' }[t] || t
}
</script>

<style scoped>
.input-field {
  @apply w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent;
}
</style>
