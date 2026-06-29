// src/api/axiosClient.js
import axios from 'axios'

const axiosClient = axios.create({
  baseURL: 'https://datn-production-d208.up.railway.app/api' || 'http://127.0.0.1:8080/api',
  headers: {
    // 'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

//ĐỂ LOGOUT HOẠT ĐỘNG
axiosClient.interceptors.request.use(
  (config) => {
    // Lấy token từ LocalStorage
    const token = localStorage.getItem('access_token')
    // Nếu có token, gắn vào Header theo chuẩn Bearer
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  },
)

export default axiosClient
