<template>
  <div ref="rootRef" class="relative" :class="$attrs.class">
    <!-- Trigger button -->
    <button
      type="button"
      @click="toggleOpen"
      :disabled="disabled"
      class="w-full flex items-center justify-between gap-2 px-3 py-1.5 border border-stone-200 rounded-lg text-sm text-stone-700 bg-white outline-none focus:ring-2 focus:ring-teal-500 disabled:bg-stone-50 disabled:cursor-not-allowed"
      :class="isOpen ? 'ring-2 ring-teal-500 border-teal-500' : ''"
    >
      <span class="truncate" :class="!selected ? 'text-stone-400' : ''">
        {{ selected ? getLabel(selected) : placeholder }}
      </span>

      <!-- Clear button -->
      <button
        v-if="clearable && selected && !disabled"
        type="button"
        @click.stop="clearSelection"
        class="text-stone-400 hover:text-stone-600 flex-shrink-0"
        tabindex="-1"
      >
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M6 18L18 6M6 6l12 12"
          />
        </svg>
      </button>

      <!-- Chevron -->
      <svg
        v-else
        class="w-3.5 h-3.5 text-stone-400 transition-transform flex-shrink-0"
        :class="isOpen ? 'rotate-180' : ''"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Dropdown panel -->
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0 -translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="absolute z-50 mt-1 left-0 right-0 bg-white border border-stone-200 rounded-xl shadow-lg overflow-hidden"
        :class="dropdownClass"
      >
        <!-- Search input -->
        <div v-if="searchable" class="p-2 border-b border-stone-100">
          <div class="relative">
            <svg
              class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-stone-400"
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
              ref="searchInputRef"
              v-model="searchQuery"
              type="text"
              :placeholder="searchPlaceholder"
              class="w-full pl-8 pr-2 py-1.5 bg-stone-50 border-none rounded-lg text-sm outline-none focus:ring-2 focus:ring-teal-500"
              @keydown.down.prevent="moveHighlight(1)"
              @keydown.up.prevent="moveHighlight(-1)"
              @keydown.enter.prevent="selectHighlighted"
              @keydown.esc="close"
            />
          </div>
        </div>

        <!-- Options list -->
        <div ref="listRef" class="max-h-60 overflow-y-auto py-1">
          <!-- Empty state -->
          <div v-if="!filteredOptions.length" class="px-3 py-4 text-center text-xs text-stone-400">
            {{ searchQuery ? 'Không tìm thấy kết quả' : emptyText }}
          </div>

          <!-- Options -->
          <button
            v-for="(opt, idx) in filteredOptions"
            :key="getKey(opt)"
            type="button"
            @click="selectOption(opt)"
            @mouseenter="highlightIndex = idx"
            class="w-full px-3 py-2 text-left text-sm transition flex items-center gap-2"
            :class="[
              isSelected(opt) ? 'bg-teal-50 text-teal-700 font-medium' : 'text-stone-700',
              highlightIndex === idx ? 'bg-stone-100' : 'hover:bg-stone-50',
            ]"
          >
            <!-- Check icon -->
            <svg
              v-if="isSelected(opt)"
              class="w-4 h-4 text-teal-600 flex-shrink-0"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              />
            </svg>
            <span v-else class="w-4 flex-shrink-0" />

            <span class="truncate flex-1">{{ getLabel(opt) }}</span>

            <!-- Optional sub-text/badge slot -->
            <slot name="option-suffix" :option="opt" />
          </button>
        </div>

        <!-- Footer info -->
        <div
          v-if="filteredOptions.length > 5"
          class="px-3 py-1.5 border-t border-stone-100 bg-stone-50"
        >
          <p class="text-[10px] text-stone-400">
            {{ filteredOptions.length }} kết quả · ↑↓ điều hướng · Enter chọn
          </p>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onBeforeUnmount, onMounted } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number, null], default: '' },
  options: { type: Array, default: () => [] },
  labelKey: { type: String, default: 'name' },
  valueKey: { type: String, default: 'id' },
  placeholder: { type: String, default: '-- Chọn --' },
  searchPlaceholder: { type: String, default: 'Tìm kiếm...' },
  emptyText: { type: String, default: 'Không có dữ liệu' },
  searchable: { type: Boolean, default: true },
  clearable: { type: Boolean, default: true },
  disabled: { type: Boolean, default: false },
  dropdownClass: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue', 'change'])

const rootRef = ref(null)
const searchInputRef = ref(null)
const listRef = ref(null)
const isOpen = ref(false)
const searchQuery = ref('')
const highlightIndex = ref(0)

// ─── Computed ───────────────────────────────
const selected = computed(() =>
  props.options.find((o) => String(getValue(o)) === String(props.modelValue)),
)

const filteredOptions = computed(() => {
  if (!searchQuery.value.trim()) return props.options
  const q = searchQuery.value.toLowerCase()
  return props.options.filter((o) => {
    const label = String(getLabel(o)).toLowerCase()
    return label.includes(q)
  })
})

// ─── Helpers ────────────────────────────────
function getLabel(opt) {
  return typeof opt === 'object' ? opt[props.labelKey] : opt
}

function getValue(opt) {
  return typeof opt === 'object' ? opt[props.valueKey] : opt
}

function getKey(opt) {
  return getValue(opt)
}

function isSelected(opt) {
  return String(getValue(opt)) === String(props.modelValue)
}

// ─── Actions ────────────────────────────────
function toggleOpen() {
  if (props.disabled) return
  isOpen.value ? close() : open()
}

async function open() {
  isOpen.value = true
  searchQuery.value = ''
  highlightIndex.value = Math.max(0, filteredOptions.value.findIndex(isSelected))

  await nextTick()
  if (props.searchable && searchInputRef.value) {
    searchInputRef.value.focus()
  }
  scrollToHighlighted()
}

function close() {
  isOpen.value = false
  searchQuery.value = ''
}

function selectOption(opt) {
  const value = getValue(opt)
  emit('update:modelValue', value)
  emit('change', value, opt)
  close()
}

function selectHighlighted() {
  const opt = filteredOptions.value[highlightIndex.value]
  if (opt) selectOption(opt)
}

function clearSelection() {
  emit('update:modelValue', '')
  emit('change', '', null)
}

function moveHighlight(delta) {
  if (!filteredOptions.value.length) return
  const len = filteredOptions.value.length
  highlightIndex.value = (highlightIndex.value + delta + len) % len
  scrollToHighlighted()
}

async function scrollToHighlighted() {
  await nextTick()
  if (!listRef.value) return
  const items = listRef.value.querySelectorAll('button')
  const item = items[highlightIndex.value]
  if (item) {
    item.scrollIntoView({ block: 'nearest' })
  }
}

// Reset highlight khi search thay đổi
watch(searchQuery, () => {
  highlightIndex.value = 0
})

// ─── Click outside ──────────────────────────
function handleClickOutside(e) {
  if (rootRef.value && !rootRef.value.contains(e.target)) {
    close()
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>
