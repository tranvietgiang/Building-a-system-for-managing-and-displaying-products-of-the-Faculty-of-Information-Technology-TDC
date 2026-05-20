import React, { useState } from "react";
import { useNavigate } from "react-router-dom";
import BackButton from "../../../components/common/BackButton";
import useImageViewer from "../../../shared/useImageViewer";
import { Icons } from "../../../components/common/Icon";
import { productApi } from "../../../api";

const ITDetailScreen = ({
  productVisitorDetail,
  loadingVisitorDetail,
  errorVisitorDetail,
  theme,
}) => {
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState("overview");
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

  const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
  };

  if (loadingVisitorDetail) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-indigo-50">
        <div className="text-center">
          <div className="w-16 h-16 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mx-auto mb-4"></div>
          <p className="text-gray-500">Đang tải dữ liệu...</p>
        </div>
      </div>
    );
  }

  if (errorVisitorDetail || !productVisitorDetail) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-indigo-50">
        <div className="text-center">
          <div className="text-6xl mb-4">😔</div>
          <p className="text-gray-500">Không tìm thấy sản phẩm</p>
          <button
            onClick={() => navigate("/nckh-visitor")}
            className="mt-4 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
          >
            Quay lại trang chủ
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50">
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
                💻 {majorDetail.programming_language || "Fullstack"} Developer
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
            <p className="text-lg text-blue-100 mb-6 leading-relaxed">
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
        <div className="h-16 bg-gradient-to-t from-blue-50 to-transparent"></div>
      </div>

      <main className="container mx-auto px-4 -mt-8">
        {/* Tech Stack Highlights */}
        <div className="bg-white rounded-2xl shadow-lg p-6 mb-8 mt-[70px]">
          <h3 className="font-bold text-gray-800 mb-4 flex items-center gap-2">
            <span className="text-2xl">🛠️</span> Tech Stack
          </h3>
          <div className="flex flex-wrap gap-3">
            {productVisitorDetail?.technologies?.map((tech, i) => (
              <span
                key={i}
                className={`px-4 py-2 rounded-xl text-sm font-medium transition-all hover:scale-105 cursor-default ${theme.lightBg} ${theme.textColor}`}
              >
                {tech}
              </span>
            ))}
          </div>
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
                <div className="w-14 h-14 bg-white/90 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 transform scale-90 group-hover:scale-100">
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
                    className="w-24 h-24 rounded-xl overflow-hidden border-2 border-gray-200 hover:border-blue-400 transition-all duration-200 flex-shrink-0 hover:scale-105"
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

            {/* Tabs */}
            <div className="bg-white rounded-2xl shadow-lg overflow-hidden">
              <div className="flex gap-1 p-2 bg-gray-50/80 border-b">
                {[
                  { id: "overview", label: "📖 Tổng quan" },
                  { id: "database", label: "🗄️ Database" },
                  { id: "api", label: "🔌 API" },
                  { id: "team", label: "👥 Đội ngũ" },
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
                {activeTab === "overview" && (
                  <div className="space-y-6">
                    <p className="text-gray-600 leading-relaxed">
                      {productVisitorDetail?.description}
                    </p>
                    <div>
                      <h4 className="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                        <span className="text-xl">🏗️</span> Kiến trúc hệ thống
                      </h4>
                      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="p-4 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50">
                          <div className="text-2xl mb-2">🎨</div>
                          <div className="font-semibold">Frontend</div>
                          <div className="text-sm text-gray-500 mt-1">
                            ReactJS / TypeScript
                          </div>
                        </div>
                        <div className="p-4 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50">
                          <div className="text-2xl mb-2">⚙️</div>
                          <div className="font-semibold">Backend</div>
                          <div className="text-sm text-gray-500 mt-1">
                            {majorDetail.framework || "Express"} / Node.js
                          </div>
                        </div>
                        <div className="p-4 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50">
                          <div className="text-2xl mb-2">🗄️</div>
                          <div className="font-semibold">Database</div>
                          <div className="text-sm text-gray-500 mt-1">
                            {majorDetail.database_used || "PostgreSQL"}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                )}

                {activeTab === "database" && (
                  <div className="space-y-4">
                    <div className="p-4 rounded-xl bg-gray-50">
                      <div className="flex items-center gap-2 mb-3">
                        <span className="text-xl">📊</span>
                        <h4 className="font-semibold text-gray-800">
                          Database Schema
                        </h4>
                      </div>
                      <div className="space-y-2 text-sm">
                        {[
                          {
                            table: "users",
                            fields: "id, name, email, password, created_at",
                          },
                          {
                            table: "products",
                            fields: "id, name, description, price, user_id",
                          },
                          {
                            table: "orders",
                            fields:
                              "id, product_id, quantity, status, order_date",
                          },
                        ].map((item, i) => (
                          <div key={i} className="p-3 bg-white rounded-lg">
                            <span className="font-semibold text-blue-600">
                              {item.table}
                            </span>
                            <span className="text-gray-500 ml-2">
                              → {item.fields}
                            </span>
                          </div>
                        ))}
                      </div>
                    </div>
                    <div className="p-4 rounded-xl bg-green-50">
                      <div className="flex items-center gap-2">
                        <span>✅</span>
                        <span className="text-sm">
                          Relations: One-to-Many, Many-to-Many
                        </span>
                      </div>
                    </div>
                  </div>
                )}

                {activeTab === "api" && (
                  <div className="space-y-3">
                    {[
                      {
                        method: "GET",
                        endpoint: "/api/users",
                        desc: "Lấy danh sách users",
                      },
                      {
                        method: "POST",
                        endpoint: "/api/users",
                        desc: "Tạo user mới",
                      },
                      {
                        method: "PUT",
                        endpoint: "/api/users/:id",
                        desc: "Cập nhật user",
                      },
                      {
                        method: "DELETE",
                        endpoint: "/api/users/:id",
                        desc: "Xóa user",
                      },
                    ].map((api, i) => (
                      <div
                        key={i}
                        className="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition"
                      >
                        <span
                          className={`px-2 py-1 rounded text-xs font-bold text-white ${
                            api.method === "GET"
                              ? "bg-green-500"
                              : api.method === "POST"
                                ? "bg-blue-500"
                                : api.method === "PUT"
                                  ? "bg-yellow-500"
                                  : "bg-red-500"
                          }`}
                        >
                          {api.method}
                        </span>
                        <code className="text-sm text-gray-700 flex-1">
                          {api.endpoint}
                        </code>
                        <button
                          onClick={() => copyToClipboard(api.endpoint)}
                          className="text-gray-400 hover:text-gray-600"
                        >
                          📋
                        </button>
                      </div>
                    ))}
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
                          💻 Developer
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
                          Giảng viên duyệt
                        </p>
                      </div>
                    </div>
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
                {productVisitorDetail?.resources?.github && (
                  <a
                    href={productVisitorDetail.resources.github}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="flex-1 px-4 py-2.5 bg-gray-800 text-white rounded-xl text-sm font-medium text-center hover:bg-gray-900 transition"
                  >
                    📦 GitHub
                  </a>
                )}
                {productVisitorDetail?.resources?.demo && (
                  <a
                    href={productVisitorDetail.resources.demo}
                    target="_blank"
                    rel="noopener noreferrer"
                    className={`flex-1 px-4 py-2.5 text-white rounded-xl text-sm font-medium text-center transition-all hover:shadow-lg ${theme.buttonBg}`}
                  >
                    🚀 Demo
                  </a>
                )}
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
                      className={`px-3 py-1.5 bg-gray-100 text-gray-600 rounded-full text-xs font-medium hover:scale-105 transition-transform cursor-default ${theme.hoverBg} ${theme.hoverText}`}
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
          <p className="text-xs text-blue-200 mt-2">
            Đào tạo nguồn nhân lực chất lượng cao - Đổi mới sáng tạo - Hội nhập
            quốc tế
          </p>
        </div>
      </footer>
    </div>
  );
};

export default ITDetailScreen;
