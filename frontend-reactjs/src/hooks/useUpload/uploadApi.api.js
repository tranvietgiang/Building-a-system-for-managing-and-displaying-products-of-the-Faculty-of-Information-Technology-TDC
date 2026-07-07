import axiosClient from "../../api/axiosClient";

export const UPLOAD_PRODUCT_TIMEOUT_MS = 300_000;

export const uploadApi = {
  uploadProduct: async (formData) => {
    try {
      const response = await axiosClient.post("/upload", formData, {
        timeout: UPLOAD_PRODUCT_TIMEOUT_MS,
        headers: {
          "Content-Type": "multipart/form-data",
          Accept: "application/json",
        },
      });

      const data = response?.data || response;

      return {
        success: true,
        ...data,
        data,
        message: data?.message,
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
            "Đăng sản phẩm mất quá nhiều thời gian (trên 5 phút). Vui lòng kiểm tra kết nối hoặc giảm dung lượng tệp rồi thử lại.",
        };
      }

      const responseError = error.response?.data;

      return {
        success: false,
        ...(responseError || {}),
        error: responseError || error,
        message: responseError?.message || "Lỗi server",
      };
    }
  },
};
