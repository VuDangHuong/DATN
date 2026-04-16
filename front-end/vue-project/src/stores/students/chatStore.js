// src/stores/chatStore.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { messageApi } from '@/api/students/studentApi'

export const useChatStore = defineStore('chat', () => {
  const messages = ref([])
  const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
  const loading = ref(false)
  const sending = ref(false)
  const error = ref(null)

  // Lấy tin nhắn
  async function fetchMessages(groupId, page = 1) {
    loading.value = true
    error.value = null
    try {
      const { data } = await messageApi.getMessages(groupId, page)
      if (page === 1) {
        messages.value = data.messages || []
      } else {
        // Load thêm tin cũ
        messages.value = [...(data.messages || []), ...messages.value]
      }
      pagination.value = data.pagination || pagination.value
    } catch (err) {
      error.value = err.response?.data?.message || 'Lỗi tải tin nhắn'
    } finally {
      loading.value = false
    }
  }

  // Gửi tin nhắn
  async function sendMessage(groupId, content) {
    sending.value = true
    error.value = null
    try {
      const { data } = await messageApi.send(groupId, content)
      // Thêm tin mới vào cuối
      messages.value.push(data.message)
      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || 'Lỗi gửi tin nhắn'
      return { success: false, message: error.value }
    } finally {
      sending.value = false
    }
  }

  function clearMessages() {
    messages.value = []
    pagination.value = { current_page: 1, last_page: 1, total: 0 }
  }

  return {
    messages,
    pagination,
    loading,
    sending,
    error,
    fetchMessages,
    sendMessage,
    clearMessages,
  }
})
