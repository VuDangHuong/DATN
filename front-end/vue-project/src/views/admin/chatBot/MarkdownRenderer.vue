<template>
  <div class="mk" v-html="renderedHtml"></div>
</template>

<script setup>
import { computed } from 'vue'
import { marked } from 'marked'
import DOMPurify from 'dompurify'

const props = defineProps({
  content: { type: String, default: '' },
})

marked.setOptions({ breaks: true, gfm: true })

const renderedHtml = computed(() => {
  if (!props.content) return ''

  const raw = marked.parse(props.content)
  const clean = DOMPurify.sanitize(raw)

  // ✅ Tự động wrap <table> trong <div class="table-wrap"> để scroll ngang
  return clean
    .replace(/<table>/g, '<div class="table-wrap"><table>')
    .replace(/<\/table>/g, '</table></div>')
})
</script>

<style scoped>
.mk {
  font-size: 13.5px;
  line-height: 1.7;
  word-break: break-word;
}

/* Paragraph */
.mk :deep(p) {
  margin: 0 0 6px;
}
.mk :deep(p:last-child) {
  margin-bottom: 0;
}

/* Tiêu đề */
.mk :deep(h1),
.mk :deep(h2),
.mk :deep(h3),
.mk :deep(h4) {
  font-weight: 600;
  margin: 10px 0 5px;
  line-height: 1.4;
}
.mk :deep(h1) {
  font-size: 16px;
}
.mk :deep(h2) {
  font-size: 15px;
}
.mk :deep(h3) {
  font-size: 14px;
}
.mk :deep(h4) {
  font-size: 13.5px;
}

/* List */
.mk :deep(ul),
.mk :deep(ol) {
  margin: 4px 0 6px;
  padding-left: 16px;
}
.mk :deep(li) {
  margin-bottom: 3px;
}
.mk :deep(li:last-child) {
  margin-bottom: 0;
}

/* Bold / Italic */
.mk :deep(strong) {
  font-weight: 600;
}
.mk :deep(em) {
  font-style: italic;
  opacity: 0.85;
}

/* ── Wrapper scroll ngang ── */
.mk :deep(.table-wrap) {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  margin: 8px 0;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  scrollbar-width: thin;
  scrollbar-color: #c7d2fe #f8fafc;
}
.mk :deep(.table-wrap::-webkit-scrollbar) {
  height: 4px;
}
.mk :deep(.table-wrap::-webkit-scrollbar-track) {
  background: #f8fafc;
  border-radius: 99px;
}
.mk :deep(.table-wrap::-webkit-scrollbar-thumb) {
  background: #c7d2fe;
  border-radius: 99px;
}

/* ── Bảng ── */
.mk :deep(table) {
  width: max-content; /* ✅ rộng theo nội dung, không bị bóp */
  min-width: 100%;
  border-collapse: collapse;
  font-size: 12.5px;
  border-radius: 8px;
}

/* Header */
.mk :deep(thead tr) {
  background: #ede9fe;
}
.mk :deep(th) {
  padding: 7px 14px;
  text-align: left;
  font-weight: 600;
  color: #4338ca;
  border-bottom: 1.5px solid #c7d2fe;
  white-space: nowrap; /* ✅ tiêu đề không bao giờ wrap */
}

/* Cell — cho phép wrap text bình thường trong ô */
.mk :deep(td) {
  padding: 6px 14px;
  color: #374151;
  border-bottom: 0.5px solid #e2e8f0;
  vertical-align: top;
  min-width: 80px; /* tránh ô quá hẹp */
}
.mk :deep(tr:last-child td) {
  border-bottom: none;
}
.mk :deep(tbody tr:nth-child(even)) {
  background: #f8fafc;
}
.mk :deep(tbody tr:hover) {
  background: #f0f9ff;
  transition: background 0.15s;
}

/* Code inline */
.mk :deep(code) {
  background: #f1f5f9;
  color: #dc2626;
  padding: 1px 5px;
  border-radius: 4px;
  font-size: 12px;
  font-family: 'Fira Code', 'Courier New', monospace;
}

/* Code block */
.mk :deep(pre) {
  background: #1e293b;
  color: #e2e8f0;
  padding: 12px 14px;
  border-radius: 8px;
  overflow-x: auto;
  margin: 8px 0;
  font-size: 12px;
}
.mk :deep(pre code) {
  background: none;
  color: inherit;
  padding: 0;
}

/* Blockquote */
.mk :deep(blockquote) {
  border-left: 3px solid #818cf8;
  margin: 8px 0;
  padding: 4px 10px;
  background: #eef2ff;
  border-radius: 0 6px 6px 0;
  font-style: italic;
  color: #4338ca;
}

/* Link */
.mk :deep(a) {
  color: #6366f1;
  text-decoration: underline;
  text-underline-offset: 2px;
}
.mk :deep(a:hover) {
  color: #4338ca;
}

/* HR */
.mk :deep(hr) {
  border: none;
  border-top: 1px solid #e2e8f0;
  margin: 10px 0;
}
</style>
