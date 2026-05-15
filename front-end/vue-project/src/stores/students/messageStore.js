// src/stores/students/messageStore.js (hoặc file message store của bạn)
// Update các action liên quan

import { defineStore } from 'pinia'
import { ref } from 'vue'
import { messageApi } from '@/api/students/studentApi'

export const useMessageStore = defineStore('message', () => {
  const messages = ref([])
  const pagination = ref({ current_page: 1, last_page: 1, per_page: 30, total: 0 })
  const loading = ref(false)
  const sending = ref(false)

  async function fetchMessages(groupId, { page = 1, perPage = 30 } = {}) {
    loading.value = true
    try {
      const { data } = await messageApi.getMessages(groupId, { page, perPage })
      // Reverse vì backend trả desc, UI cần hiện cũ → mới
      messages.value = (data.messages || []).reverse()
      pagination.value = data.pagination || pagination.value
      return { success: true }
    } catch (e) {
      return { success: false, message: e.response?.data?.message }
    } finally {
      loading.value = false
    }
  }

  // ✅ Send với mentions + files
  async function sendMessage(groupId, payload) {
    sending.value = true
    try {
      const { data } = await messageApi.send(groupId, payload)
      messages.value.push(data.message)
      return { success: true, data: data.message }
    } catch (e) {
      return {
        success: false,
        message: e.response?.data?.message ?? 'Gửi thất bại',
        errors: e.response?.data?.errors ?? {},
      }
    } finally {
      sending.value = false
    }
  }

  async function deleteMessage(messageId) {
    try {
      await messageApi.delete(messageId)
      messages.value = messages.value.filter((m) => m.id !== messageId)
      return { success: true }
    } catch (e) {
      return { success: false, message: e.response?.data?.message }
    }
  }

  async function deleteAttachment(messageId, attachmentId) {
    try {
      await messageApi.deleteAttachment(attachmentId)
      // Cập nhật local: xóa attachment khỏi message
      const msg = messages.value.find((m) => m.id === messageId)
      if (msg?.attachments) {
        msg.attachments = msg.attachments.filter((a) => a.id !== attachmentId)
      }
      return { success: true }
    } catch (e) {
      return { success: false, message: e.response?.data?.message }
    }
  }

  function reset() {
    messages.value = []
    pagination.value = { current_page: 1, last_page: 1, per_page: 30, total: 0 }
  }

  return {
    messages,
    pagination,
    loading,
    sending,
    fetchMessages,
    sendMessage,
    deleteMessage,
    deleteAttachment,
    reset,
  }
})
