<!-- src/views/chat/ChatView.vue -->
<template>
  <div class="flex flex-col" style="height: calc(100vh - 7rem)">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-2xl font-bold text-slate-800">Chat nhóm</h2>
        <p v-if="groupName" class="text-slate-500 mt-0.5 text-sm">{{ groupName }}</p>
      </div>
    </div>

    <!-- No group warning -->
    <div v-if="!groupId" class="flex-1 flex items-center justify-center">
      <div class="text-center">
        <div
          class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-100 flex items-center justify-center"
        >
          <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
            />
          </svg>
        </div>
        <p class="text-slate-500">Bạn chưa có nhóm để chat</p>
      </div>
    </div>

    <!-- Chat area -->
    <template v-else>
      <!-- Messages -->
      <div
        ref="messagesContainer"
        class="flex-1 bg-white rounded-2xl border border-slate-200 overflow-y-auto p-4 space-y-3"
      >
        <!-- Load more -->
        <div v-if="pagination.current_page < pagination.last_page" class="text-center">
          <button
            @click="loadMore"
            :disabled="loading"
            class="text-sm text-indigo-600 hover:text-indigo-700 font-medium"
          >
            {{ loading ? 'Đang tải...' : '↑ Tải tin nhắn cũ' }}
          </button>
        </div>

        <!-- Loading -->
        <div v-if="loading && messages.length === 0" class="flex justify-center py-10">
          <div
            class="w-6 h-6 border-2 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"
          />
        </div>

        <!-- Empty -->
        <div
          v-if="!loading && messages.length === 0"
          class="flex items-center justify-center py-16 text-slate-400 text-sm"
        >
          Chưa có tin nhắn. Hãy bắt đầu cuộc trò chuyện!
        </div>

        <!-- Message bubbles -->
        <div
          v-for="msg in sortedMessages"
          :key="msg.id"
          class="flex gap-2.5"
          :class="isMyMessage(msg) ? 'flex-row-reverse' : ''"
        >
          <!-- Avatar -->
          <div
            class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center text-xs font-bold text-white"
            :class="
              isMyMessage(msg)
                ? 'bg-gradient-to-br from-indigo-500 to-purple-600'
                : 'bg-gradient-to-br from-slate-400 to-slate-500'
            "
          >
            {{ msg.user?.name?.charAt(0) || '?' }}
          </div>

          <!-- Bubble -->
          <div class="max-w-[70%]">
            <p v-if="!isMyMessage(msg)" class="text-xs text-slate-400 mb-1 ml-1">
              {{ msg.user?.name }}
            </p>
            <div
              class="px-4 py-2.5 rounded-2xl text-sm leading-relaxed"
              :class="
                isMyMessage(msg)
                  ? 'bg-indigo-600 text-white rounded-tr-md'
                  : 'bg-slate-100 text-slate-700 rounded-tl-md'
              "
            >
              {{ msg.content }}
            </div>
            <p
              class="text-[10px] text-slate-400 mt-1"
              :class="isMyMessage(msg) ? 'text-right mr-1' : 'ml-1'"
            >
              {{ formatTime(msg.created_at) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Input -->
      <div class="mt-3 bg-white rounded-2xl border border-slate-200 p-3 flex items-end gap-3">
        <textarea
          v-model="messageInput"
          @keydown.enter.exact.prevent="handleSend"
          placeholder="Nhập tin nhắn... (Enter để gửi)"
          rows="1"
          class="flex-1 resize-none border-0 text-sm focus:ring-0 placeholder-slate-400 max-h-32"
        />
        <button
          @click="handleSend"
          :disabled="!messageInput.trim() || sending"
          class="p-2.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 disabled:opacity-50 transition-colors flex-shrink-0"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
            />
          </svg>
        </button>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useDashboardStore } from '@/stores/students/dashboardStore'
import { useChatStore } from '@/stores/students/chatStore'
import { storeToRefs } from 'pinia'

const route = useRoute()
const dashboardStore = useDashboardStore()
const chatStore = useChatStore()
const { messages, pagination, loading, sending } = storeToRefs(chatStore)

const messagesContainer = ref(null)
const messageInput = ref('')
const user = JSON.parse(localStorage.getItem('user') || '{}')

const groupId = computed(() => {
  const fromRoute = route.query.group_id
  if (fromRoute) return Number(fromRoute)
  return dashboardStore.myGroup?.id || null
})

const groupName = computed(() => dashboardStore.myGroup?.name || '')

const sortedMessages = computed(() =>
  [...messages.value].sort((a, b) => new Date(a.created_at) - new Date(b.created_at)),
)

function isMyMessage(msg) {
  return msg.user?.id === user.id
}

function formatTime(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return (
    d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) +
    ' · ' +
    d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' })
  )
}

async function handleSend() {
  if (!messageInput.value.trim() || !groupId.value) return
  const result = await chatStore.sendMessage(groupId.value, messageInput.value.trim())
  if (result.success) {
    messageInput.value = ''
    await nextTick()
    scrollToBottom()
  }
}

function loadMore() {
  if (groupId.value) {
    chatStore.fetchMessages(groupId.value, pagination.value.current_page + 1)
  }
}

function scrollToBottom() {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

// Load messages khi groupId thay đổi
watch(
  groupId,
  (id) => {
    if (id) {
      chatStore.clearMessages()
      chatStore.fetchMessages(id).then(() => nextTick(() => scrollToBottom()))
    }
  },
  { immediate: true },
)
</script>
