import React, { useState, useContext, useCallback, useMemo } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import { AuthContext } from "../../contexts/AuthContext";

import useProductDetailTeacher from "../../hooks/useProduct/useProductDetailTeacher";
import useImageViewer from "../../shared/useImageViewer";
import useTitle from "../../hooks/common/useTitle";
import { toast } from "react-toastify";
import { formatDate } from "../../utils/formatDate";
import { getStatusColor } from "../../components/common/getStatusColor";
import { getStatusText } from "../../components/common/getStatusText";
import { getMajorTheme } from "../../utils/uploadProductScreen/uploadRegistry";

import { useHandleApprove } from "../../hooks/useTeacher/useHandleApprove.jsx";
import { useHandleSubmitReview } from "../../hooks/useTeacher/useHandleSubmitReview";
import { useHandleSubmitRejection } from "../../hooks/useTeacher/useHandleSubmitRejection";

import useTeacherApprove from "../../hooks/useTeacher/useTeacherApprove";
import useTeacherReject from "../../hooks/useTeacher/useTeacherReject";
import useReviewToggle from "../../hooks/common/useReviewToggle";
import useCompareProduct from "../../hooks/ai/useCompareProduct";

import LoadingSpinner from "../../components/common/LoadingOverlay";
import { confirmToast } from "../../components/common/ConfirmToast";
import BackButton from "../../components/common/BackButton";

import { Icons } from "../../components/common/Icon";
import { STATUS } from "../../utils/constants";

