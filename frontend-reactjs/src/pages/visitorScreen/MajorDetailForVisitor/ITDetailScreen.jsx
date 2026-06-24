import React, { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import BackButton from "../../../components/common/BackButton";
import useImageViewer from "../../../shared/useImageViewer";
import { Icons } from "../../../components/common/Icon";
import { productApi } from "../../../api";
import VisitorTeam from "../../../components/visitor/VisitorTeam";
import VisitorReviews from "../../../components/visitor/VisitorReviews";
import VisitorShareButton from "../../../components/visitor/VisitorShareButton";

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

  useEffect(() => {
    setLikeCount(productVisitorDetail?.likes || 0);
  }, [productVisitorDetail?.id, productVisitorDetail?.likes]);

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
            onClick={() => navigate("/khach-tham-quan")}
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
                💻 {majorDetail.programming_language || "Chưa cập nhật"}
              </span>
              <button
                onClick={() => navigate("/dang-nhap")}
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
              <VisitorShareButton product={productVisitorDetail} />
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
              <div className="space-y-3">
                {[0, 5].map((start) => {
                  const rowImages = productVisitorDetail.images.slice(
                    start,
                    start + 5,
                  );

                  if (rowImages.length === 0) return null;

                  return (
                    <div
                      key={start}
                      className="group flex h-28 gap-3 overflow-hidden md:h-36"
                    >
                      {rowImages.map((img, idx) => (
                        <button
                          key={idx}
                          onClick={() => openViewer(img.image_url)}
                          className="min-w-0 flex-1 overflow-hidden rounded-xl border-2 border-gray-200 transition-all duration-500 ease-out hover:flex-[3] hover:border-blue-400 focus:flex-[3] focus:border-blue-400 focus:outline-none"
                        >
                          <img
                            src={img.image_url}
                            alt=""
                            className="h-full w-full object-cover transition-transform duration-500 hover:scale-105"
                          />
                        </button>
                      ))}
                    </div>
                  );
                })}
              </div>
            )}

            {/* Tabs */}
            <div className="bg-white rounded-2xl shadow-lg overflow-hidden">
              <div className="flex gap-1 p-2 bg-gray-50/80 border-b">
                {[
                  { id: "overview", label: "📖 Tổng quan" },
                  { id: "technical", label: "🛠️ Kỹ thuật" },
                  { id: "resources", label: "🔗 Tài nguyên" },
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
                {activeTab === "overview" && (
                  <div className="space-y-4">
                    <h4 className="font-semibold text-gray-800">
                      Giới thiệu sản phẩm
                    </h4>
                    <p className="text-gray-600 leading-relaxed">
                      {productVisitorDetail?.description ||
                        "Sản phẩm chưa có nội dung giới thiệu."}
                    </p>
                  </div>
                )}

                {activeTab === "technical" && (
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div className="p-4 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50">
                      <div className="text-2xl mb-2">💻</div>
                      <div className="font-semibold">Ngôn ngữ lập trình</div>
                      <div className="text-sm text-gray-500 mt-1">
                        {majorDetail.programming_language || "Chưa cập nhật"}
                      </div>
                    </div>
                    <div className="p-4 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50">
                      <div className="text-2xl mb-2">⚙️</div>
                      <div className="font-semibold">Framework</div>
                      <div className="text-sm text-gray-500 mt-1">
                        {majorDetail.framework || "Chưa cập nhật"}
                      </div>
                    </div>
                    <div className="p-4 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50">
                      <div className="text-2xl mb-2">🗄️</div>
                      <div className="font-semibold">Database</div>
                      <div className="text-sm text-gray-500 mt-1">
                        {majorDetail.database_used || "Chưa cập nhật"}
                      </div>
                    </div>
                  </div>
                )}

                {activeTab === "resources" && (
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {productVisitorDetail?.resources?.github && (
                      <a
                        href={productVisitorDetail.resources.github}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="p-5 rounded-xl border border-gray-200 hover:border-gray-400 hover:shadow-md transition"
                      >
                        <div className="text-2xl mb-2">📦</div>
                        <div className="font-semibold text-gray-800">
                          Mã nguồn GitHub
                        </div>
                        <p className="text-sm text-gray-500 mt-1 break-all">
                          {productVisitorDetail.resources.github}
                        </p>
                      </a>
                    )}
                    {productVisitorDetail?.resources?.demo && (
                      <a
                        href={productVisitorDetail.resources.demo}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="p-5 rounded-xl border border-gray-200 hover:border-blue-400 hover:shadow-md transition"
                      >
                        <div className="text-2xl mb-2">🚀</div>
                        <div className="font-semibold text-gray-800">
                          Bản chạy thử
                        </div>
                        <p className="text-sm text-gray-500 mt-1 break-all">
                          {productVisitorDetail.resources.demo}
                        </p>
                      </a>
                    )}
                    {!productVisitorDetail?.resources?.github &&
                      !productVisitorDetail?.resources?.demo && (
                        <p className="md:col-span-2 text-center text-gray-500 py-8">
                          Sản phẩm chưa cập nhật liên kết GitHub hoặc bản chạy
                          thử.
                        </p>
                      )}
                  </div>
                )}

                {activeTab === "team" && (
                  <VisitorTeam product={productVisitorDetail} theme={theme} />
                )}

                {activeTab === "feedback" && (
                  <VisitorReviews reviews={productVisitorDetail?.feedback} />
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
