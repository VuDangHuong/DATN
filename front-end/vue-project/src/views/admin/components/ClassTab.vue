<script setup>
import { ref, watch, onMounted, computed } from 'vue'
import { useCategoryStore } from '@/stores/admin/category'
import { categoryApi } from '@/api/admin/category'
import { useToastStore } from '@/stores/toast'
import SvgIcon from '@/components/icons/SVG.vue'
import ClassModal from './ClassModal.vue'
import { useConfirm } from '@/composables/useConfirm'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

const {
  state: confirmState,
  confirmDelete,
  setLoading: setConfirmLoading,
  close: closeConfirm,
  _handleConfirm,
  _handleCancel,
} = useConfirm()
const store = useCategoryStore()
const toast = useToastStore()

// Filter State
const filterFaculty = ref('')
const filterMajor = ref('')
const classes = ref([])

// Dropdown Data
const availableMajors = ref([])
const subjectList = ref([])
const semesterList = ref([])
const lecturerList = ref([])

// Modal State
const showModal = ref(false)
const editingItem = ref(null)

// ===== IMPORT STATE =====
const showImportModal = ref(false)
const importFile = ref(null)
const importing = ref(false)
const importResult = ref(null)
const fileInputRef = ref(null)
const currentPage = ref(1)
const searchQuery = ref('')
const classPagination = ref({ current_page: 1, last_page: 1, per_page: 5, total: 0 })

const debounce = (fn, d = 400) => {
  let t
  return (...a) => {
    clearTimeout(t)
    t = setTimeout(() => fn(...a), d)
  }
}

const onSearch = debounce(() => {
  currentPage.value = 1
  loadClasses()
}, 400)

const goToPage = (page) => {
  if (page < 1 || page > classPagination.value.last_page || page === currentPage.value) return
  currentPage.value = page
  loadClasses()
}

const pageNumbers = computed(() => {
  const last = classPagination.value.last_page
  const cur = currentPage.value
  const pages = []
  for (let i = Math.max(1, cur - 2); i <= Math.min(last, cur + 2); i++) pages.push(i)
  return pages
})
onMounted(async () => {
  loadClasses()
  try {
    const [semRes, lecRes] = await Promise.all([
      categoryApi.getSemesters(),
      categoryApi.getLecturers(),
    ])
    semesterList.value = semRes.data
    lecturerList.value = lecRes.data
  } catch (e) {
    console.error('Lỗi tải dữ liệu nền:', e)
  }
})

watch(filterFaculty, async (newVal) => {
  filterMajor.value = ''
  subjectList.value = []
  availableMajors.value = newVal ? await store.fetchMajors(newVal) : []
  currentPage.value = 1
  loadClasses()
})

watch(filterMajor, async (newVal) => {
  currentPage.value = 1
  loadClasses()
  if (newVal) {
    try {
      const res = await categoryApi.getSubjects({ major_id: newVal })
      subjectList.value = res.data
    } catch (e) {
      console.error(e)
    }
  } else {
    subjectList.value = []
  }
})

async function loadClasses() {
  const params = {
    page: currentPage.value,
    search: searchQuery.value,
  }
  if (filterMajor.value) params.major_id = filterMajor.value
  try {
    const res = await categoryApi.getClasses(params)
    const data = res.data
    classes.value = data.data ?? []
    classPagination.value = {
      current_page: data.current_page ?? 1,
      last_page: data.last_page ?? 1,
      per_page: data.per_page ?? 5,
      total: data.total ?? 0,
    }
  } catch (e) {
    console.error(e)
  }
}

const openCreate = () => {
  if (!filterMajor.value) {
    toast.error('Vui lòng chọn Khoa và Ngành trước để tải danh sách môn học.')
    return
  }
  editingItem.value = null
  showModal.value = true
}

const openEdit = (item) => {
  editingItem.value = item
  showModal.value = true
}

const handleSave = async (formData) => {
  try {
    if (editingItem.value) {
      await categoryApi.updateClass(formData.id, formData)
      toast.success('Cập nhật lớp thành công')
    } else {
      await categoryApi.createClass(formData)
      toast.success('Mở lớp học phần thành công')
    }
    showModal.value = false
    loadClasses()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Có lỗi xảy ra')
    console.error(e)
  }
}

const handleDelete = async (cls) => {
  if (!cls || !cls.id) {
    toast.error('Không tìm thấy lớp học')
    return
  }

  const ok = await confirmDelete(
    'Sau khi hủy, lớp sẽ không hoạt động. Tất cả nhóm, bài nộp và tài liệu sẽ bị ảnh hưởng.',
    {
      title: 'Hủy lớp học phần',
      itemName: cls.name,
      warningText: `Mã lớp: ${cls.code}`,
      confirmText: 'Hủy lớp',
      cancelText: 'Đóng',
    },
  )

  if (!ok) return

  setConfirmLoading(true)
  try {
    await categoryApi.deleteClass(cls.id)
    toast.success('Đã hủy lớp')
    loadClasses()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Không thể hủy lớp')
  } finally {
    closeConfirm()
  }
}