const TeacherProductDetailScreen = () => {
  useTitle("Xem chi tiết sản phẩm - Giảng viên");
  const navigate = useNavigate();
  const { state } = useLocation();
  const id = state?.productId;

  const { product, loading, error, mutate } = useProductDetailTeacher(id);
  const { openViewer, ImageViewerModal } = useImageViewer();

  const [showFeedbackModal, setShowFeedbackModal] = useState(false);
  const [feedback, setFeedback] = useState("");
  const [reviewComment, setReviewComment] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { user } = useContext(AuthContext);

  const { teacherApprove, loading_approve, error_approve } =
    useTeacherApprove();
  const { teacherReject, loading_reject, error_reject } = useTeacherReject();

  const { checkCompareProduct, loadingCompare, errorCompare } =
    useCompareProduct(id);

  const images = useMemo(() => product?.images || [], [product]);
  const productData = useMemo(() => product?.product || {}, [product]);
  const reviews = useMemo(() => product?.reviews || [], [product]);
  const theme = useMemo(
    () => getMajorTheme(productData?.major_code),
    [productData?.major_code],
  );

  const handleApproveOriginal = useHandleApprove(
    confirmToast,
    setIsSubmitting,
    toast,
    navigate,
    teacherApprove,
  );

  const handleSubmitReviewOriginal = useHandleSubmitReview(
    confirmToast,
    setIsSubmitting,
    toast,
    mutate,
    navigate,
  );

  const submitRejectionOriginal = useHandleSubmitRejection(
    feedback,
    setIsSubmitting,
    toast,
    setShowFeedbackModal,
    setFeedback,
    navigate,
    teacherReject,
  );

  const handleApprove = useCallback(async () => {
    handleApproveOriginal.handleApprove(id, productData?.title, {
      title: productData?.title,
      description: productData?.description,
      major: productData?.major_name || productData?.major_code,
      image: productData?.thumbnail || images?.[0]?.image_url,
    });
  }, [id, productData, images, handleApproveOriginal]);

  const handleSubmitReview = useCallback(async () => {
    setIsSubmitting(true);
    try {
      await handleSubmitReviewOriginal();
    } catch (err) {
      console.error(err);
    } finally {
      setIsSubmitting(false);
    }
  }, [handleSubmitReviewOriginal]);

  const submitRejection = useCallback(async () => {
    setIsSubmitting(true);
    try {
      await submitRejectionOriginal(id);
    } catch (err) {
      console.error(err);
    } finally {
      setIsSubmitting(false);
    }
  }, [id, submitRejectionOriginal]);

  const handleReject = useCallback(() => {
    setShowFeedbackModal(true);
  }, []);

  const handleCloseFeedbackModal = useCallback(() => {
    setShowFeedbackModal(false);
  }, []);

  const handleFeedbackChange = useCallback((e) => {
    setFeedback(e.target.value);
  }, []);

  const handleReviewCommentChange = useCallback((e) => {
    setReviewComment(e.target.value);
  }, []);

  const { getDisplayed, canShowMore, canCollapse, showMore, collapse } =
    useReviewToggle(3);

  const displayedReviews = useMemo(
    () => getDisplayed(reviews),
    [reviews, getDisplayed],
  );

  const statusBadgeClass = useMemo(() => {
    if (productData?.status === STATUS.APPROVED)
      return "bg-green-500 text-white";
    if (productData?.status === STATUS.PENDING)
      return "bg-yellow-500 text-white";
    return "bg-red-500 text-white";
  }, [productData?.status]);

  const handleCompare = async () => {
    try {
      const data = await checkCompareProduct();

      // console.log("FULL DATA", data);

      if (data?.status == true) {
        navigate("/nckh-compare", {
          state: {
            currentProduct: data?.current_product || null,
            matches: data?.matches || [],
            summary: data?.summary || "",
            time: Date.now(),
          },
        });
      } else {
        toast.warning("không có sản phẩm nào trùng!");
      }
    } catch (error) {
      console.error(error);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <div
            className={`w-12 h-12 border-4 border-${theme.primary}-600 border-t-transparent rounded-full animate-spin mx-auto mb-4`}
          ></div>
          <p className="text-gray-600">Đang tải thông tin sản phẩm...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <Icons.AlertCircle />
          </div>
          <p className="text-red-600">{error}</p>
        </div>
      </div>
    );
  }

  if (!product) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="text-center">
          <Icons.AlertCircle className="w-16 h-16 text-gray-400 mx-auto mb-4" />
          <p className="text-gray-600">Không tìm thấy sản phẩm</p>
        </div>
      </div>
    );
  }

  // ✅ Lấy message string từ object
  if (error_approve) {
    return (
      <div className="mb-4 p-3 bg-red-100 text-red-600 rounded-lg">
        {error_approve?.message ||
          error_approve?.reason ||
          "Có lỗi xảy ra khi duyệt"}
      </div>
    );
  }

  if (error_reject) {
    return (
      <div className="mb-4 p-3 bg-red-100 text-red-600 rounded-lg">
        {typeof error_reject === "string"
          ? error_reject
          : error_reject?.message || "Có lỗi xảy ra khi từ chối"}
      </div>
    );
  }

  if (errorCompare) {
    return (
      <div className="mb-4 p-3 bg-red-100 text-red-600 rounded-lg">
        {errorCompare}
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
      <ImageViewerModal />

      {/* Modal lỗi duyệt từ AI */}
      {handleApproveOriginal.errorModalComponent}

      {/* Modal từ chối */}
      {showFeedbackModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
          <div className="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl animate-fadeIn">
            <div className="flex items-center gap-3 mb-4">
              <div className="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                <Icons.AlertCircle className="w-6 h-6 text-red-600" />
              </div>
              <div>
                <h3 className="text-xl font-bold text-gray-900">
                  Từ chối sản phẩm
                </h3>
                <p className="text-sm text-gray-500">
                  Vui lòng nhập lý do từ chối
                </p>
              </div>
            </div>
            <textarea
              rows={5}
              value={feedback}
              onChange={handleFeedbackChange}
              placeholder="Nhập lý do từ chối sản phẩm..."
              className="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none"
            />
            <div className="flex gap-3 mt-6">
              <button
                onClick={handleCloseFeedbackModal}
                className="flex-1 py-2.5 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition font-medium"
              >
                Hủy
              </button>
              <button
                onClick={submitRejection}
                disabled={isSubmitting}
                className="flex-1 py-2.5 bg-red-600 text-white rounded-xl hover:bg-red-700 transition font-medium disabled:opacity-50"
              >
                {isSubmitting ? "Đang xử lý..." : "Xác nhận từ chối"}
              </button>
            </div>
          </div>
        </div>
      )}

      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <BackButton loading={loading} />

        {/* Header Card */}
        <div
          className={`bg-gradient-to-r ${theme.gradient} rounded-2xl shadow-xl overflow-hidden mb-8`}
        >
          <div className="p-6 md:p-8">
            <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
              <div className="flex-1">
                <div className="flex flex-wrap items-center gap-3 mb-3">
                  <h1 className="text-2xl md:text-3xl font-bold text-white">
                    {productData?.title}
                  </h1>
                  <span
                    className={`px-3 py-1 text-xs font-semibold rounded-full ${statusBadgeClass}`}
                  >
                    {getStatusText(productData?.status)}
                  </span>
                </div>
                <p className="text-white/80 text-sm line-clamp-2 mb-3">
                  {productData?.description}
                </p>
                <div className="flex flex-wrap items-center gap-4 text-sm text-white/70">
                  <span className="flex items-center gap-1">
                    <Icons.Calendar className="w-4 h-4" />
                    {formatDate(productData?.created_at)}
                  </span>
                  <span className="flex items-center gap-1">
                    <Icons.User className="w-4 h-4" />
                    {product.author?.name || "Chưa có tác giả"}
                  </span>
                </div>
              </div>
              <div className="flex items-center gap-2">
                <div className="px-4 py-2 bg-white/20 backdrop-blur rounded-full">
                  <span className="text-white text-sm font-medium flex items-center gap-2">
                    <Icons.Briefcase className="w-4 h-4" />
                    {productData.major_name || "Chưa có ngành"}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Left Column */}
          <div className="lg:col-span-2 space-y-8">
            {/* Gallery ảnh */}
            {(productData?.thumbnail || images.length > 0) && (
              <div className="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div className="p-6 border-b border-gray-100">
                  <h2
                    className={`text-lg font-bold flex items-center gap-2 ${theme.text}`}
                  >
                    <Icons.Image className="w-5 h-5" />
                    Hình ảnh sản phẩm
                  </h2>
                </div>
                <div className="p-6">
                  <div
                    className="aspect-video bg-gray-100 rounded-xl overflow-hidden mb-4 cursor-pointer group relative"
                    onClick={() => openViewer(productData?.thumbnail)}
                  >
                    <img
                      src={productData?.thumbnail}
                      alt={productData?.title}
                      className="w-full h-full object-contain transition-transform duration-300 group-hover:scale-105"
                    />
                    <div className="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                      <Icons.ZoomIn />
                    </div>
                  </div>
                  {images.length > 0 && (
                    <div className="grid grid-cols-5 gap-3">
                      {images.map((img) => (
                        <button
                          key={img.product_image_id}
                          onClick={() => openViewer(img.image_url)}
                          className="group relative aspect-square rounded-lg overflow-hidden border-2 border-gray-200 hover:border-blue-500 transition"
                        >
                          <img
                            src={img.image_url}
                            alt=""
                            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                          />
                          <div className="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors flex items-center justify-center">
                            <Icons.ZoomInSmall />
                          </div>
                        </button>
                      ))}
                    </div>
                  )}
                </div>
              </div>
            )}

            {/* Files */}
            {product.files && product.files.length > 0 && (
              <div className="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div className="p-6 border-b border-gray-100">
                  <h2
                    className={`text-lg font-bold flex items-center gap-2 ${theme.text}`}
                  >
                    <Icons.FileText className="w-5 h-5" />
                    Tệp đính kèm
                  </h2>
                </div>
                <div className="p-6">
                  <div className="grid gap-3">
                    {product.files.map((file) => {
                      const fileName =
                        file.file_url?.split("/").pop() || "Tệp tin";
                      const fileExt =
                        file.file_type?.toUpperCase() ||
                        fileName.split(".").pop().toUpperCase();
                      let fileType = "FILE";
                      if (["PDF"].includes(fileExt)) fileType = "PDF";
                      if (
                        ["JPG", "JPEG", "PNG", "GIF", "WEBP"].includes(fileExt)
                      )
                        fileType = "IMAGE";
                      if (["MP4", "MOV", "AVI", "MKV"].includes(fileExt))
                        fileType = "VIDEO";

                      return (
                        <a
                          key={file.product_file_id}
                          href={file.file_url}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:shadow-md transition-all group"
                        >
                          <div
                            className={`w-12 h-12 ${theme.light} rounded-xl flex items-center justify-center`}
                          >
                            <Icons.FileIcon type={fileType} />
                          </div>
                          <div className="flex-1">
                            <p className="font-medium text-gray-900 group-hover:underline">
                              {fileName}
                            </p>
                            <p className="text-xs text-gray-500">
                              Nhấn để tải xuống
                            </p>
                          </div>
                          <Icons.Download
                            className={`w-5 h-5 ${theme.text} opacity-60 group-hover:opacity-100 transition`}
                          />
                        </a>
                      );
                    })}
                  </div>
                </div>
              </div>
            )}

            {/* Nhận xét */}
            <div className="bg-white rounded-2xl shadow-lg overflow-hidden">
              <div className="p-6 border-b border-gray-100">
                <h2
                  className={`text-lg font-bold flex items-center gap-2 ${theme.text}`}
                >
                  <Icons.MessageCircle className="w-5 h-5" />
                  Nhận xét từ giảng viên
                </h2>
              </div>
              <div className="p-6">
                <div className={`${theme.light} rounded-xl p-4 mb-6`}>
                  <textarea
                    rows={3}
                    value={reviewComment}
                    onChange={handleReviewCommentChange}
                    placeholder="Viết nhận xét về sản phẩm này..."
                    className="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none bg-white"
                  />
                  <div className="flex justify-end mt-3">
                    <button
                      onClick={handleSubmitReview}
                      disabled={isSubmitting}
                      className={`px-5 py-2 ${theme.button} text-white rounded-lg transition text-sm font-medium flex items-center gap-2 disabled:opacity-50 shadow-md hover:shadow-lg`}
                    >
                      <Icons.Send className="w-4 h-4" />
                      Gửi nhận xét
                    </button>
                  </div>
                </div>

                {reviews?.length > 0 ? (
                  <div>
                    <div className="flex items-center gap-2 mb-4">
                      <div className="h-px flex-1 bg-gray-200"></div>
                      <span className="text-xs text-gray-400">
                        {reviews.length} nhận xét
                      </span>
                      <div className="h-px flex-1 bg-gray-200"></div>
                    </div>
                    <div className="space-y-4">
                      {displayedReviews.map((review) => (
                        <div key={review?.review_id} className="flex gap-3">
                          <div className="flex-shrink-0">
                            <div
                              className={`w-10 h-10 bg-gradient-to-r ${theme.gradient} rounded-full flex items-center justify-center text-white font-semibold text-sm shadow-md`}
                            >
                              {review.teacher?.fullname?.charAt(0) || "G"}
                            </div>
                          </div>
                          <div className="flex-1">
                            <div className="bg-gray-50 rounded-xl p-3">
                              <div className="flex items-center justify-between mb-1 flex-wrap gap-2">
                                <p className="font-medium text-gray-900 text-sm flex items-center gap-1">
                                  <Icons.User className="w-3 h-3" />
                                  {review.teacher?.fullname ||
                                    review.teacher_name ||
                                    "Giảng viên"}
                                </p>
                                <p className="text-xs text-gray-400 flex items-center gap-1">
                                  <Icons.Clock className="w-3 h-3" />
                                  {formatDate(review.created_at)}
                                </p>
                              </div>
                              <p className="text-gray-700 text-sm">
                                {review.comment}
                              </p>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                    <div className="flex justify-center gap-4 mt-4">
                      {canShowMore && (
                        <button
                          onClick={showMore}
                          className={`${theme.text} hover:opacity-80 text-sm font-medium flex items-center gap-1 transition`}
                        >
                          <Icons.ChevronDown className="w-4 h-4" />
                          Xem thêm
                        </button>
                      )}
                      {canCollapse && (
                        <button
                          onClick={collapse}
                          className="text-gray-500 hover:text-gray-700 text-sm font-medium flex items-center gap-1 transition"
                        >
                          <Icons.ChevronUp className="w-4 h-4" />
                          Thu gọn
                        </button>
                      )}
                    </div>
                  </div>
                ) : (
                  <div className="text-center py-8">
                    <Icons.MessageCircle className="w-16 h-16 text-gray-200 mx-auto mb-3" />
                    <p className="text-gray-500 text-sm">
                      Chưa có nhận xét nào
                    </p>
                    <p className="text-gray-400 text-xs mt-1">
                      Hãy là người đầu tiên nhận xét!
                    </p>
                  </div>
                )}
              </div>
            </div>
          </div>
          {/* ✅ đóng Left Column */}

          {/* Right Column */}
          <div className="space-y-6">
            {/* Author Info */}
            <div className="bg-white rounded-2xl shadow-lg p-6">
              <h2
                className={`text-lg font-bold mb-4 flex items-center gap-2 ${theme.text}`}
              >
                <Icons.User className="w-5 h-5" />
                Thông tin tác giả
              </h2>
              <div className="flex items-center gap-4 mb-4">
                <div
                  className={`w-16 h-16 bg-gradient-to-r ${theme.gradient} rounded-full flex items-center justify-center text-white text-xl font-bold shadow-lg`}
                >
                  {product.author?.name?.charAt(0) || "U"}
                </div>
                <div>
                  <p className="font-semibold text-gray-900">
                    {product.author?.name || "Chưa có tên"}
                  </p>
                  <p className="text-sm text-gray-500 flex items-center gap-1">
                    <Icons.Mail className="w-3 h-3" />
                    {product.author?.email || "Chưa có email"}
                  </p>
                  {product.author?.mssv && (
                    <p className="text-xs text-gray-400 flex items-center gap-1 mt-1">
                      <Icons.StudentId className="w-3 h-3" />
                      MSSV: {product.author?.mssv}
                    </p>
                  )}
                </div>
              </div>
              <div className="pt-3 border-t border-gray-100">
                <div className="flex justify-between text-sm">
                  <span className="text-gray-500">Chuyên ngành</span>
                  <span className="font-medium text-gray-900">
                    {productData.major_name || "—"}
                  </span>
                </div>
                <div className="flex justify-between text-sm mt-2">
                  <span className="text-gray-500">Danh mục</span>
                  <span className="font-medium text-gray-900">
                    {productData.category_name || "—"}
                  </span>
                </div>
              </div>
            </div>

            {/* Tags */}
            {product.tags && product.tags.length > 0 && (
              <div className="bg-white rounded-2xl shadow-lg p-6">
                <h2
                  className={`text-lg font-bold mb-4 flex items-center gap-2 ${theme.text}`}
                >
                  <Icons.Tag className="w-5 h-5" />
                  Công nghệ sử dụng
                </h2>
                <div className="flex flex-wrap gap-2">
                  {product.tags.map((tag) => (
                    <span
                      key={tag.tag_id || tag.id}
                      className={`px-3 py-1.5 bg-gradient-to-r ${theme.gradient} text-white rounded-full text-xs font-medium shadow-sm flex items-center gap-1`}
                    >
                      <Icons.Tag className="w-3 h-3" />#
                      {tag.tag_name || tag.name}
                    </span>
                  ))}
                </div>
              </div>
            )}

            {/* Links */}
            {(productData?.demo_link || productData?.github_link) && (
              <div className="bg-white rounded-2xl shadow-lg p-6">
                <h2
                  className={`text-lg font-bold mb-4 flex items-center gap-2 ${theme.text}`}
                >
                  <Icons.Link className="w-5 h-5" />
                  Liên kết dự án
                </h2>
                <div className="space-y-3">
                  {productData?.github_link && (
                    <a
                      href={productData?.github_link}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition group"
                    >
                      <Icons.Github />
                      <span className="flex-1 text-sm text-gray-700 group-hover:underline">
                        GitHub Repository
                      </span>
                      <Icons.ExternalLink className="text-gray-400 group-hover:text-gray-600" />
                    </a>
                  )}
                  {productData?.demo_link && (
                    <a
                      href={productData?.demo_link}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition group"
                    >
                      <Icons.Monitor />
                      <span className="flex-1 text-sm text-gray-700 group-hover:underline">
                        Xem Demo
                      </span>
                      <Icons.ExternalLink className="text-gray-400 group-hover:text-gray-600" />
                    </a>
                  )}
                </div>
              </div>
            )}
            {/* ✅ đóng Links section */}

            {/* Thông tin duyệt */}
            <div className="bg-white rounded-2xl shadow-lg p-6">
              <h2
                className={`text-lg font-bold mb-4 flex items-center gap-2 ${theme.text}`}
              >
                <Icons.CheckCircle className="w-5 h-5" />
                Thông tin duyệt
              </h2>
              <div className="space-y-3">
                <div className="flex justify-between py-2 border-b border-gray-100">
                  <span className="text-gray-500 text-sm">Người duyệt</span>
                  <span className="font-medium text-gray-900 text-sm">
                    {user?.name || "Chưa duyệt"}
                  </span>
                </div>
                <div className="flex justify-between py-2 border-b border-gray-100">
                  <span className="text-gray-500 text-sm">Ngày duyệt</span>
                  <span className="font-medium text-gray-900 text-sm">
                    {formatDate(productData?.approved_at) || "—"}
                  </span>
                </div>
                <div className="flex justify-between py-2">
                  <span className="text-gray-500 text-sm">Trạng thái</span>
                  <span
                    className={`px-2 py-0.5 rounded-full text-xs font-medium ${getStatusColor(productData?.status)}`}
                  >
                    {getStatusText(productData?.status)}
                  </span>
                </div>
              </div>
            </div>
          </div>
          {/* ✅ đóng Right Column */}
        </div>
        {/* ✅ đóng grid */}

        {/* Action Buttons - Sticky */}
        {productData?.status === "pending" && (
          <div className="sticky bottom-4 mt-8 bg-white rounded-2xl shadow-xl p-4 border border-gray-200 backdrop-blur-sm bg-white/95">
            <div className="flex gap-4 justify-end">
              <button
                onClick={handleReject}
                disabled={loading_reject || isSubmitting}
                className="px-6 py-2.5 bg-red-600 text-white rounded-xl hover:bg-red-700 transition font-semibold shadow-md flex items-center gap-2 disabled:opacity-50"
              >
                <Icons.XCircle className="w-5 h-5" />
                {loading_reject ? "Đang từ chối..." : "Từ chối"}
              </button>
              <button
                onClick={handleApprove}
                disabled={isSubmitting || loading_approve}
                className={`px-6 py-2.5 ${theme.button} text-white rounded-xl transition font-semibold shadow-md flex items-center gap-2 disabled:opacity-50`}
              >
                <Icons.CheckCircle className="w-5 h-5" />
                {loading_approve ? "Đang duyệt..." : "Duyệt sản phẩm"}
              </button>
              <button
                onClick={handleCompare}
                disabled={loadingCompare}
                className="px-6 py-2.5 bg-blue-600 text-white rounded-xl flex items-center gap-2 disabled:opacity-50"
              >
                {loadingCompare ? (
                  <>
                    <span className="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full" />
                    Đang kiểm tra...
                  </>
                ) : (
                  <>
                    <Icons.Zap className="w-5 h-5" />
                    So sánh trùng
                  </>
                )}
              </button>
            </div>
          </div>
        )}
      </div>
      {/* ✅ đóng max-w-6xl */}

      {isSubmitting && (
        <LoadingSpinner fullScreen={true} message="Đang xử lý..." size="md" />
      )}
    </div>
  );
};

export default TeacherProductDetailScreen;
