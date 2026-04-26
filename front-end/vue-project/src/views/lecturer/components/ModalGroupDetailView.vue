<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="$emit('close')" />
      <div
        class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[88vh] flex flex-col"
      >
        <!-- Header -->
        <div class="p-6 border-b border-slate-100 flex items-start justify-between flex-shrink-0">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <h3 class="text-lg font-bold text-slate-800">{{ group?.name }}</h3>
              <span
                class="px-2 py-0.5 text-[10px] font-bold rounded-full"
                :class="
                  group?.is_locked ? 'bg-red-100 text-red-600' : 'bg-emerald-100 text-emerald-700'
                "
              >
                {{ group?.is_locked ? 'Đã khóa' : 'Mở' }}
              </span>
            </div>
            <div class="flex items-center gap-3 text-xs text-slate-400">
              <span
                >Mã mời:
                <span class="font-mono font-semibold text-slate-600">{{
                  group?.invitation_code
                }}</span></span
              >
              <span>{{ localMembers.length }} thành viên</span>
            </div>
          </div>
          <button @click="$emit('close')" class="p-1.5 hover:bg-slate-100 rounded-lg transition">
            <svg
              class="w-5 h-5 text-slate-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>

        <!-- Tabs -->
        <div class="flex gap-1 px-6 pt-4 flex-shrink-0">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key"
            class="px-4 py-1.5 rounded-lg text-xs font-medium transition"
            :class="
              activeTab === tab.key
                ? 'bg-teal-100 text-teal-700'
                : 'text-slate-500 hover:bg-slate-100'
            "
          >
            {{ tab.label }}
          </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-6">
          <!-- ── Tab: Thành viên ── -->
          <div v-if="activeTab === 'members'" class="space-y-2">
            <div
              v-for="(member, idx) in localMembers"
              :key="member.id"
              class="flex items-center gap-3 p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition group"
            >
              <span class="text-xs text-slate-400 w-5 text-center flex-shrink-0">{{
                idx + 1
              }}</span>
              <div
                class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold flex-shrink-0"
                :class="
                  member.role === 'leader'
                    ? 'bg-teal-100 text-teal-700'
                    : 'bg-slate-100 text-slate-600'
                "
              >
                {{ member.name?.charAt(0) }}
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <p class="text-sm font-medium text-slate-800 truncate">{{ member.name }}</p>
                  <span
                    v-if="member.role === 'leader'"
                    class="px-1.5 py-0.5 bg-teal-100 text-teal-700 text-[10px] font-bold rounded flex-shrink-0"
                  >
                    👑 Trưởng nhóm
                  </span>
                </div>
                <p class="text-xs text-slate-400 font-mono mt-0.5">{{ member.code }}</p>
              </div>
              <div class="text-right flex-shrink-0 mr-2">
                <p class="text-[10px] text-slate-400">Tham gia</p>
                <p class="text-xs text-slate-500">{{ formatDate(member.joined_at) }}</p>
              </div>
              <!-- Xóa thành viên -->
              <button
                v-if="member.role !== 'leader'"
                @click="handleRemoveMember(member)"
                :disabled="removing === member.id"
                class="opacity-0 group-hover:opacity-100 transition p-1.5 rounded-lg hover:bg-red-50 text-slate-400 hover:text-red-500 flex-shrink-0"
                title="Xóa khỏi nhóm"
              >
                <div
                  v-if="removing === member.id"
                  class="w-3.5 h-3.5 border-2 border-red-300 border-t-red-500 rounded-full animate-spin"
                />
                <svg
                  v-else
                  class="w-3.5 h-3.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>

            <div v-if="!localMembers.length" class="text-center py-8 text-slate-400 text-sm">
              Nhóm chưa có thành viên
            </div>
          </div>

          <!-- ── Tab: Thêm sinh viên ── -->
          <div v-else-if="activeTab === 'add'">
            <!-- Toggle: nhập mã / chọn từ danh sách -->
            <div class="flex gap-1 bg-slate-100 rounded-xl p-1 mb-4">
              <button
                v-for="m in addModes"
                :key="m.key"
                @click="addMode = m.key"
                class="flex-1 py-1.5 rounded-lg text-xs font-medium transition"
                :class="
                  addMode === m.key
                    ? 'bg-white text-slate-800 shadow-sm'
                    : 'text-slate-500 hover:text-slate-700'
                "
              >
                {{ m.label }}
              </button>
            </div>

            <!-- Mode 1: Nhập mã SV -->
            <div v-if="addMode === 'code'" class="space-y-3">
              <div>
                <label class="block text-sm font-medium text-slate-600 mb-1">Mã sinh viên</label>
                <div class="flex gap-2">
                  <input
                    v-model="inputCode"
                    type="text"
                    placeholder="VD: 2251172367"
                    class="flex-1 px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent outline-none"
                    @keyup.enter="handleAddByCode"
                  />
                  <button
                    @click="handleAddByCode"
                    :disabled="!inputCode.trim() || adding"
                    class="px-4 py-2.5 bg-teal-600 text-white rounded-xl text-sm font-medium hover:bg-teal-700 disabled:opacity-50 transition flex items-center gap-2"
                  >
                    <div
                      v-if="adding"
                      class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
                    />
                    <span v-else>Thêm</span>
                  </button>
                </div>
              </div>
              <!-- Error/Success -->
              <p v-if="addError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">
                {{ addError }}
              </p>
              <p
                v-if="addSuccess"
                class="text-sm text-emerald-600 bg-emerald-50 px-3 py-2 rounded-lg"
              >
                {{ addSuccess }}
              </p>
            </div>

            <!-- Mode 2: Chọn từ danh sách SV trong lớp -->
            <div v-else class="space-y-3">
              <!-- Search -->
              <input
                v-model="searchStudent"
                type="text"
                placeholder="Tìm sinh viên..."
                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent outline-none"
              />

              <!-- Loading SV -->
              <div v-if="loadingStudents" class="flex justify-center py-6">
                <div
                  class="w-6 h-6 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin"
                />
              </div>

              <!-- Danh sách SV chưa trong nhóm -->
              <div v-else class="space-y-2 max-h-64 overflow-y-auto">
                <div
                  v-if="!filteredAvailableStudents.length"
                  class="text-center py-6 text-slate-400 text-sm"
                >
                  {{
                    searchStudent
                      ? 'Không tìm thấy sinh viên'
                      : 'Tất cả sinh viên đã có nhóm hoặc đã trong nhóm này'
                  }}
                </div>

                <div
                  v-for="sv in filteredAvailableStudents"
                  :key="sv.id"
                  class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 hover:border-teal-300 hover:bg-teal-50/30 transition cursor-pointer group"
                  @click="handleAddStudent(sv)"
                >
                  <div
                    class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-sm font-bold text-slate-600 flex-shrink-0"
                  >
                    {{ sv.name?.charAt(0) }}
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800 truncate">{{ sv.name }}</p>
                    <p class="text-xs text-slate-400 font-mono">{{ sv.code }}</p>
                  </div>
                  <div
                    v-if="adding === sv.id"
                    class="w-4 h-4 border-2 border-teal-300 border-t-teal-600 rounded-full animate-spin flex-shrink-0"
                  />
                  <svg
                    v-else
                    class="w-4 h-4 text-slate-400 group-hover:text-teal-600 transition flex-shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 4v16m8-8H4"
                    />
                  </svg>
                </div>
              </div>

              <p v-if="addError" class="text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">
                {{ addError }}
              </p>
            </div>
          </div>
        </div>

        <!-- Footer: nút thêm SV ở tab members -->
        <div
          v-if="activeTab === 'members'"
          class="px-6 py-4 border-t border-slate-100 flex-shrink-0"
        >
          <button
            @click="switchToAdd"
            class="w-full py-2.5 border-2 border-dashed border-teal-300 text-teal-600 rounded-xl text-sm font-medium hover:bg-teal-50 transition flex items-center justify-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              />
            </svg>
            Thêm sinh viên vào nhóm
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axiosClient from '@/api/axiosClient'