// ===== IMPORT HANDLERS =====
const openImportModal = () => {
  importFile.value = null
  importResult.value = null
  showImportModal.value = true
}

const closeImportModal = () => {
  showImportModal.value = false
  importFile.value = null
  importResult.value = null
  if (fileInputRef.value) fileInputRef.value.value = ''
}

const handleFileChange = (e) => {
  const file = e.target.files[0]
  if (!file) return

  const ext = file.name.split('.').pop().toLowerCase()
  if (!['xlsx', 'xls', 'csv'].includes(ext)) {
    toast.error('Chỉ chấp nhận file .xlsx, .xls hoặc .csv')
    e.target.value = ''
    return
  }
  if (file.size > 5 * 1024 * 1024) {
    toast.error('File không được lớn hơn 5MB')
    e.target.value = ''
    return
  }

  importFile.value = file
  importResult.value = null
}

const handleImport = async () => {
  if (!importFile.value) {
    toast.error('Vui lòng chọn file để import')
    return
  }

  importing.value = true
  importResult.value = null

  try {
    const formData = new FormData()
    formData.append('file', importFile.value)

    const res = await categoryApi.importClasses(formData)
    importResult.value = res.data.data

    if (importResult.value.fail_count > 0) {
      toast.warning(
        `Import xong: ${importResult.value.success_count} thành công, ${importResult.value.fail_count} lỗi`,
      )
    } else {
      toast.success(`Import thành công ${importResult.value.success_count} lớp học phần`)
    }

    loadClasses()
  } catch (e) {
    if (e.response?.status === 422 && e.response.data?.errors) {
      importResult.value = {
        success_count: 0,
        fail_count: e.response.data.errors.length,
        errors: e.response.data.errors,
      }
      toast.error('File có dữ liệu không hợp lệ')
    } else {
      toast.error(e.response?.data?.message || 'Import thất bại')
    }
  } finally {
    importing.value = false
  }
}

const downloadTemplate = async () => {
  try {
    const res = await categoryApi.downloadClassTemplate()
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'mau_import_lop_hoc_phan.csv')
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch {
    toast.error('Không thể tải file mẫu')
  }
}
</script>

