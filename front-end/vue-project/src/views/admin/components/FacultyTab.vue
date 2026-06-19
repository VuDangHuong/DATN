<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useCategoryStore } from '@/stores/admin/category'
import { categoryApi } from '@/api/admin/category'
import { useToastStore } from '@/stores/toast'
import SvgIcon from '@/components/icons/SVG.vue'
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

const isEditing = ref(false)
const showModal = ref(false)
const form = reactive({ id: null, code: '', name: '' })

// ===== IMPORT STATE =====
const showImportModal = ref(false)
const importFile = ref(null)
const importing = ref(false)
const importResult = ref(null) // { success_count, fail_count, errors }
const fileInputRef = ref(null)
//page
const currentPage = ref(1)
const searchQuery = ref('')
const facultyPagination = computed(() => store.facultyPagination)
const debounce = (fn, d = 400) => {
  let t
  return (...a) => {
    clearTimeout(t)
    t = setTimeout(() => fn(...a), d)
  }
}

const loadFaculties = () => {
  store.fetchFaculties({
    paginate: 1,
    page: currentPage.value,
    search: searchQuery.value,
  })
}

const onSearch = debounce(() => {
  currentPage.value = 1
  loadFaculties()
}, 400)

const goToPage = (page) => {
  if (page < 1 || page > facultyPagination.value.last_page || page === currentPage.value) return
  currentPage.value = page
  loadFaculties()
}

const pageNumbers = computed(() => {
  const last = facultyPagination.value.last_page
  const cur = currentPage.value
  const pages = []
  for (let i = Math.max(1, cur - 2); i <= Math.min(last, cur + 2); i++) pages.push(i)
  return pages
})
onMounted(loadFaculties)
// Actions
const openModal = (item = null) => {
  if (item) {
    isEditing.value = true
    Object.assign(form, item)
  } else {
    isEditing.value = false
    Object.assign(form, { id: null, code: '', name: '' })
  }
  showModal.value = true
}

const handleSave = async () => {
  try {
    if (isEditing.value) {
      await categoryApi.updateFaculty(form.id, form)
      toast.success('Cập nhật khoa thành công')
    } else {
      await categoryApi.createFaculty(form)
      toast.success('Thêm khoa thành công')
    }
    loadFaculties()
    showModal.value = false
  } catch (e) {
    toast.error(e.response?.data?.message || 'Có lỗi xảy ra')
  }
}

