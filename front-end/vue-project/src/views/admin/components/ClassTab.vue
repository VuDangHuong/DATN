<script setup>
import { ref, watch, onMounted } from 'vue'
import { useCategoryStore } from '@/stores/admin/category'
import { categoryApi } from '@/api/admin/category'
import { useToastStore } from '@/stores/toast'
import SvgIcon from '@/components/icons/SVG.vue'
import ClassModal from './ClassModal.vue'

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

//LOGIC FILTER
watch(filterFaculty, async (newVal) => {
  filterMajor.value = ''
  subjectList.value = []
  availableMajors.value = newVal ? await store.fetchMajors(newVal) : []
  loadClasses()
})

watch(filterMajor, async (newVal) => {
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
  const params = {}
  if (filterMajor.value) params.major_id = filterMajor.value
  try {
    const res = await categoryApi.getClasses(params)
    classes.value = res.data
  } catch (e) {
    console.error(e)
  }
}

//CRUD ACTIONS
// Mở Modal Tạo Mới
const openCreate = () => {
  if (!filterMajor.value) {
    toast.error('Vui lòng chọn Khoa và Ngành trước để tải danh sách môn học.')
    return
  }
  editingItem.value = null
  showModal.value = true
}

// Mở Modal Sửa
const openEdit = (item) => {
  editingItem.value = item
  showModal.value = true
}

// Xử lý Lưu (Create/Update)
const handleSave = async (formData) => {
  try {
    if (editingItem.value) {
      // Update
      await categoryApi.updateClass(formData.id, formData)
      toast.success('Cập nhật lớp thành công')
    } else {
      // Create
      await categoryApi.createClass(formData)
      toast.success('Mở lớp học phần thành công')
    }
    showModal.value = false
    loadClasses() // Refresh bảng
  } catch (e) {
    toast.error(e.response?.data?.message || 'Có lỗi xảy ra')
    console.error(e)
  }
}

// Xóa
const handleDelete = async (id) => {
  if (!confirm('Bạn chắc chắn muốn hủy lớp học phần này?')) return
  try {
    await categoryApi.deleteClass(id)
    toast.success('Đã hủy lớp')
    loadClasses()
  } catch (e) {
    toast.error(e.response?.data?.message)
  }
}
</script>

<template>
  <div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 bg-gray-50 p-4 rounded-lg">
      <div>
        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Lọc Khoa</label>
        <select v-model="filterFaculty" class="w-full border rounded p-2 text-sm">
          <option value="">-- Tất cả --</option>
          <option v-for="f in store.faculties" :key="f.id" :value="f.id">{{ f.name }}</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Lọc Ngành</label>
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
          @click="openCreate"
          class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full flex justify-center items-center disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="!filterMajor"
          title="Vui lòng chọn Ngành trước"
        >
          <SvgIcon name="plus" class="w-4 h-4 mr-2" /> Mở Lớp Học Phần
        </button>
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
              <div class="text-xs text-gray-500">{{ c.name }}</div>
            </td>
            <td class="p-3">
              <div class="font-medium">{{ c.subject?.name || '---' }}</div>
              <div class="text-xs text-gray-500 mt-0.5">
                {{ c.semester?.name }} ({{ c.semester?.year }})
              </div>
            </td>
            <td class="p-3">
              <div v-if="c.teacher" class="flex items-center">
                <span class="font-medium text-gray-800">{{ c.teacher.name }}</span>
              </div>
              <span v-else class="text-gray-400 italic">Chưa phân công</span>
            </td>
            <td class="p-3 text-center">
              <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">
                0 / {{ c.max_members }}
              </span>
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
                @click="handleDelete(c.id)"
                class="text-red-600 hover:text-red-800 font-medium text-sm"
              >
                <SvgIcon name="trash" class="h-4 w-4 mr-1" />
                Hủy
              </button>
            </td>
          </tr>
        </tbody>
      </table>
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
  </div>
</template>
