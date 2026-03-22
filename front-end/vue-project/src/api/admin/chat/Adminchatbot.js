import axiosInstance from '@/api/axiosClient'

const BASE = '/admin/chatbot'

export const adminChatbotApi = {
  // Chỉ text
  ask(question, category = null) {
    const payload = { question }
    if (category) payload.category = category
    return axiosInstance.post(`${BASE}/ask`, payload)
  },

  // ✅ Có file → FormData (Content-Type tự set multipart)
  askWithFile(formData) {
    return axiosInstance.post(`${BASE}/ask`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
  },

  getHistory() {
    return axiosInstance.get(`${BASE}/history`)
  },

  feedback(id, data) {
    return axiosInstance.post(`${BASE}/${id}/feedback`, data)
  },

  suggestedQuestions(question, answer) {
    return axiosInstance.post(`${BASE}/suggested-questions`, { question, answer })
  },
}
