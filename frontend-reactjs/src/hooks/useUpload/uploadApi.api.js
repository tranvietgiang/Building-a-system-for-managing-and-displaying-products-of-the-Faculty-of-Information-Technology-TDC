import axios from "axios";
import { getToken } from "../../utils/storage"; // lấy token từ sessionStorage

const API_URL = import.meta.env.VITE_API_URL;
const API_VERSION = "/v1";
const API_BASE_URL = `${API_URL?.replace(/\/$/, "")}${API_VERSION}`;
export const UPLOAD_PRODUCT_TIMEOUT_MS = 120_000;

export const uploadApi = {
  uploadProduct: async (formData) => {
    try {
      const token = getToken();

      const res = await axios.post(`${API_BASE_URL}/upload`, formData, {
        timeout: UPLOAD_PRODUCT_TIMEOUT_MS,
        headers: {
          "Content-Type": "multipart/form-data",
          Accept: "application/json",
          Authorization: token ? `Bearer ${token}` : "",
        },
      });

      return {
        success: true,
        data: res.data,
        message: res.data?.message,
      };
    } catch (error) {
      const isTimeout =
        error.code === "ECONNABORTED" ||
        error.code === "ETIMEDOUT" ||
        /timeout/i.test(error.message || "");

      if (isTimeout) {
        return {
          success: false,
          isTimeout: true,
          message:
            "Đăng sản phẩm mất quá nhiều thời gian (trên 2 phút). Vui lòng kiểm tra kết nối hoặc giảm dung lượng tệp rồi thử lại.",
        };
      }

      const responseError = error.response?.data;

      return {
        success: false,
        error: responseError || error,
        message: responseError?.message || "Lỗi server",
      };
    }
  },
};
