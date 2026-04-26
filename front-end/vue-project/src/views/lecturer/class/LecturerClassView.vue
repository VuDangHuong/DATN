<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-slate-800">Lớp học của tôi</h2>
        <p class="text-sm text-slate-500 mt-1">
          {{ filteredClasses.length }} lớp
          <span v-if="lecturerStore.selectedClassId"> · đang lọc theo lớp đã chọn</span>
        </p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-20">
      <div class="w-8 h-8 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin" />
    </div>

    <!-- Empty -->
    <div v-else-if="!filteredClasses.length" class="bg-white rounded-2xl border p-12 text-center">
      <svg
        class="w-12 h-12 mx-auto text-slate-300 mb-3"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1.5"
          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
        />
      </svg>
      <p class="text-slate-500 font-medium">Chưa có lớp nào</p>
    </div>

    <!-- Class list -->
    <div v-else class="space-y-4">
      <div
        v-for="cls in filteredClasses"
        :key="cls.id"
        :ref="
          (el) => {
            if (el) classRefs[cls.id] = el
          }
        "
        class="bg-white rounded-2xl border border-slate-200 overflow-hidden transition-all"
        :class="lecturerStore.selectedClassId === cls.id ? 'ring-2 ring-teal-400' : ''"
      >
        <!-- Class header -->
        <div
          class="p-5 flex items-center justify-between cursor-pointer hover:bg-slate-50 transition"
          @click="toggleClass(cls.id)"
        >
          <div class="flex items-center gap-4 flex-1 min-w-0">
            <div
              class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 transition"
              :class="lecturerStore.selectedClassId === cls.id ? 'bg-teal-600' : 'bg-teal-100'"
            >
              <svg
                class="w-5 h-5 transition"
                :class="lecturerStore.selectedClassId === cls.id ? 'text-white' : 'text-teal-600'"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                />
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="font-semibold text-slate-800">{{ cls.name }}</h3>
                <span
                  class="px-2 py-0.5 bg-slate-100 text-slate-500 text-xs font-mono rounded-lg"
                  >{{ cls.code }}</span
                >
                <span
                  v-if="lecturerStore.selectedClassId === cls.id"
                  class="px-2 py-0.5 bg-teal-100 text-teal-700 text-[10px] font-bold rounded-full"
                >
                  Đang chọn
                </span>
              </div>
              <div class="flex items-center gap-4 mt-1 text-xs text-slate-400">
                <span>{{ cls.semester?.name }}</span>
                <span v-if="cls.subjects?.length">{{
                  cls.subjects.map((s) => s.code).join(', ')
                }}</span>
              </div>
            </div>
            <div class="hidden sm:flex items-center gap-6 text-center flex-shrink-0">
              <div>
                <p class="text-lg font-bold text-slate-700">{{ cls.current_count ?? 0 }}</p>
                <p class="text-[10px] text-slate-400">Sinh viên</p>
              </div>
              <div>
                <p class="text-lg font-bold text-teal-600">{{ groupCounts[cls.id] ?? '–' }}</p>
                <p class="text-[10px] text-slate-400">Nhóm</p>
              </div>
            </div>
          </div>
          <svg
            class="w-4 h-4 text-slate-400 ml-3 transition-transform flex-shrink-0"
            :class="{ 'rotate-180': openClassId === cls.id }"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M19 9l-7 7-7-7"
            />
          </svg>
        </div>

        <!-- Expanded -->
        <div v-if="openClassId === cls.id" class="border-t border-slate-100">
          <div v-if="loadingGroups" class="flex justify-center py-8">
            <div
              class="w-6 h-6 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin"
            />
          </div>

          <div v-else-if="!groups.length" class="py-8 text-center text-slate-400 text-sm">
            Chưa có nhóm nào trong lớp này
          </div>

          <div v-else class="p-5">
            <div class="flex items-center justify-between mb-4">
              <h4 class="text-sm font-semibold text-slate-600">
                Danh sách nhóm
                <span class="ml-1.5 px-2 py-0.5 bg-teal-100 text-teal-700 text-xs rounded-full">{{
                  groups.length
                }}</span>
              </h4>
              <input
                v-model="groupSearch"
                type="text"
                placeholder="Tìm nhóm..."
                class="px-3 py-1.5 border border-slate-200 rounded-lg text-xs focus:ring-2 focus:ring-teal-500 focus:border-transparent outline-none w-40"
              />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
              <div
                v-for="group in filteredGroups"
                :key="group.id"
                @click="openGroupDetail(group)"
                class="border border-slate-200 rounded-xl p-4 hover:border-teal-400 hover:shadow-md hover:bg-teal-50/30 transition cursor-pointer group"
              >
                <div class="flex items-start justify-between mb-3">
                  <div>
                    <p
                      class="font-semibold text-slate-800 text-sm group-hover:text-teal-700 transition"
                    >
                      {{ group.name }}
                    </p>
                    <p class="text-xs text-slate-400 font-mono mt-0.5">
                      {{ group.invitation_code }}
                    </p>
                  </div>
                  <span
                    class="px-2 py-0.5 text-[10px] font-bold rounded-full"
                    :class="
                      group.is_locked
                        ? 'bg-red-100 text-red-600'
                        : 'bg-emerald-100 text-emerald-700'
                    "
                  >
                    {{ group.is_locked ? 'Đã khóa' : 'Mở' }}
                  </span>
                </div>
                <div class="flex items-center gap-2 mb-3">
                  <div
                    class="w-6 h-6 rounded-full bg-teal-100 flex items-center justify-center text-[10px] font-bold text-teal-700 flex-shrink-0"
                  >
                    {{ group.leader?.name?.charAt(0) }}
                  </div>
                  <div class="min-w-0">
                    <p class="text-xs font-medium text-slate-700 truncate">
                      {{ group.leader?.name }}
                    </p>
                    <p class="text-[10px] text-slate-400">Trưởng nhóm</p>
                  </div>
                </div>
                <div class="flex items-center justify-between">
                  <div class="flex -space-x-1.5">
                    <div
                      v-for="m in group.members?.slice(0, 4)"
                      :key="m.id"
                      class="w-6 h-6 rounded-full border-2 border-white bg-slate-200 flex items-center justify-center text-[9px] font-bold text-slate-600"
                      :title="m.name"
                    >
                      {{ m.name?.charAt(0) }}
                    </div>
                    <div
                      v-if="group.members?.length > 4"
                      class="w-6 h-6 rounded-full border-2 border-white bg-slate-300 flex items-center justify-center text-[9px] font-bold text-slate-600"
                    >
                      +{{ group.members.length - 4 }}
                    </div>
                  </div>
                  <span class="text-xs text-slate-500"
                    >{{ group.member_count ?? group.members?.length ?? 0 }} thành viên</span
                  >
                </div>
                <p
                  class="text-[10px] text-teal-600 mt-2 opacity-0 group-hover:opacity-100 transition"
                >
                  Click để xem chi tiết →
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal chi tiết nhóm -->
    <ModalGroupDetailView
      :show="!!selectedGroup"
      :group="selectedGroup"
      :class-id="openClassId"
      @close="selectedGroup = null"
      @updated="onGroupUpdated"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useLecturerStore } from '@/stores/lecturer/lecturerStore'
