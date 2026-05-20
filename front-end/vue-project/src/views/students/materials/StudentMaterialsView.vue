<!-- src/views/students/materials/StudentMaterialsView.vue -->
<template>
  <div class="max-w-5xl mx-auto p-6">
    <!-- Header -->
    <div class="mb-6">
      <!-- Breadcrumb -->
      <div class="flex items-center gap-2 text-xs text-stone-500 mb-3">
        <router-link to="/student/materials" class="hover:text-indigo-600 transition">
          Tài liệu
        </router-link>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <span class="text-stone-800 font-medium">{{ classInfo?.name ?? `Lớp #${classId}` }}</span>
      </div>

      <div class="flex items-start justify-between flex-wrap gap-3">
        <div>
          <h2 class="text-2xl font-bold text-stone-800">
            📚 {{ classInfo?.name ?? 'Tài liệu lớp học' }}
          </h2>
          <p class="text-sm text-stone-500 mt-1">
            {{ materialStore.totalMaterials }} tài liệu · {{ materialStore.totalFiles }} file
            <span v-if="classInfo?.lecturer?.name" class="ml-1">
              · GV: <strong>{{ classInfo.lecturer.name }}</strong>
            </span>
          </p>
        </div>

        <router-link
          to="/student/materials"
          class="px-3 py-2 text-xs text-stone-600 hover:bg-stone-100 rounded-xl flex items-center gap-1"
        >
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 19l-7-7 7-7"
            />
          </svg>
          Quay lại danh sách lớp
        </router-link>
      </div>
    </div>

    <!-- Stats by category -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
      <button
        v-for="(label, key) in categories"
        :key="key"
        @click="toggleCategoryFilter(key)"
        class="bg-white rounded-xl border p-3 text-left transition"
        :class="
          materialStore.filters.category === key
            ? 'border-indigo-500 ring-2 ring-indigo-100'
            : 'border-stone-200 hover:border-stone-300'
        "
      >
        <p class="text-xs text-stone-500 truncate">{{ label }}</p>
        <p
          class="text-2xl font-bold mt-1"
          :class="materialStore.filters.category === key ? 'text-indigo-600' : 'text-stone-800'"
        >
          {{ materialStore.categoryStats[key] ?? 0 }}
        </p>
      </button>
    </div>

    <!-- Search -->
    <div class="flex gap-3 mb-4 flex-wrap">
      <div class="relative flex-1 min-w-48">
        <svg
          class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"
          />
        </svg>
        <input
          v-model="searchInput"
          @input="onSearch"
          type="text"
          placeholder="Tìm tài liệu hoặc file..."
          class="w-full pl-9 pr-3 py-2 border border-stone-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
        />
      </div>
      <button
        v-if="materialStore.filters.category || materialStore.filters.search"
        @click="clearFilters"
        class="px-3 py-2 text-xs text-stone-600 hover:bg-stone-100 rounded-xl"
      >
        Xóa lọc
      </button>
    </div>

    <!-- Loading -->
    <div v-if="materialStore.loading" class="flex justify-center py-12">
      <div
        class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"
      />
    </div>

    <!-- Empty -->
    <div
      v-else-if="!materialStore.materials.length"
      class="bg-white rounded-2xl border p-12 text-center"
    >
      <p class="text-5xl mb-3">📭</p>
      <p class="text-stone-500 font-medium">
        {{
          materialStore.filters.search || materialStore.filters.category
            ? 'Không tìm thấy tài liệu phù hợp'
            : 'Giảng viên chưa chia sẻ tài liệu nào'
        }}
      </p>
    </div>

    <!-- Material list (accordion) -->
    <div v-else class="space-y-3">
      <div
        v-for="m in materialStore.materials"
        :key="m.id"
        class="bg-white rounded-2xl border border-stone-200 transition hover:shadow-md"
      >
        <div class="p-4 flex items-center gap-3 cursor-pointer" @click="toggleExpand(m.id)">
          <div
            class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0"
          >
            <span class="text-xl">{{ categoryIcons[m.category] ?? '📚' }}</span>
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <p class="text-sm font-bold text-stone-800">{{ m.title }}</p>
              <span
                class="px-2 py-0.5 bg-stone-100 text-stone-600 text-[10px] font-bold rounded-full"
              >
                {{ m.file_count }} file
              </span>
            </div>

            <p v-if="m.description" class="text-xs text-stone-600 mt-0.5 line-clamp-1">
              {{ m.description }}
            </p>

            <div class="flex items-center gap-2 mt-1 text-xs text-stone-400 flex-wrap">
              <span>{{ m.category_label }}</span>
              <span>· {{ formatSize(m.total_size) }}</span>
              <span>· {{ formatDate(m.created_at) }}</span>
              <span v-if="m.uploader?.name">· 👤 {{ m.uploader.name }}</span>
            </div>
          </div>

          <svg
            class="w-5 h-5 text-stone-400 transition-transform flex-shrink-0"
            :class="{ 'rotate-180': expandedIds.includes(m.id) }"
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

        <!-- Files list (expanded) -->
        <div v-if="expandedIds.includes(m.id)" class="border-t border-stone-100">
          <div v-if="!m.files?.length" class="p-4 text-center text-stone-400 text-xs">
            Chưa có file
          </div>

          <div v-else class="divide-y divide-stone-100">
            <div
              v-for="f in m.files"
              :key="f.id"
              class="px-4 py-3 flex items-center gap-3 hover:bg-stone-50 transition"
            >
              <span class="text-2xl flex-shrink-0">{{ f.icon }}</span>

              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-stone-700 truncate">{{ f.file_name }}</p>
                <p class="text-xs text-stone-400 mt-0.5">
                  {{ f.file_size_formatted }}
                  <span v-if="f.download_count > 0" class="ml-1"
                    >· 📥 {{ f.download_count }} lượt tải</span
                  >
                </p>
              </div>

              <button
                @click="handleDownload(f, m.id)"
                :disabled="downloadingId === f.id"
                class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-xs font-semibold hover:bg-indigo-700 disabled:opacity-50 flex items-center gap-1.5 flex-shrink-0"
              >
                <div
                  v-if="downloadingId === f.id"
                  class="w-3 h-3 border-2 border-white/30 border-t-white rounded-full animate-spin"
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
                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                  />
                </svg>
                Tải xuống
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useStudentMaterialStore } from '@/stores/students/materialStore'
import { useToastStore } from '@/stores/toast'
import axiosClient from '@/api/axiosClient'

