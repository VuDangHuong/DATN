<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { storeToRefs } from 'pinia'

import { useUserStore } from '@/stores/admin/user'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import SvgIcon from '@/components/icons/SVG.vue'
import UserForm from './components/UserForm.vue'
import { getAvatarUrl, handleImageError } from '@/utils/imageHelper'
import { useConfirm } from '@/composables/useConfirm'
import ConfirmModal from '@/components/common/ConfirmModal.vue'

/* =======================
   STORES
======================= */
const userStore = useUserStore()
const authStore = useAuthStore()
const toast = useToastStore()
const {
  state: confirmState,
  confirmDelete,
  setLoading: setConfirmLoading,
  close: closeConfirm,
  _handleConfirm,
  _handleCancel,
} = useConfirm()
const { users, loading, pagination, filters } = storeToRefs(userStore)

/* =======================
   LOCAL STATE
======================= */
const showFormModal = ref(false)
const showImportModal = ref(false)
const isEditing = ref(false)
const selectedUser = ref(null)
const importFile = ref(null)

/* =======================
   WATCHERS
======================= */

// Đồng bộ avatar user đăng nhập trong bảng
watch(
  () => authStore.user?.avatar,
  (avatar) => {
    if (!authStore.user || !avatar) return

    const user = users.value.find((u) => u.id === authStore.user.id)
    if (user) user.avatar = avatar
  },
)

// Lọc theo role
watch(() => filters.value.role, userStore.setFilterRole)

/* =======================
   COMPUTED
======================= */

// Pagination thông minh
const visiblePages = computed(() => {
  const { current_page, last_page } = pagination.value
  const delta = 1
  const pages = []

  for (let i = 1; i <= last_page; i++) {
    if (i === 1 || i === last_page || Math.abs(i - current_page) <= delta) {
      pages.push(i)
    }
  }

  return pages.reduce((acc, page, i) => {
    if (i && page - pages[i - 1] > 1) acc.push('...')
    acc.push(page)
    return acc
  }, [])
})

/* =======================
   HELPERS
======================= */
const debounce = (fn, delay = 300) => {
  let timer
  return (...args) => {
    clearTimeout(timer)
    timer = setTimeout(() => fn(...args), delay)
  }
}

//HANDLERS

// Search
const handleSearch = debounce(() => {
  filters.value.page = 1
  userStore.fetchUsers()
}, 500)

// Modal
const openCreateModal = () => {
  isEditing.value = false
  selectedUser.value = {}
  showFormModal.value = true
}

const openEditModal = (user) => {
  isEditing.value = true
  selectedUser.value = { ...user }
  showFormModal.value = true
}

// Save
const handleSaveUser = async (data) => {
  const action = isEditing.value ? userStore.updateUser(data.id, data) : userStore.createUser(data)

  const result = await action

  result.success ? toast.success(result.message) : toast.error(result.message)

  if (result.success) showFormModal.value = false
}

// Delete
const handleDelete = async (user) => {
  // ✅ Mở modal xác nhận - chờ user click
  const ok = await confirmDelete(user.name, {
    title: 'Xóa người dùng',
    message:
      'Hành động này sẽ xóa vĩnh viễn người dùng và toàn bộ dữ liệu liên quan. Không thể hoàn tác.',
    warningText: `Mã: ${user.code} · Email: ${user.email}`,
    confirmText: 'Xóa người dùng',
    cancelText: 'Hủy',
    // Có thể thêm nếu muốn user phải gõ tên để confirm:
    // requireTypeConfirm: user.code,
  })
  if (!ok) return

  // ✅ Bật loading state trên modal
  setConfirmLoading(true)
  try {
    const result = await userStore.deleteUser(user.id)
    if (result.success) {
      toast.success(result.message)
    } else {
      toast.error(result.message)
    }
  } catch (e) {
    toast.error('Lỗi khi xóa: ' + (e.message ?? 'Unknown'))
  } finally {
    closeConfirm() // Đóng modal sau khi xong
  }
}
// Status
const handleStatusToggle = async (user) => {
  try {
    await userStore.toggleStatus(user)
    toast.success('Đã cập nhật trạng thái')
  } catch {
    toast.error('Không thể thay đổi trạng thái')
  }
}

// Import
const onFileChange = (e) => {
  importFile.value = e.target.files[0]
}

const handleImportFile = async () => {
  if (!importFile.value) return alert('Vui lòng chọn file')

  const formData = new FormData()
  formData.append('file', importFile.value)

  const result = await userStore.importUsers(formData)

  result.success ? toast.success(result.message) : toast.error(result.message)

  if (result.success) showImportModal.value = false
}

// Pagination
const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    userStore.setPage(page)
  }
}

onMounted(userStore.fetchUsers)
</script>

