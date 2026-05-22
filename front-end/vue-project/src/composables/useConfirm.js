// src/composables/useConfirm.js
//
// Cho phép gọi confirm như Promise:
//   const ok = await confirm({ title: 'Xóa lớp?', itemName: 'K01' })
//   if (ok) { await api.delete(...) }

import { ref, reactive } from 'vue'

// ─── Singleton state - share giữa các component ──
const state = reactive({
  show: false,
  loading: false,
  title: 'Xác nhận',
  message: 'Bạn có chắc chắn muốn thực hiện hành động này?',
  itemName: '',
  warningText: '',
  confirmText: 'Xác nhận',
  cancelText: 'Hủy',
  variant: 'danger',
  requireTypeConfirm: '',
})

let resolveFn = null

export function useConfirm() {
  /**
   * Mở modal và trả về Promise<boolean>
   *
   * @param {object} options - Cấu hình modal
   * @returns {Promise<boolean>} true nếu user xác nhận, false nếu hủy
   */
  function confirm(options = {}) {
    Object.assign(state, {
      title: 'Xác nhận',
      message: 'Bạn có chắc chắn muốn thực hiện hành động này?',
      itemName: '',
      warningText: '',
      confirmText: 'Xác nhận',
      cancelText: 'Hủy',
      variant: 'danger',
      requireTypeConfirm: '',
      ...options,
      show: true,
      loading: false,
    })

    return new Promise((resolve) => {
      resolveFn = resolve
    })
  }

  // ─── Shortcuts ──
  function confirmDelete(itemName, options = {}) {
    return confirm({
      title: 'Xác nhận xóa',
      message: 'Hành động này không thể hoàn tác. Tất cả dữ liệu liên quan sẽ bị xóa vĩnh viễn.',
      confirmText: 'Xóa',
      variant: 'danger',
      itemName,
      ...options,
    })
  }

  function confirmAction(message, options = {}) {
    return confirm({
      title: 'Xác nhận',
      message,
      variant: 'warning',
      ...options,
    })
  }

  // ─── Internal handlers ──
  function _handleConfirm() {
    if (resolveFn) {
      resolveFn(true)
      resolveFn = null
    }
    // Note: KHÔNG đóng modal ở đây - để component gọi setLoading/close
    // Hoặc nếu không có async action, đóng luôn:
    if (!state.loading) state.show = false
  }

  function _handleCancel() {
    if (resolveFn) {
      resolveFn(false)
      resolveFn = null
    }
    state.show = false
    state.loading = false
  }

  function setLoading(val) {
    state.loading = val
  }

  function close() {
    state.show = false
    state.loading = false
  }

  return {
    state,
    confirm,
    confirmDelete,
    confirmAction,
    setLoading,
    close,
    _handleConfirm,
    _handleCancel,
  }
}
