<!-- src/components/students/chat/ChatMessageItem.vue -->
<template>
  <div class="flex gap-3 group" :class="isMine ? 'flex-row-reverse' : ''">
    <!-- Avatar -->
    <div
      class="w-8 h-8 rounded-full flex-shrink-0 overflow-hidden flex items-center justify-center text-base font-bold text-white"
      :class="
        message.user?.avatar_url
          ? 'bg-slate-200'
          : isMine
            ? 'bg-gradient-to-br from-indigo-500 to-purple-600'
            : 'bg-gradient-to-br from-slate-400 to-slate-500'
      "
    >
      <img
        v-if="message.user?.avatar_url"
        :src="message.user.avatar_url"
        :alt="message.user.name"
        class="w-full h-full object-cover"
        @error="$event.target.style.display = 'none'"
      />
      <span v-else>{{ message.user?.name?.charAt(0) || '?' }}</span>
    </div>

    <!-- Bubble -->
    <div class="max-w-[75%] min-w-0" :class="isMine ? 'items-end' : 'items-start'">
      <!-- Name + time -->
      <div class="flex items-center gap-2 mb-0.5" :class="isMine ? 'flex-row-reverse' : ''">
        <span class="text-base font-semibold text-slate-700">{{ message.user?.name }}</span>
        <span class="text-[10px] text-slate-400">{{ formatTime(message.created_at) }}</span>
        <button
          v-if="canDelete"
          @click="$emit('delete', message.id)"
          class="opacity-0 group-hover:opacity-100 text-slate-400 hover:text-red-500 transition"
        >
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
            />
          </svg>
        </button>
      </div>

      <!-- Bubble content -->
      <div
        class="px-3 py-2 rounded-2xl text-sm leading-relaxed"
        :class="
          isMine
            ? 'bg-indigo-600 text-white rounded-tr-sm'
            : 'bg-slate-100 text-slate-700 rounded-tl-sm'
        "
      >
        <!-- Content với mention highlighted -->
        <p
          v-if="message.content"
          v-html="formattedContent"
          class="whitespace-pre-wrap break-words"
        />

        <!-- Attachments -->
        <div v-if="message.attachments?.length" class="mt-2 space-y-1.5">
          <!-- Images grid -->
          <div
            v-if="imageAttachments.length"
            class="grid gap-1.5"
            :class="imageAttachments.length === 1 ? 'grid-cols-1' : 'grid-cols-2'"
          >
            <a
              v-for="att in imageAttachments"
              :key="att.id"
              :href="att.file_url"
              target="_blank"
              rel="noopener"
              class="block relative group/img"
            >
              <img
                :src="att.file_url"
                :alt="att.file_name"
                class="w-full h-32 object-cover rounded-lg"
              />
              <button
                v-if="canDeleteAttachment(att)"
                @click.prevent="$emit('delete-attachment', att.id)"
                class="absolute top-1 right-1 w-5 h-5 bg-black/60 text-white rounded-full opacity-0 group-hover/img:opacity-100 flex items-center justify-center hover:bg-red-600 transition"
              >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </a>
          </div>

          <!-- PDFs -->
          <a
            v-for="att in pdfAttachments"
            :key="att.id"
            :href="att.file_url"
            target="_blank"
            rel="noopener"
            class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg transition group/file"
            :class="isMine ? 'bg-white/20 hover:bg-white/30' : 'bg-white hover:bg-slate-50'"
          >
            <span class="text-lg"><SvgIcon name="document" class="h-5 w-5" /></span>
            <div class="flex-1 min-w-0">
              <p
                class="text-base font-medium truncate"
                :class="isMine ? 'text-white' : 'text-slate-700'"
              >
                {{ att.file_name }}
              </p>
              <p class="text-[10px]" :class="isMine ? 'text-white/70' : 'text-slate-400'">
                {{ att.file_size_human }}
              </p>
            </div>
            <button
              v-if="canDeleteAttachment(att)"
              @click.prevent="$emit('delete-attachment', att.id)"
              class="opacity-0 group-hover/file:opacity-100 transition"
              :class="
                isMine ? 'text-white/70 hover:text-white' : 'text-slate-400 hover:text-red-500'
              "
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                />
              </svg>
            </button>
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import SvgIcon from '@/components/icons/SVG.vue'
const props = defineProps({
  message: { type: Object, required: true },
  currentUserId: { type: Number, required: true },
  isLeader: { type: Boolean, default: false },
})
onMounted(() => {
  console.log('ChatMessageItem mounted:', {
    id: props.message.id,
    content: props.message.content,
    mentioned_users: props.message.mentioned_users,
    formattedContent: formattedContent.value,
  })
})
defineEmits(['delete', 'delete-attachment'])

const isMine = computed(() => props.message.user?.id === props.currentUserId)

const canDelete = computed(() => isMine.value || props.isLeader)

function canDeleteAttachment(att) {
  return att.uploaded_by === props.currentUserId || isMine.value
}

const imageAttachments = computed(() => (props.message.attachments || []).filter((a) => a.is_image))
const pdfAttachments = computed(() => (props.message.attachments || []).filter((a) => !a.is_image))

//Highlight @mentions trong content
const formattedContent = computed(() => {
  if (!props.message.content) return ''

  const mentionedUsers = props.message.mentioned_users || []
  if (!mentionedUsers.length) return escapeHtml(props.message.content)

  let html = escapeHtml(props.message.content)
  const sorted = [...mentionedUsers].sort((a, b) => b.name.length - a.name.length)

  for (const u of sorted) {
    const safeName = escapeHtml(u.name).replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
    const regex = new RegExp(`@${safeName}\\b`, 'g')
    const isMeTagged = u.id === props.currentUserId

    //Style theo bubble (mine = xanh, others = trắng)
    let style
    if (isMeTagged) {
      // Bị tag → vàng nổi
      style =
        'background-color:#fde68a;color:#78350f;padding:1px 6px;border-radius:4px;font-weight:600;'
    } else if (isMine.value) {
      // Tin của mình (bubble xanh) → mention màu vàng nhạt nổi trên xanh
      style =
        'background-color:rgba(255,255,255,0.25);color:#fef3c7;padding:1px 6px;border-radius:4px;font-weight:600;'
    } else {
      // Tin người khác (bubble xám) → mention xanh đậm
      style =
        'background-color:#e0e7ff;color:#3730a3;padding:1px 6px;border-radius:4px;font-weight:600;'
    }

    html = html.replace(regex, `<span style="${style}">@${escapeHtml(u.name)}</span>`)
  }
  return html
})

function escapeHtml(s) {
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

function formatTime(d) {
  if (!d) return ''
  const date = new Date(d)
  const now = new Date()
  const sameDay = date.toDateString() === now.toDateString()
  if (sameDay) {
    return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' })
  }
  return date.toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>
