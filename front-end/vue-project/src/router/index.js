import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Layouts
import AdminLayout from '@/layouts/AdminLayout.vue'
import LecturerLayout from '@/layouts/LecturerLayout.vue'

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
// Student views
import GroupView from '@/views/students/groups/GroupView.vue'
import ChatView from '@/views/students/chat/ChatView.vue'
import TaskView from '@/views/students/tasks/TaskView.vue'
import DasboardStudentView from '@/views/students/dashboard/DasboardStudentView.vue'
import StudentSubmisionView from '@/views/students/Assignment/StudentSubmisionView.vue'

// Lecturer views
import SubmissionReviewView from '@/views/lecturer/assignment/SubmissionReviewView.vue'
import StudentLayout from '@/layouts/StudentLayout.vue'
import LecturerAssignmentView from '@/views/lecturer/assignment/LecturerAssignmentView.vue'
import LecturerClassView from '@/views/lecturer/class/LecturerClassView.vue'
import AdminSignView from '@/views/admin/sign/AdminSignView.vue'
import LecturerSignView from '@/views/lecturer/sign/LecturerSignView.vue'
import LecturerSignHistoryView from '@/views/lecturer/sign/LecturerSignHistoryView.vue'
import LecturerTaskView from '@/views/lecturer/tasks/LecturerTaskView.vue'
import LecturerSignProfileView from '@/views/lecturer/sign/LecturerSignProfileView.vue'
import VerifyDocumentView from '@/views/public/VerifyDocumentView.vue'
import LecturerDashboardView from '@/views/lecturer/LecturerDashboardView.vue'
import AdminSignProfilesView from '@/views/admin/sign/AdminSignProfilesView.vue'
import AdminDeactivationRequestsView from '@/views/admin/sign/AdminDeactivationRequestsView.vue'

const routes = [
  {
    path: '/verify',
    name: 'public-verify',
    component: VerifyDocumentView,
    meta: { public: true }, // không cần auth
  },
  // Redirect mặc định
  { path: '/', redirect: '/login' },

  // ── AUTH ──────────────────────────────────────────────────
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { requiresGuest: true },
  },

  // ── ADMIN ─────────────────────────────────────────────────
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
      { path: 'sign-requests', name: 'admin-sign-requests', component: AdminSignView },
      {
        path: 'sign-profiles',
        name: 'admin-sign-profiles',
        component: AdminSignProfilesView,
        meta: { requiresAuth: true, role: 'admin', title: 'Quản lý chữ ký số' },
      },
      {
        path: 'deactivation-requests',
        name: 'admin-deactivation-requests',
        component: AdminDeactivationRequestsView,
        meta: { requiresAuth: true, role: 'admin', title: 'Yêu cầu vô hiệu hóa' },
      },
    ],
  },

  // ── STUDENT ───────────────────────────────────────────────
  {
    path: '/student',
    component: StudentLayout,
    meta: { requiresAuth: true, role: 'student' },
    children: [
      { path: '', redirect: 'dashboard' },
      { path: 'dashboard', name: 'student-dashboard', component: DasboardStudentView },
      { path: 'groups', name: 'groups', component: GroupView },
      { path: 'groups/:groupId', name: 'group-detail', component: GroupView, props: true },
      { path: 'chat', name: 'chat', component: ChatView },
      { path: 'tasks', name: 'tasks', component: TaskView },
      { path: 'assignments', name: 'assignments', component: StudentSubmisionView },
      { path: 'profile', name: 'student-profile', component: ProfileView },
      { path: 'change-password', name: 'student-change-password', component: ChangePasswordForm },
    ],
  },

  // ── LECTURER ──────────────────────────────────────────────
  {
    path: '/lecturer',
    component: LecturerLayout,
    meta: { requiresAuth: true, role: 'lecturer' },
    redirect: '/lecturer/assignments',
    children: [
      {
        path: 'dashboard',
        name: 'lecturer-dashboard',
        component: LecturerDashboardView,
        meta: { title: 'Dashboard' },
      },
      // Đợt nộp bài — danh sách + tạo mới
      {
        path: 'assignments',
        name: 'lecturer-assignments',
        component: LecturerAssignmentView,
        meta: { title: 'Đợt nộp bài' },
      },

      // Duyệt bài nộp của 1 đợt cụ thể
      {
        path: 'assignments/:assignmentId/review',
        name: 'lecturer-assignment-review',
        component: SubmissionReviewView,
        props: (route) => ({ assignmentId: Number(route.params.assignmentId) }),
        meta: { title: 'Duyệt bài nộp' },
      },

      // Tổng quan duyệt bài (không chọn đợt cụ thể)
      {
        path: 'reviews',
        name: 'lecturer-reviews',
        component: SubmissionReviewView,
        meta: { title: 'Duyệt bài nộp' },
      },
      {
        path: 'classes',
        name: 'lecturer-classes',
        component: LecturerClassView,
        meta: { title: 'Quản lý lớp' },
      },
      {
        path: 'sign-requests',
        name: 'lecturer-sign-requests',
        component: LecturerSignView,
        meta: { title: 'Ký số' },
      },
      {
        path: 'sign-history',
        name: 'lecturer-sign-history',
        component: LecturerSignHistoryView,
        meta: { title: 'Ký số' },
      },
      {
        path: 'tasks',
        name: 'lecturer-tasks',
        component: LecturerTaskView,
        meta: { title: 'Công việc nhóm' },
      },
      {
        path: 'sign-profile',
        name: 'lecturer-sign-profile',
        component: LecturerSignProfileView,
        meta: { title: 'Chữ ký số của tôi' },
      },
      { path: 'profile', name: 'lecturer-profile', component: ProfileView },
      { path: 'change-password', name: 'lecturer-change-password', component: ChangePasswordForm },
    ],
  },
]

// Create router
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

// ── GLOBAL GUARD ──────────────────────────────────────────────
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  const isAuthenticated = authStore.isAuthenticated
  const userRole = authStore.user?.role // 'admin' | 'lecturer' | 'student'

  // Chưa login → về /login
  if (to.meta.requiresAuth && !isAuthenticated) {
    return next('/login')
  }

  // Đã login mà vào /login → về đúng dashboard theo role
  if (to.meta.requiresGuest && isAuthenticated) {
    if (userRole === 'admin') return next('/admin/dashboard')
    if (userRole === 'lecturer') return next('/lecturer/assignments')
    if (userRole === 'student') return next('/student/dashboard')
  }

  // Sai role → về /login
  if (to.meta.role && to.meta.role !== userRole) {
    return next('/login')
  }

  next()
})

export default router
