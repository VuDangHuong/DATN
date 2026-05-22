<!-- src/views/lecturer/materials/ClassMaterialsView.vue -->
<template>
  <div class="w-full p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
      <div>
        <h2 class="text-2xl font-bold text-stone-800">Tài liệu lớp học</h2>
        <p class="text-sm text-stone-500 mt-1">
          {{ materialStore.totalMaterials }} tài liệu · {{ materialStore.totalFiles }} file
        </p>
      </div>
      <div class="flex gap-2">
        <button
          v-if="selectedIds.length"
          @click="openCopyModal(selectedIds)"
          class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold flex items-center gap-2"
        >
          <SvgICon name="copy" class="w-4 h-4" />
          Sao chép ({{ selectedIds.length }})
        </button>
        <button
          @click="openCreateModal"
          class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold flex items-center gap-2"
        >
          <SvgICon name="upload" class="w-4 h-4" />
          Upload tài liệu
        </button>
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
            ? 'border-emerald-500 ring-2 ring-emerald-100'
            : 'border-stone-200 hover:border-stone-300'
        "
      >
        <p class="text-xs text-stone-500 truncate">{{ label }}</p>
        <p
          class="text-2xl font-bold mt-1"
          :class="materialStore.filters.category === key ? 'text-emerald-600' : 'text-stone-800'"
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
          class="w-full pl-9 pr-3 py-2 border border-stone-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500 outline-none"
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
        class="w-8 h-8 border-4 border-emerald-200 border-t-emerald-600 rounded-full animate-spin"
      />
    </div>

    <!-- Empty -->
    <div
      v-else-if="!materialStore.materials.length"
      class="bg-white rounded-2xl border p-12 text-center flex flex-col items-center"
    >
      <SvgICon name="class-book-open" class="w-10 h-10 text-stone-400 mb-3" />

      <p class="text-stone-500 font-medium">Chưa có tài liệu nào</p>

      <button
        @click="openCreateModal"
        class="mt-3 px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700"
      >
        Upload tài liệu đầu tiên
      </button>
    </div>

    <!-- ✅ Material list (accordion) -->
    <div v-else class="space-y-3">
      <div
        v-for="m in materialStore.materials"
        :key="m.id"
        class="bg-white rounded-2xl border transition"
        :class="selectedIds.includes(m.id) ? 'border-emerald-300' : 'border-stone-200'"
      >
        <!-- Title header -->
        <div class="p-4 flex items-center gap-3">
          <input
            type="checkbox"
            :checked="selectedIds.includes(m.id)"
            @change="toggleSelect(m.id)"
            class="rounded text-emerald-600 focus:ring-emerald-500 flex-shrink-0"
            @click.stop
          />

          <div class="flex-1 min-w-0 cursor-pointer" @click="toggleExpand(m.id)">
            <div class="flex items-center gap-2 flex-wrap">
              <p class="text-sm font-bold text-stone-800">{{ m.title }}</p>

              <span
                class="px-2 py-0.5 bg-stone-100 text-stone-600 text-[10px] font-bold rounded-full"
              >
                {{ m.file_count }} file
              </span>

              <span
                v-if="m.is_copied"
                class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-blue-100 text-blue-700 text-[10px] font-bold rounded-full"
              >
                <SvgICon name="copy" class="w-3 h-3" />
                <span>Copy</span>
              </span>
            </div>

            <p v-if="m.description" class="text-xs text-stone-600 mt-0.5 line-clamp-1">
              {{ m.description }}
            </p>

            <div class="flex items-center gap-2 mt-1 text-xs text-stone-400 flex-wrap">
              <span>{{ m.category_label }}</span>
              <span>· {{ formatSize(m.total_size) }}</span>
              <span>· {{ formatDate(m.created_at) }}</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-1 flex-shrink-0">
            <button
              @click.stop="openAddFilesModal(m)"
              class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition"
              title="Thêm file"
            >
              <SvgICon name="plus" class="w-4 h-4" />
            </button>
            <button
              @click.stop="openCopyModal([m.id])"
              class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition"
              title="Sao chép"
            >
              <SvgICon name="copy" class="w-4 h-4" />
            </button>
            <button
              @click.stop="handleDeleteMaterial(m)"
              class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition"
              title="Xóa tài liệu"
            >
              <SvgICon name="trash" class="w-4 h-4" />
            </button>

            <SvgICon
              name="chevron-down"
              class="w-4 h-4 transition-transform duration-200 cursor-pointer"
              :class="{ 'rotate-180': expandedIds.includes(m.id) }"
              @click="toggleExpand(m.id)"
            />
          </div>
        </div>

        <!-- ✅ Files list (expanded) -->
        <div v-if="expandedIds.includes(m.id)" class="border-t border-stone-100">
          <div v-if="!m.files?.length" class="p-4 text-center text-stone-400 text-xs">
            Chưa có file. Bấm + để thêm.
          </div>

          <div v-else class="divide-y divide-stone-100">
            <div
              v-for="f in m.files"
              :key="f.id"
              class="px-4 py-2.5 flex items-center gap-3 hover:bg-stone-50 transition"
            >
              <SvgICon name="document" class="w-5 h-5 text-blue-600" />
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-stone-700 truncate">{{ f.file_name }}</p>
                <p class="text-[11px] text-stone-400">
                  {{ f.file_size_formatted }}
                  <span v-if="f.download_count > 0" class="ml-1">· 📥 {{ f.download_count }}</span>
                </p>
              </div>

              <div class="flex items-center gap-1 flex-shrink-0">
                <button
                  @click="handleDownload(f, m.id)"
                  class="p-1.5 hover:bg-emerald-50 rounded-lg transition"
                  title="Tải xuống"
                >
                  <SvgICon name="download" class="w-5 h-5 text-emerald-600" />
                </button>
                <button
                  @click="handleDeleteFile(f, m.id)"
                  class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition"
                  title="Xóa file"
                >
                  <SvgICon name="x-circle" class="w-5 h-5 text-red-600" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <UploadMaterialModal
      :show="showUploadModal"
      :class-id="classId"
      :material-id="addToMaterialId"
      :existing-title="addToMaterialTitle"
      @close="closeUploadModal"
      @uploaded="onUploaded"
    />

    <CopyMaterialModal
      :show="showCopyModal"
      :class-id="classId"
      :material-ids="copyMaterialIds"
      @close="showCopyModal = false"
      @success="onCopySuccess"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useMaterialStore } from '@/stores/lecturer/materialStore'
