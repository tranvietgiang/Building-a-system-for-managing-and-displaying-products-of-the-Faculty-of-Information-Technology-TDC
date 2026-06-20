import { useContext, useEffect, useState } from "react";
import { AuthContext } from "../contexts/AuthContext";
import useTitle from "../hooks/common/useTitle";
import { ROLE } from "../utils/constants";
import BackButton from "../components/common/BackButton";
import { getMajorTheme } from "../utils/uploadProductScreen/uploadRegistry";
import { Icons } from "../components/common/Icon";
import useMajorName from "../hooks/common/useMajorName";
import { useProfileUpdate } from "../hooks/useProfile/useProfileUpdate";
import { userAPI } from "../api/userAPI";

const statStyles = {
  green: {
    card: "bg-gradient-to-br from-green-50 to-green-100",
    text: "text-green-700",
    icon: "text-green-600",
  },
  yellow: {
    card: "bg-gradient-to-br from-yellow-50 to-yellow-100",
    text: "text-yellow-700",
    icon: "text-yellow-600",
  },
  red: {
    card: "bg-gradient-to-br from-red-50 to-red-100",
    text: "text-red-700",
    icon: "text-red-600",
  },
  purple: {
    card: "bg-gradient-to-br from-purple-50 to-purple-100",
    text: "text-purple-700",
    icon: "text-purple-600",
  },
};

