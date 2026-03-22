<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import ToastList from '@/components/ToastList.vue'
import AdminChatbot from './views/admin/AdminChatbot.vue'

const route = useRoute()
// Ẩn chatbot ở trang login (không cần đăng nhập)
const showChat = computed(() => route.name !== 'login')
</script>

<template>
  <ToastList />
  <router-view />
  <AdminChatbot v-if="showChat" />
</template>
