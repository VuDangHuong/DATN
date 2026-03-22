<template>
  <div class="fixed bottom-6 right-6 z-50 font-['Be_Vietnam_Pro',sans-serif]">
    <transition name="chat-panel">
      <div
        v-if="isOpen"
        class="absolute bottom-16 right-0 w-[480px] bg-white rounded-2xl overflow-hidden flex flex-col"
        style="height: 620px; box-shadow: 0 8px 40px rgba(0, 0, 0, 0.18)"
      >
        <!-- ── Header ── -->
        <div class="flex items-center gap-3 px-4 py-3 flex-shrink-0" style="background: #22c55e">
          <div
            class="w-10 h-10 rounded-full bg-white flex items-center justify-center flex-shrink-0 overflow-hidden"
          >
            <img
              src="@/assets/images/icon_ChatBot.png"
              alt="ChatBot"
              class="w-full h-full object-cover"
            />
          </div>
          <div class="flex-1 min-w-0">
            <div class="text-white font-semibold text-[14px] leading-tight">
              Moncover - Đồng hành cùng bạn 24/7!
            </div>
          </div>
          <button
            class="w-7 h-7 rounded-full bg-white/20 hover:bg-red-400/80 flex items-center justify-center text-white transition-colors flex-shrink-0"
            title="Xóa lịch sử"
            @click="confirmClear"
          >
            <svg
              width="13"
              height="13"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <polyline points="3 6 5 6 21 6" />
              <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6" />
              <path d="M10 11v6M14 11v6" />
              <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2" />
            </svg>
          </button>
          <button
            class="w-7 h-7 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center text-white transition-colors flex-shrink-0"
            @click="isOpen = false"
          >
            <svg
              width="14"
              height="14"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2.5"
            >
              <line x1="18" y1="6" x2="6" y2="18" />
              <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
          </button>
        </div>

        <!-- ── Messages ── -->
        <div ref="messagesRef" class="flex-1 overflow-y-auto px-4 py-3 space-y-3 bg-white">
          <!-- Loading history -->
          <div
            v-if="loadingHistory"
            class="flex flex-col items-center justify-center h-full gap-3 text-slate-400"
          >
            <span
              class="inline-block w-7 h-7 border-2 border-green-200 border-t-green-500 rounded-full animate-spin"
            ></span>
            <span class="text-[13px]">Đang tải lịch sử...</span>
          </div>

          <!-- Empty state -->
          <div
            v-else-if="messages.length === 0"
            class="flex flex-col items-center justify-center h-full gap-4 px-4"
          >
            <div class="w-16 h-16 rounded-full bg-green-50 flex items-center justify-center">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="#22c55e">
                <path
                  d="M12 2a5 5 0 015 5 5 5 0 01-5 5 5 5 0 01-5-5 5 5 0 015-5m0 12c5.33 0 8 2.67 8 4v2H4v-2c0-1.33 2.67-4 8-4z"
                />
              </svg>
            </div>
            <div class="text-center">
              <p class="text-[14px] font-semibold text-slate-700">Xin chào! 👋</p>
              <p class="text-[12.5px] text-slate-400 mt-1">
                Hỏi bất cứ điều gì, tôi luôn sẵn sàng hỗ trợ
              </p>
            </div>
            <div class="flex flex-col gap-2 w-full mt-2">
              <button
                v-for="q in quickQuestions"
                :key="q"
                class="text-left px-3 py-2.5 rounded-xl border border-green-100 bg-white text-[12.5px] text-green-600 hover:bg-green-50 hover:border-green-300 transition-all"
                @click="sendQuick(q)"
              >
                {{ q }}
              </button>
            </div>
          </div>

          <!-- Messages list -->
          <template v-else>
            <div v-for="msg in messages" :key="msg.id" class="space-y-1">
              <!-- User message -->
              <div class="flex justify-end">
                <div class="max-w-[78%] flex flex-col items-end gap-1">
                  <div
                    v-if="msg.user_message.file_name"
                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-white text-[12px]"
                    style="background: #22c55e"
                  >
                    <svg
                      width="12"
                      height="12"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="2"
                    >
                      <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
                      <polyline points="14 2 14 8 20 8" />
                    </svg>
                    <span class="truncate max-w-[150px]">{{ msg.user_message.file_name }}</span>
                  </div>
                  <div
                    v-if="msg.user_message.content"
                    class="px-4 py-2.5 rounded-2xl rounded-br-sm text-white text-[13.5px] leading-relaxed"
                    style="background: #22c55e"
                  >
                    {{ msg.user_message.content }}
                  </div>
                  <span class="text-[10.5px] text-slate-400 mr-1">{{
                    formatTime(msg.user_message.created_at)
                  }}</span>
                </div>
              </div>

              <!-- Bot message -->
              <div v-if="msg.bot_message" class="flex gap-2.5 items-start">
                <!-- Avatar bot -->
                <div
                  class="w-8 h-8 rounded-full flex-shrink-0 mt-0.5 overflow-hidden border-2 border-green-100"
                  style="background: #f0fdf4"
                >
                  <img
                    src="@/assets/images/icon_ChatBot.png"
                    alt="ChatBot"
                    class="w-full h-full object-cover"
                  />
                </div>

                <div class="flex-1 min-w-0">
                  <!-- Bubble -->
                  <div
                    class="inline-block max-w-full px-4 py-3 rounded-2xl rounded-tl-sm text-[13.5px] leading-relaxed"
                    :class="
                      msg.bot_message.is_answered === 2
                        ? 'bg-amber-50 border border-amber-200'
                        : 'bg-white border border-slate-200'
                    "
                    style="box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06)"
                  >
                    <!-- Typing -->
                    <div v-if="msg.isTemp" class="flex gap-1 py-1">
                      <span
                        class="w-2 h-2 rounded-full bg-slate-300 animate-bounce"
                        style="animation-delay: 0ms"
                      ></span>
                      <span
                        class="w-2 h-2 rounded-full bg-slate-300 animate-bounce"
                        style="animation-delay: 150ms"
                      ></span>
                      <span
                        class="w-2 h-2 rounded-full bg-slate-300 animate-bounce"
                        style="animation-delay: 300ms"
                      ></span>
                    </div>
                    <div
                      v-else
                      :class="
                        msg.bot_message.is_answered === 2 ? 'text-amber-900' : 'text-slate-700'
                      "
                    >
                      <MarkdownRenderer :content="msg.bot_message.content" />
                    </div>
                  </div>

                  <!-- Timestamp + actions -->
                  <div class="flex items-center gap-2 mt-1 ml-1">
                    <span class="text-[10.5px] text-slate-400">{{
                      formatTime(msg.bot_message.created_at)
                    }}</span>

                    <!-- Copy, Like, Dislike -->
                    <div v-if="!msg.isTemp" class="flex items-center gap-1">
                      <button
                        class="w-5 h-5 flex items-center justify-center text-slate-300 hover:text-slate-500 transition-colors"
                        title="Sao chép"
                        @click="copyText(msg.bot_message.content)"
                      >
                        <svg
                          width="11"
                          height="11"
                          viewBox="0 0 24 24"
                          fill="none"
                          stroke="currentColor"
                          stroke-width="2"
                        >
                          <rect x="9" y="9" width="13" height="13" rx="2" />
                          <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1" />
                        </svg>
                      </button>
                      <button
                        class="w-5 h-5 flex items-center justify-center transition-colors"
                        :class="
                          msg.bot_message.is_liked === true
                            ? 'text-green-500'
                            : 'text-slate-300 hover:text-green-500'
                        "
                        @click="sendLike(msg.id, true)"
                      >
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor">
                          <path
                            d="M14 9V5a3 3 0 00-3-3l-4 9v11h11.28a2 2 0 002-1.7l1.38-9a2 2 0 00-2-2.3H14z"
                          />
                          <path
                            d="M7 22H4a2 2 0 01-2-2v-7a2 2 0 012-2h3"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                          />
                        </svg>
                      </button>
                      <button
                        class="w-5 h-5 flex items-center justify-center transition-colors"
                        :class="
                          msg.bot_message.is_liked === false
                            ? 'text-red-400'
                            : 'text-slate-300 hover:text-red-400'
                        "
                        @click="sendLike(msg.id, false)"
                      >
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor">
                          <path
                            d="M10 15v4a3 3 0 003 3l4-9V2H5.72a2 2 0 00-2 1.7l-1.38 9a2 2 0 002 2.3H10z"
                          />
                          <path
                            d="M17 2h2.67A2.31 2.31 0 0122 4v7a2.31 2.31 0 01-2.33 2H17"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                          />
                        </svg>
                      </button>
                    </div>
                  </div>

                  <!-- Sources -->
                  <div v-if="!msg.isTemp && msg.bot_message.sources?.length" class="mt-1 ml-1">
                    <button
                      class="text-[11px] text-slate-400 hover:text-green-500 transition-colors flex items-center gap-1"
                      @click="toggleSources(msg.id)"
                    >
                      <svg
                        width="10"
                        height="10"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                      >
                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                      </svg>
                      {{ msg.bot_message.sources.length }} nguồn tham khảo
                      <svg
                        width="10"
                        height="10"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                      >
                        <polyline
                          :points="
                            expandedSources.has(msg.id) ? '18 15 12 9 6 15' : '6 9 12 15 18 9'
                          "
                        />
                      </svg>
                    </button>
                    <div v-if="expandedSources.has(msg.id)" class="mt-1 space-y-1">
                      <div
                        v-for="(src, i) in msg.bot_message.sources"
                        :key="i"
                        class="px-3 py-2 rounded-lg bg-slate-50 border border-slate-100 text-[11.5px] text-slate-500"
                      >
                        <p class="font-medium text-slate-600 truncate">{{ src.question }}</p>
                        <p class="mt-0.5 line-clamp-2">{{ src.answer }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Bot loading -->
              <div v-if="msg.isTemp && !msg.bot_message" class="flex gap-2.5 items-start">
                <div
                  class="w-8 h-8 rounded-full flex-shrink-0 border-2 border-green-100 flex items-center justify-center"
                  style="background: #f0fdf4"
                >
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="#22c55e">
                    <path
                      d="M12 2a5 5 0 015 5 5 5 0 01-5 5 5 5 0 01-5-5 5 5 0 015-5m0 12c5.33 0 8 2.67 8 4v2H4v-2c0-1.33 2.67-4 8-4z"
                    />
                  </svg>
                </div>
                <div
                  class="px-4 py-3 rounded-2xl rounded-tl-sm bg-white border border-slate-200 flex gap-1"
                  style="box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06)"
                >
                  <span
                    class="w-2 h-2 rounded-full bg-slate-300 animate-bounce"
                    style="animation-delay: 0ms"
                  ></span>
                  <span
                    class="w-2 h-2 rounded-full bg-slate-300 animate-bounce"
                    style="animation-delay: 150ms"
                  ></span>
                  <span
                    class="w-2 h-2 rounded-full bg-slate-300 animate-bounce"
                    style="animation-delay: 300ms"
                  ></span>
                </div>
              </div>
            </div>
          </template>
        </div>

        <!-- Suggested questions -->
        <div
          v-if="suggestedQuestions.length > 0"
          class="px-4 py-2 border-t border-slate-100 bg-white flex-shrink-0"
        >
          <p class="text-[11px] text-slate-400 mb-1.5 font-medium uppercase tracking-wide">
            Câu hỏi gợi ý
          </p>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="q in suggestedQuestions"
              :key="q"
              class="px-2.5 py-1 rounded-lg text-[12px] transition-colors truncate border"
              style="max-width: 180px; background: #f0fdf4; color: #16a34a; border-color: #bbf7d0"
              @click="sendQuick(q)"
            >
              {{ q }}
            </button>
          </div>
        </div>
        <div
          v-else-if="loadingSuggestions"
          class="px-4 py-2 border-t border-slate-100 flex-shrink-0"
        >
          <div class="flex gap-1.5">
            <div class="h-6 w-24 rounded-lg bg-slate-100 animate-pulse"></div>
            <div class="h-6 w-32 rounded-lg bg-slate-100 animate-pulse"></div>
            <div class="h-6 w-20 rounded-lg bg-slate-100 animate-pulse"></div>
          </div>
        </div>

        <!-- ── Input ── -->
        <div class="px-3 py-3 border-t border-slate-100 bg-white flex-shrink-0">
          <!-- File preview -->
          <div
            v-if="selectedFile"
            class="flex items-center gap-2 mb-2 px-3 py-1.5 rounded-lg bg-green-50 border border-green-100"
          >
            <svg
              width="13"
              height="13"
              viewBox="0 0 24 24"
              fill="none"
              stroke="#22c55e"
              stroke-width="2"
            >
              <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
              <polyline points="14 2 14 8 20 8" />
            </svg>
            <span class="flex-1 text-[12px] text-green-600 truncate">{{ selectedFile.name }}</span>
            <span class="text-[11px] text-slate-400">{{ formatFileSize(selectedFile.size) }}</span>
            <button
              class="ml-1 text-slate-400 hover:text-red-400 transition-colors"
              @click="removeFile"
            >
              <svg
                width="12"
                height="12"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
              >
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>
          </div>

          <div
            class="flex items-center gap-2 px-3 py-2 rounded-2xl border border-slate-200 bg-slate-50 focus-within:border-green-400 focus-within:bg-white transition-all"
          >
            <!-- Attach -->
            <button
              class="flex-shrink-0 text-slate-400 hover:text-green-500 transition-colors"
              title="Đính kèm file"
              @click="triggerFileInput"
            >
              <svg
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <path
                  d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"
                />
              </svg>
            </button>
            <input
              ref="fileInputRef"
              type="file"
              accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.xlsx,.xls,.csv,.doc,.docx"
              class="hidden"
              @change="onFileChange"
            />

            <input
              ref="inputRef"
              v-model="inputText"
              placeholder="Nhập tin nhắn..."
              class="flex-1 bg-transparent border-none text-[13.5px] text-slate-700 focus:outline-none leading-relaxed"
              @keydown.enter.exact.prevent="handleSend"
            />

            <!-- Send button -->
            <button
              class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 transition-all"
              :class="canSend ? 'text-white' : 'bg-slate-200 text-slate-400 cursor-default'"
              :style="canSend ? 'background:#22c55e' : ''"
              :disabled="!canSend"
              @click="handleSend"
            >
              <svg
                v-if="!loading"
                width="15"
                height="15"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
              >
                <line x1="22" y1="2" x2="11" y2="13" />
                <polygon points="22 2 15 22 11 13 2 9 22 2" />
              </svg>
              <span
                v-else
                class="inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"
              ></span>
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Toggle button -->
    <button
      class="w-20 h-20 rounded-full flex items-center justify-center transition-all hover:scale-105 active:scale-95 relative overflow-hidden"
      :style="isOpen ? 'background:#64748b' : 'background:#22c55e'"
      style="box-shadow: 0 4px 20px rgba(34, 197, 94, 0.4)"
      @click="toggleChat"
    >
      <transition name="icon-switch" mode="out-in">
        <img
          v-if="!isOpen"
          key="open"
          src="@/assets/images/icon_ChatBot.png"
          alt="ChatBot"
          class="w-16 h-16 rounded-full object-cover"
        />
        <svg
          v-else
          key="close"
          width="24"
          height="24"
          viewBox="0 0 24 24"
          fill="none"
          stroke="white"
          stroke-width="2.5"
        >
          <line x1="18" y1="6" x2="6" y2="18" />
          <line x1="6" y1="6" x2="18" y2="18" />
        </svg>
      </transition>
      <span
        v-if="!isOpen && unreadCount > 0"
        class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center"
        >{{ unreadCount }}</span
      >
    </button>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { storeToRefs } from 'pinia'
import { useAdminChatbotStore } from '@/stores/admin/chat/Adminchatbot'
import MarkdownRenderer from './chatBot/MarkdownRenderer.vue'

const store = useAdminChatbotStore()
const { messages, loading, loadingHistory, loadingSuggestions, suggestedQuestions } =
  storeToRefs(store)

const isOpen = ref(false)
const inputText = ref('')
const messagesRef = ref(null)
const inputRef = ref(null)
const fileInputRef = ref(null)
const expandedSources = ref(new Set())
const unreadCount = ref(0)
const selectedFile = ref(null)

const quickQuestions = [
  'Làm thế nào để đăng ký tín chỉ?',
  'Học phí năm học này là bao nhiêu?',
  'Quy chế học vụ sinh viên?',
]

const canSend = computed(() => (inputText.value.trim() || selectedFile.value) && !loading.value)

watch(messages, () => scrollToBottom(), { deep: true })
watch(isOpen, async (val) => {
  if (val) {
    unreadCount.value = 0
    if (messages.value.length === 0) await store.fetchHistory()
    await nextTick()
    scrollToBottom()
    inputRef.value?.focus()
  }
})

async function toggleChat() {
  isOpen.value = !isOpen.value
}

async function handleSend() {
  if (!canSend.value) return
  const q = inputText.value.trim()
  const file = selectedFile.value
  inputText.value = ''
  selectedFile.value = null
  await store.ask(q || 'Hãy phân tích nội dung file này.', null, file)
  if (!isOpen.value) unreadCount.value++
}

function sendQuick(q) {
  inputText.value = q
  handleSend()
}
function scrollToBottom() {
  nextTick(() => {
    if (messagesRef.value) messagesRef.value.scrollTop = messagesRef.value.scrollHeight
  })
}
function toggleSources(msgId) {
  const s = new Set(expandedSources.value)
  s.has(msgId) ? s.delete(msgId) : s.add(msgId)
  expandedSources.value = s
}
function confirmClear() {
  if (confirm('Xóa toàn bộ lịch sử chat?')) store.clearChat()
}
function sendLike(msgId, val) {
  store.sendLike(msgId, val)
}
function sendStar(msgId, star) {
  store.sendStar(msgId, star)
}
function triggerFileInput() {
  fileInputRef.value?.click()
}
function removeFile() {
  selectedFile.value = null
}
function copyText(text) {
  navigator.clipboard.writeText(text).catch(() => {})
}
function onFileChange(e) {
  const file = e.target.files?.[0]
  if (!file) return
  if (file.size > 10 * 1024 * 1024) {
    alert('File quá lớn! Tối đa 10MB.')
    return
  }
  selectedFile.value = file
  e.target.value = ''
}
function formatFileSize(bytes) {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}
function formatTime(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  const hh = String(d.getHours()).padStart(2, '0')
  const mm = String(d.getMinutes()).padStart(2, '0')
  const ss = String(d.getSeconds()).padStart(2, '0')
  const dd = String(d.getDate()).padStart(2, '0')
  const mo = String(d.getMonth() + 1).padStart(2, '0')
  const yy = d.getFullYear()
  return `${hh}:${mm}:${ss} ${dd}/${mo}/${yy}`
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap');

.chat-panel-enter-active {
  animation: panelIn 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.chat-panel-leave-active {
  animation: panelIn 0.2s ease reverse;
}
@keyframes panelIn {
  from {
    opacity: 0;
    transform: scale(0.92) translateY(16px);
  }
  to {
    opacity: 1;
    transform: none;
  }
}
.icon-switch-enter-active,
.icon-switch-leave-active {
  transition: all 0.15s;
}
.icon-switch-enter-from {
  opacity: 0;
  transform: rotate(-90deg) scale(0.7);
}
.icon-switch-leave-to {
  opacity: 0;
  transform: rotate(90deg) scale(0.7);
}
.animate-bounce {
  animation: bounce 1s infinite;
}
@keyframes bounce {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-4px);
  }
}
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
