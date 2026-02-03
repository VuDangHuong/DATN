import axiosClient from './axiosClient'
export const authApi = {
  login(payload) {
    return axiosClient.post('auth/login', payload)
  },

  logout() {
    return axiosClient.post('auth/logout')
  },

  changePassword(data) {
    return axiosClient.post('auth/change-password', data)
  },
  updateAvatar(formData) {
    return axiosClient.post('auth/update-avatar', formData)
  },
  getMe() {
    return axiosClient.get('auth/me')
  },
  updateProfile(data) {
    return axiosClient.post('auth/update-profile', data)
  },
  forgotPassword(email) {
    return axiosClient.post('auth/forgot-password', { email })
  },
  resetPassword(data) {
    return axiosClient.post('auth/reset-password', data)
  },
}
