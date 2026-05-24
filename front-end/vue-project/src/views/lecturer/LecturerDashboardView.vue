<!-- src/views/lecturer/LecturerDashboardView.vue -->
<template>
  <div class="space-y-6">
    <!-- ─── Welcome banner ─── -->
    <div class="bg-gradient-to-r from-teal-500 to-cyan-600 rounded-2xl p-6 text-white shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold mb-1">Xin Chào {{ user?.name || 'Giảng viên' }}!</h2>
          <p class="text-teal-50 text-sm">{{ formatToday() }}</p>
        </div>
        <div class="hidden md:block text-right">
          <p class="text-3xl font-bold">{{ stats.sign_requests_signed_today }}</p>
          <p class="text-base text-teal-100">tài liệu đã ký hôm nay</p>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="store.loading" class="flex justify-center py-16">
      <div class="w-10 h-10 border-4 border-teal-200 border-t-teal-600 rounded-full animate-spin" />
    </div>

    <template v-else>
      <!-- ─── Stats cards ─── -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <StatCard
          v-for="card in statCards"
          :key="card.key"
          :icon="card.icon"
          :label="card.label"
          :value="stats[card.key]"
          :color="card.color"
          :link="card.link"
        />
      </div>

      <!-- ─── Charts row 1: Line chart hoạt động 7 ngày ─── -->
      <div class="bg-white rounded-2xl border border-stone-200 p-5">
        <div class="flex items-center gap-2 mb-4">
          <div
            class="w-8 h-8 rounded-lg bg-teal-100 flex items-center justify-center text-teal-700"
          >
            <SvgICon name="chart-line" class="w-5 h-5 text-indigo-600" />
          </div>
          <div>
            <h3 class="text-sm font-bold text-stone-800">Hoạt động ký số 7 ngày qua</h3>
            <p class="text-[10px] text-stone-400">Theo dõi xu hướng</p>
          </div>
        </div>
        <LineChart :data="charts.sign_activity_7days" />
      </div>

      <!-- ─── Charts row 2: 3 donut/bar ─── -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Sign requests donut -->
        <div class="bg-white rounded-2xl border border-stone-200 p-5">
          <div class="flex items-center gap-2 mb-4">
            <div
              class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-700"
            >
              <SvgICon name="document-check" class="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <h3 class="text-sm font-bold text-stone-800">Yêu cầu ký số</h3>
              <p class="text-[10px] text-stone-400">Phân bố trạng thái</p>
            </div>
          </div>
          <DonutChart :data="charts.sign_requests_status" />
        </div>

        <!-- Submissions donut -->
        <div class="bg-white rounded-2xl border border-stone-200 p-5">
          <div class="flex items-center gap-2 mb-4">
            <div
              class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-700"
            >
              <SvgICon name="upload" class="w-5 h-5 text-yellow-500" />
            </div>
            <div>
              <h3 class="text-sm font-bold text-stone-800">Bài nộp</h3>
              <p class="text-[10px] text-stone-400">Phân bố trạng thái</p>
            </div>
          </div>
          <DonutChart :data="charts.submissions_status" />
        </div>

        <!-- Bar chart submissions by class -->
        <div class="bg-white rounded-2xl border border-stone-200 p-5">
          <div class="flex items-center gap-2 mb-4">
            <div
              class="w-8 h-8 rounded-lg bg-purple-100 flex items-center justify-center text-purple-700"
            >
              <SvgICon name="chart-bar" class="w-5 h-5 text-pink-600" />
            </div>
            <div>
              <h3 class="text-sm font-bold text-stone-800">Bài nộp theo lớp</h3>
              <p class="text-[10px] text-stone-400">Top lớp hoạt động</p>
            </div>
          </div>
          <BarChart :data="charts.submissions_by_class" />
        </div>
      </div>

      <!-- ─── Quick actions: Pending items ─── -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Bài nộp chờ duyệt -->
        <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
          <div class="px-5 py-3 border-b border-stone-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div
                class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-700"
              >
                <SvgICon name="upload" class="w-5 h-5 text-pink-600" />
              </div>
              <div>
                <h3 class="text-sm font-bold text-stone-800">Bài nộp chờ duyệt</h3>
                <p class="text-[10px] text-stone-400">5 bài mới nhất</p>
              </div>
            </div>
            <span
              v-if="stats.submissions_pending > 0"
              class="px-2 py-0.5 bg-amber-100 text-amber-700 text-base font-bold rounded-full"
            >
              {{ stats.submissions_pending }}
            </span>
          </div>

          <div class="divide-y divide-stone-100 max-h-80 overflow-y-auto">
            <div v-if="!pendingSubmissions.length" class="p-6 text-center text-sm text-stone-400">
              🎉 Không có bài nộp nào chờ duyệt
            </div>
            <router-link
              v-for="s in pendingSubmissions"
              :key="s.id"
              :to="`/lecturer/assignments/${s.assignment_id}`"
              class="block px-5 py-3 hover:bg-stone-50 transition"
            >
              <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                  <span class="flex items-center justify-center">
                    <SvgICon
                      :name="s.submitter_type === 'group' ? 'group-users' : 'users'"
                      :class="
                        s.submitter_type === 'group'
                          ? 'w-4 h-4 text-purple-600'
                          : 'w-4 h-4 text-blue-600'
                      "
                    />
                  </span>
                  <div class="min-w-0">
                    <p class="text-sm font-semibold text-stone-800 truncate">
                      {{ s.submitter_name }}
                    </p>
                    <p class="text-base text-stone-500 truncate">
                      {{ s.class_name }} · {{ s.assignment_title }}
                    </p>
                  </div>
                </div>
                <span class="text-[10px] text-stone-400 flex-shrink-0 whitespace-nowrap">
                  {{ formatRelative(s.submitted_at) }}
                </span>
              </div>
            </router-link>
          </div>
        </div>

        <!-- Yêu cầu ký số chờ ký -->
        <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
          <div class="px-5 py-3 border-b border-stone-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div
                class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-700"
              >
                <SvgICon name="document" class="w-5 h-5 text-blue-600" />
              </div>
              <div>
                <h3 class="text-sm font-bold text-stone-800">Yêu cầu ký số</h3>
                <p class="text-[10px] text-stone-400">Đang chờ bạn ký</p>
              </div>
            </div>
            <span
              v-if="stats.sign_requests_pending > 0"
              class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-base font-bold rounded-full"
            >
              {{ stats.sign_requests_pending }}
            </span>
          </div>

          <div class="divide-y divide-stone-100 max-h-80 overflow-y-auto">
            <div v-if="!pendingSignRequests.length" class="p-6 text-center text-sm text-stone-400">
              Không có yêu cầu nào đang chờ
            </div>
            <router-link
              v-for="r in pendingSignRequests"
              :key="r.id"
              :to="`/lecturer/sign-requests/${r.id}`"
              class="block px-5 py-3 hover:bg-stone-50 transition"
            >
              <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                  <div
                    class="w-8 h-8 rounded-full bg-stone-100 flex items-center justify-center text-base font-bold text-stone-600 overflow-hidden flex-shrink-0"
                  >
                    <img
                      v-if="r.requester?.avatar_url"
                      :src="r.requester.avatar_url"
                      class="w-full h-full object-cover"
                    />
                    <span v-else>{{ r.requester?.name?.charAt(0) }}</span>
                  </div>
                  <div class="min-w-0">
                    <p class="text-sm font-semibold text-stone-800 truncate">
                      {{ r.requester?.name }}
                    </p>
                    <p class="text-base text-stone-500 truncate">
                      <span
                        class="inline-block w-1.5 h-1.5 rounded-full mr-1"
                        :class="r.status === 'lecturer_reviewing' ? 'bg-blue-500' : 'bg-amber-500'"
                      />
                      {{ r.document_category }}
                    </p>
                  </div>
                </div>
                <span class="text-[10px] text-stone-400 flex-shrink-0 whitespace-nowrap">
                  {{ formatRelative(r.created_at) }}
                </span>
              </div>
            </router-link>
          </div>
        </div>
      </div>

      <!-- ─── Activity feed ─── -->
      <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-stone-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div
              class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-700"
            >
              <SvgICon name="bolt" class="w-5 h-5 text-yellow-500" />
            </div>
            <div>
              <h3 class="text-sm font-bold text-stone-800">Hoạt động gần đây</h3>
              <p class="text-[10px] text-stone-400">15 hoạt động mới nhất</p>
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
              <div class="flex-shrink-0">
                <SvgICon
                  :name="a.icon"
                  class="w-5 h-5 text-stone-500 group-hover:text-teal-600 transition"
                />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-stone-700 group-hover:text-teal-600 transition">
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
import { useLecturerDashboardStore } from '@/stores/lecturer/lecturerDashboardStore'
import { useAuthStore } from '@/stores/auth'
import StatCard from './components/dashboard/StatCard.vue'
import BarChart from './components/dashboard/BarChart.vue'
import LineChart from './components/dashboard/LineChart.vue'
import DonutChart from './components/dashboard/DonutChart.vue'
import SvgICon from '@/components/icons/SVG.vue'