const props = defineProps({
  classId: { type: Number, required: true },
})

const materialStore = useStudentMaterialStore()
const toast = useToastStore()

const classInfo = ref(null)
const expandedIds = ref([])
const downloadingId = ref(null)
const searchInput = ref('')
let searchTimeout

const categories = {
  lecture: '📚 Slide',
  exercise: '✍️ Bài tập',
  reference: '📖 Tham khảo',
  exam: '📝 Đề thi',
  other: '📎 Khác',
}

const categoryIcons = {
  lecture: '📚',
  exercise: '✍️',
  reference: '📖',
  exam: '📝',
  other: '📎',
}

onMounted(async () => {
  await loadClassInfo()
  await loadMaterials()
})

watch(
  () => props.classId,
  async () => {
    expandedIds.value = []
    materialStore.reset()
    await loadClassInfo()
    await loadMaterials()
  },
)

// ✅ Lấy class info từ /student/my-classes (response có nested)
async function loadClassInfo() {
  try {
    const { data: response } = await axiosClient.get('/student/my-classes')

    // Response: { data: { classes: [{ class: {...}, semester: {...}, lecturer: {...} }] } }
    const rawClasses = response.data?.classes ?? response.classes ?? []

    // Tìm class phù hợp với classId hiện tại
    const found = rawClasses.find((item) => item.class?.id === props.classId)

    if (found) {
      classInfo.value = {
        id: found.class.id,
        name: found.class.name,
        code: found.class.code,
        semester: found.semester,
        lecturer: found.lecturer,
        subjects: found.subjects,
      }
    } else {
      classInfo.value = null
    }
  } catch (e) {
    console.error('Load class info error:', e.response?.data ?? e.message)
  }
}

async function loadMaterials() {
  if (!props.classId) return
  const res = await materialStore.fetchMaterials(props.classId)
  if (!res.success) toast.error(res.message)
}

function toggleExpand(id) {
  const idx = expandedIds.value.indexOf(id)
  if (idx >= 0) expandedIds.value.splice(idx, 1)
  else expandedIds.value.push(id)
}

function toggleCategoryFilter(category) {
  if (materialStore.filters.category === category) {
    materialStore.setFilter('category', '')
  } else {
    materialStore.setFilter('category', category)
  }
  loadMaterials()
}

function onSearch() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    materialStore.setFilter('search', searchInput.value)
    loadMaterials()
  }, 400)
}

function clearFilters() {
  searchInput.value = ''
  materialStore.setFilter('category', '')
  materialStore.setFilter('search', '')
  loadMaterials()
}

async function handleDownload(file, materialId) {
  downloadingId.value = file.id
  const res = await materialStore.downloadFile(file.id, materialId)
  downloadingId.value = null
  if (!res.success) toast.error(res.message)
}

function formatSize(bytes) {
  if (!bytes) return '0 B'
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  if (bytes < 1024 * 1024 * 1024) return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
  return (bytes / (1024 * 1024 * 1024)).toFixed(1) + ' GB'
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
