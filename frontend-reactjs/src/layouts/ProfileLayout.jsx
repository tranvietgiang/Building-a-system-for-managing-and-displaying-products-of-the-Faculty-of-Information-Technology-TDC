import React, { useState, useContext } from "react";
import { AuthContext } from "../contexts/AuthContext";
import useTitle from "../hooks/common/useTitle";
import { formatDate } from "../utils/formatDate";
import { toast } from "react-toastify";
import { ROLE } from "../utils/constants";
import BackButton from "../components/common/BackButton";
import { getMajorTheme } from "../utils/uploadProductScreen/uploadRegistry";
import { Icons } from "../components/common/Icon";
import useMajorName from "../hooks/common/useMajorName";
import { useProfileUpdate } from "../hooks/useProfile/useProfileUpdate";
const ProfileScreen = () => {
  useTitle("Hồ sơ cá nhân");
  const { user, setUser } = useContext(AuthContext);
  const [isEditing, setIsEditing] = useState(false);
  const { isLoading, updateProfile } = useProfileUpdate();
  const { majorName } = useMajorName(user?.major_id);
  // Lấy theme dựa trên major_id của user
  const theme = getMajorTheme(majorName);

  const [formData, setFormData] = useState({
    name: user?.name || "",
    email: user?.email || "",
    phone: user?.phone || "",
    address: user?.address || "",
    mssv: user?.mssv || "",
    major_name: user?.major_name || "",
    class_name: user?.class_name || "",
    bio: user?.bio || "",
  });

  // Reset form data về giá trị ban đầu của user
  const resetFormData = () => {
    setFormData({
      name: user?.name || "",
      email: user?.email || "",
      phone: user?.phone || "",
      address: user?.address || "",
      mssv: user?.mssv || "",
      major_name: user?.major_name || "",
      class_name: user?.class_name || "",
      bio: user?.bio || "",
    });
  };

  const handleEdit = () => {
    resetFormData();
    setIsEditing(true);
  };

  const handleCancel = () => {
    resetFormData();
    setIsEditing(false);
  };

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  const handleSave = async () => {
    try {
      // Loại bỏ những trường không được phép update
      const { major_name, mssv, class_name, ...updateData } = formData;

      const result = await updateProfile(updateData);

      if (result?.success) {
        setUser({ ...user, ...result.user });
        setIsEditing(false);
      }
    } catch (error) {
      console.error(error);
    }
  };

  const getRoleBadge = () => {
    switch (user?.role) {
      case ROLE.TEACHER:
        return {
          text: "Giảng viên",
          color: "blue",
          icon: <Icons.Teacher className="w-3 h-3" />,
        };
      case ROLE.STUDENT:
        return {
          text: "Sinh viên",
          color: "green",
          icon: <Icons.Student className="w-3 h-3" />,
        };
      default:
        return null;
    }
  };

  const roleBadge = getRoleBadge();

  const stats = [
    {
      label: "Sản phẩm đã duyệt",
      value: user?.approved_count || 0,
      color: "green",
      icon: <Icons.CheckCircle className="w-4 h-4" />,
    },
    {
      label: "Sản phẩm chờ duyệt",
      value: user?.pending_count || 0,
      color: "yellow",
      icon: <Icons.Clock className="w-4 h-4" />,
    },
    {
      label: "Sản phẩm từ chối",
      value: user?.rejected_count || 0,
      color: "red",
      icon: <Icons.XCircle className="w-4 h-4" />,
    },
    {
      label: "Tổng sản phẩm",
      value: user?.total_products || 0,
      color: "purple",
      icon: <Icons.Product className="w-4 h-4" />,
    },
  ];

  const InfoField = ({ label, value, icon }) => (
    <div className="flex flex-col sm:flex-row sm:items-center py-3 border-b border-gray-100 last:border-0">
      <div className="flex items-center gap-2 w-32">
        {icon && <span className="text-gray-400">{icon}</span>}
        <span className="text-sm font-medium text-gray-500">{label}</span>
      </div>
      <span className="text-sm text-gray-900 mt-1 sm:mt-0">{value || "—"}</span>
    </div>
  );

  // Giới hạn độ dài cho từng field
  const fieldLimits = {
    name: { min: 3, max: 100, required: true },
    email: { min: 5, max: 100, required: true },
    phone: { max: 20, required: false },
    address: { max: 200, required: false },
    bio: { max: 500, required: false },
    mssv: { max: 20, required: false },
    class_name: { max: 50, required: false },
  };

  const EditableField = ({ label, name, type = "text", placeholder, icon }) => {
    const limits = fieldLimits[name] || { max: 255, required: false };
    const currentLength = formData[name]?.length || 0;
    const { min, max, required } = limits;

    return (
      <div className="flex flex-col sm:flex-row sm:items-start py-3 border-b border-gray-100">
        <div className="flex items-center gap-2 w-32">
          {icon && <span className="text-gray-400">{icon}</span>}
          <label className="text-sm font-medium text-gray-500">
            {label}
            {required && <span className="text-red-500"> *</span>}
          </label>
        </div>
        <div className="flex-1 mt-1 sm:mt-0 w-full">
          {type === "textarea" ? (
            <>
              <textarea
                name={name}
                value={formData[name]}
                onChange={(e) => {
                  if (e.target.value.length <= max) {
                    handleChange(e);
                  }
                }}
                maxLength={max}
                rows={3}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                placeholder={placeholder}
              />
              <div className="flex justify-between items-center mt-1">
                {min && required && (
                  <span
                    className={`text-xs ${currentLength < min ? "text-red-500" : "text-green-500"}`}
                  >
                    {currentLength < min ? `Ít nhất ${min} ký tự` : "✓"}
                  </span>
                )}
                <span className="text-xs text-gray-400 ml-auto">
                  {currentLength}/{max}
                </span>
              </div>
            </>
          ) : (
            <>
              <input
                type={type}
                name={name}
                value={formData[name]}
                onChange={(e) => {
                  if (e.target.value.length <= max) {
                    handleChange(e);
                  }
                }}
                maxLength={max}
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                placeholder={placeholder}
              />
              <div className="flex justify-between items-center mt-1">
                {min && required && (
                  <span
                    className={`text-xs ${currentLength < min ? "text-red-500" : "text-green-500"}`}
                  >
                    {currentLength < min ? `Ít nhất ${min} ký tự` : "✓"}
                  </span>
                )}
                <span className="text-xs text-gray-400 ml-auto">
                  {currentLength}/{max}
                </span>
              </div>
            </>
          )}
        </div>
      </div>
    );
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
      <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Profile Card */}
        <BackButton />
        <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
          {/* Cover Image - Sử dụng gradient từ theme */}
          <div
            className={`h-32 bg-gradient-to-r ${theme?.headerGradient} relative`}
          >
            <div className="absolute -bottom-12 left-6 sm:left-8">
              <div className="w-20 h-20 sm:w-24 sm:h-24 bg-white rounded-2xl shadow-lg flex items-center justify-center p-1">
                <div
                  className={`w-full h-full bg-gradient-to-br ${theme?.headerGradient} rounded-xl flex items-center justify-center`}
                >
                  <span className="text-white text-2xl sm:text-3xl font-bold">
                    {user?.name?.charAt(0) || "U"}
                  </span>
                </div>
              </div>
            </div>
          </div>

          {/* Profile Info */}
          <div className="pt-14 sm:pt-16 pb-6 px-4 sm:px-8">
            <div className="flex flex-wrap justify-between items-start gap-4">
              <div className="flex-1">
                <div className="flex flex-wrap items-center gap-2 mb-2">
                  <h2 className="text-xl sm:text-2xl font-bold text-gray-900">
                    {user?.name}
                  </h2>
                  {roleBadge && (
                    <span
                      className={`px-2 py-0.5 rounded-full text-xs font-medium inline-flex items-center gap-1 bg-${roleBadge.color}-100 text-${roleBadge.color}-700`}
                    >
                      {roleBadge.icon}
                      {roleBadge.text}
                    </span>
                  )}
                </div>
                <p className="text-sm text-gray-500 flex items-center gap-1">
                  <Icons.Mail className="w-4 h-4" />
                  {user?.email}
                </p>
                {user?.role === ROLE.STUDENT && user?.mssv && (
                  <p className="text-xs text-gray-400 mt-1 flex items-center gap-1">
                    <Icons.StudentId className="w-3 h-3" />
                    MSSV: {user.mssv}
                  </p>
                )}
              </div>

              <div className="flex gap-2">
                {isEditing ? (
                  <>
                    <button
                      onClick={handleCancel}
                      disabled={isLoading}
                      className="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2 bg-gray-100 text-gray-700 hover:bg-gray-200"
                    >
                      <Icons.X className="w-4 h-4" />
                      Hủy
                    </button>
                    <button
                      onClick={handleSave}
                      disabled={isLoading}
                      className="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2 bg-green-600 text-white hover:bg-green-700"
                    >
                      {isLoading ? (
                        <>
                          <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                          Đang lưu...
                        </>
                      ) : (
                        <>
                          <Icons.Check className="w-4 h-4" />
                          Lưu thay đổi
                        </>
                      )}
                    </button>
                  </>
                ) : (
                  <button
                    onClick={handleEdit}
                    className="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center gap-2 bg-gray-100 text-gray-700 hover:bg-gray-200"
                  >
                    <Icons.Edit className="w-4 h-4" />
                    Chỉnh sửa
                  </button>
                )}
              </div>
            </div>
          </div>
        </div>

        {/* Thông tin chi tiết */}
        <div className="mt-6 bg-white rounded-2xl shadow-sm overflow-hidden">
          <div className="px-4 sm:px-8 py-4 border-b border-gray-100">
            <h3
              className={`text-lg font-semibold flex items-center gap-2 ${theme?.textColor}`}
            >
              <Icons.Info className="w-5 h-5" />
              Thông tin cá nhân
            </h3>
          </div>

          <div className="p-4 sm:p-8">
            {isEditing ? (
              <>
                <EditableField
                  label="Họ tên"
                  name="name"
                  icon={<Icons.User className="w-4 h-4" />}
                  placeholder="Nhập họ tên"
                />
                <EditableField
                  label="Email"
                  name="email"
                  type="email"
                  icon={<Icons.Mail className="w-4 h-4" />}
                  placeholder="Nhập email"
                />
                <EditableField
                  label="Số điện thoại"
                  name="phone"
                  icon={<Icons.Phone className="w-4 h-4" />}
                  placeholder="Nhập số điện thoại"
                />
                <EditableField
                  label="Địa chỉ"
                  name="address"
                  icon={<Icons.Location className="w-4 h-4" />}
                  placeholder="Nhập địa chỉ"
                />
                <EditableField
                  label="Giới thiệu"
                  name="bio"
                  type="textarea"
                  icon={<Icons.Bio className="w-4 h-4" />}
                  placeholder="Giới thiệu về bản thân..."
                />

                {user?.role === ROLE.STUDENT && (
                  <>
                    <EditableField
                      label="MSSV"
                      name="mssv"
                      icon={<Icons.StudentId className="w-4 h-4" />}
                      placeholder="Nhập MSSV"
                    />
                    <EditableField
                      label="Lớp"
                      name="class_name"
                      icon={<Icons.Class className="w-4 h-4" />}
                      placeholder="Nhập tên lớp"
                    />
                    <EditableField
                      label="Chuyên ngành"
                      name="major_name"
                      icon={<Icons.Major className="w-4 h-4" />}
                      placeholder="Nhập chuyên ngành"
                    />
                  </>
                )}

                {user?.role === ROLE.TEACHER && (
                  <EditableField
                    label="Chuyên ngành"
                    name="major_name"
                    icon={<Icons.Major className="w-4 h-4" />}
                    placeholder="Nhập chuyên ngành"
                  />
                )}
              </>
            ) : (
              <>
                <InfoField
                  label="Họ tên"
                  value={user?.name}
                  icon={<Icons.User className="w-4 h-4" />}
                />
                <InfoField
                  label="Email"
                  value={user?.email}
                  icon={<Icons.Mail className="w-4 h-4" />}
                />
                <InfoField
                  label="Số điện thoại"
                  value={user?.phone}
                  icon={<Icons.Phone className="w-4 h-4" />}
                />
                <InfoField
                  label="Địa chỉ"
                  value={user?.address}
                  icon={<Icons.Location className="w-4 h-4" />}
                />
                <InfoField
                  label="Giới thiệu"
                  value={user?.bio}
                  icon={<Icons.Bio className="w-4 h-4" />}
                />

                {user?.role === ROLE.STUDENT && (
                  <>
                    <InfoField
                      label="MSSV"
                      value={user?.mssv}
                      icon={<Icons.StudentId className="w-4 h-4" />}
                    />
                    <InfoField
                      label="Lớp"
                      value={user?.class_name}
                      icon={<Icons.Class className="w-4 h-4" />}
                    />
                    <InfoField
                      label="Chuyên ngành"
                      value={user?.major_name}
                      icon={<Icons.Major className="w-4 h-4" />}
                    />
                  </>
                )}

                {user?.role === ROLE.TEACHER && (
                  <InfoField
                    label="Chuyên ngành"
                    value={user?.major_name}
                    icon={<Icons.Major className="w-4 h-4" />}
                  />
                )}

                {user?.role === ROLE.ADMIN && (
                  <InfoField
                    label="Vai trò"
                    value="Quản trị viên hệ thống"
                    icon={<Icons.Admin className="w-4 h-4" />}
                  />
                )}
              </>
            )}
          </div>
        </div>

        {/* Thống kê - chỉ hiển thị cho Teacher và Student */}
        {(user?.role === ROLE.TEACHER || user?.role === ROLE.STUDENT) && (
          <div className="mt-6 bg-white rounded-2xl shadow-sm overflow-hidden">
            <div className="px-4 sm:px-8 py-4 border-b border-gray-100">
              <h3
                className={`text-lg font-semibold flex items-center gap-2 ${theme?.textColor}`}
              >
                <Icons.Chart className="w-5 h-5" />
                Thống kê sản phẩm
              </h3>
            </div>
            <div className="p-4 sm:p-8">
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                {stats.map((stat) => (
                  <div
                    key={stat.label}
                    className={`bg-gradient-to-br from-${stat.color}-50 to-${stat.color}-100 rounded-xl p-4`}
                  >
                    <div className="flex items-center gap-2 mb-2">
                      <span className={`text-${stat.color}-600`}>
                        {stat.icon}
                      </span>
                      <p
                        className={`text-xs font-medium text-${stat.color}-600`}
                      >
                        {stat.label}
                      </p>
                    </div>
                    <p
                      className={`text-2xl font-bold text-${stat.color}-700 mt-1`}
                    >
                      {stat.value}
                    </p>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}

        {/* Thông tin hệ thống */}
        <div className="mt-6 bg-white rounded-2xl shadow-sm overflow-hidden">
          <div className="px-4 sm:px-8 py-4 border-b border-gray-100">
            <h3
              className={`text-lg font-semibold flex items-center gap-2 ${theme?.textColor}`}
            >
              <Icons.System className="w-5 h-5" />
              Thông tin hệ thống
            </h3>
          </div>
          <div className="p-4 sm:p-8">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <InfoField
                label="Ngày tham gia"
                value={formatDate(user?.created_at)}
                icon={<Icons.Calendar className="w-4 h-4" />}
              />
              <InfoField
                label="Lần đăng nhập cuối"
                value={formatDate(user?.last_login)}
                icon={<Icons.Login className="w-4 h-4" />}
              />
              <InfoField
                label="Trạng thái"
                value={
                  <span className="inline-flex items-center gap-1">
                    <span className="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    <span className="text-green-700">Hoạt động</span>
                  </span>
                }
                icon={<Icons.Status className="w-4 h-4" />}
              />
              <InfoField
                label="ID tài khoản"
                value={user?.id}
                icon={<Icons.Id className="w-4 h-4" />}
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ProfileScreen;