<template>
  <div class="p-6">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
      <h1 class="text-2xl font-bold text-gray-800">Quản lý người dùng</h1>
      <div class="flex gap-2">
        <button
          @click="showImportModal = true"
          class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 flex items-center"
        >
          <SvgIcon name="upload" class="h-5 w-5 mr-2" />
          Import Excel
        </button>
        <button
          @click="openCreateModal"
          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center"
        >
          <SvgIcon name="plus" class="h-5 w-5 mr-2" />
          Thêm mới
        </button>
      </div>
    </div>

    <div class="bg-white p-4 rounded-lg shadow mb-6 flex flex-wrap gap-4 items-center">
      <div class="flex bg-gray-100 p-1 rounded-lg overflow-x-auto max-w-full">
        <button
          v-for="role in ['all', 'lecturer', 'student', 'admin']"
          :key="role"
          @click="filters.role = role"
          :class="[
            'px-4 py-1.5 rounded-md text-sm font-medium transition-colors whitespace-nowrap',
            filters.role === role
              ? 'bg-white text-blue-600 shadow-sm'
              : 'text-gray-500 hover:text-gray-700',
          ]"
        >
          {{
            role === 'all'
              ? 'Tất cả'
              : role === 'lecturer'
                ? 'Giảng viên'
                : role === 'admin'
                  ? 'Quản trị viên'
                  : 'Sinh viên'
          }}
        </button>
      </div>
      <div class="flex-1 min-w-[200px] relative">
        <span class="absolute left-3 top-2.5 text-gray-400">
          <SvgIcon name="search" class="h-5 w-5" />
        </span>
        <input
          v-model="filters.search"
          @input="handleSearch"
          type="text"
          placeholder="Tìm theo tên, email, mã số..."
          class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
        />
      </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="hidden md:block overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Thông tin
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Vai trò
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Liên hệ
              </th>
              <th
                class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Trạng thái
              </th>
              <th
                class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                Hành động
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="loading">
              <td colspan="5" class="text-center py-8 text-gray-500">Đang tải dữ liệu...</td>
            </tr>
            <tr v-else-if="users.length === 0">
              <td colspan="5" class="text-center py-8 text-gray-500">
                Không tìm thấy kết quả nào.
              </td>
            </tr>

            <tr
              v-for="user in users"
              :key="user.id + '-desktop'"
              class="hover:bg-gray-50 transition"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <img
                      class="h-10 w-10 rounded-full object-cover"
                      :src="getAvatarUrl(user)"
                      :alt="user.name"
                      @error="(e) => handleImageError(e, user.name)"
                    />
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                    <div class="text-sm text-gray-500">{{ user.code }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  :class="[
                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                    user.role === 'admin'
                      ? 'bg-red-100 text-red-800'
                      : user.role === 'lecturer'
                        ? 'bg-purple-100 text-purple-800'
                        : 'bg-green-100 text-green-800',
                  ]"
                >
                  {{
                    user.role === 'lecturer'
                      ? 'Giảng viên'
                      : user.role === 'admin'
                        ? 'Quản trị'
                        : 'Sinh viên'
                  }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">{{ user.email }}</div>
                <div class="text-sm text-gray-500">{{ user.phone || '---' }}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <button
                  @click="handleStatusToggle(user)"
                  :class="[
                    'relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none',
                    user.is_active ? 'bg-blue-600' : 'bg-gray-200',
                  ]"
                >
                  <span
                    aria-hidden="true"
                    :class="[
                      'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200',
                      user.is_active ? 'translate-x-5' : 'translate-x-0',
                    ]"
                  />
                </button>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button
                  @click="openEditModal(user)"
                  class="text-indigo-600 hover:text-indigo-900 mr-3"
                >
                  <SvgIcon name="edit" class="h-4 w-4 mr-1" />
                  Sửa
                </button>
                <button @click="handleDelete(user)" class="text-red-600 hover:text-red-900">
                  <SvgIcon name="trash" class="h-4 w-4 mr-1" />
                  Xóa
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="md:hidden">
        <div v-if="loading" class="p-4 text-center text-gray-500">Đang tải dữ liệu...</div>
        <div v-else-if="users.length === 0" class="p-4 text-center text-gray-500">
          Không tìm thấy kết quả nào.
        </div>

        <div v-else class="space-y-4 p-4 bg-gray-50">
          <div
            v-for="user in users"
            :key="user.id + '-mobile'"
            class="bg-white rounded-lg shadow border border-gray-100 overflow-hidden"
          >
            <div class="flex items-center p-4 border-b border-gray-100">
              <img
                class="h-12 w-12 rounded-full object-cover border border-gray-200"
                :src="getAvatarUrl(user)"
                :alt="user.name"
                @error="(e) => handleImageError(e, user.name)"
              />
              <div class="ml-3 flex-1">
                <div class="text-sm font-bold text-gray-900">{{ user.name }}</div>
                <div class="text-xs text-gray-500 font-mono">{{ user.code }}</div>
              </div>
              <span
                :class="[
                  'px-2 py-1 text-xs font-semibold rounded-full',
                  user.role === 'admin'
                    ? 'bg-red-100 text-red-800'
                    : user.role === 'lecturer'
                      ? 'bg-purple-100 text-purple-800'
                      : 'bg-green-100 text-green-800',
                ]"
              >
                {{ user.role === 'lecturer' ? 'GV' : user.role === 'admin' ? 'Admin' : 'SV' }}
              </span>
            </div>

            <div class="p-4 space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-500">Email:</span>
                <span class="text-gray-900 font-medium truncate ml-2 max-w-[200px]">{{
                  user.email
                }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-500">SĐT:</span>
                <span class="text-gray-900">{{ user.phone || '---' }}</span>
              </div>
            </div>

            <div
              class="bg-gray-50 px-4 py-3 flex items-center justify-between border-t border-gray-100"
            >
              <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500">{{
                  user.is_active ? 'Hoạt động' : 'Đang khóa'
                }}</span>
                <button
                  @click="handleStatusToggle(user)"
                  :class="[
                    'relative inline-flex flex-shrink-0 h-5 w-9 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none',
                    user.is_active ? 'bg-blue-600' : 'bg-gray-300',
                  ]"
                >
                  <span
                    aria-hidden="true"
                    :class="[
                      'pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200',
                      user.is_active ? 'translate-x-4' : 'translate-x-0',
                    ]"
                  />
                </button>
              </div>

              <div class="flex gap-3">
                <button
                  @click="openEditModal(user)"
                  class="text-indigo-600 hover:text-indigo-900 text-sm font-medium flex items-center"
                >
                  <SvgIcon name="edit" class="h-4 w-4 mr-1" />
                  Sửa
                </button>
                <button
                  @click="handleDelete(user)"
                  class="text-red-600 hover:text-red-900 text-sm font-medium flex items-center"
                >
                  <SvgIcon name="trash" class="h-4 w-4 mr-1" />
                  Xóa
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div
      class="mt-4 flex flex-col items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:flex-row sm:px-6"
      v-if="pagination.last_page > 1"
    >
      <div class="mb-4 sm:mb-0 hidden sm:block">
        <p class="text-sm text-gray-700">
          Hiển thị trang
          <span class="font-medium">{{ filters.page }}</span>
          trên tổng số
          <span class="font-medium">{{ pagination.last_page }}</span>
          trang ({{ pagination.total }} kết quả)
        </p>
      </div>

      <div class="w-full sm:w-auto flex justify-center sm:justify-end overflow-x-auto pb-2 sm:pb-0">
        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
          <button
            @click="changePage(filters.page - 1)"
            :disabled="filters.page === 1"
            class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span class="sr-only">Previous</span>
            <SvgIcon name="chevron-left" class="h-5 w-5" />
          </button>

          <template v-for="(page, index) in visiblePages" :key="index">
            <span
              v-if="page === '...'"
              class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 focus:outline-offset-0"
              >...</span
            >

            <button
              v-else
              @click="changePage(page)"
              :class="[
                page === filters.page
                  ? 'relative z-10 inline-flex items-center bg-blue-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600'
                  : 'relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0',
              ]"
            >
              {{ page }}
            </button>
          </template>

          <button
            @click="changePage(filters.page + 1)"
            :disabled="filters.page === pagination.last_page"
            class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span class="sr-only">Next</span>
            <SvgIcon name="chevron-right" class="h-5 w-5" />
          </button>
        </nav>
      </div>
    </div>

    <UserForm
      :show="showFormModal"
      :is-editing="isEditing"
      :user-data="selectedUser"
      @close="showFormModal = false"
      @submit="handleSaveUser"
    />

    <div
      v-if="showImportModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    >
      <div class="bg-white p-6 rounded-lg shadow-xl w-96">
        <h3 class="text-lg font-bold mb-4">Import danh sách từ Excel</h3>
        <input
          type="file"
          @change="onFileChange"
          accept=".xlsx, .xls"
          class="mb-4 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
        />
        <div class="flex justify-end gap-2">
          <button
            @click="showImportModal = false"
            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded"
          >
            Hủy
          </button>
          <button
            @click="handleImportFile"
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
          >
            Upload
          </button>
        </div>
      </div>
    </div>
    <ConfirmModal
      v-model="confirmState.show"
      :title="confirmState.title"
      :message="confirmState.message"
      :item-name="confirmState.itemName"
      :warning-text="confirmState.warningText"
      :confirm-text="confirmState.confirmText"
      :cancel-text="confirmState.cancelText"
      :variant="confirmState.variant"
      :loading="confirmState.loading"
      :require-type-confirm="confirmState.requireTypeConfirm"
      @confirm="_handleConfirm"
      @cancel="_handleCancel"
    />
  </div>
</template>
