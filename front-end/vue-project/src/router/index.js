import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Layouts
import AdminLayout from '@/layouts/AdminLayout.vue'

// Auth
import LoginView from '@/views/auth/LoginView.vue'

// Admin views
import AdminDashboard from '@/views/admin/AdminDashboard.vue'
import UserManagement from '@/views/admin/UserManagement.vue'
import ClassManagement from '@/views/admin/ClassManagement.vue'
import CategoryManagement from '@/views/admin/CategoryManagement.vue'
import SystemConfig from '@/views/admin/SystemConfig.vue'
import Reports from '@/views/admin/Reports.vue'
import ChangePasswordForm from '@/views/auth/ChangePasswordForm.vue'
import ProfileView from '@/components/profile/ProfileView.vue'
import SemesterManagement from '@/views/admin/SemesterManagement.vue'
import ChatBotManagement from '@/views/admin/ChatBotManagement.vue'
import DashboardView from '@/DashboardView.vue'
import StudentLayout from '@/layouts/StudentLayout.vue'
import GroupView from '@/views/students/groups/GroupView.vue'
import ChatView from '@/views/students/chat/ChatView.vue'
import TaskView from '@/views/students/tasks/TaskView.vue'

// Router config
const routes = [
  // Redirect mặc định
  { path: '/', redirect: '/login' },

  // ── AUTH ─────────────────────────────────────
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { requiresGuest: true },
  },

  // ── ADMIN ────────────────────────────────────
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true, role: 'admin' },
    children: [
      { path: 'dashboard', name: 'admin-dashboard', component: AdminDashboard },
      { path: 'semesters', name: 'semester-management', component: SemesterManagement },
      { path: 'users', name: 'user-management', component: UserManagement },
      { path: 'classes', name: 'class-management', component: ClassManagement },
      { path: 'master-data', name: 'master-data', component: CategoryManagement },
      { path: 'settings', name: 'system-config', component: SystemConfig },
      { path: 'reports', name: 'reports', component: Reports },
      { path: 'change-password', name: 'admin.change-password', component: ChangePasswordForm },
      { path: 'profile', name: 'admin-profile', component: ProfileView },
      { path: 'chatBot', name: 'chatBot', component: ChatBotManagement },
    ],
  },

  // ── STUDENT ──────────────────────────────────
  {
    path: '/student',
    component: StudentLayout,
    meta: { requiresAuth: true, role: 'student' },
    children: [
      {
        path: '',
        redirect: 'dashboard',
      },
      {
        path: 'dashboard',
        name: 'student-dashboard',
        component: DashboardView,
      },
      {
        path: 'groups',
        name: 'groups',
        component: GroupView,
      },
      {
        path: 'groups/:groupId',
        name: 'group-detail',
        component: GroupView,
        props: true, // 🔥 nên thêm
      },
      {
        path: 'chat',
        name: 'chat',
        component: ChatView,
      },
      {
        path: 'tasks',
        name: 'tasks',
        component: TaskView,
      },
    ],
  },
]

// Create router
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

// ── GLOBAL GUARD ───────────────────────────────
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  const isAuthenticated = authStore.isAuthenticated
  const userRole = authStore.user?.role // 'admin' | 'student'

  // Chưa login
  if (to.meta.requiresAuth && !isAuthenticated) {
    return next('/login')
  }

  // Đã login mà vào login
  if (to.meta.requiresGuest && isAuthenticated) {
    return next(userRole === 'admin' ? '/admin/dashboard' : '/student/dashboard')
  }

  // Check role
  if (to.meta.role && to.meta.role !== userRole) {
    return next('/login')
  }

  next()
})

export default router