const props = defineProps({
  show: { type: Boolean, default: false },
  group: { type: Object, default: null },
  classId: { type: Number, default: null },
})

const emit = defineEmits(['close', 'updated'])

const activeTab = ref('members')
const addMode = ref('code')
const inputCode = ref('')
const searchStudent = ref('')
const adding = ref(null) // id hoặc true khi đang add
const removing = ref(null) // member.id đang xóa
const addError = ref('')
const addSuccess = ref('')
const loadingStudents = ref(false)

const localMembers = ref([])
const availableStudents = ref([]) // SV trong lớp chưa có nhóm/chưa trong nhóm này

const tabs = [
  { key: 'members', label: 'Thành viên' },
  { key: 'add', label: '+ Thêm SV' },
]

const addModes = [
  { key: 'code', label: 'Nhập mã SV' },
  { key: 'list', label: 'Chọn từ danh sách' },
]

// Sync members khi group thay đổi
watch(
  () => props.group,
  (g) => {
    if (g) {
      localMembers.value = g.members ? [...g.members] : []
      activeTab.value = 'members'
      addError.value = ''
      addSuccess.value = ''
      inputCode.value = ''
    }
  },
  { immediate: true },
)

// Load danh sách SV khi chuyển sang tab add mode list
watch([() => activeTab.value, () => addMode.value], ([tab, mode]) => {
  if (tab === 'add' && mode === 'list') {
    loadAvailableStudents()
  }
})

