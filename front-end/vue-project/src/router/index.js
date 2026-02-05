import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
// Import Layout
import AdminLayout from '@/layouts/AdminLayout.vue'
import LoginView from '@/views/auth/LoginView.vue'
import DashboardView from '../DashboardView.vue'

import AdminDashboard from '@/views/admin/AdminDashboard.vue'
import UserManagement from '@/views/admin/UserManagement.vue'
import ClassManagement from '@/views/admin/ClassManagement.vue'
import CategoryManagement from '@/views/admin/CategoryManagement.vue'
import SystemConfig from '@/views/admin/SystemConfig.vue'
import Reports from '@/views/admin/Reports.vue'
import ChangePasswordForm from '@/views/auth/ChangePasswordForm.vue'
import ProfileView from '@/components/profile/ProfileView.vue'
import SemesterManagement from '@/views/admin/SemesterManagement.vue'
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', redirect: '/login' },

    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { requiresGuest: true },
    },
    // --- ADMIN ROUTES ---
    {
      path: '/admin',
      component: AdminLayout, // Dùng Layout chung
      meta: { requiresAuth: true, role: 'admin' }, // Thêm check role nếu cần
      children: [
        {
          path: 'dashboard', // /admin/dashboard
          name: 'admin-dashboard',
          component: AdminDashboard,
        },
        {
          path: 'semesters', // /admin/users
          name: 'semester-management',
          component: SemesterManagement,
        },
        {
          path: 'users', // /admin/users
          name: 'user-management',
          component: UserManagement,
        },
        {
          path: 'classes',
          name: 'class-management',
          component: ClassManagement,
        },
        {
          path: 'master-data',
          name: 'master-data',
          component: CategoryManagement,
        },
        {
          path: 'settings',
          name: 'system-config',
          component: SystemConfig,
        },
        {
          path: 'reports',
          name: 'reports',
          component: Reports,
        },
        {
          path: 'change-password',
          name: 'admin.change-password',
          component: ChangePasswordForm,
          meta: { title: 'Đổi mật khẩu Admin' },
        },
        {
          path: 'profile',
          name: 'admin-profile',
          component: ProfileView,
        },
      ],
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: DashboardView,
      meta: { requiresAuth: true },
    },
  ],
})

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  const isAuthenticated = authStore.isAuthenticated

  if (to.meta.requiresAuth && !isAuthenticated) {
    next('/login')
  } else if (to.meta.requiresGuest && isAuthenticated) {
    next('/dashboard')
  } else {
    next()
  }
})

export default router
