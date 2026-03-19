<template>
  <div
    class="min-h-screen bg-[#f4f6fb] px-8 py-7 text-slate-800 font-['Be_Vietnam_Pro',sans-serif]"
  >
    <!-- ─── Header ─────────────────────────────────────────────────────────── -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-3.5">
        <div
          class="w-12 h-12 rounded-[14px] bg-gradient-to-br from-indigo-500 to-indigo-400 flex items-center justify-center text-white shadow-[0_4px_14px_#6366f140]"
        >
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
            <path
              d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"
              fill="currentColor"
              opacity="0.3"
            />
            <path
              d="M21 6.5h-3v-2c0-.83-.67-1.5-1.5-1.5h-9C6.67 3 6 3.67 6 4.5v2H3c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h18c.55 0 1-.45 1-1v-10c0-.55-.45-1-1-1zM8 4.5h8v2H8v-2zm13 12.5H3V8.5h18V17z"
              fill="currentColor"
            />
            <circle cx="12" cy="12.75" r="1.25" fill="currentColor" />
          </svg>
        </div>
        <div>
          <h1 class="text-[22px] font-bold text-slate-900">Kho Tri Thức</h1>
          <p class="text-[13px] text-slate-500 mt-0.5">
            Quản lý tài liệu & dữ liệu huấn luyện ChatBot
          </p>
        </div>
      </div>
      <div class="flex gap-2.5">
        <button
          class="inline-flex items-center gap-1.5 px-[18px] py-[9px] rounded-xl text-[13.5px] font-medium bg-white text-gray-700 border border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition-all"
          @click="showImportModal = true"
        >
          <svg
            width="16"
            height="16"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4" />
            <polyline points="7 10 12 15 17 10" />
            <line x1="12" y1="15" x2="12" y2="3" />
          </svg>
          Nhập tài liệu
        </button>
        <button
          class="inline-flex items-center gap-1.5 px-[18px] py-[9px] rounded-xl text-[13.5px] font-medium text-white bg-gradient-to-br from-indigo-500 to-indigo-400 shadow-[0_2px_10px_#6366f130] hover:-translate-y-px hover:shadow-[0_4px_16px_#6366f140] transition-all"
          @click="openAddModal"
        >
          <svg
            width="16"
            height="16"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <line x1="12" y1="5" x2="12" y2="19" />
            <line x1="5" y1="12" x2="19" y2="12" />
          </svg>
          Thêm tri thức
        </button>
      </div>
    </div>

    <!-- ─── Stats ──────────────────────────────────────────────────────────── -->
    <div class="grid grid-cols-4 gap-4 mb-6">
      <div
        v-for="stat in statsWithValues"
        :key="stat.label"
        class="bg-white rounded-[14px] px-5 py-[18px] flex items-center gap-3.5 shadow-sm border border-slate-100"
      >
        <div
          class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
          :style="{ background: stat.bg }"
        >
          <span v-html="stat.icon"></span>
        </div>
        <div class="flex-1">
          <div class="text-xl font-bold text-slate-900">{{ stat.value }}</div>
          <div class="text-xs text-slate-500 mt-0.5">{{ stat.label }}</div>
        </div>
      </div>
    </div>

    <!-- ─── Main Content ───────────────────────────────────────────────────── -->
    <div class="flex gap-5 items-start">
      <!-- Sidebar: Danh mục -->
      <div
        class="w-[220px] flex-shrink-0 bg-white rounded-[14px] p-[18px] shadow-sm border border-slate-100 sticky top-5"
      >
        <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-[0.06em] mb-2.5">
          Danh mục
        </div>
        <ul class="space-y-0.5">
          <li
            v-for="cat in categories"
            :key="cat.id"
            class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg cursor-pointer transition-all text-[13.5px]"
            :class="
              selectedCategory === cat.id
                ? 'bg-indigo-50 text-indigo-600 font-semibold'
                : 'text-gray-700 hover:bg-slate-50'
            "
            @click="selectCategory(cat.id)"
          >
            <span
              class="w-2 h-2 rounded-full flex-shrink-0"
              :style="{ background: cat.color }"
            ></span>
            <span class="flex-1">{{ cat.name }}</span>
            <span class="text-xs text-slate-400 font-medium">{{ cat.count }}</span>
          </li>
        </ul>
      </div>

      <!-- Table Container -->
      <div
        class="flex-1 bg-white rounded-[14px] shadow-sm border border-slate-100 overflow-hidden relative"
      >
        <!-- Loading overlay -->
        <div
          v-if="loading"
          class="absolute inset-0 bg-white/70 z-10 flex items-center justify-center"
        >
          <span
            class="inline-block w-8 h-8 border-[3px] border-indigo-200 border-t-indigo-600 rounded-full animate-spin"
          ></span>
        </div>

        <!-- Toolbar -->
        <div
          class="flex flex-wrap items-center justify-between px-5 py-4 border-b border-slate-100 gap-3"
        >
          <!-- Search -->
          <div
            class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-[9px] px-3.5 py-2 flex-1 max-w-[320px] text-slate-400"
          >
            <svg
              width="16"
              height="16"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
            >
              <circle cx="11" cy="11" r="8" />
              <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input
              v-model="searchQuery"
              placeholder="Tìm kiếm tri thức..."
              class="bg-transparent border-none text-[13.5px] text-slate-700 w-full focus:outline-none"
            />
          </div>

          <div class="flex items-center gap-2 flex-wrap">
            <!-- Filter: Danh mục -->
            <select
              :value="selectedCategory"
              @change="selectCategory($event.target.value === '' ? null : $event.target.value)"
              class="border border-slate-200 rounded-lg px-3 py-[7px] text-[13px] text-gray-700 bg-slate-50 cursor-pointer focus:outline-none"
            >
              <option value="">Tất cả danh mục</option>
              <option v-for="cat in categories.slice(1)" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>

            <!-- Sort -->
            <select
              :value="sortBy"
              @change="changeSortBy($event.target.value)"
              class="border border-slate-200 rounded-lg px-3 py-[7px] text-[13px] text-gray-700 bg-slate-50 cursor-pointer focus:outline-none"
            >
              <option value="date">Mới nhất</option>
              <option value="name">Tên A-Z</option>
            </select>

            <!-- Nút xóa lọc (chỉ hiện khi đang filter) -->
            <button
              v-if="selectedCategory !== null || searchQuery"
              @click="resetFilters"
              class="flex items-center gap-1 px-3 py-[7px] rounded-lg border border-slate-200 text-[13px] text-slate-500 bg-slate-50 hover:bg-red-50 hover:text-red-500 hover:border-red-200 transition-colors"
            >
              <svg
                width="13"
                height="13"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
              >
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
              </svg>
              Xóa lọc
            </button>

            <!-- Toggle view -->
            <div class="flex border border-slate-200 rounded-lg overflow-hidden">
              <button
                class="px-2.5 py-[7px] border-none cursor-pointer transition-all"
                :class="
                  viewMode === 'table'
                    ? 'bg-indigo-50 text-indigo-600'
                    : 'bg-transparent text-slate-400 hover:bg-slate-50'
                "
                @click="viewMode = 'table'"
              >
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                  <rect x="3" y="3" width="7" height="4" rx="1" />
                  <rect x="14" y="3" width="7" height="4" rx="1" />
                  <rect x="3" y="10" width="7" height="4" rx="1" />
                  <rect x="14" y="10" width="7" height="4" rx="1" />
                  <rect x="3" y="17" width="7" height="4" rx="1" />
                  <rect x="14" y="17" width="7" height="4" rx="1" />
                </svg>
              </button>
              <button
                class="px-2.5 py-[7px] border-none cursor-pointer transition-all"
                :class="
                  viewMode === 'grid'
                    ? 'bg-indigo-50 text-indigo-600'
                    : 'bg-transparent text-slate-400 hover:bg-slate-50'
                "
                @click="viewMode = 'grid'"
              >
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor">
                  <rect x="3" y="3" width="8" height="8" rx="1" />
                  <rect x="13" y="3" width="8" height="8" rx="1" />
                  <rect x="3" y="13" width="8" height="8" rx="1" />
                  <rect x="13" y="13" width="8" height="8" rx="1" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- ── Table View ── -->
        <div v-if="viewMode === 'table'" class="overflow-x-auto">
          <table class="w-full border-collapse">
            <thead class="bg-slate-50">
              <tr>
                <th
                  class="px-4 py-[11px] text-left text-[12px] font-semibold text-slate-500 uppercase tracking-[0.04em] border-b border-slate-100 w-10"
                >
                  <input
                    type="checkbox"
                    v-model="selectAll"
                    @change="toggleSelectAll"
                    class="cursor-pointer"
                  />
                </th>
                <th
                  class="px-4 py-[11px] text-left text-[12px] font-semibold text-slate-500 uppercase tracking-[0.04em] border-b border-slate-100"
                >
                  Tiêu đề
                </th>
                <th
                  class="px-4 py-[11px] text-left text-[12px] font-semibold text-slate-500 uppercase tracking-[0.04em] border-b border-slate-100"
                >
                  Danh mục
                </th>
                <th
                  class="px-4 py-[11px] text-left text-[12px] font-semibold text-slate-500 uppercase tracking-[0.04em] border-b border-slate-100"
                >
                  Cập nhật
                </th>
                <th
                  class="px-4 py-[11px] text-left text-[12px] font-semibold text-slate-500 uppercase tracking-[0.04em] border-b border-slate-100"
                >
                  Thao tác
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in pagedItems"
                :key="item.id"
                class="border-b border-slate-50 last:border-0 hover:[&>td]:bg-[#fafbff] transition-colors"
                :class="{ '[&>td]:bg-[#f0f4ff]': selectedItems.includes(item.id) }"
              >
                <td class="px-4 py-3.5 align-middle">
                  <input
                    type="checkbox"
                    :value="item.id"
                    v-model="selectedItems"
                    class="cursor-pointer"
                  />
                </td>
                <td class="px-4 py-3.5 align-middle">
                  <div class="text-[13.5px] font-semibold text-slate-800">{{ item.title }}</div>
                  <div class="text-xs text-slate-400 mt-0.5">{{ item.description }}</div>
                </td>
                <td class="px-4 py-3.5 align-middle">
                  <span
                    class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :style="{
                      background: getCategoryColor(item.category) + '22',
                      color: getCategoryColor(item.category),
                    }"
                    >{{ item.category }}</span
                  >
                </td>
                <td class="px-4 py-3.5 align-middle text-slate-400 text-[13.5px]">
                  {{ item.updatedAt }}
                </td>
                <td class="px-4 py-3.5 align-middle">
                  <div class="flex gap-1">
                    <button
                      class="w-[30px] h-[30px] rounded-[7px] bg-slate-50 text-slate-500 flex items-center justify-center hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
                      title="Xem"
                      @click="viewItem(item)"
                    >
                      <svg
                        width="14"
                        height="14"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                      >
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                        <circle cx="12" cy="12" r="3" />
                      </svg>
                    </button>
                    <button
                      class="w-[30px] h-[30px] rounded-[7px] bg-slate-50 text-slate-500 flex items-center justify-center hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
                      title="Sửa"
                      @click="openEditModal(item)"
                    >
                      <svg
                        width="14"
                        height="14"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                      >
                        <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
                        <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                      </svg>
                    </button>
                    <button
                      class="w-[30px] h-[30px] rounded-[7px] bg-slate-50 text-slate-500 flex items-center justify-center hover:bg-red-50 hover:text-red-500 transition-colors"
                      title="Xóa"
                      @click="handleDeleteItem(item)"
                    >
                      <svg
                        width="14"
                        height="14"
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
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <div
            v-if="pagedItems.length === 0"
            class="text-center py-12 text-slate-400 flex flex-col items-center gap-2.5"
          >
            <svg
              width="48"
              height="48"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="1.5"
              opacity="0.3"
            >
              <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
              <polyline points="14 2 14 8 20 8" />
            </svg>
            <p>Không tìm thấy tài liệu nào</p>
          </div>
        </div>

        <!-- ── Grid View ── -->
        <div v-else class="grid grid-cols-[repeat(auto-fill,minmax(260px,1fr))] gap-4 p-5">
          <div
            v-for="item in pagedItems"
            :key="item.id"
            class="border border-slate-100 rounded-xl overflow-hidden transition-all hover:border-indigo-200 hover:shadow-[0_4px_16px_#6366f115]"
          >
            <div class="px-4 pt-4 pb-3">
              <div class="text-[13.5px] font-semibold text-slate-800">{{ item.title }}</div>
              <div class="text-xs text-slate-400 mt-0.5">{{ item.description }}</div>
              <div class="flex items-center justify-between mt-2.5">
                <span
                  class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :style="{
                    background: getCategoryColor(item.category) + '22',
                    color: getCategoryColor(item.category),
                  }"
                  >{{ item.category }}</span
                >
                <span class="text-slate-400 text-xs">{{ item.updatedAt }}</span>
              </div>
            </div>
            <div class="flex gap-1.5 px-4 py-2.5 border-t border-slate-100 bg-slate-50/60">
              <button
                class="w-[30px] h-[30px] rounded-[7px] bg-white text-slate-500 flex items-center justify-center hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
                @click="viewItem(item)"
              >
                <svg
                  width="14"
                  height="14"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
              </button>
              <button
                class="w-[30px] h-[30px] rounded-[7px] bg-white text-slate-500 flex items-center justify-center hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
                @click="openEditModal(item)"
              >
                <svg
                  width="14"
                  height="14"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
                  <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
              </button>
              <button
                class="w-[30px] h-[30px] rounded-[7px] bg-white text-slate-500 flex items-center justify-center hover:bg-red-50 hover:text-red-500 transition-colors"
                @click="handleDeleteItem(item)"
              >
                <svg
                  width="14"
                  height="14"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <polyline points="3 6 5 6 21 6" />
                  <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between px-5 py-3.5 border-t border-slate-100">
          <span class="text-[13px] text-slate-500"
            >Hiển thị {{ items.length }} / {{ total }} mục</span
          >
          <div class="flex gap-1">
            <button
              class="min-w-[32px] h-8 rounded-lg border border-slate-200 bg-white text-gray-700 text-[13px] cursor-pointer transition-all hover:bg-slate-50 disabled:opacity-40 disabled:cursor-default"
              :disabled="currentPage === 1"
              @click="changePage(currentPage - 1)"
            >
              &#8249;
            </button>
            <button
              v-for="p in totalPages"
              :key="p"
              class="min-w-[32px] h-8 rounded-lg border text-[13px] cursor-pointer transition-all"
              :class="
                currentPage === p
                  ? 'bg-indigo-600 text-white border-indigo-600'
                  : 'border-slate-200 bg-white text-gray-700 hover:bg-slate-50'
              "
              @click="changePage(p)"
            >
              {{ p }}
            </button>
            <button
              class="min-w-[32px] h-8 rounded-lg border border-slate-200 bg-white text-gray-700 text-[13px] cursor-pointer transition-all hover:bg-slate-50 disabled:opacity-40 disabled:cursor-default"
              :disabled="currentPage === totalPages"
              @click="changePage(currentPage + 1)"
            >
              &#8250;
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ─── View Detail Modal ──────────────────────────────────────────────── -->
    <div
      v-if="viewingItem"
      class="fixed inset-0 bg-black/55 backdrop-blur-sm z-50 flex items-center justify-center p-5"
      @click.self="viewingItem = null"
    >
      <div
        class="bg-white rounded-2xl w-full max-w-[680px] shadow-2xl animate-modal max-h-[90vh] overflow-y-auto"
      >
        <div
          class="flex items-center justify-between px-6 pt-[22px] pb-4 border-b border-slate-100"
        >
          <div class="flex items-center gap-3">
            <div>
              <h3 class="text-[17px] font-bold text-slate-900">{{ viewingItem.title }}</h3>
              <span
                class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium mt-0.5"
                :style="{
                  background: getCategoryColor(viewingItem.category) + '22',
                  color: getCategoryColor(viewingItem.category),
                }"
                >{{ viewingItem.category }}</span
              >
            </div>
          </div>
          <button
            class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 text-lg flex items-center justify-center hover:bg-red-100 hover:text-red-500 transition-colors"
            @click="viewingItem = null"
          >
            ×
          </button>
        </div>
        <div class="px-6 py-5 space-y-4">
          <div class="flex items-center gap-2">
            <span class="text-xs text-slate-400">Cập nhật</span>
            <span class="text-[13px] text-slate-700">{{ viewingItem.updatedAt }}</span>
          </div>
          <div>
            <label class="block text-[13px] font-semibold text-gray-700 mb-1.5">Nội dung</label>
            <div
              class="bg-slate-50 rounded-xl px-4 py-3.5 text-[13.5px] text-gray-700 leading-[1.7] border border-slate-100 whitespace-pre-wrap"
            >
              {{ viewingItem.content || 'Không có nội dung.' }}
            </div>
          </div>
          <div v-if="viewingItem.tags">
            <label class="block text-[13px] font-semibold text-gray-700 mb-1.5">Tags</label>
            <div class="flex flex-wrap gap-1.5">
              <span
                v-for="tag in viewingItem.tags.split(',')"
                :key="tag"
                class="bg-indigo-50 text-indigo-600 px-2.5 py-0.5 rounded-full text-xs font-medium"
                >{{ tag.trim() }}</span
              >
            </div>
          </div>
        </div>
        <div class="flex justify-end gap-2.5 px-6 py-4 border-t border-slate-100">
          <button
            class="inline-flex items-center gap-1.5 px-[18px] py-[9px] rounded-xl text-[13.5px] font-medium bg-transparent text-slate-500 border border-slate-200 hover:bg-slate-50 transition-colors"
            @click="viewingItem = null"
          >
            Đóng
          </button>
          <button
            class="inline-flex items-center gap-1.5 px-[18px] py-[9px] rounded-xl text-[13.5px] font-medium text-white bg-gradient-to-br from-indigo-500 to-indigo-400 shadow-[0_2px_10px_#6366f130] hover:-translate-y-px hover:shadow-[0_4px_16px_#6366f140] transition-all"
            @click="openEditFromView"
          >
            Chỉnh sửa
          </button>
        </div>
      </div>
    </div>

    <!-- ─── KnowledgeFormModal ─────────────────────────────────────────────── -->
    <KnowledgeFormModal
      v-if="showFormModal"
      :model-value="editingItem"
      :categories="categories.slice(1)"
      @close="closeFormModal"
      @saved="handleSaved"
    />

    <!-- ─── ImportDocumentModal ────────────────────────────────────────────── -->
    <ImportDocumentModal
      v-if="showImportModal"
      @close="showImportModal = false"
      @imported="handleImported"
    />
  </div>
