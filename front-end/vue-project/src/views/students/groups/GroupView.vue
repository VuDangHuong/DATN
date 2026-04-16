<!-- src/views/groups/GroupView.vue -->
<template>
  <div>
    <div class="flex items-center justify-between mb-8">
      <div>
        <h2 class="text-2xl font-bold text-slate-800">Quản lý nhóm</h2>
        <p class="text-slate-500 mt-1">Tạo nhóm, thêm thành viên và quản lý nhóm của bạn</p>
      </div>
      <button v-if="!myGroup && selectedClass" @click="showCreateModal = true" class="btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 4v16m8-8H4"
          />
        </svg>
        Tạo nhóm mới
      </button>
    </div>

    <!-- ══════════════════════════════════════════ -->
    <!-- Current group detail                       -->
    <!-- ══════════════════════════════════════════ -->
    <div v-if="currentGroup" class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
      <!-- Group header with actions -->
      <div class="p-6 bg-gradient-to-r from-indigo-500 to-purple-600 text-white">
        <div class="flex items-start justify-between">
          <div>
            <h3 class="text-xl font-bold">{{ currentGroup.name }}</h3>
            <p class="text-indigo-100 mt-1 text-sm">
              {{ currentGroup.member_count }} thành viên
              <span class="mx-2">·</span>
              Mã mời: <span class="font-mono font-bold">{{ currentGroup.invitation_code }}</span>
            </p>
          </div>

          <!-- Action buttons (leader) -->
          <div class="flex items-center gap-2">
            <span
              class="px-3 py-1 rounded-full text-xs font-bold"
              :class="
                currentGroup.is_locked ? 'bg-red-500/20 text-red-100' : 'bg-white/20 text-white'
              "
            >
              {{ currentGroup.is_locked ? '🔒 Đã khóa' : '🔓 Đang mở' }}
            </span>

            <!-- Dropdown menu -->
            <div class="relative" ref="dropdownRef">
              <button
                @click="showDropdown = !showDropdown"
                class="p-2 rounded-lg bg-white/10 hover:bg-white/20 transition-colors"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"
                  />
                </svg>
              </button>

              <div
                v-if="showDropdown"
                class="absolute right-0 top-full mt-2 w-56 bg-white rounded-xl shadow-xl border border-slate-200 py-2 z-20"
              >
                <!-- Leader actions -->
                <template v-if="isLeader">
                  <button @click="openEditModal" class="dropdown-item">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                      />
                    </svg>
                    Chỉnh sửa nhóm
                  </button>
                  <button @click="handleToggleLock" class="dropdown-item">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                      />
                    </svg>
                    {{ currentGroup.is_locked ? 'Mở khóa nhóm' : 'Khóa nhóm' }}
                  </button>
                  <button
                    @click="((showTransferModal = true), (showDropdown = false))"
                    class="dropdown-item"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                      />
                    </svg>
                    Chuyển quyền trưởng nhóm
                  </button>
                  <div class="border-t border-slate-100 my-1" />
                  <button
                    @click="handleDeleteGroup"
                    class="dropdown-item text-red-600 hover:bg-red-50"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                      />
                    </svg>
                    Xóa nhóm
                  </button>
                </template>

                <!-- Member actions -->
                <template v-if="!isLeader">
                  <button
                    @click="handleLeaveGroup"
                    class="dropdown-item text-red-600 hover:bg-red-50"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                      />
                    </svg>
                    Rời nhóm
                  </button>
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Leader info -->
      <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center">
          <span class="text-amber-600 text-sm">👑</span>
        </div>
        <div>
          <p class="text-xs text-slate-400">Nhóm trưởng</p>
          <p class="text-sm font-semibold text-slate-700">
            {{ currentGroup.leader?.name }} ({{ currentGroup.leader?.code }})
          </p>
        </div>
      </div>

      <!-- Members list -->
      <div class="p-6">
        <div class="flex items-center justify-between mb-4">
          <h4 class="font-semibold text-slate-700">
            Thành viên ({{ currentGroup.members?.length || 0 }})
          </h4>
          <button
            v-if="isLeader && !currentGroup.is_locked"
            @click="showAddMemberModal = true"
            class="text-sm text-indigo-600 hover:text-indigo-700 font-medium flex items-center gap-1"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
              />
            </svg>
            Thêm thành viên
          </button>
        </div>

        <div class="space-y-2">
          <div
            v-for="member in currentGroup.members"
            :key="member.id"
            class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <div
                class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold"
                :class="
                  member.role === 'leader'
                    ? 'bg-gradient-to-br from-amber-400 to-orange-500'
                    : 'bg-gradient-to-br from-slate-400 to-slate-500'
                "
              >
                {{ member.name?.charAt(0) }}
              </div>
              <div>
                <p class="text-sm font-medium text-slate-700">
                  {{ member.name }}
                  <span v-if="member.role === 'leader'" class="ml-1 text-amber-500 text-xs"
                    >👑 Leader</span
                  >
                </p>
                <p class="text-xs text-slate-400">{{ member.code }}</p>
              </div>
            </div>

            <!-- Xóa thành viên (leader only, không xóa chính mình) -->
            <button
              v-if="isLeader && member.role !== 'leader' && !currentGroup.is_locked"
              @click="handleRemoveMember(member)"
              class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
              title="Xóa khỏi nhóm"
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

      <!-- Quick actions -->
      <div class="px-6 pb-6 flex gap-3">
        <router-link
          :to="`/student/chat?group_id=${currentGroup.id}`"
          class="flex-1 py-2.5 bg-emerald-50 text-emerald-700 text-sm font-semibold rounded-xl text-center hover:bg-emerald-100 transition-colors"
        >
          💬 Chat nhóm
        </router-link>
        <router-link
          :to="`/student/tasks?group_id=${currentGroup.id}`"
          class="flex-1 py-2.5 bg-indigo-50 text-indigo-700 text-sm font-semibold rounded-xl text-center hover:bg-indigo-100 transition-colors"
        >
          📋 Công việc
        </router-link>
      </div>
    </div>

    <!-- ══════════════════════════════════════════ -->
    <!-- No group state                             -->
    <!-- ══════════════════════════════════════════ -->
    <div v-else-if="!loading" class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
      <div class="w-20 h-20 mx-auto mb-4 rounded-2xl bg-indigo-50 flex items-center justify-center">
        <svg
          class="w-10 h-10 text-indigo-400"
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
      </div>
      <h3 class="text-lg font-bold text-slate-700 mb-2">Bạn chưa có nhóm</h3>
      <p class="text-slate-500 mb-6">Hãy tạo nhóm mới hoặc chờ nhóm trưởng thêm bạn vào</p>
      <button @click="showCreateModal = true" class="btn-primary">Tạo nhóm mới</button>
    </div>

    <!-- ══════════════════════════════════════════ -->
    <!-- MODALS                                     -->
    <!-- ══════════════════════════════════════════ -->

    <!-- Modal: Tạo nhóm -->
    <Teleport to="body">
      <div v-if="showCreateModal" class="modal-overlay" @click.self="showCreateModal = false">
        <div class="modal-box">
          <h3 class="text-lg font-bold text-slate-800 mb-4">Tạo nhóm mới</h3>
          <div>
            <label class="label">Tên nhóm</label>
            <input
              v-model="newGroupName"
              type="text"
              placeholder="VD: Nhóm 1"
              class="input-field"
              @keyup.enter="handleCreateGroup"
            />
          </div>
          <div class="modal-actions">
            <button @click="showCreateModal = false" class="btn-cancel">Hủy</button>
            <button
              @click="handleCreateGroup"
              :disabled="!newGroupName.trim() || groupStore.loading"
              class="btn-primary flex-1 disabled:opacity-50"
            >
              {{ groupStore.loading ? 'Đang tạo...' : 'Tạo nhóm' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal: Chỉnh sửa nhóm -->
    <Teleport to="body">
      <div v-if="showEditModal" class="modal-overlay" @click.self="showEditModal = false">
        <div class="modal-box">
          <h3 class="text-lg font-bold text-slate-800 mb-4">Chỉnh sửa nhóm</h3>
          <div class="space-y-4">
            <div>
              <label class="label">Tên nhóm</label>
              <input v-model="editForm.name" type="text" class="input-field" />
            </div>
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
              <div>
                <p class="text-sm font-medium text-slate-700">Khóa nhóm</p>
                <p class="text-xs text-slate-400">Khi khóa, không thể thêm/xóa thành viên</p>
              </div>
              <button
                @click="editForm.is_locked = !editForm.is_locked"
                class="w-12 h-7 rounded-full transition-colors relative"
                :class="editForm.is_locked ? 'bg-red-500' : 'bg-slate-300'"
              >
                <span
                  class="absolute top-0.5 w-6 h-6 rounded-full bg-white shadow transition-transform"
                  :class="editForm.is_locked ? 'translate-x-5' : 'translate-x-0.5'"
                />
              </button>
            </div>
          </div>
          <div class="modal-actions">
            <button @click="showEditModal = false" class="btn-cancel">Hủy</button>
            <button
              @click="handleUpdateGroup"
              :disabled="groupStore.loading"
              class="btn-primary flex-1 disabled:opacity-50"
            >
              {{ groupStore.loading ? 'Đang lưu...' : 'Lưu thay đổi' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal: Thêm thành viên -->
    <Teleport to="body">
      <div v-if="showAddMemberModal" class="modal-overlay" @click.self="showAddMemberModal = false">
        <div class="modal-box">
          <h3 class="text-lg font-bold text-slate-800 mb-4">Thêm thành viên</h3>
          <div>
            <label class="label">Mã sinh viên</label>
            <input
              v-model="newMemberCode"
              type="text"
              placeholder="VD: 2251172367"
              class="input-field"
              @keyup.enter="handleAddMember"
            />
          </div>
          <p v-if="modalError" class="mt-2 text-sm text-red-500">{{ modalError }}</p>
          <div class="modal-actions">
            <button @click="showAddMemberModal = false" class="btn-cancel">Hủy</button>
            <button
              @click="handleAddMember"
              :disabled="!newMemberCode.trim()"
              class="btn-primary flex-1 disabled:opacity-50"
            >
              Thêm
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal: Chuyển quyền trưởng nhóm -->
    <Teleport to="body">
      <div v-if="showTransferModal" class="modal-overlay" @click.self="showTransferModal = false">
        <div class="modal-box">
          <h3 class="text-lg font-bold text-slate-800 mb-2">Chuyển quyền trưởng nhóm</h3>
          <p class="text-sm text-slate-500 mb-4">
            Chọn thành viên sẽ trở thành trưởng nhóm mới. Bạn sẽ trở thành thành viên thường.
          </p>

          <div class="space-y-2 max-h-60 overflow-y-auto">
            <button
              v-for="member in nonLeaderMembers"
              :key="member.id"
              @click="selectedTransferId = member.id"
              class="w-full flex items-center gap-3 p-3 rounded-xl border transition-all"
              :class="
                selectedTransferId === member.id
                  ? 'border-indigo-500 bg-indigo-50'
                  : 'border-slate-200 hover:bg-slate-50'
              "
            >
              <div
                class="w-9 h-9 rounded-full bg-gradient-to-br from-slate-400 to-slate-500 flex items-center justify-center text-white text-sm font-bold"
              >
                {{ member.name?.charAt(0) }}
              </div>
              <div class="text-left">
                <p class="text-sm font-medium text-slate-700">{{ member.name }}</p>
                <p class="text-xs text-slate-400">{{ member.code }}</p>
              </div>
              <div v-if="selectedTransferId === member.id" class="ml-auto text-indigo-600">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
            </button>
          </div>

          <p v-if="modalError" class="mt-2 text-sm text-red-500">{{ modalError }}</p>
          <div class="modal-actions">
            <button @click="showTransferModal = false" class="btn-cancel">Hủy</button>
            <button
              @click="handleTransferLeader"
              :disabled="!selectedTransferId"
              class="btn-primary flex-1 disabled:opacity-50"
            >
              Chuyển quyền
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useDashboardStore } from '@/stores/students/dashboardStore'
import { useGroupStore } from '@/stores/students/groupStore'
import { storeToRefs } from 'pinia'

const route = useRoute()
const router = useRouter()
const dashboardStore = useDashboardStore()
const groupStore = useGroupStore()
const { selectedClass, myGroup, selectedClassId } = storeToRefs(dashboardStore)
const { currentGroup, loading } = storeToRefs(groupStore)

// ── State ────────────────────────────────────
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showAddMemberModal = ref(false)
const showTransferModal = ref(false)
const showDropdown = ref(false)
const dropdownRef = ref(null)

const newGroupName = ref('')
const newMemberCode = ref('')
const editForm = ref({ name: '', is_locked: false })
const selectedTransferId = ref(null)
const modalError = ref('')

const user = JSON.parse(localStorage.getItem('user') || '{}')
const isLeader = computed(() => {
  return Number(currentGroup.value?.leader?.id) === Number(user.id)
})
const nonLeaderMembers = computed(() =>
  (currentGroup.value?.members || []).filter((m) => m.role !== 'leader'),
)
watch(currentGroup, (val) => {
  console.log('USER:', user)
  console.log('GROUP:', val)
  console.log('LEADER CODE:', val?.leader?.code)
  console.log('USER CODE:', user.code)
  console.log('isLeader:', isLeader.value)
})
// ── Load group ───────────────────────────────
watch(
  myGroup,
  (g) => {
    if (g?.id) groupStore.fetchGroupDetail(g.id)
  },
  { immediate: true },
)

onMounted(() => {
  const gid = route.params.groupId
  if (gid) groupStore.fetchGroupDetail(Number(gid))

  // Close dropdown on outside click
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})

function handleClickOutside(e) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    showDropdown.value = false
  }
}

// ── Handlers ─────────────────────────────────
async function handleCreateGroup() {
  if (!selectedClassId.value) return
  const result = await groupStore.createGroup(selectedClassId.value, newGroupName.value.trim())
  if (result.success) {
    showCreateModal.value = false
    newGroupName.value = ''
    dashboardStore.fetchMyClasses()
  }
}

function openEditModal() {
  showDropdown.value = false
  editForm.value = {
    name: currentGroup.value.name,
    is_locked: currentGroup.value.is_locked,
  }
  showEditModal.value = true
}

async function handleUpdateGroup() {
  const result = await groupStore.updateGroup(currentGroup.value.id, editForm.value)
  if (result.success) {
    showEditModal.value = false
    dashboardStore.fetchMyClasses()
  }
}

async function handleToggleLock() {
  showDropdown.value = false
  const newLocked = !currentGroup.value.is_locked
  await groupStore.updateGroup(currentGroup.value.id, { is_locked: newLocked })
}

async function handleDeleteGroup() {
  showDropdown.value = false
  if (!confirm('⚠️ Xóa nhóm sẽ xóa toàn bộ tin nhắn, công việc và dữ liệu nhóm. Tiếp tục?')) return
  const result = await groupStore.deleteGroup(currentGroup.value.id)
  if (result.success) {
    dashboardStore.fetchMyClasses()
    router.push('/student/dashboard')
  }
}

async function handleLeaveGroup() {
  showDropdown.value = false
  if (!confirm('Bạn chắc chắn muốn rời nhóm?')) return
  const result = await groupStore.leaveGroup(currentGroup.value.id)
  if (result.success) {
    dashboardStore.fetchMyClasses()
    router.push('/student/dashboard')
  }
}

async function handleAddMember() {
  modalError.value = ''
  const result = await groupStore.addMember(currentGroup.value.id, newMemberCode.value.trim())
  if (result.success) {
    showAddMemberModal.value = false
    newMemberCode.value = ''
  } else {
    modalError.value = result.message
  }
}

async function handleRemoveMember(member) {
  if (!confirm(`Xóa ${member.name} khỏi nhóm?`)) return
  await groupStore.removeMember(currentGroup.value.id, member.id)
}

async function handleTransferLeader() {
  modalError.value = ''
  if (!selectedTransferId.value) return
  if (!confirm('Bạn sẽ mất quyền nhóm trưởng. Tiếp tục?')) return
  const result = await groupStore.transferLeader(currentGroup.value.id, selectedTransferId.value)
  if (result.success) {
    showTransferModal.value = false
    selectedTransferId.value = null
    dashboardStore.fetchMyClasses()
  } else {
    modalError.value = result.message
  }
}
</script>

<style>
.btn-primary {
  @apply px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-semibold
         hover:bg-indigo-700 transition-colors flex items-center gap-2 justify-center;
}
.btn-cancel {
  @apply flex-1 py-2.5 border border-slate-200 rounded-xl text-sm font-medium
         text-slate-600 hover:bg-slate-50 transition-colors;
}
.input-field {
  @apply w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm
         focus:ring-2 focus:ring-indigo-500 focus:border-transparent;
}
.label {
  @apply block text-sm font-medium text-slate-600 mb-1;
}
.modal-overlay {
  @apply fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/30 backdrop-blur-sm;
}
.modal-box {
  @apply relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6;
}
.modal-actions {
  @apply flex gap-3 mt-6;
}
.dropdown-item {
  @apply w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700
         hover:bg-slate-50 transition-colors text-left;
}
</style>