const store = useLecturerDashboardStore()
const authStore = useAuthStore()

const { stats, charts, pendingSubmissions, pendingSignRequests, recentActivities } =
  storeToRefs(store)

const user = computed(() => authStore.user)

const statCards = [
  {
    key: 'classes_count',
    icon: 'graduation-cap',
    label: 'Lớp giảng dạy',
    color: 'teal',
    link: '/lecturer/classes',
  },
  { key: 'students_count', icon: 'users', label: 'Sinh viên', color: 'blue', link: null },
  { key: 'groups_count', icon: 'group-users', label: 'Nhóm', color: 'purple', link: null },
  {
    key: 'submissions_pending',
    icon: 'document-check',
    label: 'Bài chờ duyệt',
    color: 'amber',
    link: null,
  },
  {
    key: 'sign_requests_pending',
    icon: 'document-check',
    label: 'Chờ ký số',
    color: 'indigo',
    link: '/lecturer/sign-requests',
  },
  {
    key: 'sign_requests_signed_today',
    icon: 'check-circle',
    label: 'Đã ký hôm nay',
    color: 'emerald',
    link: null,
  },
]

onMounted(() => {
  store.fetchDashboard()
})

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
  if (diff < 3600) return `${Math.floor(diff / 60)} phút trước`
  if (diff < 86400) return `${Math.floor(diff / 3600)} giờ trước`
  if (diff < 604800) return `${Math.floor(diff / 86400)} ngày trước`

  return date.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' })
}
</script>