import axiosClient from '@/api/axiosClient'
import ModalGroupDetailView from '../components/ModalGroupDetailView.vue'
const lecturerStore = useLecturerStore()

const classes = ref([])
const loading = ref(false)
const groups = ref([])
const loadingGroups = ref(false)
const openClassId = ref(null)
const groupSearch = ref('')
const groupCounts = ref({})
const selectedGroup = ref(null)
const classRefs = ref({}) // refs để scroll

// ✅ Lọc theo selectedClassId từ store
const filteredClasses = computed(() => {
  const id = lecturerStore.selectedClassId
  if (!id) return classes.value
  return classes.value.filter((c) => c.id === id)
})

onMounted(fetchClasses)

// ✅ Watch selectedClassId → tự mở accordion + scroll đến lớp đó
watch(
  () => lecturerStore.selectedClassId,
  async (classId) => {
    if (!classId) return

    // Mở accordion của lớp được chọn
    if (openClassId.value !== classId) {
      await loadGroups(classId)
    }

    // Scroll đến lớp đó
    await nextTick()
    const el = classRefs.value[classId]
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
  },
)

async function fetchClasses() {
  loading.value = true
  try {
    // Dùng lại data từ store nếu đã có, không cần gọi lại
    if (lecturerStore.classes.length > 0) {
      classes.value = lecturerStore.classes
    } else {
      const { data } = await axiosClient.get('/lecturer/classes')
      classes.value = data
      lecturerStore.setClasses(data)
    }
    await loadGroupCounts(classes.value)

    // Nếu đã có selectedClassId → mở luôn
    if (lecturerStore.selectedClassId) {
      await loadGroups(lecturerStore.selectedClassId)
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function loadGroupCounts(classList) {
  await Promise.all(
    classList.map(async (cls) => {
      try {
        const { data } = await axiosClient.get(`/lecturer/classes/${cls.id}/groups`)
        groupCounts.value[cls.id] = data.groups?.length ?? 0
      } catch {
        groupCounts.value[cls.id] = 0
      }
    }),
  )
}

async function toggleClass(classId) {
  if (openClassId.value === classId) {
    openClassId.value = null
    groups.value = []
    groupSearch.value = ''
    return
  }
  await loadGroups(classId)
}

async function loadGroups(classId) {
  openClassId.value = classId
  groups.value = []
  groupSearch.value = ''
  loadingGroups.value = true
  try {
    const { data } = await axiosClient.get(`/lecturer/classes/${classId}/groups`)
    groups.value = data.groups ?? []
  } catch (e) {
    groups.value = []
  } finally {
    loadingGroups.value = false
  }
}

function openGroupDetail(group) {
  selectedGroup.value = group
}
// Thêm handler
function onGroupUpdated(groupId) {
  // Reload group count
  loadGroupCounts(classes.value)
}
const filteredGroups = computed(() => {
  if (!groupSearch.value.trim()) return groups.value
  const q = groupSearch.value.toLowerCase()
  return groups.value.filter(
    (g) =>
      g.name?.toLowerCase().includes(q) ||
      g.leader?.name?.toLowerCase().includes(q) ||
      g.invitation_code?.toLowerCase().includes(q),
  )
})

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}
</script>
