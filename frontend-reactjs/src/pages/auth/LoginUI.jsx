// LoginUI.jsx
import { Link } from "react-router-dom";
import logoTdc from "../../assets/logo-tdc-orginal.webp";
import {
  Eye,
  EyeOff,
  Loader2,
  GraduationCap,
  Users,
  Shield,
  Sparkles,
  Cpu,
  Palette,
  Server,
  BookOpen,
  Award,
  Zap,
} from "lucide-react";
const programs = [
  {
    name: "Công nghệ thông tin",
    icon: Cpu,
    description: "Phát triển phần mềm, AI, Data Science",
  },
  {
    name: "Trí tuệ nhân tạo",
    icon: Sparkles,
    description: "Machine Learning, Deep Learning, Robotics",
  },
  {
    name: "Thiết kế đồ họa",
    icon: Palette,
    description: "UI/UX, Motion Graphics, Branding",
  },
  {
    name: "Mạng máy tính",
    icon: Server,
    description: "Cybersecurity, Cloud, IoT Infrastructure",
  },
];

const stats = [
  { value: "2026", label: "Năm phát triển website", icon: Award },
  { value: "4", label: "Ngành đào tạo chất lượng cao", icon: BookOpen },
];

export const LoginUI = ({
  animated,
  userRole,
  setUserRole,
  username,
  setUsername,
  password,
  setPassword,
  showPassword,
  setShowPassword,
  remember,
  setRemember,
  loading,
  handleLogin,
  navigate,
}) => {
  return (
    <div className="min-h-screen bg-gradient-to-br from-[#003087]/5 via-white to-[#C8102E]/5 font-['Inter'] overflow-hidden">
      <div className="min-h-screen flex flex-col md:flex-row">
        {/* LEFT PANEL */}
        <div className="relative md:w-1/2 bg-[#003087] overflow-hidden">
          {/* Decorative elements */}
          <div className="absolute inset-0 opacity-5">
            <div className="absolute top-20 left-10 w-72 h-72 bg-white rounded-full blur-3xl"></div>
            <div className="absolute bottom-20 right-10 w-96 h-96 bg-[#C8102E] rounded-full blur-3xl"></div>
            <svg
              className="absolute inset-0 w-full h-full"
              xmlns="http://www.w3.org/2000/svg"
            >
              <defs>
                <pattern
                  id="grid"
                  width="40"
                  height="40"
                  patternUnits="userSpaceOnUse"
                >
                  <path
                    d="M 40 0 L 0 0 0 40"
                    fill="none"
                    stroke="rgba(255,255,255,0.03)"
                    strokeWidth="1"
                  />
                </pattern>
              </defs>
              <rect width="100%" height="100%" fill="url(#grid)" />
            </svg>
          </div>

          <div
            className={`relative z-10 p-8 md:p-12 lg:p-16 transition-all duration-1000 transform ${
              animated
                ? "translate-y-0 opacity-100"
                : "translate-y-10 opacity-0"
            }`}
          >
            {/* Logo */}
            <div className="mb-12 md:mb-16">
              <Link
                to="/nckh-visitor"
                className="group inline-flex items-center gap-3"
              >
                <div className="bg-white/10 backdrop-blur-sm w-12 h-12 rounded-xl flex items-center justify-center border border-white/20 group-hover:scale-110 transition-transform">
                  <img src={logoTdc} alt="TDC" className="h-8 w-auto" />
                </div>
                <div>
                  <p className="text-white font-semibold text-sm">
                    Trường Cao Đẳng
                  </p>
                  <p className="text-white/80 text-xs">Công Nghệ Thủ Đức</p>
                </div>
              </Link>
            </div>

            {/* Main title */}
            <div className="mb-8">
              <div className="inline-flex items-center gap-2 bg-white/10 backdrop-blur-sm rounded-full px-4 py-1.5 mb-4">
                <Zap size={14} className="text-[#C8102E]" />
                <span className="text-white/90 text-xs font-medium">
                  ĐÀO TẠO CHẤT LƯỢNG CAO
                </span>
              </div>
              <h1 className="text-3xl md:text-4xl lg:text-5xl font-bold text-white leading-tight mb-4">
                Nền tảng quản lý sản phẩm sinh viên
              </h1>
              <p className="text-white/80 text-base md:text-lg leading-relaxed">
                Nơi trưng bày, đánh giá và quản lý đồ án của 4 ngành đào tạo
                chất lượng cao.
              </p>
            </div>

            {/* Programs grid */}
            <div className="mb-12">
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {programs.map((program, idx) => {
                  const Icon = program.icon;
                  return (
                    <div
                      key={idx}
                      className="group bg-white/5 backdrop-blur-sm rounded-2xl p-4 border border-white/10 hover:bg-white/10 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl cursor-pointer"
                    >
                      <div className="flex items-start gap-3">
                        <div className="bg-white/10 p-2.5 rounded-xl group-hover:bg-white/20 transition-colors">
                          <Icon className="text-white" size={20} />
                        </div>
                        <div>
                          <h3 className="text-white font-semibold text-sm mb-1">
                            {program.name}
                          </h3>
                          <p className="text-white/60 text-xs">
                            {program.description}
                          </p>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Statistics */}
            <div className="flex flex-wrap gap-6 md:gap-8 pt-6 border-t border-white/10">
              {stats.map((stat, idx) => {
                const Icon = stat.icon;
                return (
                  <div key={idx} className="flex items-center gap-3 group">
                    <div className="bg-white/10 rounded-full p-2 group-hover:bg-[#C8102E] transition-colors duration-300">
                      <Icon
                        className="text-[#C8102E] group-hover:text-white transition-colors"
                        size={20}
                      />
                    </div>
                    <div>
                      <div className="text-white font-bold text-2xl">
                        {stat.value}
                      </div>
                      <div className="text-white/60 text-xs">{stat.label}</div>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        </div>

        {/* RIGHT PANEL - Login Form */}
        <div className="relative md:w-1/2 bg-white flex items-center justify-center p-6 md:p-8 lg:p-12">
          <div
            className={`w-full max-w-md transition-all duration-1000 transform ${
              animated
                ? "translate-y-0 opacity-100"
                : "translate-y-10 opacity-0"
            }`}
          >
            {/* Header */}
            <div className="text-center mb-8">
              <div className="inline-flex items-center justify-center w-16 h-16 bg-[#003087] rounded-2xl shadow-xl mb-4">
                <Shield className="text-white" size={32} />
              </div>
              <h2 className="text-3xl md:text-4xl font-bold text-[#003087] mb-2">
                Đăng nhập hệ thống
              </h2>
              <p className="text-gray-500 text-sm">
                HỆ THỐNG QUẢN LÝ VÀ TRƯNG BÀY SẢN PHẨM
              </p>
            </div>

            {/* Role Selector */}
            <div className="mb-8">
              <div className="bg-gray-100 rounded-xl p-1 flex gap-1">
                <button
                  type="button"
                  onClick={() => setUserRole("student")}
                  className={`flex-1 py-3 px-4 rounded-lg font-semibold text-sm transition-all duration-300 flex items-center justify-center gap-2 ${
                    userRole === "student"
                      ? "bg-[#003087] text-white shadow-lg"
                      : "text-gray-600 hover:text-[#003087]"
                  }`}
                >
                  <GraduationCap size={18} />
                  <span>Sinh viên</span>
                </button>
                <button
                  type="button"
                  onClick={() => setUserRole("lecturer")}
                  className={`flex-1 py-3 px-4 rounded-lg font-semibold text-sm transition-all duration-300 flex items-center justify-center gap-2 ${
                    userRole === "lecturer"
                      ? "bg-[#003087] text-white shadow-lg"
                      : "text-gray-600 hover:text-[#003087]"
                  }`}
                >
                  <Users size={18} />
                  <span>Giảng viên</span>
                </button>
              </div>
            </div>

            {/* Login Form */}
            <form onSubmit={handleLogin} className="space-y-5">
              {/* Username Field */}
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">
                  {userRole === "student"
                    ? "Mã số sinh viên (MSSV)"
                    : "Mã số giảng viên (MSGV)"}
                </label>
                <div className="relative group">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg
                      className="w-5 h-5 text-gray-400 group-focus-within:text-[#003087] transition-colors"
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
                  </div>
                  <input
                    type="text"
                    placeholder={
                      userRole === "student"
                        ? "Nhập mã số sinh viên"
                        : "Nhập mã số giảng viên"
                    }
                    value={username}
                    onChange={(e) => setUsername(e.target.value)}
                    className="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl focus:outline-none focus:border-[#003087] focus:ring-2 focus:ring-[#003087]/20 transition-all duration-200"
                    maxLength={100}
                  />
                </div>
                <p className="text-xs text-gray-400 mt-1">
                  {userRole === "student" ? "VD: 20240001" : "VD: GV001"}
                </p>
              </div>

              {/* Password Field */}
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-2">
                  Mật khẩu
                </label>
                <div className="relative group">
                  <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg
                      className="w-5 h-5 text-gray-400 group-focus-within:text-[#003087] transition-colors"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                      />
                    </svg>
                  </div>
                  <input
                    type={showPassword ? "text" : "password"}
                    placeholder="Nhập mật khẩu"
                    value={password}
                    maxLength={100}
                    onChange={(e) => setPassword(e.target.value)}
                    className="w-full pl-10 pr-10 py-3 border border-gray-300 rounded-xl focus:outline-none focus:border-[#003087] focus:ring-2 focus:ring-[#003087]/20 transition-all duration-200"
                  />
                  <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#003087] transition-colors"
                  >
                    {showPassword ? <EyeOff size={20} /> : <Eye size={20} />}
                  </button>
                </div>
              </div>

              {/* Remember me & Forgot password */}
              <div className="flex items-center justify-between py-2">
                <label className="flex items-center gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    checked={remember}
                    onChange={(e) => setRemember(e.target.checked)}
                    className="w-4 h-4 text-[#003087] border border-gray-300 rounded focus:ring-1 focus:ring-[#003087] cursor-pointer"
                  />
                  <span className="text-sm text-gray-600">
                    Ghi nhớ đăng nhập
                  </span>
                </label>
                <a
                  onClick={() => navigate("/forgot")}
                  href=""
                  className="text-sm text-[#C8102E] hover:underline"
                >
                  Quên mật khẩu?
                </a>
              </div>

              {/* Login Button */}
              <button
                type="submit"
                disabled={loading}
                className="w-full bg-[#003087] text-white py-3.5 rounded-xl font-bold text-base transition-all duration-300 hover:bg-[#003087] hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                {loading ? (
                  <>
                    <Loader2 className="animate-spin" size={20} />
                    Đang đăng nhập...
                  </>
                ) : (
                  <>Đăng nhập</>
                )}
              </button>
            </form>

            {/* Footer */}
            <div className="mt-6 pt-6 border-t border-gray-100 text-center">
              <p className="text-xs text-gray-400">
                © 2025 Trường Cao Đẳng Công Nghệ Thủ Đức
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
