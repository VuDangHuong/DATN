import { defineStore } from 'pinia'
import { ref } from 'vue'
import { adminChatbotApi } from '@/api/admin/chat/Adminchatbot'
import { useToastStore } from '@/stores/toast'

export const useAdminChatbotStore = defineStore('adminChatbot', () => {
  const toast = useToastStore()

  const messages = ref([])
  const loading = ref(false)
  const loadingHistory = ref(false)
  const loadingSuggestions = ref(false)
  const suggestedQuestions = ref([])

  function pushOptimistic(question, fileName = null) {
    const tempId = `temp_${Date.now()}`
    messages.value.push({
      id: tempId,
      isTemp: true,
      user_message: {
        role: 'user',
        content: question,
        file_name: fileName, // ✅ hiển thị tên file trong bubble
        created_at: new Date().toISOString(),
      },
      bot_message: null,
    })
    return tempId
  }

  function replaceTempMessage(tempId, realMsg) {
    const idx = messages.value.findIndex((m) => m.id === tempId)
    if (idx > -1) messages.value.splice(idx, 1, { ...realMsg, isTemp: false })
  }

  async function fetchHistory() {
    loadingHistory.value = true
    try {
      const res = await adminChatbotApi.getHistory()
      const data = res.data?.data ?? []
      messages.value = Array.isArray(data) ? data : []
    } catch (err) {
      toast.error('Không thể tải lịch sử chat')
      console.error(err)
    } finally {
      loadingHistory.value = false
    }
  }

  // ✅ Thêm tham số file
  async function ask(question, category = null, file = null) {
    if (!question.trim() && !file) return

    loading.value = true
    suggestedQuestions.value = []

    const tempId = pushOptimistic(question, file?.name ?? null)

    try {
      let res

      if (file) {
        // ✅ Có file → dùng FormData
        const formData = new FormData()
        if (question.trim()) formData.append('question', question)
        if (category) formData.append('category', category)
        formData.append('file', file)
        res = await adminChatbotApi.askWithFile(formData)
      } else {
        // Chỉ text → JSON như cũ
        res = await adminChatbotApi.ask(question, category)
      }

      const data = res.data?.data ?? res.data

      const newMsg = {
        id: data.id,
        user_message: {
          role: 'user',
          content: data.question,
          file_name: data.file_name ?? null,
          created_at: data.created_at,
        },
        bot_message: {
          role: 'assistant',
          content: data.answer,
          is_answered: data.is_answered,
          is_liked: null,
          star: null,
          sources: data.sources ?? [],
          created_at: data.created_at,
        },
      }

      replaceTempMessage(tempId, newMsg)
      fetchSuggestedQuestions(question, data.answer)
    } catch (err) {
      messages.value = messages.value.filter((m) => m.id !== tempId)
      toast.error(err?.response?.data?.message ?? 'Gửi câu hỏi thất bại')
      console.error(err)
    } finally {
      loading.value = false
    }
  }

  async function fetchSuggestedQuestions(question, answer) {
    loadingSuggestions.value = true
    try {
      const res = await adminChatbotApi.suggestedQuestions(question, answer)
      const data = res.data?.data ?? []
      suggestedQuestions.value = Array.isArray(data) ? data : []
    } catch (err) {
      console.error('[suggestedQuestions]', err)
      suggestedQuestions.value = []
    } finally {
      loadingSuggestions.value = false
    }
  }

  async function sendLike(msgId, isLiked) {
    try {
      await adminChatbotApi.feedback(msgId, { is_liked: isLiked })
      const msg = messages.value.find((m) => m.id === msgId)
      if (msg?.bot_message) {
        msg.bot_message.is_liked = msg.bot_message.is_liked === isLiked ? null : isLiked
      }
    } catch (err) {
      toast.error('Không thể gửi phản hồi')
      console.error(err)
    }
  }

  async function sendStar(msgId, star) {
    try {
      await adminChatbotApi.feedback(msgId, { star })
      const msg = messages.value.find((m) => m.id === msgId)
      if (msg?.bot_message) {
        msg.bot_message.star = msg.bot_message.star === star ? null : star
      }
    } catch (err) {
      toast.error('Không thể gửi đánh giá')
      console.error(err)
    }
  }

  function clearChat() {
    messages.value = []
    suggestedQuestions.value = []
  }

  return {
    messages,
    loading,
    loadingHistory,
    loadingSuggestions,
    suggestedQuestions,
    fetchHistory,
    ask,
    fetchSuggestedQuestions,
    sendLike,
    sendStar,
    clearChat,
  }
})
