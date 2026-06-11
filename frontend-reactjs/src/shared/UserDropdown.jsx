import { useState, useContext, useRef } from "react";
import { AuthContext } from "../contexts/AuthContext";
import { useNavigate } from "react-router-dom";
import useClickOutside from "../hooks/common/useClickOutside";
import { useRouterProfile } from "../hooks/common/useRouters";
function UserDropdown() {
  const [showDropdown, setShowDropdown] = useState(false);
  const { user, logout } = useContext(AuthContext);
  const navigate = useNavigate();
  const dropdownRef = useRef(null);

  useClickOutside(dropdownRef, () => {
    setShowDropdown(false);
  });

  const [loadingLogout, setLoadingLogout] = useState(false);

  const handleLogout = async () => {
    try {
      setLoadingLogout(true);
      await logout();
      navigate("/login", { replace: true });
    } catch (error) {
      console.error(error);
    } finally {
      setLoadingLogout(false);
      setShowDropdown(false);
    }
  };
  const { goToProfile } = useRouterProfile();

  const handleProfile = () => {
    setShowDropdown(false);
    goToProfile("/nckh-profile");
  };

  const handleSetting = () => {
    setShowDropdown(false);
    navigate("/settings");
  };

  const handleViewAllProducts = () => {
    setShowDropdown(false);
    navigate("/nckh-visitor");
  };

  return (
    <div className="relative" ref={dropdownRef}>
      <div
        onClick={() => setShowDropdown(!showDropdown)}
        className="w-16 h-16 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white text-2xl font-bold cursor-pointer hover:from-blue-600 hover:to-blue-700 transition"
      >
        {user?.name?.charAt(0) || "U"}
      </div>

      {showDropdown && (
        <div className="absolute right-full top-10 mr-2 w-60 bg-white rounded-lg shadow-xl border border-gray-200 z-20">
          {" "}
          <div className="p-3 border-b border-gray-100">
            <p className="text-sm font-semibold text-gray-900">
              {user?.name || "Người dùng"}
            </p>
            <p className="text-xs text-gray-500 mt-1">
              {user?.email || "Chưa có email"}
            </p>
          </div>
          <div className="p-2">
            <button
              onClick={handleProfile}
              className="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg"
            >
              <svg
                className="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                />
              </svg>
              Hồ sơ của tôi
            </button>

            <button
              onClick={handleSetting}
              className="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg"
            >
              <svg
                className="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                />
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
              Cài đặt
            </button>

            <button
              onClick={handleViewAllProducts}
              className="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg"
            >
              <svg
                className="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"
                />
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M8 13h8"
                />
              </svg>
              Xem sản phẩm toàn ngành
            </button>

            <hr className="my-2 border-gray-100" />

            <button
              onClick={handleLogout}
              disabled={loadingLogout}
              className="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg"
            >
              <svg
                className="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                />
              </svg>
              {loadingLogout ? "Đang đăng xuất..." : "Đăng xuất"}
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

export default UserDropdown;
