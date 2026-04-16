// src/stores/groupStore.js
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { groupApi } from '@/api/students/studentApi'

export const useGroupStore = defineStore('group', () => {
  const groups = ref([])
  const currentGroup = ref(null)
  const loading = ref(false)
  const error = ref(null)

  // Danh sách nhóm trong lớp
  async function fetchGroups(classId) {
    loading.value = true
    error.value = null
    try {
      const { data } = await groupApi.getByClass(classId)
      groups.value = data.groups || []
    } catch (err) {
      error.value = err.response?.data?.message || 'Lỗi tải danh sách nhóm'
    } finally {
      loading.value = false
    }
  }

  // Chi tiết nhóm
  async function fetchGroupDetail(groupId) {
    loading.value = true
    error.value = null
    try {
      const { data } = await groupApi.getDetail(groupId)
      currentGroup.value = data.group
    } catch (err) {
      error.value = err.response?.data?.message || 'Lỗi tải chi tiết nhóm'
    } finally {
      loading.value = false
    }
  }

  // Tạo nhóm
  async function createGroup(classId, name) {
    loading.value = true
    error.value = null
    try {
      const { data } = await groupApi.create({ class_id: classId, name })
      currentGroup.value = data.group
      return { success: true, data }
    } catch (err) {
      error.value = err.response?.data?.message || 'Lỗi tạo nhóm'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  // Thêm thành viên
  async function addMember(groupId, studentCode) {
    error.value = null
    try {
      const { data } = await groupApi.addMember(groupId, studentCode)
      // Reload chi tiết
      await fetchGroupDetail(groupId)
      return { success: true, data }
    } catch (err) {
      error.value = err.response?.data?.message || 'Lỗi thêm thành viên'
      return { success: false, message: error.value }
    }
  }

  // Xóa thành viên
  async function removeMember(groupId, memberId) {
    error.value = null
    try {
      await groupApi.removeMember(groupId, memberId)
      await fetchGroupDetail(groupId)
      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || 'Lỗi xóa thành viên'
      return { success: false, message: error.value }
    }
  }

  // Sửa nhóm (tên, khóa/mở)
  async function updateGroup(groupId, data) {
    error.value = null
    try {
      const { data: res } = await groupApi.update(groupId, data)
      currentGroup.value = res.group
      return { success: true, data: res }
    } catch (err) {
      error.value = err.response?.data?.message || 'Lỗi cập nhật nhóm'
      return { success: false, message: error.value }
    }
  }

  async function deleteGroup(groupId) {
    error.value = null
    try {
      await groupApi.delete(groupId)
      currentGroup.value = null
      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || 'Lỗi xóa nhóm'
      return { success: false, message: error.value }
    }
  }

  async function leaveGroup(groupId) {
    error.value = null
    try {
      await groupApi.leave(groupId)
      currentGroup.value = null
      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || 'Lỗi rời nhóm'
      return { success: false, message: error.value }
    }
  }

  // Chuyển quyền leader
  async function transferLeader(groupId, newLeaderId) {
    error.value = null
    try {
      const { data: res } = await groupApi.transferLeader(groupId, newLeaderId)
      currentGroup.value = res.group
      return { success: true }
    } catch (err) {
      error.value = err.response?.data?.message || 'Lỗi chuyển quyền'
      return { success: false, message: error.value }
    }
  }
  return {
    groups,
    currentGroup,
    loading,
    error,
    fetchGroups,
    fetchGroupDetail,
    createGroup,
    addMember,
    removeMember,
    updateGroup,
    deleteGroup,
    leaveGroup,
    transferLeader,
  }
})
