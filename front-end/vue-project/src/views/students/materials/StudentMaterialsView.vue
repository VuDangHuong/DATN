<!-- src/views/students/materials/StudentMaterialsView.vue -->
<template>
  <div>
    <!-- Header -->
    <div class="mb-6">
      <!-- Breadcrumb -->
      <div class="flex items-center gap-2 text-base text-slate-500 mb-3">
        <router-link to="/student/materials" class="hover:text-indigo-600 transition">
          Tài liệu
        </router-link>

        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>

        <span class="text-slate-800 font-medium">
          {{ classInfo?.name ?? `Lớp #${classId}` }}
        </span>
      </div>

      <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
          <h2 class="text-2xl font-bold text-slate-800">
            {{ classInfo?.name ?? 'Tài liệu lớp học' }}
          </h2>

          <p class="text-sm text-slate-500 mt-1 flex items-center gap-2 flex-wrap">
            <span> {{ materialStore.totalMaterials }} tài liệu </span>

            <span>•</span>

            <span> {{ materialStore.totalFiles }} file </span>

            <template v-if="classInfo?.lecturer?.name">
              <span>•</span>

              <span class="flex items-center gap-1.5">
                <span class="w-4 h-4 flex items-center justify-center text-slate-400">
                  <SvgIcon name="user-profile" class="w-4 h-4" />
                </span>

                <span class="font-medium text-slate-700">
                  {{ classInfo.lecturer.name }}
                </span>
              </span>
            </template>
          </p>
        </div>

        <router-link
          to="/student/materials"
          class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl shadow-sm hover:shadow-md transition-all duration-200"
        >
          <SvgICon name="back-arrow" class="w-4 h-4" />
          <span>Quay lại danh sách lớp</span>
        </router-link>
      </div>
    </div>

    <!-- Category stats -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
      <button
        v-for="(label, key) in categories"
        :key="key"
        @click="toggleCategoryFilter(key)"
        class="bg-white rounded-2xl border p-4 text-left transition-all"
        :class="
          materialStore.filters.category === key
            ? 'border-indigo-500 ring-2 ring-indigo-100'
            : 'border-slate-200 hover:border-slate-300'
        "
      >
        <p class="text-base text-slate-500 truncate">
          {{ label }}
        </p>

        <p
          class="text-2xl font-bold mt-1"
          :class="materialStore.filters.category === key ? 'text-indigo-600' : 'text-slate-800'"
        >
          {{ materialStore.categoryStats[key] ?? 0 }}
        </p>
      </button>
    </div>

    <!-- Search -->
    <div class="flex gap-3 mb-5 flex-wrap">
      <div class="relative flex-1 min-w-48">
        <svg
          class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
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
          class="w-full pl-9 pr-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"
        />
      </div>

      <button
        v-if="materialStore.filters.category || materialStore.filters.search"
        @click="clearFilters"
        class="px-3 py-2 text-base text-slate-600 hover:bg-slate-100 rounded-xl transition"
      >
        Xóa lọc
      </button>
    </div>

    <!-- Loading -->
    <div v-if="materialStore.loading" class="flex justify-center py-20">
      <div
        class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"
      />
    </div>

    <!-- Empty -->
    <div
      v-else-if="!materialStore.materials.length"
      class="bg-white rounded-2xl border border-slate-200 p-12 text-center"
    >
      <div class="w-12 h-12 mx-auto text-slate-300 mb-3">
        <SvgIcon name="class-book" />
      </div>

      <p class="text-slate-500">
        {{
          materialStore.filters.search || materialStore.filters.category
            ? 'Không tìm thấy tài liệu phù hợp'
            : 'Giảng viên chưa chia sẻ tài liệu nào'
        }}
      </p>
    </div>

    <!-- Materials -->
    <div v-else class="space-y-4">
      <div
        v-for="m in materialStore.materials"
        :key="m.id"
        class="bg-white rounded-2xl border border-slate-200 overflow-hidden transition hover:shadow-sm"
      >
        <!-- Material header -->
        <div class="p-5 border-b border-slate-100 cursor-pointer" @click="toggleExpand(m.id)">
          <div class="flex items-start gap-4">
            <!-- Icon -->
            <div
              class="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0"
            >
              <span class="text-xl">
                <SvgICon name="class-book" class="w-4 h-4 text-violet-600" />
              </span>
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                <h3 class="font-semibold text-slate-800 text-base">
                  {{ m.title }}
                </h3>

                <span
                  class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-base font-bold rounded-full"
                >
                  {{ m.file_count }} file
                </span>
              </div>

              <p v-if="m.description" class="text-sm text-slate-500 mb-2 line-clamp-2">
                {{ m.description }}
              </p>

              <div class="flex items-center gap-4 text-base text-slate-500 flex-wrap">
                <span>{{ m.category_label }}</span>

                <span>
                  {{ formatSize(m.total_size) }}
                </span>

                <span>
                  {{ formatDate(m.created_at) }}
                </span>

                <span v-if="m.uploader?.name" class="flex items-center gap-1">
                  <SvgIcon name="user-profile" class="w-3.5 h-3.5" />

                  {{ m.uploader.name }}
                </span>
              </div>
            </div>

            <!-- Expand -->
            <svg
              class="w-5 h-5 text-slate-400 transition-transform flex-shrink-0 mt-1"
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
        </div>

        <!-- Files -->
        <div v-if="expandedIds.includes(m.id)">
          <div v-if="!m.files?.length" class="p-5 text-center text-slate-400 text-sm">
            Chưa có file
          </div>

          <div v-else class="divide-y divide-slate-100">
            <div
              v-for="f in m.files"
              :key="f.id"
              class="px-5 py-4 flex items-center gap-3 hover:bg-slate-50 transition"
            >
              <!-- File icon -->
              <div
                class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center flex-shrink-0 text-lg"
              >
                <SvgICon name="document" class="w-4 h-4 text-slate-400" />
              </div>

              <!-- File info -->
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-slate-700 truncate">
                  {{ f.file_name }}
                </p>

                <p class="text-base text-slate-400 mt-0.5">
                  {{ f.file_size_formatted }}

                  <span v-if="f.download_count > 0"> · {{ f.download_count }} lượt tải </span>
                </p>
              </div>

              <!-- Download -->
              <button
                @click="handleDownload(f, m.id)"
                :disabled="downloadingId === f.id"
                class="px-3 py-2 bg-indigo-600 text-white rounded-xl text-base font-semibold hover:bg-indigo-700 disabled:opacity-50 flex items-center gap-1.5 flex-shrink-0 transition"
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
import SvgICon from '@/components/icons/SVG.vue'

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
  lecture: 'Slide',
  exercise: 'Bài tập',
  reference: 'Tham khảo',
  exam: ' Đề thi',
  other: ' Khác',
}

// const categoryIcons = {
//   lecture: '📚',
//   exercise: '✍️',
//   reference: '📖',
//   exam: '📝',
//   other: '📎',
// }

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
