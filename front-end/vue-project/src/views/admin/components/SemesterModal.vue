<script setup>
import { reactive, watch, computed } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  editingItem: {
    type: Object,
    default: null,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['close', 'save'])

// Giá trị mặc định của form
const defaultForm = {
  id: null,
  name: '',
  code: '',
  year: new Date().getFullYear(),
  start_date: '',
  end_date: '',
  is_active: false,
}

const form = reactive({ ...defaultForm })

const formatDateForInput = (dateString) => {
  if (!dateString) return ''
  return dateString.toString().substring(0, 10)
}

// Watch để cập nhật form khi mở modal hoặc đổi item
watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      if (props.editingItem) {
        // Edit Mode
        Object.assign(form, {
          id: props.editingItem.id,
          name: props.editingItem.name,
          code: props.editingItem.code,
          year: props.editingItem.year,
          start_date: formatDateForInput(props.editingItem.start_date),
          end_date: formatDateForInput(props.editingItem.end_date),
          is_active: Boolean(props.editingItem.is_active),
        })
      } else {
        // Create Mode: Reset form
        Object.assign(form, defaultForm)
        form.is_active = true
      }
    }
  },
)

const closeModal = () => {
  emit('close')
}

const handleSubmit = () => {
  // Gửi form data về cha để xử lý gọi API
  emit('save', { ...form })
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm px-4"
    >
      <div class="bg-white rounded-xl shadow-xl w-full max-w-lg overflow-hidden animate-fade-in-up">
        <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
          <h3 class="text-lg font-bold text-gray-800">
            {{ editingItem ? 'Cập nhật Học kỳ' : 'Thêm Học kỳ mới' }}
          </h3>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tên hiển thị</label>
              <input
                v-model="form.name"
                type="text"
                placeholder="Ví dụ: Học kỳ 1"
                class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                required
              />
              <p v-if="errors.name" class="text-red-500 text-xs mt-1">{{ errors.name[0] }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Mã học kỳ</label>
              <input
                v-model="form.code"
                type="text"
                placeholder="HK1-2024"
                class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 uppercase"
                required
              />
              <p v-if="errors.code" class="text-red-500 text-xs mt-1">{{ errors.code[0] }}</p>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Năm học</label>
            <input
              v-model="form.year"
              type="number"
              min="2000"
              class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
              required
            />
            <p v-if="errors.year" class="text-red-500 text-xs mt-1">{{ errors.year[0] }}</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Ngày bắt đầu</label>
              <input
                v-model="form.start_date"
                type="date"
                class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                required
              />
              <p v-if="errors.start_date" class="text-red-500 text-xs mt-1">
                {{ errors.start_date[0] }}
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Ngày kết thúc</label>
              <input
                v-model="form.end_date"
                type="date"
                class="w-full border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                required
              />
              <p v-if="errors.end_date" class="text-red-500 text-xs mt-1">
                {{ errors.end_date[0] }}
              </p>
            </div>
          </div>

          <div class="flex items-center mt-2">
            <input
              v-model="form.is_active"
              id="is_active"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label for="is_active" class="ml-2 block text-sm text-gray-900">
              Kích hoạt ngay (Sẽ tự động đóng các kỳ khác)
            </label>
          </div>

          <div class="flex justify-end gap-3 pt-4 border-t mt-4">
            <button
              type="button"
              @click="closeModal"
              class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
            >
              Hủy
            </button>
            <button
              type="submit"
              class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm"
            >
              {{ editingItem ? 'Cập nhật' : 'Tạo mới' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.animate-fade-in-up {
  animation: fadeInUp 0.3s ease-out;
}
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
