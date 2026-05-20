import axiosClient from "./axiosClient";

export const userAPI = {
  // Lấy profile hiện tại
  getProfile: async () => {
    try {
      const response = await axiosClient.get("/profile");
      return response.data;
    } catch (error) {
      throw (
        error.response?.data || { success: false, message: "Lỗi lấy profile" }
      );
    }
  },

  // Lấy thông tin user theo ID
  getUserById: async (userId) => {
    try {
      const response = await axiosClient.get(`/user/${userId}`);
      return response.data;
    } catch (error) {
      throw (
        error.response?.data || {
          success: false,
          message: "Lỗi lấy thông tin user",
        }
      );
    }
  },

  // Cập nhật profile
  updateProfile: async (data) => {
    try {
      const response = await axiosClient.put("/profile", data);
      return response.data;
    } catch (error) {
      throw (
        error.response?.data || {
          success: false,
          message: "Lỗi cập nhật profile",
        }
      );
    }
  },

  // Đổi mật khẩu
  updatePassword: async (data) => {
    try {
      const response = await axiosClient.post("/profile/password", data);
      return response.data;
    } catch (error) {
      throw (
        error.response?.data || { success: false, message: "Lỗi đổi mật khẩu" }
      );
    }
  },

  // Lấy thống kê (cho student)
  getStatistics: async () => {
    try {
      const response = await axiosClient.get("/profile/statistics");
      return response.data;
    } catch (error) {
      throw (
        error.response?.data || { success: false, message: "Lỗi lấy thống kê" }
      );
    }
  },

  // Tìm kiếm users
  searchUsers: async (query, role = null, majorId = null) => {
    try {
      const params = new URLSearchParams();
      if (query) params.append("q", query);
      if (role) params.append("role", role);
      if (majorId) params.append("major_id", majorId);

      const response = await axiosClient.get(
        `/users/search?${params.toString()}`,
      );
      return response.data;
    } catch (error) {
      throw (
        error.response?.data || {
          success: false,
          message: "Lỗi tìm kiếm users",
        }
      );
    }
  },
};
