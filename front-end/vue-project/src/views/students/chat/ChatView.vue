<!-- src/views/students/chat/ChatView.vue -->
<template>
  <div class="flex flex-col h-[calc(100vh-100px)] bg-white rounded-2xl border border-slate-200">
    <!-- Header -->
    <div class="px-5 py-3 border-b border-slate-200 flex items-center justify-between">
      <div>
        <h3 class="text-sm font-bold text-slate-800">{{ groupName }}</h3>
        <p class="text-xs text-slate-400">{{ members.length }} thành viên</p>
      </div>
    </div>

    <!-- Messages list -->
    <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-3">
      <div v-if="messageStore.loading" class="flex justify-center py-8">
        <div
          class="w-6 h-6 border-2 border-indigo-300 border-t-indigo-600 rounded-full animate-spin"
        />
      </div>
      <div v-else-if="!messages.length" class="text-center py-12 text-sm text-slate-400">
        Chưa có tin nhắn nào. Hãy bắt đầu cuộc trò chuyện!
      </div>
      <ChatMessageItem
        v-for="message in messages"
        :key="message.id"
        :message="message"
        :current-user-id="user.id"
        :is-leader="isLeader"
        @delete="handleDeleteMessage"
        @delete-attachment="(attId) => handleDeleteAttachment(message.id, attId)"
      />
    </div>

    <!-- Input -->
    <ChatMessageInput
      :members="formattedMembers"
      :sending="messageStore.sending"
      @send="handleSend"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { storeToRefs } from 'pinia'
import { useMessageStore } from '@/stores/students/messageStore'
import { useDashboardStore } from '@/stores/students/dashboardStore'
import { useGroupStore } from '@/stores/students/groupStore'
import { useToastStore } from '@/stores/toast'
import ChatMessageItem from '../components/chat/ChatMessageItem.vue'
import ChatMessageInput from '../components/chat/ChatMessageInput.vue'

const messageStore = useMessageStore()
const dashboardStore = useDashboardStore()
const groupStore = useGroupStore()
const toast = useToastStore()

const { messages } = storeToRefs(messageStore)
const messagesContainer = ref(null)

const user = JSON.parse(localStorage.getItem('user') || '{}')

// ── Computed ───────────────────────────
const groupId = computed(() => dashboardStore.myGroup?.id || null)
const groupName = computed(() => dashboardStore.myGroup?.name || 'Nhóm chat')
const isLeader = computed(() => dashboardStore.myGroup?.leader?.id === user.id)
const members = computed(
  () => groupStore.currentGroup?.members || dashboardStore.myGroup?.members || [],
)

// ✅ Members format cho mention picker
const formattedMembers = computed(
  () =>
    members.value
      .map((m) => ({
        id: m.user?.id ?? m.id,
        name: m.user?.name ?? m.name,
        code: m.user?.code ?? m.code,
        avatar_url: m.user?.avatar_url ?? m.avatar_url,
      }))
      .filter((m) => m.id !== user.id), // Bỏ chính mình khỏi mention list
)

// ── Lifecycle ──────────────────────────
onMounted(async () => {
  if (groupId.value) {
    await messageStore.fetchMessages(groupId.value)
    scrollToBottom()
  }
})

watch(groupId, async (id) => {
  if (id) {
    await messageStore.fetchMessages(id)
    scrollToBottom()
  }
})

// Auto scroll khi có message mới
watch(
  () => messages.value.length,
  () => {
    nextTick(scrollToBottom)
  },
)

// ── Handlers ───────────────────────────
async function handleSend(payload) {
  const result = await messageStore.sendMessage(groupId.value, payload)
  if (!result.success) {
    toast.error(result.message || 'Gửi tin nhắn thất bại')
  }
}

async function handleDeleteMessage(messageId) {
  if (!confirm('Xóa tin nhắn này?')) return
  const result = await messageStore.deleteMessage(messageId)
  if (!result.success) toast.error(result.message)
}

async function handleDeleteAttachment(messageId, attachmentId) {
  if (!confirm('Xóa file này?')) return
  const result = await messageStore.deleteAttachment(messageId, attachmentId)
  if (!result.success) toast.error(result.message)
}

function scrollToBottom() {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}
</script>
