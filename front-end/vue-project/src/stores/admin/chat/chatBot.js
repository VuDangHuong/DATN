import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { knowledgeApi } from '@/api/admin/chat/chatBot'
import { useToastStore } from '@/stores/toast'

export const useChatbotStore = defineStore('chatbot', () => {
  const toast = useToastStore()

  // ─── State ──────────────────────────────────────────────────────────────────
  const items = ref([])
  const categories = ref([])
  const total = ref(0)
  const currentPage = ref(1)
  const perPage = ref(10)
  const loading = ref(false)
  const importing = ref(false)

  const filters = ref({
    search: '',
    category: null, // tên danh mục (string) hoặc null = tất cả
    status: null, // 'active' | 'draft' | 'pending' | null = tất cả
    sort_by: 'date',
  })

  // ─── Computed ────────────────────────────────────────────────────────────────
  const totalPages = computed(() => Math.ceil(total.value / perPage.value) || 1)

  // ─── Helper: chuẩn hoá 1 item từ backend → frontend ─────────────────────────
  // Backend trả về: { id, question, answer, category, created_at, updated_at, ... }
  // Frontend cần:   { id, title, content, category, type, typeLabel, status, tags,
  //                   description, size, updatedAt }
  function normalizeItem(raw) {
    return {
      id: raw.id,
      title: raw.question ?? raw.title ?? '',
      content: raw.answer ?? raw.content ?? '',
      description: raw.description ?? '',
      category: raw.category ?? raw.category_name ?? '',
      category_id: raw.category_id ?? null,
      type: raw.type ?? 'faq',
      typeLabel: (raw.type ?? 'faq').toUpperCase(),
      status: raw.status ?? 'active',
      tags: raw.tags ?? '',
      size: raw.size ?? '—',
      updatedAt: raw.updated_at
        ? new Date(raw.updated_at).toLocaleDateString('vi-VN')
        : (raw.updatedAt ?? '—'),
    }
  }

  // ─── Actions ─────────────────────────────────────────────────────────────────

  /** GET /knowledge */
  async function fetchList() {
    loading.value = true
    try {
      const params = {
        page: currentPage.value,
        per_page: perPage.value,
        ...Object.fromEntries(
          Object.entries(filters.value).filter(([, v]) => v !== null && v !== ''),
        ),
      }
      const res = await knowledgeApi.getList(params)

      // Hỗ trợ nhiều dạng response:
      // 1. { success, data: { current_page, data: [...], total } }  ← backend hiện tại
      // 2. { data: { data: [...], total } }                          ← Laravel paginate thường
      // 3. { data: [...], total }
      const payload = res.data?.data ?? res.data // bóc lớp { success, data: ... }
      const list = payload?.data ?? payload // bóc lớp paginate { data: [...] }

      items.value = Array.isArray(list) ? list.map(normalizeItem) : []
      total.value = payload?.total ?? payload?.meta?.total ?? items.value.length
    } catch (err) {
      toast.error('Không thể tải danh sách tri thức')
      console.error(err)
    } finally {
      loading.value = false
    }
  }

  /** GET /knowledge/categories */
  async function fetchCategories() {
    try {
      const res = await knowledgeApi.getCategories()
      const raw = res.data?.data ?? res.data ?? []
      const list = Array.isArray(raw) ? raw : []

      const colors = [
        '#3b82f6',
        '#10b981',
        '#f59e0b',
        '#ef4444',
        '#8b5cf6',
        '#6b7280',
        '#ec4899',
        '#14b8a6',
      ]

      categories.value = [
        { id: null, name: 'Tất cả', count: 0, color: '#6366f1' },
        ...list.map((item, idx) => {
          let name =
            typeof item === 'string' ? item : (item.name ?? item.category ?? item.label ?? '')
          const id = typeof item === 'string' ? name : (item.id ?? name)
          const count = typeof item === 'object' ? (item.count ?? item.total ?? 0) : 0
          const color =
            typeof item === 'object' && item.color ? item.color : colors[idx % colors.length]

          // Bỏ dấu ngoặc kép thừa nếu backend trả về `"\"Thông tin chung\""`
          name = name.replace(/^"+|"+$/g, '').trim()

          return { id: name, name, count, color }
        }),
      ]
    } catch (err) {
      toast.error('Không thể tải danh mục')
      console.error(err)
    }
  }

  /** POST /knowledge */
  async function createItem(payload) {
    try {
      await knowledgeApi.create(payload)
      toast.success('Thêm tri thức mới thành công')
      // Reload cả list lẫn categories vì có thể thêm danh mục mới
      await Promise.all([fetchList(), fetchCategories()])
      return true
    } catch (err) {
      const res = err?.response?.data
      const message = res?.message ?? 'Thêm tri thức thất bại'
      const errors = res?.errors ?? {}
      const detail = Object.values(errors).flat().join(' | ')
      toast.error(detail ? `${message}: ${detail}` : message)
      console.error('[createItem] 422 errors:', errors)
      return null
    }
  }

  /** PUT /knowledge/{id} */
  async function updateItem(id, payload) {
    try {
      const res = await knowledgeApi.update(id, payload)
      toast.success('Cập nhật tri thức thành công')
      // Cập nhật trực tiếp trong mảng
      const raw = res.data?.data ?? res.data
      const idx = items.value.findIndex((i) => i.id === id)
      if (idx > -1 && raw) items.value.splice(idx, 1, normalizeItem(raw))
      return true
    } catch (err) {
      toast.error(err?.response?.data?.message ?? 'Cập nhật tri thức thất bại')
      console.error(err)
      return null
    }
  }

  /** DELETE /knowledge/{id} */
  async function deleteItem(id) {
    try {
      await knowledgeApi.remove(id)
      toast.success('Đã xóa tri thức thành công')
      items.value = items.value.filter((i) => i.id !== id)
      total.value = Math.max(0, total.value - 1)
      if (items.value.length === 0 && currentPage.value > 1) {
        currentPage.value--
        await fetchList()
      }
    } catch (err) {
      toast.error(err?.response?.data?.message ?? 'Xóa tri thức thất bại')
      console.error(err)
    }
  }

  /** POST /knowledge/import */
  async function importFile(file) {
    importing.value = true
    try {
      const res = await knowledgeApi.importCsv(file)
      const msg = res.data?.message ?? res.data?.data?.message
      toast.success(msg ?? 'Đã nhập tài liệu thành công')
      // Reload cả list lẫn categories vì import có thể thêm danh mục mới
      await Promise.all([fetchList(), fetchCategories()])
      return true
    } catch (err) {
      toast.error(err?.response?.data?.message ?? 'Nhập tài liệu thất bại')
      console.error(err)
      return false
    } finally {
      importing.value = false
    }
  }

  /** Đổi trang */
  async function goToPage(page) {
    currentPage.value = page
    await fetchList()
  }

  /** Áp dụng filter và reset trang 1 */
  async function applyFilters(newFilters) {
    filters.value = { ...filters.value, ...newFilters }
    currentPage.value = 1
    await fetchList()
  }

  return {
    items,
    categories,
    total,
    currentPage,
    perPage,
    loading,
    importing,
    filters,
    totalPages,
    fetchList,
    fetchCategories,
    createItem,
    updateItem,
    deleteItem,
    importFile,
    goToPage,
    applyFilters,
  }
})
