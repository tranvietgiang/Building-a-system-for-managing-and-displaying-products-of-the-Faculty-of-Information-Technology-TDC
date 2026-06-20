// useLoginLogic.js
import { useState, useEffect, useContext } from "react";
import { AuthContext } from "../../contexts/AuthContext";
import { ROLE } from "../../utils/constants";
import { toast } from "react-toastify";
import { validateLogin } from "./validateLogin";

export const useLoginLogic = () => {
  const { login } = useContext(AuthContext);

  const [showPassword, setShowPassword] = useState(false);
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [remember, setRemember] = useState(false);
  const [loading, setLoading] = useState(false);
  const [animated, setAnimated] = useState(false);
  const [userRole, setUserRole] = useState("student");

  useEffect(() => {
    document.title = "Đăng nhập - TDC";
    setAnimated(true);

    const savedUser = localStorage.getItem("savedUser");
    if (savedUser) {
      setUsername(savedUser);
      setRemember(true);
    }
  }, []);

  const handleLogin = async (e) => {
    e.preventDefault();
    setLoading(true);

    const errorMsg = validateLogin(username, password);
    if (errorMsg) {
      toast.dismiss();
      toast.error(errorMsg, { autoClose: 2000 });
      setLoading(false);
      return;
    }

    try {
      const res = await login({ username, password, user_role: userRole });

      if (remember) {
        localStorage.setItem("savedUser", username);
      } else {
        localStorage.removeItem("savedUser");
      }

      toast.dismiss();

      const actualRole = res.user.role;

      // Điều hướng theo role thực tế
      if (actualRole === ROLE.STUDENT) {
        window.location.href = "/sinh-vien";
      } else if (actualRole === ROLE.TEACHER) {
        window.location.href = "/giang-vien";
      } else if (actualRole === ROLE.ADMIN) {
        window.location.href = "/quan-tri";
      }
    } catch (error) {
      toast.dismiss();

      if (error.response?.status === 429) {
        // Account lockout - show the server message
        toast.error(
          error.response.data.message ||
            "Quá nhiều lần đăng nhập sai. Vui lòng thử lại sau.",
          { autoClose: 5000 },
        );
      } else if (error.response?.status === 422) {
        // Role mismatch error
        toast.error(
          error.response.data.message ||
            "Tài khoản không phù hợp với vai trò được chọn!",
          { autoClose: 3000 },
        );
      } else if (error.response?.data?.message) {
        // Any other error from server with message
        toast.error(error.response.data.message, { autoClose: 3000 });
      } else if (error.response?.status === 401) {
        // Unauthorized
        toast.error("Sai tài khoản hoặc mật khẩu!", { autoClose: 3000 });
      } else if (error.response) {
        // Other server errors
        toast.error("Lỗi từ máy chủ. Vui lòng thử lại sau.", {
          autoClose: 3000,
        });
      } else if (error.request) {
        // Request made but no response
        toast.error(
          "Không thể kết nối tới máy chủ. Kiểm tra kết nối internet của bạn.",
          { autoClose: 3000 },
        );
      } else {
        // Other errors
        toast.error(error.message || "Đã xảy ra lỗi. Vui lòng thử lại.", {
          autoClose: 3000,
        });
      }
    } finally {
      setLoading(false);
    }
  };

  return {
    // State
    showPassword,
    username,
    password,
    remember,
    loading,
    animated,
    userRole,
    // Setters
    setShowPassword,
    setUsername,
    setPassword,
    setRemember,
    setUserRole,
    // Handlers
    handleLogin,
  };
};
