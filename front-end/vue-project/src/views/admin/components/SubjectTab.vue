<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
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

// State
const subjects = ref([])
const filterFaculty = ref('')
const filterMajor = ref('')
const availableMajors = ref([])

// Modal CRUD
const showModal = ref(false)
const isEditing = ref(false)
const form = reactive({ id: null, major_id: '', code: '', name: '', credits: 3 })

// ===== IMPORT STATE =====
const showImportModal = ref(false)
const importFile = ref(null)
const importing = ref(false)
const importResult = ref(null)
const fileInputRef = ref(null)

// Logic lọc
watch(filterFaculty, async (newVal) => {
  filterMajor.value = ''
  subjects.value = []
  if (newVal) {
    availableMajors.value = await store.fetchMajors(newVal)
  } else {
    availableMajors.value = []
  }
})

watch(filterMajor, loadSubjects)

async function loadSubjects() {
  if (!filterMajor.value) {
    subjects.value = []
    return
  }
  try {
    const res = await categoryApi.getSubjects({ major_id: filterMajor.value })
    subjects.value = res.data
  } catch (e) {
    console.error(e)
  }
}

// CRUD
const openModal = (item = null) => {
  if (item) {
    isEditing.value = true
    Object.assign(form, item)
  } else {
    if (!filterMajor.value) {
      toast.error('Vui lòng chọn Ngành trước khi thêm môn.')
      return
    }
    isEditing.value = false
    Object.assign(form, {
      id: null,
      major_id: filterMajor.value,
      code: '',
      name: '',
      credits: 3,
    })
  }
  showModal.value = true
}

const handleSave = async () => {
  try {
    if (isEditing.value) {
      await categoryApi.updateSubject(form.id, form)
      toast.success('Cập nhật môn học thành công')
    } else {
      await categoryApi.createSubject(form)
      toast.success('Thêm môn học thành công')
    }
    showModal.value = false
    loadSubjects()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Có lỗi xảy ra')
  }
}

