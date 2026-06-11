import React, { useState } from "react";
import { useNavigate } from "react-router-dom";
import BackButton from "../../../components/common/BackButton";
import useImageViewer from "../../../shared/useImageViewer";
import { Icons } from "../../../components/common/Icon";
import { productApi } from "../../../api";

const GraphicDetailScreen = ({
  productVisitorDetail,
  loadingVisitorDetail,
  errorVisitorDetail,
  theme, // theme từ file getTheme
}) => {
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState("showcase");
  const [isLiked, setIsLiked] = useState(false);
  // ✅ Khởi tạo trực tiếp, không cần useEffect
  const [likeCount, setLikeCount] = useState(productVisitorDetail?.likes || 0);
  const [shareCount, setShareCount] = useState(productVisitorDetail?.shares || 0);
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

  const handleShare = async () => {
    const productId = productVisitorDetail?.id;
    if (!productId) return;

    const shareUrl = `${window.location.origin}/visitor-detail/${productId}`;

    try {
      if (navigator.share) {
        await navigator.share({
          title: productVisitorDetail?.title,
          text: productVisitorDetail?.description,
          url: shareUrl,
        });
      } else {
        await navigator.clipboard.writeText(shareUrl);
      }

      setShareCount((prev) => prev + 1);
      const res = await productApi.incrementShare(productId);
      if (typeof res?.shares === "number") {
        setShareCount(res.shares);
      }
    } catch (error) {
      if (error?.name !== "AbortError") {
        console.error(error);
      }
    }
  };

  // Color palette từ theme
  const colorPalette = [
    theme.bgColor,
    "#F472B6",
    "#FB7185",
    "#1F2937",
    "#9CA3AF",
  ];

  if (loadingVisitorDetail) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-pink-50 via-white to-rose-50">
        <div className="text-center">
          <div className="w-16 h-16 border-4 border-pink-200 border-t-pink-600 rounded-full animate-spin mx-auto mb-4"></div>
          <p className="text-gray-500">Đang tải dữ liệu...</p>
        </div>
      </div>
    );
  }

  if (errorVisitorDetail || !productVisitorDetail) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-pink-50 via-white to-rose-50">
        <div className="text-center">
          <div className="text-6xl mb-4">😔</div>
          <p className="text-gray-500">Không tìm thấy sản phẩm</p>
          <button
            onClick={() => navigate("/nckh-visitor")}
            className="mt-4 px-6 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition"
          >
            Quay lại trang chủ
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-pink-50 via-white to-rose-50">
      <ImageViewerModal />

      {/* Header Gradient - dùng theme.headerGradient */}
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
                🎨 {majorDetail.design_type || "Branding"} Design
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
            <p className="text-lg text-pink-100 mb-6 leading-relaxed">
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
              <button
                onClick={handleShare}
                className="flex items-center gap-2 transition-transform hover:scale-110"
              >
                <Icons.Share className="w-4 h-4" />
                <span>{shareCount?.toLocaleString()} chia sẻ</span>
              </button>
              <div className="flex items-center gap-2">
                📅 {productVisitorDetail?.year}
              </div>
            </div>
          </div>
        </div>
        <div className="h-16 bg-gradient-to-t from-pink-50 to-transparent"></div>
      </div>

      <main className="container mx-auto px-4 -mt-8">
        {/* Design Type Banner */}
        <div className="bg-white rounded-2xl shadow-lg p-4 mb-8 text-center mt-[100px]">
          <span
            className={`inline-flex items-center gap-2 px-5 py-2 rounded-full text-sm font-semibold ${theme.lightBg} ${theme.textColor}`}
          >
            <span className="text-xl">🎯</span>{" "}
            {majorDetail.design_type || "Branding Identity"} Design
          </span>
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

            {/* Thumbnails Gallery - Grid Layout */}
            {productVisitorDetail?.images?.length > 0 && (
              <div className="grid grid-cols-4 gap-3">
                {productVisitorDetail.images.map((img, idx) => (
                  <button
                    key={idx}
                    onClick={() => openViewer(img.image_url)}
                    className="aspect-square rounded-xl overflow-hidden border-2 border-gray-200 hover:border-pink-400 transition-all duration-200 hover:scale-105"
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

            {/* Color Palette */}
            <div className="bg-white rounded-2xl shadow-lg p-6">
              <h3 className="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <span className="text-2xl">🎨</span> Bảng màu chủ đạo
              </h3>
              <div className="flex gap-3 flex-wrap">
                {colorPalette.map((color, idx) => (
                  <div key={idx} className="text-center">
                    <div
                      className="w-20 h-20 rounded-xl shadow-md transition-transform hover:scale-110 cursor-pointer"
                      style={{ backgroundColor: color }}
                    ></div>
                    <div className="text-xs text-gray-500 mt-2 font-mono">
                      {color}
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Tabs */}
            <div className="bg-white rounded-2xl shadow-lg overflow-hidden">
              <div className="flex gap-1 p-2 bg-gray-50/80 border-b">
                {[
                  { id: "showcase", label: "🎨 Showcase" },
                  { id: "tools", label: "🛠️ Tools" },
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
                {activeTab === "showcase" && (
                  <div className="space-y-6">
                    <div className="grid grid-cols-2 gap-4">
                      {productVisitorDetail?.images
                        ?.slice(0, 4)
                        .map((img, idx) => (
                          <div
                            key={idx}
                            className="rounded-xl overflow-hidden shadow-md cursor-pointer hover:shadow-xl transition"
                            onClick={() => openViewer(img.image_url)}
                          >
                            <img
                              src={img.image_url}
                              alt=""
                              className="w-full h-40 object-cover"
                            />
                          </div>
                        ))}
                    </div>
                    <div
                      className={`p-4 rounded-xl bg-gradient-to-r ${theme.lightBg}`}
                    >
                      <div className="flex items-center gap-2 mb-2">
                        <span className="text-xl">✨</span>
                        <span className="font-semibold text-gray-800">
                          Phong cách thiết kế
                        </span>
                      </div>
                      <p className="text-gray-600">
                        {majorDetail.design_type || "Modern Minimalist"} •
                        Creative • Professional
                      </p>
                    </div>
                  </div>
                )}

                {activeTab === "tools" && (
                  <div className="space-y-6">
                    <div>
                      <h4 className="font-semibold text-gray-800 mb-3 flex items-center gap-2">
                        <span className="text-xl">🛠️</span> Công cụ thiết kế
                      </h4>
                      <div className="flex flex-wrap gap-2">
                        {productVisitorDetail?.technologies?.map((tool, i) => (
                          <span
                            key={i}
                            className={`px-4 py-2 rounded-xl text-sm font-medium transition-all hover:scale-105 ${theme.lightBg} ${theme.textColor}`}
                          >
                            {tool}
                          </span>
                        ))}
                      </div>
                    </div>
                    <div className="space-y-3">
                      {productVisitorDetail?.resources?.behance && (
                        <a
                          href={productVisitorDetail.resources.behance}
                          target="_blank"
                          rel="noopener noreferrer"
                          className={`flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition group ${theme.hoverBg}`}
                        >
                          <span className="text-2xl">🎨</span>
                          <div className="flex-1">
                            <div
                              className={`font-medium text-gray-800 ${theme.hoverText} transition`}
                            >
                              Behance Portfolio
                            </div>
                            <div className="text-xs text-gray-400">
                              Xem dự án trên Behance
                            </div>
                          </div>
                          <span className="text-gray-400 group-hover:translate-x-1 transition">
                            →
                          </span>
                        </a>
                      )}
                      {productVisitorDetail?.resources?.drive && (
                        <a
                          href={productVisitorDetail.resources.drive}
                          target="_blank"
                          rel="noopener noreferrer"
                          className={`flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-gray-100 transition group ${theme.hoverBg}`}
                        >
                          <span className="text-2xl">📁</span>
                          <div className="flex-1">
                            <div
                              className={`font-medium text-gray-800 ${theme.hoverText} transition`}
                            >
                              Google Drive
                            </div>
                            <div className="text-xs text-gray-400">
                              Tải xuống file thiết kế
                            </div>
                          </div>
                          <span className="text-gray-400 group-hover:translate-x-1 transition">
                            →
                          </span>
                        </a>
                      )}
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
                          🎨 Graphic Designer
                        </p>
                      </div>
                    </div>
                    <div className="flex items-center gap-4 p-4 rounded-xl bg-gray-50">
                      <div className="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center text-xl">
                        <Icons.User />
                      </div>
                      <div>
                        <h3 className="font-semibold text-gray-800">
                          {productVisitorDetail?.advisor}
                        </h3>
                        <p className="text-sm text-gray-500">
                          Giảng viên Duyệt
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
                  🎨 Xem Portfolio
                </button>
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

            {productVisitorDetail?.awards?.length > 0 && (
              <div className="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-2xl shadow-lg p-6">
                <h3 className="font-bold text-lg mb-3 flex items-center gap-2">
                  <span>🏆</span> Giải thưởng
                </h3>
                <div className="space-y-2">
                  {productVisitorDetail.awards.map((award, i) => (
                    <div
                      key={i}
                      className="flex items-center gap-2 text-sm p-2 bg-white/50 rounded-lg"
                    >
                      <span>🏅</span>
                      <span>{award}</span>
                    </div>
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
          <p className="text-xs text-pink-200 mt-2">
            Đào tạo nguồn nhân lực chất lượng cao - Đổi mới sáng tạo - Hội nhập
            quốc tế
          </p>
        </div>
      </footer>
    </div>
  );
};

export default GraphicDetailScreen;
