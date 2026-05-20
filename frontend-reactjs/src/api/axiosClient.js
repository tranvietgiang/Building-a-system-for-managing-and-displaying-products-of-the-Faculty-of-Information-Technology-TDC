import axios from "axios";
import { getToken, clearAuth } from "../utils/storage";

const API_VERSION = "/v1";
const API_BASE_URL = `${import.meta.env.VITE_API_URL?.replace(/\/$/, "")}${API_VERSION}`;

// Hàm tạo axios client
const axiosClient = axios.create({
  baseURL: API_BASE_URL,
  withCredentials: true,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

// Request interceptor
axiosClient.interceptors.request.use((config) => {
  const token = getToken();

  if (token) {
    // Nếu token có → dùng Bearer token (API token)
    config.headers.Authorization = `Bearer ${token}`;
    config.withCredentials = false; // không dùng cookie
  } else {
    // Nếu không token → SPA cookie/session
    config.withCredentials = true;
  }

  return config;
});

// Response interceptor
axiosClient.interceptors.response.use(
  (res) => res.data,
  (error) => {
    // Handle unauthorized (401) - token expired or invalid
    if (error.response?.status === 401) {
      clearAuth();
      window.location.href = "/login";
    }

    // Handle forbidden (403) - insufficient permissions
    if (error.response?.status === 403) {
      console.error("Access Forbidden:", error.response.data?.message);
    }

    // Handle too many requests (429) - rate limited
    if (error.response?.status === 429) {
      console.warn("Rate limited:", error.response.data?.message);
    }

    return Promise.reject(error);
  },
);

export default axiosClient;