const handleDelete = async (faculty) => {
  const ok = await confirmDelete(faculty.name, {
    title: 'Xóa khoa',
    message:
      'Hành động này sẽ xóa khoa cùng tất cả ngành, môn học và lớp thuộc khoa. Không thể hoàn tác.',
    warningText: `Mã khoa: ${faculty.code}`,
    confirmText: 'Xóa khoa',
  })

  if (!ok) return

  setConfirmLoading(true)
  try {
    await categoryApi.deleteFaculty(faculty.id)
    toast.success('Đã xóa khoa')
    loadFaculties()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Không thể xóa')
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

  const allowedExt = ['xlsx', 'xls', 'csv']
  const ext = file.name.split('.').pop().toLowerCase()
  if (!allowedExt.includes(ext)) {
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

    const res = await categoryApi.importFaculties(formData)
    importResult.value = res.data.data

    if (importResult.value.fail_count > 0) {
      toast.warning(
        `Import xong: ${importResult.value.success_count} thành công, ${importResult.value.fail_count} lỗi`,
      )
    } else {
      toast.success(`Import thành công ${importResult.value.success_count} khoa`)
    }

    loadFaculties()
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
    const res = await categoryApi.downloadFacultyTemplate()
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'mau_import_khoa.csv')
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
    <div class="flex justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-700">Danh sách Khoa / Viện</h3>
      <div class="flex gap-2">
        <input
          v-model="searchQuery"
          @input="onSearch"
          type="text"
          placeholder="Tìm mã hoặc tên khoa..."
          class="border border-gray-300 rounded px-3 py-2 text-sm w-56 focus:ring-2 focus:ring-blue-500 outline-none"
        />
        <button
          @click="openImportModal"
          class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 flex items-center"
        >
          <SvgIcon name="upload" class="w-4 h-4 mr-2" /> Import Excel
        </button>
        <button
          @click="openModal()"
          class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center"
        >
          <SvgIcon name="plus" class="w-4 h-4 mr-2" /> Thêm Khoa
        </button>
      </div>
    </div>

    <table class="w-full border-collapse">
      <thead>
        <tr class="bg-gray-50 border-b">
          <th class="p-3 text-left">Mã Khoa</th>
          <th class="p-3 text-left">Tên Khoa</th>
          <th class="p-3 text-right">Hành động</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in store.faculties" :key="item.id" class="border-b hover:bg-gray-50">
          <td class="p-3 font-mono text-blue-600">{{ item.code }}</td>
          <td class="p-3 font-medium">{{ item.name }}</td>
          <td class="p-3 text-right space-x-2">
            <button @click="openModal(item)" class="text-indigo-600 hover:underline">
              <SvgIcon name="edit" class="h-4 w-4 mr-1" />
              Sửa
            </button>
            <button @click="handleDelete(item)" class="text-red-600 hover:underline">
              <SvgIcon name="trash" class="h-4 w-4 mr-1" />
              Xóa
            </button>
          </td>
        </tr>
      </tbody>
    </table>
    <div
      v-if="facultyPagination.total > 0"
      class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-4 px-1"
    >
      <p class="text-sm text-gray-600">
        Hiển thị
        <span class="font-medium">{{
          (facultyPagination.current_page - 1) * facultyPagination.per_page + 1
        }}</span>
        –
        <span class="font-medium">{{
          Math.min(
            facultyPagination.current_page * facultyPagination.per_page,
            facultyPagination.total,
          )
        }}</span>
        / <span class="font-medium">{{ facultyPagination.total }}</span> khoa
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
          :disabled="currentPage >= facultyPagination.last_page"
          class="px-3 py-1.5 rounded-lg border border-gray-300 text-sm text-gray-600 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed"
        >
          Sau
        </button>
      </div>
    </div>
    <!-- Modal Thêm / Sửa -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white p-6 rounded-lg w-96">
        <h3 class="text-lg font-bold mb-4">{{ isEditing ? 'Sửa Khoa' : 'Thêm Khoa' }}</h3>
        <input
          v-model="form.code"
          placeholder="Mã khoa (VD: CNTT)"
          class="w-full mb-3 p-2 border rounded"
        />
        <input v-model="form.name" placeholder="Tên khoa" class="w-full mb-4 p-2 border rounded" />
        <div class="flex justify-end gap-2">
          <button
            @click="showModal = false"
            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded"
          >
            Hủy
          </button>
          <button @click="handleSave" class="px-4 py-2 bg-blue-600 text-white rounded">Lưu</button>
        </div>
      </div>
    </div>

    <!-- Modal Import -->
    <div
      v-if="showImportModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white rounded-lg w-[600px] max-h-[90vh] flex flex-col">
        <!-- Header -->
        <div class="p-6 border-b">
          <div class="flex justify-between items-center">
            <h3 class="text-lg font-bold">Import danh sách Khoa</h3>
            <button @click="closeImportModal" class="text-gray-400 hover:text-gray-600">
              <SvgIcon name="x" class="w-5 h-5" />
            </button>
          </div>
        </div>

        <!-- Body -->
        <div class="p-6 overflow-y-auto flex-1">
          <!-- Hướng dẫn -->
          <div class="bg-blue-50 border border-blue-200 rounded p-4 mb-4">
            <p class="text-sm text-blue-800 font-medium mb-2">📌 Hướng dẫn:</p>
            <ul class="text-sm text-blue-700 list-disc list-inside space-y-1">
              <li>File phải có 2 cột: <strong>code</strong> và <strong>name</strong></li>
              <li>Định dạng cho phép: .xlsx, .xls, .csv (tối đa 5MB)</li>
              <li>Nếu mã khoa đã tồn tại, hệ thống sẽ cập nhật tên khoa</li>
            </ul>
            <button
              @click="downloadTemplate"
              class="mt-3 text-sm text-blue-600 hover:underline font-medium flex items-center"
            >
              <SvgIcon name="download" class="w-4 h-4 mr-1" />
              Tải file mẫu
            </button>
          </div>

          <!-- Chọn file -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Chọn file Excel/CSV</label>
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

          <!-- Kết quả Import -->
          <div v-if="importResult" class="mt-4">
            <div class="grid grid-cols-2 gap-3 mb-3">
              <div class="bg-green-50 border border-green-200 rounded p-3">
                <p class="text-sm text-green-700">Thành công</p>
                <p class="text-2xl font-bold text-green-600">
                  {{ importResult.success_count }}
                </p>
              </div>
              <div class="bg-red-50 border border-red-200 rounded p-3">
                <p class="text-sm text-red-700">Lỗi</p>
                <p class="text-2xl font-bold text-red-600">
                  {{ importResult.fail_count }}
                </p>
              </div>
            </div>

            <!-- Chi tiết lỗi -->
            <div
              v-if="importResult.errors && importResult.errors.length"
              class="border border-red-200 rounded overflow-hidden"
            >
              <div class="bg-red-50 px-3 py-2 text-sm font-medium text-red-800">
                Chi tiết lỗi ({{ importResult.errors.length }} dòng):
              </div>
              <div class="max-h-60 overflow-y-auto">
                <table class="w-full text-sm">
                  <thead class="bg-gray-50 sticky top-0">
                    <tr>
                      <th class="p-2 text-left">Dòng</th>
                      <th class="p-2 text-left">Dữ liệu</th>
                      <th class="p-2 text-left">Lỗi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(err, idx) in importResult.errors" :key="idx" class="border-t">
                      <td class="p-2 font-mono">{{ err.row }}</td>
                      <td class="p-2 text-xs text-gray-600">
                        {{ err.values?.code }} - {{ err.values?.name }}
                      </td>
                      <td class="p-2 text-xs text-red-600">
                        <div v-for="(e, i) in (err.errors || [err.errors]).flat()" :key="i">
                          {{ e }}
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
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