const handleDelete = async (subject) => {
  const ok = await confirmDelete(subject.name, {
    title: 'Xóa môn học',
    message:
      'Hành động này sẽ xóa môn học cùng tất cả lớp và tài liệu liên quan. Không thể hoàn tác.',
    warningText: `Mã môn: ${subject.code}${subject.credits ? ` · ${subject.credits} tín chỉ` : ''}`,
    confirmText: 'Xóa môn học',
  })

  if (!ok) return

  setConfirmLoading(true)
  try {
    await categoryApi.deleteSubject(subject.id)
    toast.success('Đã xóa môn học')
    loadSubjects()
  } catch (e) {
    toast.error(e.response?.data?.message || 'Không thể xóa môn học')
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

    const res = await categoryApi.importSubjects(formData)
    importResult.value = res.data.data

    if (importResult.value.fail_count > 0) {
      toast.warning(
        `Import xong: ${importResult.value.success_count} thành công, ${importResult.value.fail_count} lỗi`,
      )
    } else {
      toast.success(`Import thành công ${importResult.value.success_count} môn học`)
    }

    // Refresh nếu đang xem ngành nào đó
    if (filterMajor.value) loadSubjects()
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
    const res = await categoryApi.downloadSubjectTemplate()
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'mau_import_mon_hoc.csv')
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
          <option value="">-- Chọn Khoa --</option>
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
          <option value="">-- Chọn Ngành --</option>
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
          @click="openModal()"
          class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full flex justify-center items-center disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="!filterMajor"
          title="Vui lòng chọn Ngành trước"
        >
          <SvgIcon name="plus" class="w-4 h-4 mr-2" /> Thêm Môn Học
        </button>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full border-collapse min-w-[600px]">
        <thead>
          <tr class="bg-white border-b border-gray-200 text-sm">
            <th class="p-3 text-left w-32">Mã Môn</th>
            <th class="p-3 text-left">Tên Môn Học</th>
            <th class="p-3 text-center w-24">Số TC</th>
            <th class="p-3 text-right w-32">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="subjects.length === 0">
            <td colspan="4" class="p-6 text-center text-gray-500">
              {{ filterMajor ? 'Chưa có môn học nào.' : 'Vui lòng chọn Ngành để xem danh sách.' }}
            </td>
          </tr>

          <tr v-for="s in subjects" :key="s.id" class="border-b hover:bg-gray-50 text-sm">
            <td class="p-3 font-mono text-blue-600 font-bold">{{ s.code }}</td>
            <td class="p-3 font-medium">{{ s.name }}</td>
            <td class="p-3 text-center">
              <span class="bg-gray-100 text-gray-800 text-base font-bold px-2 py-1 rounded">{{
                s.credits
              }}</span>
            </td>
            <td class="p-3 text-right space-x-2">
              <button @click="openModal(s)" class="text-indigo-600 hover:underline">
                <SvgIcon name="edit" class="h-4 w-4 mr-1" />
                Sửa
              </button>
              <button @click="handleDelete(s)" class="text-red-600 hover:underline">
                <SvgIcon name="trash" class="h-4 w-4 mr-1" />
                Xóa
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal Thêm / Sửa -->
    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      >
        <div class="bg-white p-6 rounded-lg w-[500px] shadow-xl animate-fade-in-up">
          <div class="flex justify-between items-center mb-4 border-b pb-2">
            <h3 class="text-lg font-bold">
              {{ isEditing ? 'Cập nhật Môn học' : 'Thêm Môn học mới' }}
            </h3>
            <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>

          <form @submit.prevent="handleSave" class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-1"
                >Mã môn học <span class="text-red-500">*</span></label
              >
              <input
                v-model="form.code"
                class="w-full border rounded p-2 uppercase"
                placeholder="VD: CSE101"
                required
              />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1"
                >Tên môn học <span class="text-red-500">*</span></label
              >
              <input
                v-model="form.name"
                class="w-full border rounded p-2"
                placeholder="VD: Nhập môn lập trình"
                required
              />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1"
                >Số tín chỉ <span class="text-red-500">*</span></label
              >
              <input
                v-model="form.credits"
                type="number"
                min="1"
                max="10"
                class="w-full border rounded p-2"
                required
              />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Thuộc Ngành</label>
              <select
                v-model="form.major_id"
                class="w-full border rounded p-2 bg-gray-100"
                disabled
              >
                <option v-for="m in availableMajors" :key="m.id" :value="m.id">{{ m.name }}</option>
              </select>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t">
              <button
                type="button"
                @click="showModal = false"
                class="px-4 py-2 text-gray-600 bg-gray-100 rounded hover:bg-gray-200"
              >
                Hủy
              </button>
              <button
                type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
              >
                Lưu
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal Import -->
    <Teleport to="body">
      <div
        v-if="showImportModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      >
        <div class="bg-white rounded-lg w-[600px] max-h-[90vh] flex flex-col shadow-xl">
          <div class="p-6 border-b">
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-bold">Import danh sách Môn học</h3>
              <button @click="closeImportModal" class="text-gray-400 hover:text-gray-600">
                <SvgIcon name="x" class="w-5 h-5" />
              </button>
            </div>
          </div>

          <div class="p-6 overflow-y-auto flex-1">
            <div class="bg-blue-50 border border-blue-200 rounded p-4 mb-4">
              <p class="text-sm text-blue-800 font-medium mb-2">📌 Hướng dẫn:</p>
              <ul class="text-sm text-blue-700 list-disc list-inside space-y-1">
                <li>
                  File phải có 4 cột: <strong>major_code</strong>, <strong>code</strong>,
                  <strong>name</strong>, <strong>credits</strong>
                </li>
                <li><strong>major_code</strong> là mã ngành đã có sẵn (VD: 7480201, 7340101...)</li>
                <li><strong>credits</strong> là số tín chỉ (1 - 10)</li>
                <li>Định dạng cho phép: .xlsx, .xls, .csv (tối đa 5MB)</li>
                <li>Nếu mã môn đã tồn tại trong cùng ngành, hệ thống sẽ cập nhật</li>
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
                          {{ err.values?.major_code }} | {{ err.values?.code }} -
                          {{ err.values?.name }} ({{ err.values?.credits }}tc)
                        </td>
                        <td class="p-2 text-xs text-red-600">
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

<style scoped>
.animate-fade-in-up {
  animation: fadeInUp 0.3s ease-out;
}
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