<template>
  <div>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 bg-gray-50 p-4 rounded-lg">
      <div>
        <label class="block text-base font-bold text-gray-500 uppercase mb-1">Lọc Khoa</label>
        <select v-model="filterFaculty" class="w-full border rounded p-2 text-sm">
          <option value="">-- Tất cả --</option>
          <option v-for="f in store.faculties" :key="f.id" :value="f.id">{{ f.name }}</option>
        </select>
      </div>
      <div>
        <label class="block text-base font-bold text-gray-500 uppercase mb-1">Lọc Ngành</label>
        <select
          v-model="filterMajor"
          class="w-full border rounded p-2 text-sm"
          :disabled="!filterFaculty"
        >
          <option value="">-- Tất cả --</option>
          <option v-for="m in availableMajors" :key="m.id" :value="m.id">{{ m.name }}</option>
        </select>
      </div>
      <div class="flex items-end">
        <button
          @click="openImportModal"
          class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 w-full flex justify-center items-center"
        >
          <SvgIcon name="upload" class="w-4 h-4 mr-2" /> Import Excel
        </button>
      </div>
      <div class="flex items-end">
        <button
          @click="openCreate"
          class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full flex justify-center items-center disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="!filterMajor"
          title="Vui lòng chọn Ngành trước"
        >
          <SvgIcon name="plus" class="w-4 h-4 mr-2" /> Mở Lớp Học Phần
        </button>
      </div>
    </div>
    <!-- Thanh tìm kiếm -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
      <h3 class="text-base font-semibold text-gray-700">
        Danh sách lớp học phần
        <span v-if="classPagination.total" class="text-gray-400 font-normal">
          ({{ classPagination.total }} lớp)
        </span>
      </h3>

      <div class="relative w-full sm:w-80">
        <span
          class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400"
        >
          <SvgIcon name="search" class="w-4 h-4" />
        </span>
        <input
          v-model="searchQuery"
          @input="onSearch"
          type="text"
          placeholder="Tìm theo mã hoặc tên lớp..."
          class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
        />
      </div>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full border-collapse min-w-[800px]">
        <thead>
          <tr class="bg-white border-b border-gray-200 text-sm">
            <th class="p-3 text-left">Mã Lớp / Lớp</th>
            <th class="p-3 text-left">Môn Học / Học kỳ</th>
            <th class="p-3 text-left">Giảng viên</th>
            <th class="p-3 text-center">Sĩ số</th>
            <th class="p-3 text-right">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="classes.length === 0">
            <td colspan="5" class="p-6 text-center text-gray-500">Không có lớp học phần nào</td>
          </tr>
          <tr v-for="c in classes" :key="c.id" class="border-b hover:bg-gray-50 text-sm">
            <td class="p-3">
              <div class="font-mono text-blue-600 font-bold">{{ c.code }}</div>
              <div class="text-base text-gray-500">{{ c.name }}</div>
            </td>
            <td class="p-3">
              <div class="font-medium text-gray-900">
                <template v-if="c.subjects && c.subjects.length > 0">
                  <div v-for="sub in c.subjects" :key="sub.id" class="mb-1 last:mb-0">
                    • {{ sub.name }}
                    <span class="text-base text-gray-500 font-normal">({{ sub.code }})</span>
                  </div>
                </template>
                <span v-else class="text-gray-400 italic">---</span>
              </div>
              <div class="text-base text-blue-600 mt-1 pt-1 border-t border-gray-100">
                {{ c.semester?.name }} ({{ c.semester?.year }})
              </div>
            </td>
            <td class="p-3">
              <div v-if="c.lecturer" class="flex items-center">
                <span class="font-medium text-gray-800">{{ c.lecturer.name }}</span>
              </div>
              <span v-else class="text-gray-400 italic">Chưa phân công</span>
            </td>
            <td class="p-3 text-center">
              <template v-if="c.subjects && c.subjects.length > 0">
                <div v-for="sub in c.subjects" :key="sub.id" class="mb-1 last:mb-0">
                  <div
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-base font-medium"
                    :class="
                      (c.current_students || 0) >= (sub.pivot?.max_members || c.max_members || 60)
                        ? 'bg-red-100 text-red-800'
                        : 'bg-green-100 text-green-800'
                    "
                  >
                    {{ sub.pivot?.max_members || c.max_members || 60 }}
                  </div>
                </div>
              </template>
              <span v-else class="text-gray-400 italic">---</span>
            </td>
            <td class="p-3 text-right space-x-2">
              <button
                @click="openEdit(c)"
                class="text-indigo-600 hover:text-indigo-900 font-medium text-sm"
              >
                <SvgIcon name="edit" class="h-4 w-4 mr-1" />
                Sửa
              </button>
              <button
                @click="handleDelete(c)"
                class="text-red-600 hover:text-red-800 font-medium text-sm"
              >
                <SvgIcon name="trash" class="h-4 w-4 mr-1" />
                Xóa
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div
      v-if="classPagination.total > 0"
      class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-4 px-1"
    >
      <p class="text-sm text-gray-600">
        Hiển thị
        <span class="font-medium">{{
          (classPagination.current_page - 1) * classPagination.per_page + 1
        }}</span>
        –
        <span class="font-medium">{{
          Math.min(classPagination.current_page * classPagination.per_page, classPagination.total)
        }}</span>
        / <span class="font-medium">{{ classPagination.total }}</span> lớp
      </p>

      <div class="flex items-center gap-1">
        <button
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage <= 1"
          class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm text-gray-600 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed"
        >
          Trước
        </button>
        <button
          v-for="p in pageNumbers"
          :key="p"
          @click="goToPage(p)"
          :class="[
            'px-3 py-1.5 rounded-lg border text-sm transition',
            p === currentPage
              ? 'bg-blue-600 border-blue-600 text-white'
              : 'border-gray-300 text-gray-600 hover:bg-gray-100',
          ]"
        >
          {{ p }}
        </button>
        <button
          @click="goToPage(currentPage + 1)"
          :disabled="currentPage >= classPagination.last_page"
          class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm text-gray-600 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed"
        >
          Sau
        </button>
      </div>
    </div>
    <ClassModal
      :show="showModal"
      :editing-item="editingItem"
      :subjects="subjectList"
      :semesters="semesterList"
      :lecturers="lecturerList"
      @close="showModal = false"
      @save="handleSave"
    />

    <!-- Modal Import -->
    <Teleport to="body">
      <div
        v-if="showImportModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      >
        <div class="bg-white rounded-lg w-[700px] max-h-[90vh] flex flex-col shadow-xl">
          <div class="p-6 border-b">
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-bold">Import danh sách Lớp học phần</h3>
              <button @click="closeImportModal" class="text-gray-400 hover:text-gray-600">
                <SvgIcon name="x" class="w-5 h-5" />
              </button>
            </div>
          </div>

          <div class="p-6 overflow-y-auto flex-1">
            <div class="bg-blue-50 border border-blue-200 rounded p-4 mb-4">
              <p class="text-sm text-blue-800 font-medium mb-2">📌 Hướng dẫn:</p>
              <ul class="text-sm text-blue-700 list-disc list-inside space-y-1">
                <li>File phải có 6 cột:</li>
                <li class="ml-4"><strong>code</strong> - Mã lớp (VD: CSE101.01)</li>
                <li class="ml-4"><strong>name</strong> - Tên lớp</li>
                <li class="ml-4"><strong>semester_code</strong> - Mã học kỳ (VD: HK1_2024)</li>
                <li class="ml-4">
                  <strong>lecturer_email</strong> - Email giảng viên
                  <span class="text-orange-700">(có thể để trống)</span>
                </li>
                <li class="ml-4">
                  <strong>subject_codes</strong> - Mã môn, nhiều môn ngăn cách bởi
                  <code class="bg-blue-100 px-1 rounded">|</code> (VD: CSE201|CSE301)
                </li>
                <li class="ml-4">
                  <strong>max_members_list</strong> - Sĩ số tương ứng từng môn, ngăn cách bởi
                  <code class="bg-blue-100 px-1 rounded">|</code> (VD: 35|40)
                </li>
                <li>Nếu mã lớp đã tồn tại, hệ thống sẽ cập nhật</li>
                <li>Định dạng: .xlsx, .xls, .csv (tối đa 5MB)</li>
              </ul>
              <button
                @click="downloadTemplate"
                class="mt-3 text-sm text-blue-600 hover:underline font-medium flex items-center"
              >
                <SvgIcon name="download" class="w-4 h-4 mr-1" />
                Tải file mẫu
              </button>
            </div>

            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2"
                >Chọn file Excel/CSV</label
              >
              <input
                ref="fileInputRef"
                type="file"
                accept=".xlsx,.xls,.csv"
                @change="handleFileChange"
                class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-300 rounded p-2"
              />
              <p v-if="importFile" class="mt-2 text-sm text-green-600">
                ✓ Đã chọn: <strong>{{ importFile.name }}</strong> ({{
                  (importFile.size / 1024).toFixed(2)
                }}
                KB)
              </p>
            </div>

            <div v-if="importResult" class="mt-4">
              <div class="grid grid-cols-2 gap-3 mb-3">
                <div class="bg-green-50 border border-green-200 rounded p-3">
                  <p class="text-sm text-green-700">Thành công</p>
                  <p class="text-2xl font-bold text-green-600">{{ importResult.success_count }}</p>
                </div>
                <div class="bg-red-50 border border-red-200 rounded p-3">
                  <p class="text-sm text-red-700">Lỗi</p>
                  <p class="text-2xl font-bold text-red-600">{{ importResult.fail_count }}</p>
                </div>
              </div>

              <div
                v-if="importResult.errors && importResult.errors.length"
                class="border border-red-200 rounded overflow-hidden"
              >
                <div class="bg-red-50 px-3 py-2 text-sm font-medium text-red-800">
                  Chi tiết lỗi ({{ importResult.errors.length }} dòng):
                </div>
                <div class="max-h-72 overflow-y-auto">
                  <table class="w-full text-sm">
                    <thead class="bg-gray-50 sticky top-0">
                      <tr>
                        <th class="p-2 text-left w-12">Dòng</th>
                        <th class="p-2 text-left">Dữ liệu</th>
                        <th class="p-2 text-left">Lỗi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(err, idx) in importResult.errors" :key="idx" class="border-t">
                        <td class="p-2 font-mono align-top">{{ err.row }}</td>
                        <td class="p-2 text-xs text-gray-600 align-top">
                          <div>
                            <strong>{{ err.values?.code }}</strong> - {{ err.values?.name }}
                          </div>
                          <div class="text-gray-400">
                            HK: {{ err.values?.semester_code }} | GV:
                            {{ err.values?.lecturer_email || '(trống)' }}
                          </div>
                          <div class="text-gray-400">
                            Môn: {{ err.values?.subject_codes }} ({{
                              err.values?.max_members_list
                            }})
                          </div>
                        </td>
                        <td class="p-2 text-xs text-red-600 align-top">
                          <div v-for="(e, i) in (err.errors || []).flat()" :key="i">{{ e }}</div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <div class="p-6 border-t flex justify-end gap-2">
            <button
              @click="closeImportModal"
              :disabled="importing"
              class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded disabled:opacity-50"
            >
              Đóng
            </button>
            <button
              @click="handleImport"
              :disabled="!importFile || importing"
              class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50 flex items-center"
            >
              <span v-if="importing" class="mr-2">
                <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                  <circle
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                    opacity="0.25"
                  />
                  <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" />
                </svg>
              </span>
              {{ importing ? 'Đang import...' : 'Bắt đầu Import' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

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
      @confirm="_handleConfirm"
      @cancel="_handleCancel"
    />
  </div>
</template>
