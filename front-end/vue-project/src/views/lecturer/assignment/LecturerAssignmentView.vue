<!-- src/views/lecturer/LecturerAssignmentView.vue -->
<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-slate-800">Quản lý nộp bài</h2>
        <p class="text-base text-slate-500 mt-1">Tạo và theo dõi đợt nộp bài của lớp</p>
      </div>
      <button
        v-if="!selectedAssignment"
        @click="openCreate"
        class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-base font-semibold hover:bg-indigo-700 transition flex items-center gap-2"
      >
        <SvgIcon name="plus" class="w-4 h-4" />
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

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useLecturerAssignmentStore } from '@/stores/lecturer/lecturerAssignmentStore'
import { useLecturerStore } from '@/stores/lecturer/lecturerStore'
import { useToastStore } from '@/stores/toast'
import AssignmentDetail from '../components/assignment/AssignmentDetail.vue'
import AssignmentFormModal from '../components/assignment/AssignmentFormModal.vue'
import AssignmentList from '../components/assignment/AssignmentList.vue'
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

async function handleDelete(assignment) {
  if (!assignment || !assignment.id) {
    toast.error('Đợt nộp không hợp lệ')
    return
  }

  const ok = await confirmDelete(assignment.title, {
    title: 'Xóa đợt nộp bài',
    message: 'Hành động này sẽ xóa đợt nộp cùng tất cả bài nộp của sinh viên. Không thể hoàn tác.',
    warningText:
      assignment.submission_count > 0
        ? `${assignment.submission_count} bài nộp sẽ bị xóa theo`
        : 'Đợt này chưa có bài nộp nào',
    confirmText: 'Xóa đợt nộp',
  })

  if (!ok) return

  setConfirmLoading(true)
  try {
    const result = await store.deleteAssignment(assignment.id)
    if (result.success) {
      toast.success('Đã xóa đợt nộp bài')
      if (selectedAssignment.value?.id === assignment.id) {
        selectedAssignment.value = null
      }
    } else {
      toast.error(result.message ?? 'Lỗi khi xóa')
    }
  } catch (e) {
    toast.error(e.response?.data?.message ?? 'Lỗi khi xóa')
  } finally {
    closeConfirm()
  }
}

function onSaved() {
  // Reload danh sách sau khi tạo/sửa
  if (classId.value) store.fetchByClass(classId.value)
}
</script>
