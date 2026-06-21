import React, { useState } from "react";
import useTitle from "../../hooks/common/useTitle";
import useImageViewer from "../../shared/useImageViewer";
import { formatDate } from "../../utils/formatDate";
import { STATUS } from "../../utils/constants";
import { Icons } from "../../components/common/Icon";
const ItDetail = ({ product, theme }) => {
  useTitle("Chi tiết sản phẩm CNTT");

  const { openViewer, ImageViewerModal } = useImageViewer();
  const [activeTab, setActiveTab] = useState("overview");

  // if (loading) {
  //   return (
  //     <div className="min-h-screen bg-gray-50 flex items-center justify-center">
  //       <div className="text-center">
  //         <div
  //           className={`w-16 h-16 border-4 border-${theme.primary}-600 border-t-transparent rounded-full animate-spin mx-auto mb-4`}
  //         ></div>
  //         <p className="text-gray-600">Đang tải thông tin sản phẩm CNTT...</p>
  //       </div>
  //     </div>
  //   );
  // }

  // if (error) {
  //   return (
  //     <div className="min-h-screen bg-gray-50 flex items-center justify-center">
  //       <div className="text-center">
  //         <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
  //           <Icons.AlertCircle />
  //         </div>
  //         <h2 className="text-xl font-bold text-gray-900 mb-2">
  //           Có lỗi xảy ra
  //         </h2>
  //         <p className="text-gray-600">{error}</p>
  //       </div>
  //     </div>
  //   );
  // }

  if (!product) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <Icons.AlertCircle />
          <h2 className="text-xl font-bold text-gray-900 mb-2">
            Không tìm thấy sản phẩm
          </h2>
          <p className="text-gray-600">
            Sản phẩm CNTT không tồn tại hoặc đã bị xóa.
          </p>
        </div>
      </div>
    );
  }

  // Lấy thông tin CNTT specific fields
  const itDetail = product.it_detail || {};

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
      <ImageViewerModal />

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Breadcrumb */}
        <div className="mb-6">
          <nav className="flex items-center gap-2 text-sm">
            <a
              href="/sinh-vien"
              className="text-gray-500 hover:text-gray-700 transition flex items-center gap-1"
            >
              <Icons.Home />
              <span>Trang chủ</span>
            </a>
            <span className="text-gray-400">›</span>
            <a
              href="/sinh-vien"
              className="text-gray-500 hover:text-gray-700 transition flex items-center gap-1"
            >
              <Icons.Users />
              <span>Sinh viên</span>
            </a>
            <span className="text-gray-400">›</span>
            <a
              href="/student/it"
              className="text-gray-500 hover:text-gray-700 transition flex items-center gap-1"
            >
              <Icons.Code />
              <span>CNTT</span>
            </a>
            <span className="text-gray-400">›</span>
            <span
              className={`font-medium ${theme.text} truncate flex items-center gap-1`}
            >
              <Icons.FileText />
              {product.title}
            </span>
          </nav>
        </div>

        {/* Header Card */}
        <div
          className={`bg-gradient-to-r ${theme?.gradient} rounded-2xl shadow-xl overflow-hidden mb-6`}
        >
          <div className="p-6 md:p-8">
            <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
              <div className="flex-1">
                <div className="flex flex-wrap items-center gap-3 mb-3">
                  <h1 className="text-2xl md:text-3xl font-bold text-white">
                    {product.title}
                  </h1>
                  <span
                    className={`px-3 py-1 text-xs font-semibold rounded-full flex items-center gap-1 ${
                      product.status === STATUS.APPROVED
                        ? "bg-green-500 text-white"
                        : product.status === STATUS.PENDING
                          ? "bg-yellow-500 text-white"
                          : "bg-red-500 text-white"
                    }`}
                  >
                    {product.status === STATUS.APPROVED ? (
                      <>
                        <Icons.CheckCircle /> Đã duyệt
                      </>
                    ) : product.status === STATUS.PENDING ? (
                      <>
                        <Icons.Clock /> Chờ duyệt
                      </>
                    ) : (
                      <>
                        <Icons.AlertCircle /> Từ chối
                      </>
                    )}
                  </span>
                </div>

                <div className="flex flex-wrap items-center gap-2 text-sm">
                  <span className="px-3 py-1 bg-white/20 backdrop-blur rounded-full text-white flex items-center gap-1">
                    <Icons.Briefcase />
                    {product.major?.major_name || "Công nghệ thông tin"}
                  </span>
                  <span className="px-3 py-1 bg-white/20 backdrop-blur rounded-full text-white flex items-center gap-1">
                    <Icons.Grid />
                    {product.category?.name || "Chưa có danh mục"}
                  </span>
                  <span className="flex items-center gap-1 text-white/80">
                    <Icons.Calendar />
                    {formatDate(product.created_at)}
                  </span>
                </div>
              </div>

              {product.status !== STATUS.REJECTED && (
                <div className="flex items-center gap-2">
                  <button className="p-2 bg-white/10 rounded-lg text-white hover:bg-white/20 transition">
                    <Icons.Heart />
                  </button>
                  <button className="p-2 bg-white/10 rounded-lg text-white hover:bg-white/20 transition">
                    <Icons.Share />
                  </button>
                </div>
              )}
            </div>

            {/* Stats */}
            {product.status !== STATUS.REJECTED && (
              <div className="flex flex-wrap items-center gap-6 mt-6 pt-4 border-t border-white/20">
                <div className="flex items-center gap-2 text-white/90">
                  <Icons.Eye />
                  <span className="text-sm font-medium">
                    {product.activity_logs?.views || 0} lượt xem
                  </span>
                </div>
                <div className="flex items-center gap-2 text-white/90">
                  <Icons.Share />
                  <span className="text-sm font-medium">
                    {product.activity_logs?.shares || 0} lượt chia sẻ
                  </span>
                </div>
              </div>
            )}

            {/* Approved info */}
            {product.status === STATUS.APPROVED && product.approved_by_user && (
              <div className="mt-4 p-3 bg-white/10 backdrop-blur rounded-lg flex items-center gap-2">
                <Icons.CheckCircle />
                <span className="text-sm text-white">
                  Được duyệt bởi {product.approved_by_user.fullname} vào ngày{" "}
                  {formatDate(product.approved_at)}
                </span>
              </div>
            )}
          </div>
        </div>

        {/* Main Content */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Left Column - 2/3 */}
          <div className="lg:col-span-2 space-y-6">
            {/* Images Section */}
            <div className="bg-white rounded-2xl shadow-lg p-6">
              <h2
                className={`text-lg font-bold mb-4 ${theme.text} border-l-4 ${theme.border} pl-3 flex items-center gap-2`}
              >
                <Icons.Image />
                Hình ảnh sản phẩm
              </h2>

              <div
                className="aspect-video bg-gray-100 rounded-xl overflow-hidden mb-4 cursor-pointer group relative"
                onClick={() => openViewer(product.thumbnail)}
              >
                <img
                  src={product.thumbnail}
                  alt={product.title}
                  className="w-full h-full object-contain transition-transform duration-300 group-hover:scale-105"
                />
                <div className="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                  <Icons.ZoomIn />
                </div>
              </div>

              {product?.images && (
                <div className="grid grid-cols-5 gap-3">
                  {product.images.map((img, index) => (
                    <button
                      key={img.product_image_id || index}
                      onClick={() => openViewer(img.image_url)}
                      className="group relative aspect-square bg-gray-100 rounded-lg overflow-hidden hover:opacity-80 transition"
                    >
                      <img
                        src={img.image_url}
                        alt=""
                        className="w-full h-full object-cover"
                      />
                      <div className="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors flex items-center justify-center">
                        <Icons.ZoomInSmall />
                      </div>
                    </button>
                  ))}
                </div>
              )}
            </div>

            {/* CNTT Specific Info Card */}
            <div className="bg-white rounded-2xl shadow-lg p-6">
              <h2
                className={`text-lg font-bold mb-4 ${theme.text} border-l-4 ${theme.border} pl-3 flex items-center gap-2`}
              >
                <Icons.Code />
                Thông tin kỹ thuật
              </h2>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="bg-purple-50 rounded-lg p-4">
                  <div className="flex items-center gap-2 mb-2">
                    <Icons.Code className="text-purple-600" />
                    <span className="text-sm font-medium text-purple-600">
                      Ngôn ngữ lập trình
                    </span>
                  </div>
                  <p className="text-gray-900 font-semibold">
                    {itDetail.programming_language || "Chưa cập nhật"}
                  </p>
                </div>
                <div className="bg-purple-50 rounded-lg p-4">
                  <div className="flex items-center gap-2 mb-2">
                    <Icons.Framework className="text-purple-600" />
                    <span className="text-sm font-medium text-purple-600">
                      Framework
                    </span>
                  </div>
                  <p className="text-gray-900 font-semibold">
                    {itDetail.framework || "Chưa cập nhật"}
                  </p>
                </div>
                <div className="bg-purple-50 rounded-lg p-4">
                  <div className="flex items-center gap-2 mb-2">
                    <Icons.Database className="text-purple-600" />
                    <span className="text-sm font-medium text-purple-600">
                      Cơ sở dữ liệu
                    </span>
                  </div>
                  <p className="text-gray-900 font-semibold">
                    {itDetail.database_used || "Chưa cập nhật"}
                  </p>
                </div>
              </div>
            </div>

            {/* Tabs Section */}
            <div className="bg-white rounded-2xl shadow-lg overflow-hidden">
              <div className="border-b">
                <div className="flex">
                  {["overview", "reviews"].map((tab) => (
                    <button
                      key={tab}
                      onClick={() => setActiveTab(tab)}
                      className={`px-6 py-3 font-medium text-sm transition flex items-center gap-2 ${
                        activeTab === tab
                          ? `border-b-2 ${theme.border} ${theme.text}`
                          : "text-gray-500 hover:text-gray-700"
                      }`}
                    >
                      {tab === "overview" && (
                        <>
                          <Icons.FileText /> Tổng quan
                        </>
                      )}
                      {tab === "reviews" && (
                        <>
                          <Icons.MessageCircle /> Đánh giá (
                          {product.reviews?.length || 0})
                        </>
                      )}
                    </button>
                  ))}
                </div>
              </div>

              <div className="p-6">
                {activeTab === "overview" && (
                  <div className="prose max-w-none">
                    <p className="text-gray-700 leading-relaxed whitespace-pre-wrap">
                      {product.description}
                    </p>
                  </div>
                )}


                {activeTab === "reviews" && (
                  <div className="space-y-5">
                    {product.reviews?.length > 0 ? (
                      product.reviews.map((review) => (
                        <div
                          key={review.review_id}
                          className="flex gap-3 p-4 bg-gray-50 rounded-xl"
                        >
                          <div
                            className={`w-10 h-10 bg-gradient-to-r ${theme?.gradient} rounded-full flex items-center justify-center text-white font-semibold shadow-md`}
                          >
                            {review.teacher?.fullname?.charAt(0) || (
                              <Icons.User />
                            )}
                          </div>
                          <div className="flex-1">
                            <div className="flex items-center justify-between flex-wrap gap-2">
                              <div>
                                <p className="font-semibold text-gray-900 flex items-center gap-2">
                                  {review.teacher?.fullname}
                                  <span className="text-xs text-gray-500 flex items-center gap-1">
                                    <Icons.User />
                                    Giảng viên
                                  </span>
                                </p>
                              </div>
                              <span className="text-xs text-gray-400 flex items-center gap-1">
                                <Icons.Clock />
                                {formatDate(review.created_at)}
                              </span>
                            </div>
                            <p className="text-gray-700 mt-2">
                              {review.comment}
                            </p>
                          </div>
                        </div>
                      ))
                    ) : (
                      <p className="text-center text-gray-500 py-8 flex items-center justify-center gap-2">
                        <Icons.MessageCircle />
                        Chưa có đánh giá từ giảng viên
                      </p>
                    )}
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Right Column - 1/3 */}
          <div className="space-y-6">
            {/* Author Card */}
            <div className="bg-white rounded-2xl shadow-lg p-6">
              <h2
                className={`text-lg font-bold mb-4 ${theme.text} flex items-center gap-2`}
              >
                <Icons.User />
                Tác giả
              </h2>
              <div className="flex items-center gap-4">
                <div
                  className={`w-16 h-16 bg-gradient-to-r ${theme?.gradient} rounded-full flex items-center justify-center text-white text-xl font-bold shadow-lg`}
                >
                  {product.fullname?.charAt(0)?.toUpperCase() || <Icons.User />}
                </div>
                <div>
                  <p className="font-semibold text-gray-900 text-lg">
                    {product?.user_id}
                  </p>
                  <p
                    className={`text-sm ${theme.text} flex items-center gap-1`}
                  >
                    {product.major?.major_name}
                  </p>
                </div>
              </div>
            </div>

            {/* Tags Card */}
            {product.tags?.length > 0 && (
              <div className="bg-white rounded-2xl shadow-lg p-6">
                <h2
                  className={`text-lg font-bold mb-4 ${theme.text} flex items-center gap-2`}
                >
                  <Icons.Tag />
                  Công nghệ sử dụng
                </h2>
                <div className="flex flex-wrap gap-2">
                  {product.tags.map((tag) => (
                    <span
                      key={tag.tag_id}
                      className={`px-3 py-1.5 bg-gradient-to-r ${theme.gradient} text-white rounded-full text-sm font-medium shadow-sm flex items-center gap-1`}
                    >
                      <Icons.Tag />#{tag.tag_name}
                    </span>
                  ))}
                </div>
              </div>
            )}

            {/* Links Card */}
            {(product.github_link || product.demo_link) && (
              <div className="bg-white rounded-2xl shadow-lg p-6">
                <h2
                  className={`text-lg font-bold mb-4 ${theme.text} flex items-center gap-2`}
                >
                  <Icons.Link />
                  Liên kết
                </h2>
                <div className="space-y-3">
                  {product.github_link && (
                    <a
                      href={product.github_link}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition group"
                    >
                      <Icons.Github />
                      <span className="flex-1 text-sm font-medium group-hover:underline">
                        GitHub Repository
                      </span>
                      <Icons.ExternalLink />
                    </a>
                  )}
                  {product.demo_link && (
                    <a
                      href={product.demo_link}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition group"
                    >
                      <Icons.Monitor />
                      <span className="flex-1 text-sm font-medium group-hover:underline">
                        Xem Demo
                      </span>
                      <Icons.ExternalLink />
                    </a>
                  )}
                </div>
              </div>
            )}

            {/* Info Card */}
            <div className="bg-white rounded-2xl shadow-lg p-6">
              <h2
                className={`text-lg font-bold mb-4 ${theme.text} flex items-center gap-2`}
              >
                <Icons.Info />
                Thông tin thêm
              </h2>
              <div className="space-y-3 text-sm">
                <div className="flex justify-between py-2 border-b border-gray-100">
                  <span className="text-gray-500 flex items-center gap-1">
                    <Icons.Calendar /> Ngày đăng:
                  </span>
                  <span className="text-gray-900 font-medium">
                    {formatDate(product.created_at)}
                  </span>
                </div>
                <div className="flex justify-between py-2 border-b border-gray-100">
                  <span className="text-gray-500 flex items-center gap-1">
                    <Icons.RefreshCw /> Cập nhật:
                  </span>
                  <span className="text-gray-900 font-medium">
                    {formatDate(product.updated_at)}
                  </span>
                </div>
                {product.approved_at && (
                  <div className="flex justify-between py-2 border-b border-gray-100">
                    <span className="text-gray-500 flex items-center gap-1">
                      <Icons.CheckCircle /> Ngày duyệt:
                    </span>
                    <span className="text-gray-900 font-medium">
                      {formatDate(product.approved_at)}
                    </span>
                  </div>
                )}
                {product.awards && (
                  <div className="flex justify-between py-2 border-b border-gray-100">
                    <span className="text-gray-500 flex items-center gap-1">
                      <Icons.Trophy /> Giải thưởng:
                    </span>
                    <span className="text-gray-900 font-medium">
                      {product.awards}
                    </span>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ItDetail;
