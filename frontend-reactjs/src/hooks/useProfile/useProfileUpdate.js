import { useState } from "react";
import { userAPI } from "../../api/userAPI";
import { toast } from "react-toastify";

export const useProfileUpdate = () => {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);

  const updateProfile = async (profileData) => {
    setIsLoading(true);
    setError(null);

    try {
      // Validate required fields
      if (!profileData.name || profileData.name.trim().length < 3) {
        throw new Error("Tên phải ít nhất 3 ký tự");
      }

      if (!profileData.email) {
        throw new Error("Email là bắt buộc");
      }

      const result = await userAPI.updateProfile(profileData);

      if (result.success) {
        toast.success(result.message || "Cập nhật hồ sơ thành công!");
        return result;
      } else {
        throw new Error(result.message || "Cập nhật thất bại");
      }
    } catch (err) {
      const errorMessage = err.message || "Có lỗi xảy ra khi cập nhật hồ sơ";
      setError(errorMessage);
      toast.error(errorMessage);
      throw err;
    } finally {
      setIsLoading(false);
    }
  };

  const updatePassword = async (passwordData) => {
    setIsLoading(true);
    setError(null);

    try {
      if (!passwordData.current_password) {
        throw new Error("Vui lòng nhập mật khẩu hiện tại");
      }

      if (!passwordData.new_password || passwordData.new_password.length < 6) {
        throw new Error("Mật khẩu mới phải ít nhất 6 ký tự");
      }

      if (passwordData.new_password !== passwordData.password_confirmation) {
        throw new Error("Xác nhận mật khẩu không khớp");
      }

      const result = await userAPI.updatePassword(passwordData);

      if (result.success) {
        toast.success(result.message || "Cập nhật mật khẩu thành công!");
        return result;
      } else {
        throw new Error(result.message || "Cập nhật mật khẩu thất bại");
      }
    } catch (err) {
      const errorMessage = err.message || "Có lỗi xảy ra khi cập nhật mật khẩu";
      setError(errorMessage);
      toast.error(errorMessage);
      throw err;
    } finally {
      setIsLoading(false);
    }
  };

  return {
    isLoading,
    error,
    updateProfile,
    updatePassword,
  };
};
