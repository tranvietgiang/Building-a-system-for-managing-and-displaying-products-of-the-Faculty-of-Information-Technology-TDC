import React, { useState } from "react";
import { useNavigate } from "react-router-dom";
import BackButton from "../../../components/common/BackButton";
import useImageViewer from "../../../shared/useImageViewer";
import { Icons } from "../../../components/common/Icon";
import { productApi } from "../../../api";

const NetworkDetailScreen = ({
  productVisitorDetail,
  loadingVisitorDetail,
  errorVisitorDetail,
  theme,
}) => {
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState("topology");
  const [isLiked, setIsLiked] = useState(false);
  const [likeCount, setLikeCount] = useState(productVisitorDetail?.likes || 0);
  const { openViewer, ImageViewerModal } = useImageViewer();
  const majorDetail = productVisitorDetail?.major_detail || {};

  const handleLike = async () => {
    if (isLiked) return;

    setIsLiked(true);
    setLikeCount((prev) => prev + 1);

    try {
      const res = await productApi.incrementLike(productVisitorDetail?.id);
      if (typeof res?.likes === "number") {
        setLikeCount(res.likes);
      }
    } catch (error) {
      console.error(error);
      setIsLiked(false);
      setLikeCount((prev) => Math.max(0, prev - 1));
    }
  };

  if (loadingVisitorDetail) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-green-50 via-white to-teal-50">
        <div className="text-center">
          <div className="w-16 h-16 border-4 border-green-200 border-t-green-600 rounded-full animate-spin mx-auto mb-4"></div>
          <p className="text-gray-500">Đang tải dữ liệu...</p>
        </div>
      </div>
    );
  }

  if (errorVisitorDetail || !productVisitorDetail) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-green-50 via-white to-teal-50">
        <div className="text-center">
          <div className="text-6xl mb-4">😔</div>
          <p className="text-gray-500">Không tìm thấy sản phẩm</p>
          <button
            onClick={() => navigate("/nckh-visitor")}
            className="mt-4 px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
          >
            Quay lại trang chủ
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-green-50 via-white to-teal-50">
      <ImageViewerModal />

      {/* Header Gradient */}
      <div
        className={`relative bg-gradient-to-r ${theme.headerGradient} text-white`}
      >
        <div className="absolute inset-0 bg-black/10"></div>
        <header className="relative z-10 container mx-auto px-4 py-4">
          <div className="flex items-center justify-between">
            <BackButton variant="light" />
            <div className="flex gap-3">
              <span
                className={`px-3 py-1.5 ${theme.badgeBg} backdrop-blur-sm rounded-full text-xs font-medium`}
              >
                🌐 {majorDetail.topology_type || "Mesh"} Network
              </span>
              <button
                onClick={() => navigate("/login")}
                className="px-5 py-1.5 bg-white/20 backdrop-blur-sm rounded-lg text-sm font-medium hover:bg-white/30 transition"
              >
                Đăng nhập
              </button>
            </div>
          </div>
        </header>

        {/* Hero Section */}
        <div className="relative z-10 container mx-auto px-4 py-12 pb-16">
          <div className="max-w-3xl">
            <h1 className="text-4xl md:text-5xl font-bold mb-4 leading-tight">
              {productVisitorDetail?.title}
            </h1>
            <p className="text-lg text-green-100 mb-6 leading-relaxed">
              {productVisitorDetail?.description}
            </p>
            <div className="flex flex-wrap gap-6 text-sm">
              <div className="flex items-center gap-2">
                <Icons.Eye className="w-4 h-4" />{" "}
                {productVisitorDetail?.views?.toLocaleString()} lượt xem
              </div>
              <button
                onClick={handleLike}
                className="flex items-center gap-2 transition-transform hover:scale-110"
              >
                {isLiked ? (
                  <Icons.Heart className="w-4 h-4 fill-red-500 text-red-500" />
                ) : (
                  <Icons.Heart className="w-4 h-4" />
                )}
                <span>{likeCount?.toLocaleString()} yêu thích</span>
              </button>
              <div className="flex items-center gap-2">
                📅 {productVisitorDetail?.year}
              </div>
            </div>
          </div>
        </div>
        <div className="h-16 bg-gradient-to-t from-green-50 to-transparent"></div>
      </div>

      <main className="container mx-auto px-4 -mt-8">
        {/* Network Metrics */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
          {[
            {
              icon: "🌐",
              value: majorDetail.topology_type || "Mesh",
              label: "Topology",
            },
            {
              icon: "🔌",
              value: majorDetail.network_protocol || "TCP/IP",
              label: "Protocol",
            },
            {
              icon: "🛠️",
              value: majorDetail.simulation_tool || "Wireshark",
              label: "Tool",
            },
            { icon: "📡", value: "24/7", label: "Uptime" },
          ].map((stat, idx) => (
            <div
              key={idx}
              className="bg-white rounded-2xl p-5 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1"
            >
              <div className="text-3xl mb-2">{stat.icon}</div>
              <div className={`text-lg font-bold truncate ${theme.textColor}`}>
                {stat.value}
              </div>
              <div className="text-sm text-gray-500 mt-1">{stat.label}</div>
            </div>
          ))}
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Left Column */}
          <div className="lg:col-span-2 space-y-6">
            {/* Main Image */}
            <div
              className="bg-white rounded-2xl overflow-hidden shadow-lg group cursor-pointer relative"
              onClick={() => openViewer(productVisitorDetail?.thumbnail)}
            >
              <img
                src={productVisitorDetail?.thumbnail}
                alt={productVisitorDetail?.title}
                className="w-full h-[420px] object-cover transition-transform duration-500 group-hover:scale-105"
              />
              <div className="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-all duration-300 flex items-center justify-center">
                <div className="w-14 h-14 bg-white/90 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300">
                  <svg
                    className="w-6 h-6 text-gray-800"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth="2"
                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"
                    />
                  </svg>
                </div>
              </div>
            </div>

            {/* Thumbnails */}
            {productVisitorDetail?.images?.length > 0 && (
              <div className="flex gap-3 overflow-x-auto pb-2">
                {productVisitorDetail.images.map((img, idx) => (
                  <button
                    key={idx}
                    onClick={() => openViewer(img.image_url)}
                    className="w-24 h-24 rounded-xl overflow-hidden border-2 border-gray-200 hover:border-green-400 transition-all duration-200 flex-shrink-0 hover:scale-105"
                  >
                    <img
                      src={img.image_url}
                      alt=""
                      className="w-full h-full object-cover"
                    />
                  </button>
                ))}
              </div>
            )}

            {/* Network Topology Visualization */}
            <div className="bg-white rounded-2xl shadow-lg p-6">
              <h3 className="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <span className="text-2xl">🗺️</span> Mô hình mạng{" "}
                {majorDetail.topology_type || "Mesh"}
              </h3>
              <div className="relative h-80 bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl flex items-center justify-center">
                <div className="relative w-full h-full">
                  {/* Center Node */}
                  <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                    <div
                      className={`w-20 h-20 rounded-full flex items-center justify-center text-white shadow-lg ${theme.buttonBg}`}
                    >
                      <span className="text-2xl">🌐</span>
                    </div>
                    <div className="text-center text-xs mt-2 font-semibold">
                      Gateway
                    </div>
                  </div>
                  {/* Node 1 */}
                  <div className="absolute top-1/4 left-1/4">
                    <div className="w-14 h-14 rounded-full bg-gray-300 flex items-center justify-center shadow-md">
                      <span className="text-2xl">🖥️</span>
                    </div>
                    <div className="text-center text-xs mt-1">Server 1</div>
                  </div>
                  {/* Node 2 */}
                  <div className="absolute top-1/4 right-1/4">
                    <div className="w-14 h-14 rounded-full bg-gray-300 flex items-center justify-center shadow-md">
                      <span className="text-2xl">🖥️</span>
                    </div>
                    <div className="text-center text-xs mt-1">Server 2</div>
                  </div>
                  {/* Node 3 */}
                  <div className="absolute bottom-1/4 left-1/3">
                    <div className="w-14 h-14 rounded-full bg-gray-300 flex items-center justify-center shadow-md">
                      <span className="text-2xl">💻</span>
                    </div>
                    <div className="text-center text-xs mt-1">Client</div>
                  </div>
                  {/* Node 4 */}
                  <div className="absolute bottom-1/4 right-1/3">
                    <div className="w-14 h-14 rounded-full bg-gray-300 flex items-center justify-center shadow-md">
                      <span className="text-2xl">📱</span>
                    </div>
                    <div className="text-center text-xs mt-1">Mobile</div>
                  </div>
                </div>
              </div>
            </div>

            {/* Tabs */}
            <div className="bg-white rounded-2xl shadow-lg overflow-hidden">
              <div className="flex gap-1 p-2 bg-gray-50/80 border-b">
                {[
                  { id: "topology", label: "🗺️ Topology" },
                  { id: "security", label: "🔒 Security" },
                  { id: "team", label: "👥 Đội ngũ" },
                  { id: "feedback", label: "💬 Đánh giá" },
                ].map((tab) => (
                  <button
                    key={tab.id}
                    onClick={() => setActiveTab(tab.id)}
                    className={`flex-1 px-4 py-3 rounded-xl font-medium text-sm transition-all duration-200 ${
                      activeTab === tab.id
                        ? "bg-white shadow-sm"
                        : "text-gray-500 hover:text-gray-700"
                    }`}
                    style={
                      activeTab === tab.id
                        ? { color: theme.textColor.replace("text-", "") }
                        : {}
                    }
                  >
                    {tab.label}
                  </button>
                ))}
              </div>

              <div className="p-6">
                {activeTab === "topology" && (
                  <div className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                      <div className="p-4 rounded-xl bg-gray-50">
                        <div className="text-sm text-gray-500">Kiến trúc</div>
                        <div className="font-semibold text-gray-800">
                          {majorDetail.topology_type || "Mesh"}
                        </div>
                      </div>
                      <div className="p-4 rounded-xl bg-gray-50">
                        <div className="text-sm text-gray-500">Giao thức</div>
                        <div className="font-semibold text-gray-800">
                          {majorDetail.network_protocol || "TCP/IP"}
                        </div>
                      </div>
                      <div className="p-4 rounded-xl bg-gray-50">
                        <div className="text-sm text-gray-500">
                          Công cụ mô phỏng
                        </div>
                        <div className="font-semibold text-gray-800">
                          {majorDetail.simulation_tool || "Wireshark"}
                        </div>
                      </div>
                      <div className="p-4 rounded-xl bg-gray-50">
                        <div className="text-sm text-gray-500">
                          Số lượng node
                        </div>
                        <div className="font-semibold text-gray-800">
                          24 devices
                        </div>
                      </div>
                    </div>
                    {productVisitorDetail?.resources?.config_file && (
                      <div className="p-4 rounded-xl bg-yellow-50">
                        <div className="flex items-center gap-2">
                          <span>📄</span>
                          <span className="text-sm">
                            Config file:{" "}
                            {productVisitorDetail.resources.config_file}
                          </span>
                        </div>
                      </div>
                    )}
                  </div>
                )}

                {activeTab === "security" && (
                  <div className="space-y-4">
                    <div className="flex flex-wrap gap-2 mb-4">
                      {productVisitorDetail?.tags
                        ?.filter((t) =>
                          [
                            "Firewall",
                            "Security",
                            "Infrastructure",
                            "Network",
                            "Routing",
                          ].includes(t),
                        )
                        .map((tag, i) => (
                          <span
                            key={i}
                            className={`px-3 py-1.5 rounded-lg text-sm font-medium ${theme.lightBg} ${theme.textColor}`}
                          >
                            #{tag}
                          </span>
                        ))}
                    </div>
                    <div className="grid grid-cols-1 gap-3">
                      <div className="flex items-center gap-3 p-3 rounded-xl bg-green-50">
                        <span className="text-xl">✅</span>
                        <span className="text-sm">Tường lửa Layer 7</span>
                      </div>
                      <div className="flex items-center gap-3 p-3 rounded-xl bg-green-50">
                        <span className="text-xl">✅</span>
                        <span className="text-sm">IDS/IPS Detection</span>
                      </div>
                      <div className="flex items-center gap-3 p-3 rounded-xl bg-green-50">
                        <span className="text-xl">✅</span>
                        <span className="text-sm">DDoS Protection</span>
                      </div>
                      <div className="flex items-center gap-3 p-3 rounded-xl bg-green-50">
                        <span className="text-xl">✅</span>
                        <span className="text-sm">VPN Support</span>
                      </div>
                    </div>
                  </div>
                )}

                {activeTab === "team" && (
                  <div className="space-y-6">
                    <div
                      className={`flex items-center gap-5 p-5 rounded-xl bg-gradient-to-r ${theme.lightBg}`}
                    >
                      <div
                        className={`w-16 h-16 rounded-full flex items-center justify-center text-white text-2xl font-bold shadow-lg ${theme.buttonBg}`}
                      >
                        {productVisitorDetail?.student?.charAt(0)}
                      </div>
                      <div>
                        <h3 className="font-bold text-lg text-gray-800">
                          {productVisitorDetail?.student}
                        </h3>
                        <p className="text-sm text-gray-500">
                          MSSV: {productVisitorDetail?.studentId}
                        </p>
                        <p className={`text-sm mt-1 ${theme.textColor}`}>
                          🌐 Network Engineer
                        </p>
                      </div>
                    </div>
                    <div className="flex items-center gap-4 p-4 rounded-xl bg-gray-50">
                      <div className="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center text-xl">
                        👨‍🏫
                      </div>
                      <div>
                        <h3 className="font-semibold text-gray-800">
                          {productVisitorDetail?.advisor}
                        </h3>
                        <p className="text-sm text-gray-500">
                          Giảng viên hướng dẫn
                        </p>
                      </div>
                    </div>
                  </div>
                )}

                {activeTab === "feedback" && (
                  <div className="space-y-4">
                    {productVisitorDetail?.feedback?.length > 0 ? (
                      productVisitorDetail.feedback.map((fb, i) => (
                        <div
                          key={i}
                          className="p-4 rounded-xl bg-gradient-to-r from-gray-50 to-gray-100"
                        >
                          <div className="flex items-center gap-2 mb-2">
                            <span className="text-yellow-500">⭐</span>
                            <span className="text-sm text-gray-500">
                              Đánh giá
                            </span>
                          </div>
                          <p className="text-gray-700 italic">"{fb}"</p>
                        </div>
                      ))
                    ) : (
                      <div className="text-center py-8 text-gray-400">
                        <div className="text-4xl mb-2">💬</div>
                        <p>Chưa có đánh giá nào</p>
                      </div>
                    )}
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Right Column */}
          <div className="space-y-6">
            <div className="bg-white rounded-2xl shadow-lg p-6">
              <h3 className="font-bold text-lg mb-4 flex items-center gap-2">
                <span>📌</span> Thông tin dự án
              </h3>
              <div className="space-y-3">
                <div className="flex justify-between items-center py-3 border-b border-gray-100">
                  <span className="text-gray-500">Ngành</span>
                  <span className={`font-medium ${theme.textColor}`}>
                    {productVisitorDetail?.major}
                  </span>
                </div>
                <div className="flex justify-between items-center py-3 border-b border-gray-100">
                  <span className="text-gray-500">Loại</span>
                  <span className="font-medium text-gray-700">
                    {productVisitorDetail?.type}
                  </span>
                </div>
                <div className="flex justify-between items-center py-3 border-b border-gray-100">
                  <span className="text-gray-500">Năm</span>
                  <span className="font-medium text-gray-700">
                    {productVisitorDetail?.year}
                  </span>
                </div>
              </div>

              <div className="flex gap-3 mt-6">
                <button
                  className={`flex-1 px-4 py-2.5 text-white rounded-xl text-sm font-medium text-center transition-all hover:shadow-lg ${theme.buttonBg}`}
                >
                  🔍 Scan Network
                </button>
              </div>
            </div>

            {/* Network Stats */}
            <div className="bg-white rounded-2xl shadow-lg p-6">
              <h3 className="font-bold text-lg mb-3 flex items-center gap-2">
                <span>📊</span> Network Stats
              </h3>
              <div className="space-y-3">
                <div className="flex justify-between items-center p-3 rounded-xl bg-gray-50">
                  <span className="text-gray-600">Bandwidth</span>
                  <span className="font-semibold text-gray-800">1 Gbps</span>
                </div>
                <div className="flex justify-between items-center p-3 rounded-xl bg-gray-50">
                  <span className="text-gray-600">Latency</span>
                  <span className="font-semibold text-gray-800">&lt; 10ms</span>
                </div>
                <div className="flex justify-between items-center p-3 rounded-xl bg-gray-50">
                  <span className="text-gray-600">Packet Loss</span>
                  <span className="font-semibold text-green-600">0.01%</span>
                </div>
                <div className="flex justify-between items-center p-3 rounded-xl bg-gray-50">
                  <span className="text-gray-600">Active Nodes</span>
                  <span className="font-semibold text-gray-800">24/7</span>
                </div>
              </div>
            </div>

            {productVisitorDetail?.tags?.length > 0 && (
              <div className="bg-white rounded-2xl shadow-lg p-6">
                <h3 className="font-bold text-lg mb-3 flex items-center gap-2">
                  <span>🏷️</span> Tags
                </h3>
                <div className="flex flex-wrap gap-2">
                  {productVisitorDetail.tags.map((tag, i) => (
                    <span
                      key={i}
                      className={`px-3 py-1.5 bg-gray-100 text-gray-600 rounded-full text-xs font-medium hover:scale-105 transition-transform ${theme.hoverBg} ${theme.hoverText}`}
                    >
                      #{tag}
                    </span>
                  ))}
                </div>
              </div>
            )}
          </div>
        </div>
      </main>

      {/* Footer */}
      <footer
        className={`mt-16 py-8 text-white bg-gradient-to-r ${theme.headerGradient}`}
      >
        <div className="container mx-auto px-4 text-center">
          <p className="text-sm">
            © 2025 Trường Cao Đẳng Công Nghệ Thủ Đức - Khoa Công Nghệ Thông Tin
          </p>
          <p className="text-xs text-green-200 mt-2">
            Đào tạo nguồn nhân lực chất lượng cao - Đổi mới sáng tạo - Hội nhập
            quốc tế
          </p>
        </div>
      </footer>
    </div>
  );
};

export default NetworkDetailScreen;
