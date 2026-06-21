import axios from "axios";
import {
  getToken,
  setToken,
  getRefreshToken,
  setRefreshToken,
  clearAuth,
} from "../utils/storage";

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

let refreshRequest = null;

const refreshAccessToken = async () => {
  const refreshToken = getRefreshToken();

  if (!refreshToken) throw new Error("Missing refresh token");

  const response = await axios.post(`${API_BASE_URL}/refresh`, {
    refresh_token: refreshToken,
  }, {
    headers: { Accept: "application/json" },
  });

  setToken(response.data.access_token);
  setRefreshToken(response.data.refresh_token);

  return response.data.access_token;
};

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
  async (error) => {
    // Handle unauthorized (401) - token expired or invalid
    const originalRequest = error.config;
    const isAuthRequest = ["/login", "/refresh", "/logout"].some((path) =>
      originalRequest?.url?.includes(path),
    );

    if (
      error.response?.status === 401 &&
      !isAuthRequest &&
      !originalRequest?._retry &&
      getRefreshToken()
    ) {
      originalRequest._retry = true;

      try {
        refreshRequest ??= refreshAccessToken().finally(() => {
          refreshRequest = null;
        });
        const accessToken = await refreshRequest;
        originalRequest.headers.Authorization = `Bearer ${accessToken}`;
        return axiosClient(originalRequest);
      } catch {
        clearAuth();
        window.location.href = "/dang-nhap";
      }
    } else if (error.response?.status === 401 && !isAuthRequest) {
      clearAuth();
      window.location.href = "/dang-nhap";
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