import { useToastStore } from '@/stores/toast'
import UploadMaterialModal from '../components/materials/UploadMaterialModal.vue'
import CopyMaterialModal from '../components/materials/CopyMaterialModal.vue'
import SvgICon from '@/components/icons/SVG.vue'

const props = defineProps({
  classId: { type: Number, required: true },
})

const materialStore = useMaterialStore()
const toast = useToastStore()

const showUploadModal = ref(false)
const showCopyModal = ref(false)
const selectedIds = ref([]) // Multi-select để copy
const expandedIds = ref([]) // Material đang xòe để hiện files
const copyMaterialIds = ref([])
const addToMaterialId = ref(null)
const addToMaterialTitle = ref('')
const searchInput = ref('')
let searchTimeout

const categories = {
  lecture: 'Slide',
  exercise: 'Bài tập',
  reference: 'Tham khảo',
  exam: 'Đề thi',
  other: 'Khác',
}

onMounted(loadMaterials)

watch(() => props.classId, loadMaterials)

async function loadMaterials() {
  if (!props.classId) return
  selectedIds.value = []
  await materialStore.fetchMaterials(props.classId)
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

// ── Selection / Expand ──
function toggleSelect(id) {
  const idx = selectedIds.value.indexOf(id)
  if (idx >= 0) selectedIds.value.splice(idx, 1)
  else selectedIds.value.push(id)
}

function toggleExpand(id) {
  const idx = expandedIds.value.indexOf(id)
  if (idx >= 0) expandedIds.value.splice(idx, 1)
  else expandedIds.value.push(id)
}

// ── Modal handlers ──
function openCreateModal() {
  addToMaterialId.value = null
  addToMaterialTitle.value = ''
  showUploadModal.value = true
}

function openAddFilesModal(m) {
  addToMaterialId.value = m.id
  addToMaterialTitle.value = m.title
  showUploadModal.value = true
  // Tự động expand sau khi add
  if (!expandedIds.value.includes(m.id)) {
    expandedIds.value.push(m.id)
  }
}

function closeUploadModal() {
  showUploadModal.value = false
  addToMaterialId.value = null
  addToMaterialTitle.value = ''
}

function openCopyModal(ids) {
  copyMaterialIds.value = ids
  showCopyModal.value = true
}

// ── Actions ──
async function handleDownload(file, materialId) {
  const res = await materialStore.downloadFile(file.id, materialId)
  if (!res.success) toast.error(res.message)
}

async function handleDeleteFile(file, materialId) {
  if (!confirm(`Xóa file "${file.file_name}"?`)) return
  const res = await materialStore.deleteFile(file.id, materialId)
  if (res.success) toast.success(res.message)
  else toast.error(res.message)
}

async function handleDeleteMaterial(m) {
  if (!confirm(`Xóa "${m.title}" và toàn bộ ${m.file_count} file bên trong?`)) return
  const res = await materialStore.deleteMaterial(m.id)
  if (res.success) {
    toast.success(res.message)
    selectedIds.value = selectedIds.value.filter((id) => id !== m.id)
    expandedIds.value = expandedIds.value.filter((id) => id !== m.id)
  } else {
    toast.error(res.message)
  }
}

function onUploaded() {
  // Auto reload để cập nhật stats
  loadMaterials()
}

function onCopySuccess() {
  setTimeout(() => {
    showCopyModal.value = false
    selectedIds.value = []
  }, 3000)
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