const filteredAvailableStudents = computed(() => {
  if (!searchStudent.value.trim()) return availableStudents.value
  const q = searchStudent.value.toLowerCase()
  return availableStudents.value.filter(
    (sv) => sv.name?.toLowerCase().includes(q) || sv.code?.toLowerCase().includes(q),
  )
})

async function loadAvailableStudents() {
  if (!props.classId) return
  loadingStudents.value = true
  try {
    const { data } = await axiosClient.get(`/lecturer/classes/${props.classId}/students`)
    const memberIds = new Set(localMembers.value.map((m) => m.id))
    // Lọc ra SV chưa có trong nhóm này
    const students = Array.isArray(data) ? data : (data.data ?? data.students ?? [])
    availableStudents.value = students.filter((sv) => !memberIds.has(sv.id))
  } catch {
    availableStudents.value = []
  } finally {
    loadingStudents.value = false
  }
}

function switchToAdd() {
  activeTab.value = 'add'
  addMode.value = 'code'
  addError.value = ''
  addSuccess.value = ''
  inputCode.value = ''
}

// Thêm bằng mã SV
async function handleAddByCode() {
  if (!inputCode.value.trim()) return
  addError.value = ''
  addSuccess.value = ''
  adding.value = true
  try {
    const { data } = await axiosClient.post(`/lecturer/groups/${props.group.id}/members`, {
      student_code: inputCode.value.trim(),
    })
    // Thêm thành viên mới vào localMembers
    const newMember = data.member ?? data
    localMembers.value.push(newMember)
    addSuccess.value = `Đã thêm ${newMember.name ?? inputCode.value} vào nhóm`
    inputCode.value = ''
    emit('updated', props.group.id)
  } catch (e) {
    addError.value = e.response?.data?.message ?? 'Không tìm thấy sinh viên với mã này'
  } finally {
    adding.value = false
  }
}

// Thêm từ danh sách
async function handleAddStudent(sv) {
  addError.value = ''
  adding.value = sv.id
  try {
    const { data } = await axiosClient.post(`/lecturer/groups/${props.group.id}/members`, {
      student_code: sv.code,
    })
    const newMember = data.member ?? { ...sv, role: 'member', joined_at: new Date().toISOString() }
    localMembers.value.push(newMember)
    // Xóa khỏi danh sách available
    availableStudents.value = availableStudents.value.filter((s) => s.id !== sv.id)
    emit('updated', props.group.id)
  } catch (e) {
    addError.value = e.response?.data?.message ?? 'Lỗi khi thêm sinh viên'
  } finally {
    adding.value = null
  }
}

// Xóa thành viên
async function handleRemoveMember(member) {
  if (!confirm(`Xóa ${member.name} khỏi nhóm?`)) return
  removing.value = member.id
  try {
    await axiosClient.delete(`/lecturer/groups/${props.group.id}/members/${member.id}`)
    localMembers.value = localMembers.value.filter((m) => m.id !== member.id)
    // Thêm lại vào available nếu đang ở tab list
    if (addMode.value === 'list') {
      availableStudents.value.push(member)
    }
    emit('updated', props.group.id)
  } catch (e) {
    alert(e.response?.data?.message ?? 'Lỗi khi xóa thành viên')
  } finally {
    removing.value = null
  }
}

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}
</script>
