<!-- src/views/dashboard/DashboardView.vue -->
<template>
  <div>
    <!-- Page header -->
    <div class="mb-8">
      <h2 class="text-2xl font-bold text-slate-800">Dashboard</h2>
      <p class="text-slate-500 mt-1">Tổng quan lớp học và nhóm của bạn</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div
        class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"
      />
    </div>

    <!-- Classes grid -->
    <div v-else class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <div
        v-for="item in classes"
        :key="item.class.id"
        class="bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-lg transition-shadow duration-300"
      >
        <!-- Class header -->
        <div class="p-6 border-b border-slate-100">
          <div class="flex items-start justify-between">
            <div>
              <div class="flex items-center gap-2 mb-1">
                <span
                  class="px-2.5 py-0.5 bg-indigo-50 text-indigo-700 text-xs font-bold rounded-full"
                >
                  {{ item.class.code }}
                </span>
                <span
                  class="px-2 py-0.5 text-xs rounded-full font-medium"
                  :class="
                    item.class.is_active
                      ? 'bg-emerald-50 text-emerald-700'
                      : 'bg-slate-100 text-slate-500'
                  "
                >
                  {{ item.class.is_active ? 'Đang mở' : 'Đã đóng' }}
                </span>
              </div>
              <h3 class="text-lg font-bold text-slate-800">{{ item.class.name }}</h3>
            </div>
            <div
              class="w-10 h-10 rounded-xl flex items-center justify-center"
              :class="
                item.has_group ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600'
              "
            >
              <svg
                v-if="item.has_group"
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
                />
              </svg>
            </div>
          </div>
        </div>

        <!-- Info rows -->
        <div class="px-6 py-4 space-y-3">
          <!-- Semester -->
          <div v-if="item.semester" class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center">
              <svg
                class="w-4 h-4 text-purple-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
              </svg>
            </div>
            <div>
              <p class="text-xs text-slate-400">Kỳ học</p>
              <p class="text-sm font-medium text-slate-700">
                {{ item.semester.name }} ({{ item.semester.year }})
              </p>
            </div>
          </div>

          <!-- Lecturer -->
          <div v-if="item.lecturer" class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
              <svg
                class="w-4 h-4 text-blue-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                />
              </svg>
            </div>
            <div>
              <p class="text-xs text-slate-400">Giảng viên</p>
              <p class="text-sm font-medium text-slate-700">{{ item.lecturer.name }}</p>
            </div>
          </div>

          <!-- Subjects -->
          <div v-if="item.subjects?.length" class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center">
              <svg
                class="w-4 h-4 text-amber-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                />
              </svg>
            </div>
            <div>
              <p class="text-xs text-slate-400">Môn học</p>
              <p v-for="s in item.subjects" :key="s.id" class="text-sm font-medium text-slate-700">
                {{ s.name }} <span class="text-slate-400">({{ s.credits }} TC)</span>
              </p>
            </div>
          </div>

          <!-- Group info -->
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center">
              <svg
                class="w-4 h-4 text-emerald-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
            </div>
            <div>
              <p class="text-xs text-slate-400">Nhóm</p>
              <p v-if="item.my_group" class="text-sm font-medium text-slate-700">
                {{ item.my_group.name }}
                <span class="text-slate-400">· {{ item.my_group.members?.length }} thành viên</span>
              </p>
              <p v-else class="text-sm text-amber-600 font-medium">Chưa có nhóm</p>
            </div>
          </div>
        </div>

        <!-- Group members preview -->
        <div v-if="item.my_group" class="px-6 pb-4">
          <div class="flex items-center gap-1.5 mt-1">
            <div
              v-for="m in item.my_group.members?.slice(0, 5)"
              :key="m.id"
              class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-600 border-2 border-white -ml-1 first:ml-0"
              :title="m.name"
            >
              {{ m.name?.charAt(0) }}
            </div>
            <span v-if="item.my_group.members?.length > 5" class="text-xs text-slate-400 ml-1">
              +{{ item.my_group.members.length - 5 }}
            </span>
          </div>
        </div>

        <!-- Actions -->
        <div class="px-6 pb-5 flex gap-2">
          <router-link
            v-if="item.my_group"
            :to="`/student/groups/${item.my_group.id}`"
            class="flex-1 py-2 px-4 bg-indigo-600 text-white text-sm font-medium rounded-xl text-center hover:bg-indigo-700 transition-colors"
          >
            Vào nhóm
          </router-link>
          <router-link
            v-else
            :to="`/student/groups?class_id=${item.class.id}`"
            class="flex-1 py-2 px-4 bg-amber-500 text-white text-sm font-medium rounded-xl text-center hover:bg-amber-600 transition-colors"
          >
            Tạo / Tham gia nhóm
          </router-link>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-if="!loading && classes.length === 0" class="text-center py-20">
      <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-100 flex items-center justify-center">
        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
          />
        </svg>
      </div>
      <p class="text-slate-500">Bạn chưa được thêm vào lớp nào</p>
      <p class="text-sm text-slate-400 mt-1">Liên hệ giảng viên hoặc admin để được thêm vào lớp</p>
    </div>
  </div>
</template>

<script setup>
import { useDashboardStore } from '@/stores/students/dashboardStore'
import { storeToRefs } from 'pinia'

const dashboardStore = useDashboardStore()
console.log('Dashboard store ID:', dashboardStore.$id)
const { filteredClasses: classes, loading } = storeToRefs(dashboardStore)
</script>
