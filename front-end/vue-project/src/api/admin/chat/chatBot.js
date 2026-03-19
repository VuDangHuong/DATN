import axiosInstance from '@/api/axiosClient'

const BASE = '/admin/knowledge'

// Map field frontend → field backend
function toBackend(data) {
  return {
    question: data.title ?? data.question,
    answer: data.content ?? data.answer,
    category: data.category,
    description: data.description ?? '',
    type: data.type ?? 'faq',
    status: data.status ?? 'active',
    tags: data.tags ?? '',
  }
}

export const knowledgeApi = {
  /**
   * GET /knowledge
   * Params: { page, per_page, search, category_id, status, sort_by }
   */
  getList(params = {}) {
    return axiosInstance.get(BASE, { params })
  },

  /**
   * GET /knowledge/categories
   */
  getCategories() {
    return axiosInstance.get(`${BASE}/categories`)
  },

  /**
   * POST /knowledge
   */
  create(data) {
    return axiosInstance.post(BASE, toBackend(data))
  },

  /**
   * PUT /knowledge/{id}
   */
  update(id, data) {
    return axiosInstance.put(`${BASE}/${id}`, toBackend(data))
  },

  /**
   * DELETE /knowledge/{id}
   */
  remove(id) {
    return axiosInstance.delete(`${BASE}/${id}`)
  },

  /**
   * POST /knowledge/import
   * Body: FormData (key: "file")
   */
  importCsv(file) {
    const form = new FormData()
    form.append('file', file)
    return axiosInstance.post(`${BASE}/import`, form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },
}