const ProfileScreen = () => {
  useTitle("Hồ sơ cá nhân");
  const { user } = useContext(AuthContext);
  const { isLoading, updatePassword } = useProfileUpdate();
  const { majorName } = useMajorName(user?.major_id);
  const theme = getMajorTheme(majorName);
  const [statsData, setStatsData] = useState(null);
  const [statsLoading, setStatsLoading] = useState(false);
  const [statsError, setStatsError] = useState("");
  const [passwordData, setPasswordData] = useState({
    current_password: "",
    new_password: "",
    password_confirmation: "",
  });

  useEffect(() => {
    if (![ROLE.STUDENT, ROLE.TEACHER].includes(user?.role)) {
      setStatsData(null);
      setStatsError("");
      return;
    }

    setStatsLoading(true);
    setStatsError("");

    userAPI
      .getStatistics()
      .then((res) => setStatsData(res.statistics || null))
      .catch((error) => {
        setStatsData(null);
        setStatsError(error?.message || "Không tải được thống kê sản phẩm");
      })
      .finally(() => setStatsLoading(false));
  }, [user?.role, user?.user_id]);

  const handlePasswordChange = (event) => {
    setPasswordData((prev) => ({
      ...prev,
      [event.target.name]: event.target.value,
    }));
  };

  const handlePasswordSave = async (event) => {
    event.preventDefault();

    try {
      const result = await updatePassword(passwordData);

      if (result?.success) {
        setPasswordData({
          current_password: "",
          new_password: "",
          password_confirmation: "",
        });
      }
    } catch (error) {
      console.error(error);
    }
  };

  const roleBadge =
    user?.role === ROLE.TEACHER
      ? {
          text: "Giảng viên",
          color: "bg-blue-100 text-blue-700",
          icon: <Icons.Teacher className="h-3 w-3" />,
        }
      : user?.role === ROLE.STUDENT
        ? {
            text: "Sinh viên",
            color: "bg-green-100 text-green-700",
            icon: <Icons.Student className="h-3 w-3" />,
          }
        : null;

  const stats = [
    {
      label: "Sản phẩm đã duyệt",
      value: statsData?.approved_products || user?.approved_count || 0,
      color: "green",
      icon: <Icons.CheckCircle className="h-4 w-4" />,
    },
    {
      label: "Sản phẩm chờ duyệt",
      value: statsData?.pending_products || user?.pending_count || 0,
      color: "yellow",
      icon: <Icons.Clock className="h-4 w-4" />,
    },
    {
      label: "Sản phẩm từ chối",
      value: statsData?.rejected_products || user?.rejected_count || 0,
      color: "red",
      icon: <Icons.XCircle className="h-4 w-4" />,
    },
    {
      label: "Tổng sản phẩm",
      value: statsData?.total_products || user?.total_products || 0,
      color: "purple",
      icon: <Icons.Product className="h-4 w-4" />,
    },
  ];

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
      <div className="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <BackButton />

        <div className="overflow-hidden rounded-2xl bg-white shadow-sm">
          <div
            className={`relative h-32 bg-gradient-to-r ${theme?.headerGradient}`}
          >
            <div className="absolute -bottom-12 left-6 sm:left-8">
              <div className="flex h-20 w-20 items-center justify-center rounded-2xl bg-white p-1 shadow-lg sm:h-24 sm:w-24">
                <div
                  className={`flex h-full w-full items-center justify-center rounded-xl bg-gradient-to-br ${theme?.headerGradient}`}
                >
                  <span className="text-2xl font-bold text-white sm:text-3xl">
                    {user?.name?.charAt(0) || "U"}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div className="px-4 pb-6 pt-14 sm:px-8 sm:pt-16">
            <div className="flex flex-wrap items-center gap-2">
              <h2 className="text-xl font-bold text-gray-900 sm:text-2xl">
                {user?.name}
              </h2>
              {roleBadge && (
                <span
                  className={`inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ${roleBadge.color}`}
                >
                  {roleBadge.icon}
                  {roleBadge.text}
                </span>
              )}
            </div>
            <p className="mt-2 flex items-center gap-1 text-sm text-gray-500">
              <Icons.Mail className="h-4 w-4" />
              {user?.email}
            </p>
          </div>
        </div>

        <form
          onSubmit={handlePasswordSave}
          className="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm"
        >
          <div className="border-b border-gray-100 px-4 py-4 sm:px-8">
            <h3
              className={`flex items-center gap-2 text-lg font-semibold ${theme?.textColor}`}
            >
              <Icons.Id className="h-5 w-5" />
              Đổi mật khẩu
            </h3>
          </div>

          <div className="p-4 sm:p-8">
            <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
              <label className="block">
                <span className="text-sm font-medium text-gray-600">
                  Mật khẩu hiện tại
                </span>
                <input
                  type="password"
                  name="current_password"
                  value={passwordData.current_password}
                  onChange={handlePasswordChange}
                  autoComplete="current-password"
                  className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-blue-500"
                  placeholder="Nhập mật khẩu hiện tại"
                />
              </label>

              <label className="block">
                <span className="text-sm font-medium text-gray-600">
                  Mật khẩu mới
                </span>
                <input
                  type="password"
                  name="new_password"
                  value={passwordData.new_password}
                  onChange={handlePasswordChange}
                  autoComplete="new-password"
                  className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-blue-500"
                  placeholder="Ít nhất 6 ký tự"
                />
              </label>

              <label className="block">
                <span className="text-sm font-medium text-gray-600">
                  Xác nhận mật khẩu
                </span>
                <input
                  type="password"
                  name="password_confirmation"
                  value={passwordData.password_confirmation}
                  onChange={handlePasswordChange}
                  autoComplete="new-password"
                  className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-transparent focus:ring-2 focus:ring-blue-500"
                  placeholder="Nhập lại mật khẩu mới"
                />
              </label>
            </div>

            <div className="mt-4 flex justify-end">
              <button
                type="submit"
                disabled={isLoading}
                className="flex items-center gap-2 rounded-lg bg-[#003087] px-4 py-2 text-sm font-medium text-white transition-all duration-200 hover:bg-[#00266b] disabled:cursor-not-allowed disabled:opacity-60"
              >
                {isLoading ? (
                  <>
                    <div className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                    Đang cập nhật...
                  </>
                ) : (
                  <>
                    <Icons.Check className="h-4 w-4" />
                    Cập nhật mật khẩu
                  </>
                )}
              </button>
            </div>
          </div>
        </form>

        {(user?.role === ROLE.TEACHER || user?.role === ROLE.STUDENT) && (
          <div className="mt-6 overflow-hidden rounded-2xl bg-white shadow-sm">
            <div className="border-b border-gray-100 px-4 py-4 sm:px-8">
              <h3
                className={`flex items-center gap-2 text-lg font-semibold ${theme?.textColor}`}
              >
                <Icons.Chart className="h-5 w-5" />
                Thống kê sản phẩm
              </h3>
            </div>

            <div className="p-4 sm:p-8">
              {statsError && (
                <div className="mb-4 rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700">
                  {statsError}
                </div>
              )}

              <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                {stats.map((stat) => {
                  const style = statStyles[stat.color];

                  return (
                    <div
                      key={stat.label}
                      className={`${style.card} rounded-xl p-4`}
                    >
                      <div className="mb-2 flex items-center gap-2">
                        <span className={style.icon}>{stat.icon}</span>
                        <p className={`text-xs font-medium ${style.icon}`}>
                          {stat.label}
                        </p>
                      </div>
                      <p className={`mt-1 text-2xl font-bold ${style.text}`}>
                        {statsLoading ? "..." : stat.value}
                      </p>
                    </div>
                  );
                })}
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default ProfileScreen;
