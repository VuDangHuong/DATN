<!-- src/views/lecturer/LecturerAssignmentView.vue -->
<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-slate-800">Quản lý nộp bài</h2>
        <p class="text-sm text-slate-500 mt-1">Tạo và theo dõi đợt nộp bài của lớp</p>
      </div>
      <button
        v-if="!selectedAssignment"
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

    <!-- Danh sách đợt nộp -->
    <AssignmentList
      v-if="!selectedAssignment"
      @select="openDetail"
      @edit="openEdit"
      @delete="handleDelete"
    />

    <!-- Chi tiết đợt nộp -->
    <AssignmentDetail v-else @back="selectedAssignment = null" />

    <!-- Modal tạo / sửa -->
    <AssignmentFormModal
      :show="showForm"
      :editing-id="editingId"
      :initial="editingData"
      :class-id="classId"
      @close="showForm = false"
      @saved="onSaved"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useLecturerAssignmentStore } from '@/stores/lecturer/lecturerAssignmentStore'
import { useLecturerStore } from '@/stores/lecturer/lecturerStore'
import { useToastStore } from '@/stores/toast'
import AssignmentDetail from '../components/assignment/AssignmentDetail.vue'
import AssignmentFormModal from '../components/assignment/AssignmentFormModal.vue'
import AssignmentList from '../components/assignment/AssignmentList.vue'

const store = useLecturerAssignmentStore()
const lecturerStore = useLecturerStore()
const toast = useToastStore()

const selectedAssignment = ref(null)
const showForm = ref(false)
const editingId = ref(null)
const editingData = ref(null)

const classId = computed(() => lecturerStore.selectedClassId)

onMounted(async () => {
  if (classId.value) store.fetchByClass(classId.value)
  await store.loadDocumentCategories()
})

watch(classId, (id) => {
  if (id) store.fetchByClass(id)
  selectedAssignment.value = null
})

function openCreate() {
  editingId.value = null
  editingData.value = null
  showForm.value = true
}

function openEdit(assignment) {
  editingId.value = assignment.id
  editingData.value = assignment
  showForm.value = true
}

async function openDetail(assignment) {
  selectedAssignment.value = assignment
  await store.fetchDetail(assignment.id)
}

async function handleDelete(id) {
  if (!confirm('Xóa đợt nộp này?')) return
  const result = await store.deleteAssignment(id)
  if (result.success) {
    toast.success('Đã xóa đợt nộp bài')
    if (selectedAssignment.value?.id === id) selectedAssignment.value = null
  } else {
    toast.error(result.message ?? 'Lỗi khi xóa')
  }
}

function onSaved() {
  // Reload danh sách sau khi tạo/sửa
  if (classId.value) store.fetchByClass(classId.value)
}
</script>
