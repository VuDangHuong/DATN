<!-- src/views/lecturer/components/materials/MaterialAccordionItem.vue -->
<template>
  <div
    class="bg-white rounded-2xl border transition"
    :class="selected ? 'border-emerald-300' : 'border-stone-200'"
  >
    <!-- Title header -->
    <div class="p-4 flex items-center gap-3">
      <input
        type="checkbox"
        :checked="selected"
        @change="$emit('toggle-select', material.id)"
        class="rounded text-emerald-600 focus:ring-emerald-500 flex-shrink-0"
        @click.stop
      />

      <div class="flex-1 min-w-0">
        <!-- Chế độ sửa tiêu đề -->
        <div v-if="editing" class="flex items-center gap-2" @click.stop>
          <input
            v-model="editTitle"
            type="text"
            maxlength="255"
            class="flex-1 min-w-0 px-2 py-1 border border-emerald-400 rounded-lg text-sm focus:ring-2 focus:ring-emerald-100 outline-none"
            :disabled="savingTitle"
            @keyup.enter="saveTitle"
            @keyup.esc="cancelEdit"
          />
          <button
            class="px-2.5 py-1 bg-emerald-600 text-white text-xs font-semibold rounded-lg disabled:opacity-50"
            :disabled="savingTitle || !editTitle.trim()"
            @click="saveTitle"
          >
            {{ savingTitle ? '...' : 'Lưu' }}
          </button>
          <button
            class="px-2.5 py-1 bg-stone-100 text-stone-600 text-xs font-semibold rounded-lg"
            :disabled="savingTitle"
            @click="cancelEdit"
          >
            Hủy
          </button>
        </div>

        <!-- Chế độ xem -->
        <div v-else class="cursor-pointer" @click="$emit('toggle-expand', material.id)">
          <div class="flex items-center gap-2 flex-wrap">
            <p class="text-sm font-bold text-stone-800">{{ material.title }}</p>

            <span
              class="px-2 py-0.5 bg-stone-100 text-stone-600 text-[10px] font-bold rounded-full"
            >
              {{ material.file_count }} file
            </span>

            <span
              v-if="material.is_copied"
              class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-blue-100 text-blue-700 text-[10px] font-bold rounded-full"
            >
              <SvgICon name="copy" class="w-3 h-3" />
              <span>Copy</span>
            </span>
          </div>

          <p v-if="material.description" class="text-base text-stone-600 mt-0.5 line-clamp-1">
            {{ material.description }}
          </p>

          <div class="flex items-center gap-2 mt-1 text-base text-stone-400 flex-wrap">
            <span>{{ material.category_label }}</span>
            <span>· {{ formatSize(material.total_size) }}</span>
            <span>· {{ formatDate(material.created_at) }}</span>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div v-if="!editing" class="flex items-center gap-1 flex-shrink-0">
        <button
          @click.stop="startEdit"
          class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition"
          title="Sửa tiêu đề"
        >
          <SvgICon name="edit" class="w-4 h-4" />
        </button>
        <button
          @click.stop="$emit('add-files', material)"
          class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition"
          title="Thêm file"
        >
          <SvgICon name="plus" class="w-4 h-4" />
        </button>
        <button
          @click.stop="$emit('copy', [material.id])"
          class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition"
          title="Sao chép"
        >
          <SvgICon name="copy" class="w-4 h-4" />
        </button>
        <button
          @click.stop="$emit('delete', material)"
          class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition"
          title="Xóa tài liệu"
        >
          <SvgICon name="trash" class="w-4 h-4" />
        </button>

        <SvgICon
          name="chevron-down"
          class="w-4 h-4 transition-transform duration-200 cursor-pointer"
          :class="{ 'rotate-180': expanded }"
          @click="$emit('toggle-expand', material.id)"
        />
      </div>
    </div>

    <!-- Files list (expanded) -->
    <div v-if="expanded" class="border-t border-stone-100">
      <div v-if="!material.files?.length" class="p-4 text-center text-stone-400 text-base">
        Chưa có file. Bấm + để thêm.
      </div>

      <div v-else class="divide-y divide-stone-100">
        <div
          v-for="f in material.files"
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
              @click="$emit('download', f, material.id)"
              class="p-1.5 hover:bg-emerald-50 rounded-lg transition"
              title="Tải xuống"
            >
              <SvgICon name="download" class="w-5 h-5 text-emerald-600" />
            </button>
            <button
              @click="$emit('delete-file', f, material.id)"
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
</template>

<script setup>
import { ref } from 'vue'
import { useMaterialStore } from '@/stores/lecturer/materialStore'
import { useToastStore } from '@/stores/toast'
import SvgICon from '@/components/icons/SVG.vue'
const props = defineProps({
  material: { type: Object, required: true },
  selected: { type: Boolean, default: false },
  expanded: { type: Boolean, default: false },
})

defineEmits([
  'toggle-select',
  'toggle-expand',
  'add-files',
  'copy',
  'delete',
  'download',
  'delete-file',
])

const materialStore = useMaterialStore()
const toast = useToastStore()

// ── Sửa tiêu đề inline ──
const editing = ref(false)
const editTitle = ref('')
const savingTitle = ref(false)

function startEdit() {
  editTitle.value = props.material.title
  editing.value = true
}

function cancelEdit() {
  editing.value = false
  editTitle.value = ''
}

async function saveTitle() {
  const title = editTitle.value.trim()
  if (!title || title === props.material.title) {
    cancelEdit()
    return
  }

  savingTitle.value = true
  const res = await materialStore.updateMaterial(props.material.id, { title })
  savingTitle.value = false

  if (res.success) {
    toast.success(res.message || 'Đã cập nhật tiêu đề')
    editing.value = false
  } else {
    toast.error(res.message || 'Cập nhật thất bại')
  }
}

// ── Helpers ──
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
