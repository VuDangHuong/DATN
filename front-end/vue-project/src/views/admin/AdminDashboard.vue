<!-- src/views/admin/AdminDashboardView.vue -->
<template>
  <div class="space-y-6">
    <!-- ─── Welcome banner ─── -->

    <!-- Loading -->
    <div v-if="store.loading" class="flex justify-center py-16">
      <div class="w-10 h-10 border-4 border-rose-200 border-t-rose-600 rounded-full animate-spin" />
    </div>

    <template v-else>
      <!-- ─── Section: Users ─── -->
      <div>
        <h3
          class="flex items-center gap-1.5 text-base font-bold text-stone-500 uppercase tracking-wider mb-3"
        >
          <SvgICon name="users" class="w-4 h-4 text-gray-600" /> Người dùng
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
          <StatCard
            icon="users"
            label="Tổng user"
            :value="stats.total_users"
            color="indigo"
            link="/admin/users"
          />
          <StatCard icon="crown" label="Admin" :value="stats.total_admins" color="red" />
          <StatCard icon="users" label="Giảng viên" :value="stats.total_lecturers" color="teal" />
          <StatCard icon="users" label="Sinh viên" :value="stats.total_students" color="blue" />
          <StatCard
            icon="check"
            label="Đang hoạt động"
            :value="stats.active_users"
            color="emerald"
          />
        </div>
      </div>

      <!-- ─── Section: Academic ─── -->
      <div>
        <h3
          class="flex items-center gap-1.5 text-base font-bold text-stone-500 uppercase tracking-wider mb-3"
        >
          <SvgICon name="graduation-cap" class="w-4 h-4 text-gray-600" /> Học vụ
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
          <StatCard
            icon="graduation-cap"
            label="Lớp học"
            :value="stats.total_classes"
            color="purple"
            link="/admin/classes"
          />
          <StatCard
            icon="building"
            label="Lớp hoạt động"
            :value="stats.active_classes"
            color="emerald"
          />
          <StatCard
            icon="class-book-open"
            label="Môn học"
            :value="stats.total_subjects"
            color="amber"
            link="/admin/subjects"
          />
          <StatCard
            icon="class-book"
            label="Học kỳ"
            :value="stats.total_semesters"
            color="cyan"
            link="/admin/semesters"
          />
          <StatCard icon="group-users" label="Nhóm" :value="stats.total_groups" color="indigo" />
        </div>
      </div>

      <!-- ─── Section: Activity ─── -->
      <div>
        <h3
          class="flex items-center gap-1.5 text-base font-bold text-stone-500 uppercase tracking-wider mb-3"
        >
          <SvgICon name="chart-bar" class="w-5 h-5 text-pink-600" />Hoạt động hệ thống
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
          <StatCard icon="document" label="Bài tập" :value="stats.total_assignments" color="blue" />
          <StatCard
            icon="document-check"
            label="Bài nộp"
            :value="stats.total_submissions"
            color="cyan"
          />
          <StatCard
            icon="loading-time"
            label="Bài chờ duyệt"
            :value="stats.submissions_pending"
            color="amber"
          />
          <StatCard
            icon="edit-pencil"
            label="Yêu cầu ký"
            :value="stats.total_sign_requests"
            color="indigo"
          />
          <StatCard
            icon="check"
            label="GV có chữ ký số"
            :value="stats.lecturers_with_pki"
            color="emerald"
            link="/admin/lecturers"
          />
        </div>
      </div>

      <!-- ─── Charts row 1: Users growth + System activity ─── -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl border border-stone-200 p-5">
          <div class="flex items-center gap-2 mb-4">
            <div
              class="w-8 h-8 rounded-lg bg-teal-100 flex items-center justify-center text-teal-700"
            >
              <SvgICon name="chart-line" class="w-5 h-5 text-indigo-600" />
            </div>
            <div>
              <h3 class="text-sm font-bold text-stone-800">Tăng trưởng user 12 tháng</h3>
              <p class="text-[10px] text-stone-400">User đăng ký mới theo tháng</p>
            </div>
          </div>
          <LineChartAdmin :data="charts.users_growth_12months" />
        </div>

        <div class="bg-white rounded-2xl border border-stone-200 p-5">
          <div class="flex items-center gap-2 mb-4">
            <div
              class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center text-blue-700"
            >
              <SvgICon name="bolt" class="w-5 h-5 text-yellow-500" />
            </div>
            <div>
              <h3 class="text-sm font-bold text-stone-800">Hoạt động 30 ngày qua</h3>
              <p class="text-[10px] text-stone-400">Bài nộp + Yêu cầu ký theo ngày</p>
            </div>
          </div>
          <LineChartAdmin :data="charts.system_activity_30days" />
        </div>
      </div>

      <!-- ─── Charts row 2: 3 donuts + bar ─── -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-stone-200 p-5">
          <div class="flex items-center gap-2 mb-4">
            <div
              class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-700"
            >
              <SvgICon name="group-users" class="w-5 h-5" />
            </div>
            <h3 class="text-sm font-bold text-stone-800">User theo vai trò</h3>
          </div>
          <DonutChartAdmin :data="charts.users_by_role" />
        </div>

        <div class="bg-white rounded-2xl border border-stone-200 p-5">
          <div class="flex items-center gap-2 mb-4">
            <div
              class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-700"
            >
              <SvgICon name="edit-pencil" class="w-5 h-5" />
            </div>
            <h3 class="text-sm font-bold text-stone-800">Trạng thái ký số</h3>
          </div>
          <DonutChartAdmin :data="charts.sign_requests_status" />
        </div>

        <div class="bg-white rounded-2xl border border-stone-200 p-5">
          <div class="flex items-center gap-2 mb-4">
            <div
              class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-700"
            >
              <SvgICon name="upload" class="w-5 h-5" />
            </div>
            <h3 class="text-sm font-bold text-stone-800">Trạng thái bài nộp</h3>
          </div>
          <DonutChartAdmin :data="charts.submissions_status" />
        </div>

        <div class="bg-white rounded-2xl border border-stone-200 p-5">
          <div class="flex items-center gap-2 mb-4">
            <div
              class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center text-purple-700"
            >
              <SvgICon name="chart-line" class="w-5 h-5 text-indigo-600" />
            </div>
            <h3 class="text-sm font-bold text-stone-800">Top lớp nhiều nhóm</h3>
          </div>
          <BarChartAdmin :data="charts.top_classes_by_groups" />
        </div>
      </div>

      <!-- ─── Recent users + Recent classes + Top lecturers ─── -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Recent users -->
        <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
          <div class="px-5 py-3 border-b border-stone-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div
                class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center text-blue-700"
              >
                <SvgICon name="users" class="w-5 h-5" />
              </div>
              <div>
                <h3 class="text-sm font-bold text-stone-800">User mới nhất</h3>
                <p class="text-[10px] text-stone-400">5 người mới đăng ký</p>
              </div>
            </div>
            <router-link to="/admin/users" class="text-base text-rose-600 hover:underline">
              Xem tất cả →
            </router-link>
          </div>
          <div class="divide-y divide-stone-100 max-h-80 overflow-y-auto">
            <div v-if="!recentUsers.length" class="p-6 text-center text-sm text-stone-400">
              Chưa có user nào
            </div>
            <div v-for="u in recentUsers" :key="u.id" class="px-5 py-3 flex items-center gap-3">
              <div
                class="w-9 h-9 rounded-full bg-stone-100 flex items-center justify-center text-base font-bold text-stone-600 overflow-hidden flex-shrink-0"
              >
                <img v-if="u.avatar_url" :src="u.avatar_url" class="w-full h-full object-cover" />
                <span v-else>{{ u.name?.charAt(0) }}</span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-stone-800 truncate">{{ u.name }}</p>
                <p class="text-base text-stone-500 truncate">
                  <span
                    class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold uppercase mr-1"
                    :class="roleBadge(u.role)"
                  >
                    {{ u.role }}
                  </span>
                  {{ u.email }}
                </p>
              </div>
              <span class="text-[10px] text-stone-400 flex-shrink-0">
                {{ formatRelative(u.created_at) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Recent classes -->
        <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
          <div class="px-5 py-3 border-b border-stone-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div
                class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center text-purple-700"
              >
                <SvgICon name="graduation-cap" class="w-5 h-5" />
              </div>
              <div>
                <h3 class="text-sm font-bold text-stone-800">Lớp mới nhất</h3>
                <p class="text-[10px] text-stone-400">5 lớp mới tạo</p>
              </div>
            </div>
            <router-link to="/admin/classes" class="text-base text-rose-600 hover:underline">
              Xem tất cả →
            </router-link>
          </div>
          <div class="divide-y divide-stone-100 max-h-80 overflow-y-auto">
            <div v-if="!recentClasses.length" class="p-6 text-center text-sm text-stone-400">
              Chưa có lớp nào
            </div>
            <div v-for="c in recentClasses" :key="c.id" class="px-5 py-3">
              <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-stone-800 truncate">{{ c.code }}</p>
                <span
                  class="px-1.5 py-0.5 rounded-full text-[10px] font-bold"
                  :class="
                    c.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-stone-100 text-stone-500'
                  "
                >
                  {{ c.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
              <p class="text-base text-stone-500 truncate mt-0.5">{{ c.name }}</p>
              <p class="text-[10px] text-stone-400 mt-1">
                GV: {{ c.lecturer?.name || 'Chưa gán' }} · {{ formatRelative(c.created_at) }}
              </p>
            </div>
          </div>
        </div>

        <!-- Top lecturers -->
        <!-- <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
          <div class="px-5 py-3 border-b border-stone-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div
                class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-700"
              >
                🏆
              </div>
              <div>
                <h3 class="text-sm font-bold text-stone-800">Top giảng viên</h3>
                <p class="text-[10px] text-stone-400">Hoạt động nhiều nhất</p>
              </div>
            </div>
          </div>
          <div class="divide-y divide-stone-100 max-h-80 overflow-y-auto">
            <div v-if="!topLecturers.length" class="p-6 text-center text-sm text-stone-400">
              Chưa có dữ liệu
            </div>
            <div
              v-for="(l, idx) in topLecturers"
              :key="l.id"
              class="px-5 py-3 flex items-center gap-3"
            >
              <span
                class="text-base font-bold w-5 text-center flex-shrink-0"
                :class="
                  idx === 0
                    ? 'text-amber-500'
                    : idx === 1
                      ? 'text-stone-400'
                      : idx === 2
                        ? 'text-orange-700'
                        : 'text-stone-300'
                "
              >
                {{ idx === 0 ? '🥇' : idx === 1 ? '🥈' : idx === 2 ? '🥉' : `#${idx + 1}` }}
              </span>
              <div
                class="w-9 h-9 rounded-full bg-stone-100 flex items-center justify-center text-base font-bold text-stone-600 overflow-hidden flex-shrink-0"
              >
                <img v-if="l.avatar_url" :src="l.avatar_url" class="w-full h-full object-cover" />
                <span v-else>{{ l.name?.charAt(0) }}</span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-stone-800 truncate">{{ l.name }}</p>
                <p class="text-[10px] text-stone-500">
                  🎓 {{ l.classes_count }} lớp · ✅ {{ l.signed_count }} ký
                </p>
              </div>
            </div>
          </div>
        </div> -->
      </div>

      <!-- ─── Activity feed ─── -->
      <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-stone-100">
          <div class="flex items-center gap-2">
            <div
              class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-700"
            >
              <SvgICon name="bolt" class="w-5 h-5 text-yellow-500" />
            </div>
            <div>
              <h3 class="text-sm font-bold text-stone-800">Hoạt động gần đây</h3>
              <p class="text-[10px] text-stone-400">20 hoạt động mới nhất toàn hệ thống</p>
            </div>
          </div>
        </div>
        <div class="p-5 max-h-96 overflow-y-auto">
          <div v-if="!recentActivities.length" class="text-center py-6 text-sm text-stone-400">
            Chưa có hoạt động nào
          </div>
          <div v-else class="space-y-3">
            <router-link
              v-for="(a, idx) in recentActivities"
              :key="idx"
              :to="a.link"
              class="flex items-start gap-3 p-2 -mx-2 rounded-lg hover:bg-stone-50 transition group"
            >
              <div class="text-lg flex-shrink-0">
                <SvgICon name="check-circle" class="w-5 h-5 text-green-700" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-stone-700 group-hover:text-rose-600 transition">
                  {{ a.title }}
                </p>
                <p class="text-base text-stone-500 truncate">{{ a.subtitle }}</p>
              </div>
              <span class="text-[10px] text-stone-400 flex-shrink-0 whitespace-nowrap">
                {{ formatRelative(a.time) }}
              </span>
            </router-link>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useAdminDashboardStore } from '@/stores/admin/adminDashboardStore'
import { useAuthStore } from '@/stores/auth'
import StatCard from '../lecturer/components/dashboard/StatCard.vue'
import SvgICon from '@/components/icons/SVG.vue'
import LineChartAdmin from './components/charts/LineChartAdmin.vue'
import BarChartAdmin from './components/charts/BarChartAdmin.vue'
import DonutChartAdmin from './components/charts/DonutChartAdmin.vue'

const store = useAdminDashboardStore()
const authStore = useAuthStore()

const { stats, charts, recentUsers, recentClasses, recentActivities, topLecturers } =
  storeToRefs(store)

const user = computed(() => authStore.user)

onMounted(() => store.fetchDashboard())

function roleBadge(role) {
  return (
    {
      admin: 'bg-red-100 text-red-700',
      lecturer: 'bg-teal-100 text-teal-700',
      student: 'bg-blue-100 text-blue-700',
    }[role] || 'bg-stone-100 text-stone-600'
  )
}

function formatToday() {
  return new Date().toLocaleDateString('vi-VN', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

function formatRelative(d) {
  if (!d) return ''
  const date = new Date(d)
  const now = new Date()
  const diff = Math.floor((now - date) / 1000)

  if (diff < 60) return 'Vừa xong'
  if (diff < 3600) return `${Math.floor(diff / 60)}p trước`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h trước`
  if (diff < 604800) return `${Math.floor(diff / 86400)}d trước`

  return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' })
}
</script>