</template>

<script>
import { mapState, mapActions } from 'pinia'
import { useChatbotStore } from '@/stores/admin/chat/chatBot'
import KnowledgeFormModal from './components/chat/KnowledgeFormModal.vue'
import ImportDocumentModal from './components/chat/ImportDocumentModal.vue'

export default {
  name: 'ChatBotManagement',
  components: { KnowledgeFormModal, ImportDocumentModal },

  data() {
    return {
      viewMode: 'table',
      selectAll: false,
      selectedItems: [],
      showFormModal: false,
      showImportModal: false,
      editingItem: null,
      viewingItem: null,
      searchQuery: '',
      selectedCategory: null,
      sortBy: 'date',
      searchTimer: null,

      stats: [
        {
          label: 'Tổng tri thức',
          bg: 'linear-gradient(135deg,#6366f1,#818cf8)',
          icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="white"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
          valueKey: 'total',
        },
      ],
    }
  },

  computed: {
    ...mapState(useChatbotStore, [
      'items',
      'categories',
      'total',
      'currentPage',
      'totalPages',
      'loading',
    ]),

    pagedItems() {
      return this.items
    },
    statsWithValues() {
      return this.stats.map((s) => ({ ...s, value: s.valueKey === 'total' ? this.total : '—' }))
    },
  },

  watch: {
    searchQuery(val) {
      clearTimeout(this.searchTimer)
      this.searchTimer = setTimeout(() => this.applyFilters({ search: val }), 400)
    },
  },

  async mounted() {
    const store = useChatbotStore()
    await Promise.all([store.fetchCategories(), store.fetchList()])
  },

  methods: {
    ...mapActions(useChatbotStore, [
      'fetchList',
      'goToPage',
      'applyFilters',
      'createItem',
      'updateItem',
      'deleteItem',
      'importFile',
    ]),

    // ── Filters ──────────────────────────────────────────────────────────────
    selectCategory(catId) {
      this.selectedCategory = catId
      this.applyFilters({ category: catId })
    },

    changeSortBy(val) {
      this.sortBy = val
      this.applyFilters({ sort_by: val })
    },

    resetFilters() {
      this.selectedCategory = null
      this.searchQuery = ''
      this.sortBy = 'date'
      this.applyFilters({ category: null, search: '', sort_by: 'date' })
    },

    // ── Modal helpers ─────────────────────────────────────────────────────────
    openAddModal() {
      this.editingItem = null
      this.showFormModal = true
    },
    openEditModal(item) {
      this.editingItem = { ...item }
      this.showFormModal = true
    },
    openEditFromView() {
      this.openEditModal(this.viewingItem)
      this.viewingItem = null
    },
    closeFormModal() {
      this.showFormModal = false
      this.editingItem = null
    },
    viewItem(item) {
      this.viewingItem = item
    },

    // ── CRUD ──────────────────────────────────────────────────────────────────
    async handleSaved(payload) {
      const body = {
        title: payload.title,
        description: payload.description,
        content: payload.content,
        type: payload.type,
        tags: payload.tags,
        category: payload.category,
      }
      const ok = payload.id ? await this.updateItem(payload.id, body) : await this.createItem(body)
      if (ok) this.closeFormModal()
    },

    async handleDeleteItem(item) {
      if (!confirm(`Xóa "${item.title}"?`)) return
      await this.deleteItem(item.id)
    },

    async handleImported(files) {
      for (const file of files) {
        const ok = await this.importFile(file)
        if (!ok) break
      }
    },

    // ── Pagination ────────────────────────────────────────────────────────────
    async changePage(page) {
      await this.goToPage(page)
    },

    // ── Helpers ───────────────────────────────────────────────────────────────
    toggleSelectAll() {
      this.selectedItems = this.selectAll ? this.items.map((i) => i.id) : []
    },

    getCategoryColor(name) {
      return this.categories.find((c) => c.name === name || c.id === name)?.color ?? '#6b7280'
    },
  },
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700&display=swap');

.animate-modal {
  animation: modalIn 0.2s ease;
}
@keyframes modalIn {
  from {
    transform: translateY(20px) scale(0.97);
    opacity: 0;
  }
  to {
    transform: none;
    opacity: 1;
  }
}
</style>
