<template>
  <div
    class="fixed inset-0 bg-black/55 backdrop-blur-sm z-50 flex items-center justify-center p-5"
    @click.self="$emit('close')"
  >
    <div
      class="bg-white rounded-2xl w-full max-w-[580px] shadow-2xl animate-modal max-h-[92vh] overflow-y-auto font-['Be_Vietnam_Pro',sans-serif]"
    >
      <!-- Header (sticky) -->
      <div
        class="flex items-center justify-between px-6 pt-[22px] pb-4 border-b border-slate-100 sticky top-0 bg-white z-10 rounded-t-2xl"
      >
        <h3 class="text-[17px] font-bold text-slate-900">
          {{ isEdit ? 'Chỉnh sửa tri thức' : 'Thêm tri thức mới' }}
        </h3>
        <button
          class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 text-lg flex items-center justify-center hover:bg-red-100 hover:text-red-500 transition-colors"
          @click="$emit('close')"
        >
          ×
        </button>
      </div>

      <!-- Body -->
      <div class="px-6 py-5 space-y-3.5">
        <!-- Row: Tiêu đề + Danh mục -->
        <div class="grid grid-cols-2 gap-3.5">
          <div class="flex flex-col gap-1.5">
            <label class="text-[13px] font-semibold text-gray-700">
              Tiêu đề <span class="text-red-500 ml-0.5">*</span>
            </label>
            <input
              v-model="localForm.title"
              placeholder="Nhập tiêu đề tài liệu..."
              class="w-full px-3 py-[9px] border-[1.5px] border-slate-200 rounded-[9px] text-[13.5px] text-slate-800 bg-slate-50 transition-all focus:outline-none focus:border-indigo-500 focus:bg-white focus:shadow-[0_0_0_3px_#6366f115]"
            />
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[13px] font-semibold text-gray-700">
              Danh mục <span class="text-red-500 ml-0.5">*</span>
            </label>
            <select
              v-model="localForm.category"
              class="w-full px-3 py-[9px] border-[1.5px] border-slate-200 rounded-[9px] text-[13.5px] text-slate-800 bg-slate-50 cursor-pointer transition-all focus:outline-none focus:border-indigo-500 focus:bg-white focus:shadow-[0_0_0_3px_#6366f115]"
            >
              <option value="">Chọn danh mục</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.name">
                {{ cat.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Mô tả -->
        <!-- <div class="flex flex-col gap-1.5">
          <label class="text-[13px] font-semibold text-gray-700">Mô tả</label>
          <input
            v-model="localForm.description"
            placeholder="Mô tả ngắn về tài liệu..."
            class="w-full px-3 py-[9px] border-[1.5px] border-slate-200 rounded-[9px] text-[13.5px] text-slate-800 bg-slate-50 transition-all focus:outline-none focus:border-indigo-500 focus:bg-white focus:shadow-[0_0_0_3px_#6366f115]"
          />
        </div> -->

        <!-- Nội dung tri thức -->
        <div class="flex flex-col gap-1.5">
          <label class="text-[13px] font-semibold text-gray-700">
            Nội dung tri thức <span class="text-red-500 ml-0.5">*</span>
          </label>
          <textarea
            v-model="localForm.content"
            rows="6"
            placeholder="Nhập nội dung tri thức, câu hỏi/trả lời, hoặc thông tin tham khảo..."
            class="w-full px-3 py-[9px] border-[1.5px] border-slate-200 rounded-[9px] text-[13.5px] text-slate-800 bg-slate-50 resize-y min-h-[120px] leading-relaxed transition-all focus:outline-none focus:border-indigo-500 focus:bg-white focus:shadow-[0_0_0_3px_#6366f115]"
          ></textarea>
        </div>

        <!-- Row: Loại tài liệu + Trạng thái -->
        <!-- <div class="grid grid-cols-2 gap-3.5">
          <div class="flex flex-col gap-1.5">
            <label class="text-[13px] font-semibold text-gray-700">Loại tài liệu</label>
            <select
              v-model="localForm.type"
              class="w-full px-3 py-[9px] border-[1.5px] border-slate-200 rounded-[9px] text-[13.5px] text-slate-800 bg-slate-50 cursor-pointer transition-all focus:outline-none focus:border-indigo-500 focus:bg-white focus:shadow-[0_0_0_3px_#6366f115]"
            >
              <option value="faq">FAQ</option>
              <option value="doc">Tài liệu</option>
              <option value="url">URL</option>
              <option value="pdf">PDF</option>
            </select>
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[13px] font-semibold text-gray-700">Trạng thái</label>
            <select
              v-model="localForm.status"
              class="w-full px-3 py-[9px] border-[1.5px] border-slate-200 rounded-[9px] text-[13.5px] text-slate-800 bg-slate-50 cursor-pointer transition-all focus:outline-none focus:border-indigo-500 focus:bg-white focus:shadow-[0_0_0_3px_#6366f115]"
            >
              <option value="active">Hoạt động</option>
              <option value="draft">Nháp</option>
              <option value="pending">Chờ duyệt</option>
            </select>
          </div>
        </div> -->

        <!-- Tags -->
        <div class="flex flex-col gap-1.5">
          <label class="text-[13px] font-semibold text-gray-700">
            Tags
            <span class="font-normal text-slate-400 text-xs ml-1">(cách nhau bằng dấu phẩy)</span>
          </label>
          <input
            v-model="localForm.tags"
            placeholder="học phí, nhập học, tuyển sinh..."
            class="w-full px-3 py-[9px] border-[1.5px] border-slate-200 rounded-[9px] text-[13.5px] text-slate-800 bg-slate-50 transition-all focus:outline-none focus:border-indigo-500 focus:bg-white focus:shadow-[0_0_0_3px_#6366f115]"
          />
        </div>
      </div>

      <!-- Footer (sticky) -->
      <div
        class="flex justify-end gap-2.5 px-6 py-4 border-t border-slate-100 sticky bottom-0 bg-white rounded-b-2xl"
      >
        <button
          class="inline-flex items-center gap-1.5 px-[18px] py-[9px] rounded-xl text-[13.5px] font-medium bg-transparent text-slate-500 border border-slate-200 hover:bg-slate-50 transition-colors"
          @click="$emit('close')"
        >
          Hủy
        </button>
        <button
          class="inline-flex items-center gap-1.5 px-[18px] py-[9px] rounded-xl text-[13.5px] font-medium text-white bg-gradient-to-br from-indigo-500 to-indigo-400 shadow-[0_2px_10px_#6366f130] hover:-translate-y-px hover:shadow-[0_4px_16px_#6366f140] transition-all"
          @click="handleSave"
        >
          <svg
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
          >
            <polyline v-if="isEdit" points="20 6 9 17 4 12" />
            <template v-else>
              <line x1="12" y1="5" x2="12" y2="19" />
              <line x1="5" y1="12" x2="19" y2="12" />
            </template>
          </svg>
          {{ isEdit ? 'Cập nhật' : 'Thêm mới' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { useToastStore } from '@/stores/toast'

// ─── Props & Emits ────────────────────────────────────────────────────────────
const props = defineProps({
  /** Truyền object item để edit, hoặc null để thêm mới. */
  modelValue: {
    type: Object,
    default: null,
  },
  /** Danh sách danh mục (không bao gồm mục "Tất cả"). */
  categories: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['close', 'saved'])

// ─── Store ────────────────────────────────────────────────────────────────────
const toast = useToastStore()

// ─── State ────────────────────────────────────────────────────────────────────
const defaultForm = () => ({
  title: '',
  category: '',
  description: '',
  content: '',
  type: 'faq',
  status: 'active',
  tags: '',
})

const localForm = ref(defaultForm())

const isEdit = computed(() => !!props.modelValue?.id)

// Sync form khi prop thay đổi (mở modal edit)
watch(
  () => props.modelValue,
  (val) => {
    localForm.value = val ? { ...val } : defaultForm()
  },
  { immediate: true },
)

// ─── Methods ──────────────────────────────────────────────────────────────────
function handleSave() {
  if (!localForm.value.title.trim()) {
    toast.error('Vui lòng nhập tiêu đề tài liệu')
    return
  }
  if (!localForm.value.category) {
    toast.error('Vui lòng chọn danh mục')
    return
  }
  if (!localForm.value.content.trim()) {
    toast.error('Vui lòng nhập nội dung tri thức')
    return
  }

  emit('saved', {
    ...localForm.value,
    typeLabel: localForm.value.type.toUpperCase(),
  })
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap');

.animate-modal {
  animation: modalIn 0.2s ease;
}
@keyframes modalIn {
  from {
    transform: translateY(20px) scale(0.97);
    opacity: 0;
  }
  to {
    transform: none;
    opacity: 1;
  }
}
</style>
