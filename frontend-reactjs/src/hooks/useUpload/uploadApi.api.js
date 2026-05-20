import axios from "axios";
import { getToken } from "../../utils/storage"; // lấy token từ sessionStorage

const API_URL = import.meta.env.VITE_API_URL;
const API_VERSION = "/v1";
const API_BASE_URL = `${API_URL?.replace(/\/$/, "")}${API_VERSION}`;

export const uploadApi = {
  uploadProduct: async (formData) => {
    try {
      const token = getToken();

      const res = await axios.post(`${API_BASE_URL}/upload`, formData, {
        headers: {
          "Content-Type": "multipart/form-data",
          Accept: "application/json",
          Authorization: token ? `Bearer ${token}` : "",
        },
      });

      return {
        success: true,
        data: res.data,
      };
    } catch (error) {
      return {
        success: false,
        error: error.response?.data || {
          message: "Lỗi server",
        },
      };
    }
  },
};
