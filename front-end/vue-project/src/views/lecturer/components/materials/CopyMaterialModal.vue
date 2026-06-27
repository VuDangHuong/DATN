<!-- src/components/lecturer/materials/CopyMaterialModal.vue -->
<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="$emit('close')" />
      <div
        class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl p-6 max-h-[90vh] flex flex-col"
      >
        <div class="flex items-start gap-3 mb-4 flex-shrink-0">
          <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
            <SvgICon name="copy" class="w-4 h-4" />
          </div>
          <div>
            <h3 class="text-lg font-bold text-stone-800">Sao chép tài liệu sang lớp khác</h3>
            <p class="text-base text-stone-500 mt-1">
              Chọn lớp đích để sao chép {{ materialIds.length }} tài liệu
            </p>
          </div>
        </div>

        <!-- Source info -->
        <div v-if="source" class="mb-4 p-3 bg-stone-50 rounded-xl flex-shrink-0">
          <p class="text-base text-stone-500 mb-1">Từ lớp gốc:</p>
          <p class="text-sm font-semibold text-stone-800">{{ source.name }}</p>
          <p class="text-base text-stone-500 mt-1">
            Môn: {{ source.subjects?.map((s) => s.code).join(', ') ?? '—' }}
          </p>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex-1 flex items-center justify-center py-8">
          <div
            class="w-6 h-6 border-2 border-blue-200 border-t-blue-600 rounded-full animate-spin"
          />
        </div>

        <!-- Empty -->
        <div v-else-if="!classes.length" class="flex-1 text-center py-8 text-stone-500 text-sm">
          Bạn không có lớp nào khác để sao chép
        </div>

        <!-- Class list -->
        <div v-else class="flex-1 overflow-y-auto -mx-2 px-2 space-y-2 mb-4">
          <!-- Cùng môn -->
          <template v-if="sameSubjectClasses.length">
            <p class="text-base font-bold text-emerald-700 uppercase tracking-wide mb-2">
              Lớp cùng môn ({{ sameSubjectClasses.length }})
            </p>
            <label
              v-for="c in sameSubjectClasses"
              :key="c.id"
              class="flex items-start gap-3 p-3 border rounded-xl cursor-pointer transition"
              :class="
                selectedIds.includes(c.id)
                  ? 'bg-emerald-50 border-emerald-300'
                  : 'border-stone-200 hover:bg-stone-50'
              "
            >
              <input
                type="checkbox"
                :value="c.id"
                v-model="selectedIds"
                class="mt-1 rounded text-emerald-600 focus:ring-emerald-500"
              />
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-stone-800 truncate">{{ c.name }}</p>
                <p class="text-base text-stone-500 mt-0.5">
                  {{ c.code }} · {{ c.semester_name }}
                  <span v-if="c.subjects?.length" class="ml-1">
                    · {{ c.subjects.map((s) => s.code).join(', ') }}
                  </span>
                </p>
              </div>
              <span
                class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded-full flex-shrink-0"
              >
                Cùng môn
              </span>
            </label>
          </template>

          <!-- Lớp khác -->
          <template v-if="otherClasses.length">
            <p class="text-base font-bold text-stone-500 uppercase tracking-wide mt-3 mb-2">
              Lớp khác ({{ otherClasses.length }})
            </p>
            <label
              v-for="c in otherClasses"
              :key="c.id"
              class="flex items-start gap-3 p-3 border rounded-xl cursor-pointer transition"
              :class="
                selectedIds.includes(c.id)
                  ? 'bg-blue-50 border-blue-300'
                  : 'border-stone-200 hover:bg-stone-50'
              "
            >
              <input
                type="checkbox"
                :value="c.id"
                v-model="selectedIds"
                class="mt-1 rounded text-blue-600 focus:ring-blue-500"
              />
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-stone-800 truncate">{{ c.name }}</p>
                <p class="text-base text-stone-500 mt-0.5">
                  {{ c.code }} · {{ c.semester_name }}
                  <span v-if="c.subjects?.length" class="ml-1">
                    · {{ c.subjects.map((s) => s.code).join(', ') }}
                  </span>
                </p>
              </div>
            </label>
          </template>
        </div>

        <!-- Selected count + Result -->
        <div
          v-if="result"
          class="mb-4 p-3 rounded-xl flex-shrink-0"
          :class="
            result.skippedCount > 0
              ? 'bg-amber-50 border border-amber-200'
              : 'bg-emerald-50 border border-emerald-200'
          "
        >
          <p
            class="text-sm font-medium"
            :class="result.skippedCount > 0 ? 'text-amber-800' : 'text-emerald-800'"
          >
            Đã sao chép {{ result.copiedCount }} tài liệu
            <span v-if="result.skippedCount > 0">· Bỏ qua {{ result.skippedCount }}</span>
          </p>
          <details v-if="result.skippedReasons?.length" class="mt-2">
            <summary class="text-base cursor-pointer text-stone-600">Xem chi tiết</summary>
            <ul class="text-base text-stone-600 mt-1 space-y-0.5 ml-4 list-disc">
              <li v-for="(r, i) in result.skippedReasons" :key="i">{{ r }}</li>
            </ul>
          </details>
        </div>

        <!-- Actions -->
        <div class="flex gap-3 flex-shrink-0">
          <button
            @click="$emit('close')"
            class="flex-1 py-2.5 border border-stone-200 rounded-xl text-sm font-medium text-stone-600 hover:bg-stone-50"
          >
            {{ result ? 'Đóng' : 'Hủy' }}
          </button>
          <button
            v-if="!result"
            @click="handleCopy"
            :disabled="!selectedIds.length || copying"
            class="flex-1 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-semibold hover:bg-blue-700 disabled:opacity-50 flex items-center justify-center gap-2"
          >
            <div
              v-if="copying"
              class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
            />
            {{ copying ? 'Đang sao chép...' : `Sao chép sang ${selectedIds.length} lớp` }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useMaterialStore } from '@/stores/lecturer/materialStore'
import { useToastStore } from '@/stores/toast'
import SvgICon from '@/components/icons/SVG.vue'
const props = defineProps({
  show: { type: Boolean, default: false },
  classId: { type: Number, required: true }, // Lớp nguồn
  materialIds: { type: Array, default: () => [] },
})

const emit = defineEmits(['close', 'success'])

const materialStore = useMaterialStore()
const toast = useToastStore()

const classes = ref([])
const source = ref(null)
const selectedIds = ref([])
const loading = ref(false)
const copying = ref(false)
const result = ref(null)

const sameSubjectClasses = computed(() => classes.value.filter((c) => c.is_same_subject))

const otherClasses = computed(() => classes.value.filter((c) => !c.is_same_subject))

watch(
  () => props.show,
  async (v) => {
    if (v) {
      selectedIds.value = []
      result.value = null
      await loadTargets()
    }
  },
)

async function loadTargets() {
  loading.value = true
  const res = await materialStore.fetchCopyTargets(props.classId)
  loading.value = false

  if (res.success) {
    classes.value = res.classes ?? []
    source.value = res.source
  } else {
    toast.error(res.message ?? 'Không thể tải danh sách lớp')
  }
}

async function handleCopy() {
  if (!selectedIds.value.length) return

  copying.value = true
  const res = await materialStore.copyMaterials(props.materialIds, selectedIds.value)
  copying.value = false

  if (res.success) {
    toast.success(res.message)
    result.value = res
    emit('success', res)
  } else {
    toast.error(res.message)
  }
}
</script>
