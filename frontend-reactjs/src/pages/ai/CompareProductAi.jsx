import React, { useEffect, useState } from "react";
import { useLocation } from "react-router-dom";
import { formatDate } from "../../utils/formatDate";
import { Icons } from "../../components/common/Icon";
import BackButton from "../../components/common/BackButton";
import useMajorName from "../../hooks/common/useMajorName";
import { getStatusBadge } from "../../utils/getStatusBadge";
import useCompareProduct from "../../hooks/ai/useCompareProduct";
import { toast } from "react-toastify";
export default function CompareProductAi() {
  const location = useLocation();
  const [productData, setProductData] = useState(null);
  const [selectedMatch, setSelectedMatch] = useState(null);
  const [allMatches, setAllMatches] = useState([]);

  // Lấy data từ location state hoặc từ API response
  const { currentProduct, matches: initialMatches } = location.state || {};

  const { majorName } = useMajorName(currentProduct?.major_id);

  const { checkCompareProductImages, loadingImageCompare, errorImageCompare } =
    useCompareProduct(currentProduct?.product_id);

  const handleCheckSelectedImages = async () => {
    const matchProductId = selectedMatch?.product_id;

    if (!matchProductId || loadingImageCompare) return;

    try {
      const data = await checkCompareProductImages(
        matchProductId,
        selectedMatch.ai_similarity ?? 0,
      );

      if (!data) return;

      const imageResultFields = {
        image_checked: true,
        images: Array.isArray(data.product_b_images)
          ? data.product_b_images
          : selectedMatch.images,
        image_similarity: data.image_similarity ?? 0,
        image_level: data.image_level,
        image_level_code: data.image_level_code,
        image_reason: data.image_reason,
        duplicate_images: data.duplicate_images || [],
        duplicate_image_count: data.duplicate_image_count ?? 0,
        has_duplicate_images: data.has_duplicate_images ?? false,
        ai_unavailable: data.ai_unavailable ?? false,
        overall_similarity:
          data.overall_similarity ?? selectedMatch.ai_similarity ?? 0,
        overall_level: data.overall_level,
        overall_reason: data.overall_reason,
      };

      setSelectedMatch((current) =>
        current?.product_id === matchProductId
          ? { ...current, ...imageResultFields }
          : current,
      );

      setAllMatches((prev) =>
        prev.map((item) =>
          item.product_id === matchProductId
            ? { ...item, ...imageResultFields }
            : item,
        ),
      );

      if (Array.isArray(data.product_a_images)) {
        setProductData((current) =>
          current ? { ...current, images: data.product_a_images } : current,
        );
      }

      const imageReason =
        data.image_reason || data.message || "Đã kiểm tra hình ảnh.";
      const duplicateCount = data.duplicate_image_count ?? 0;
      const shouldWarn =
        data.has_duplicate_images ||
        duplicateCount > 0 ||
        /ai chưa thể|không thể|lỗi/i.test(imageReason);

      if (shouldWarn) {
        toast.warning(
          duplicateCount > 0
            ? `Phát hiện ${duplicateCount} ảnh nghi trùng. ${imageReason}`
            : imageReason,
          { toastId: `compare-image-${matchProductId}` },
        );
      } else {
        toast.success(imageReason, {
          toastId: `compare-image-${matchProductId}`,
        });
      }
    } catch {
      // error đã được hook lưu vào errorImageCompare
    }
  };

  useEffect(() => {
    // Xử lý data khi có currentProduct
    if (currentProduct) {
      setProductData({
        ...currentProduct,
        majorName,
      });
    }

    // Xử lý matches - có thể là object {approved: [], unapproved: []} hoặc array
    let combined = [];

    if (Array.isArray(initialMatches)) {
      combined = initialMatches;
    } else if (initialMatches?.approved || initialMatches?.unapproved) {
      // Gộp cả approved và unapproved lại
      combined = [
        ...(initialMatches.approved || []),
        ...(initialMatches.unapproved || []),
      ];
    }

    setAllMatches(combined);
    setSelectedMatch((current) => {
      if (combined.length === 0) return null;

      if (!current?.product_id) return combined[0];

      return (
        combined.find((item) => item.product_id === current.product_id) ||
        combined[0]
      );
    });
  }, [currentProduct, majorName, initialMatches]);

  const _getComparisonFields = (product, majorName) => {
    const majorLower = (majorName || "").toLowerCase();

    // Phát hiện AI projects (bao gồm "nhân tạo" + "trí tuệ")
    if (
      majorLower.includes("ai") ||
      (majorLower.includes("nhân tạo") && majorLower.includes("trí")) ||
      majorLower.includes("artificial") ||
      product?.model_used
    ) {
      return [
        { key: "model_used", label: "Model", getValue: (p) => p.model_used },
        { key: "framework", label: "Framework", getValue: (p) => p.framework },
        { key: "language", label: "Ngôn ngữ", getValue: (p) => p.language },
        {
          key: "dataset_used",
          label: "Dataset",
          getValue: (p) => p.dataset_used,
        },
        {
          key: "accuracy_score",
          label: "Độ chính xác",
          getValue: (p) => p.accuracy_score,
        },
        {
          key: "ai_similarity",
          label: "Độ tương đồng AI",
          getValue: (p) => p.ai_similarity,
        },
        { key: "ai_level", label: "Cấp độ AI", getValue: (p) => p.ai_level },
      ];
    } else if (majorLower.includes("cntt") || majorLower.includes("computer")) {
      return [
        { key: "framework", label: "Framework", getValue: (p) => p.framework },
        { key: "database", label: "Database", getValue: (p) => p.database },
        { key: "language", label: "Ngôn ngữ", getValue: (p) => p.language },
        { key: "api_type", label: "API", getValue: (p) => p.api_type },
      ];
    } else if (
      majorLower.includes("multimedia") ||
      majorLower.includes("mmt")
    ) {
      return [
        { key: "format", label: "Định dạng", getValue: (p) => p.format },
        { key: "tools_used", label: "Công cụ", getValue: (p) => p.tools_used },
        {
          key: "resolution",
          label: "Độ phân giải",
          getValue: (p) => p.resolution,
        },
        { key: "file_size", label: "Dung lượng", getValue: (p) => p.file_size },
      ];
    }

    // Fallback: nếu không có major name, dùng các fields có sẵn
    return [
      { key: "model_used", label: "Model", getValue: (p) => p.model_used },
      { key: "framework", label: "Framework", getValue: (p) => p.framework },
      { key: "language", label: "Ngôn ngữ", getValue: (p) => p.language },
      {
        key: "dataset_used",
        label: "Dataset",
        getValue: (p) => p.dataset_used,
      },
      {
        key: "accuracy_score",
        label: "Độ chính xác",
        getValue: (p) => p.accuracy_score,
      },
    ];
  };

  const getFullComparisonFields = (product, majorName) => {
    const majorLower = (majorName || product?.major_name || "").toLowerCase();

    if (
      majorLower.includes("ai") ||
      majorLower.includes("artificial") ||
      product?.model_used
    ) {
      return [
        { key: "title", label: "Tiêu đề", getValue: (p) => p.title },
        { key: "model_used", label: "Model", getValue: (p) => p.model_used },
        { key: "framework", label: "Framework", getValue: (p) => p.framework },
        { key: "language", label: "Ngôn ngữ", getValue: (p) => p.language },
        {
          key: "dataset_used",
          label: "Dataset",
          getValue: (p) => p.dataset_used,
        },
        {
          key: "accuracy_score",
          label: "Độ chính xác",
          getValue: (p) => p.accuracy_score,
        },
        {
          key: "ai_similarity",
          label: "Độ tương đồng AI",
          getValue: (p) => p.ai_similarity,
        },
        { key: "ai_level", label: "Cấp độ AI", getValue: (p) => p.ai_level },
      ];
    }

    if (
      majorLower.includes("cntt") ||
      majorLower.includes("computer") ||
      majorLower.includes("công nghệ thông tin") ||
      product?.programming_language ||
      product?.database_used
    ) {
      return [
        { key: "title", label: "Tiêu đề", getValue: (p) => p.title },
        {
          key: "programming_language",
          label: "Ngôn ngữ lập trình",
          getValue: (p) => p.programming_language,
        },
        { key: "framework", label: "Framework", getValue: (p) => p.framework },
        {
          key: "database_used",
          label: "Cơ sở dữ liệu",
          getValue: (p) => p.database_used,
        },
      ];
    }

    if (
      majorLower.includes("multimedia") ||
      majorLower.includes("mmt") ||
      majorLower.includes("đa phương tiện") ||
      product?.simulation_tool ||
      product?.network_protocol ||
      product?.topology_type
    ) {
      return [
        { key: "title", label: "Tiêu đề", getValue: (p) => p.title },
        {
          key: "simulation_tool",
          label: "Công cụ mô phỏng",
          getValue: (p) => p.simulation_tool,
        },
        {
          key: "network_protocol",
          label: "Giao thức mạng",
          getValue: (p) => p.network_protocol,
        },
        {
          key: "topology_type",
          label: "Loại hệ thống",
          getValue: (p) => p.topology_type,
        },
        {
          key: "config_file",
          label: "File config",
          getValue: (p) => p.config_file,
        },
      ];
    }

    if (
      majorLower.includes("graphics") ||
      majorLower.includes("graphic") ||
      majorLower.includes("đồ họa") ||
      product?.design_type ||
      product?.tools_used
    ) {
      return [
        { key: "title", label: "Tiêu đề", getValue: (p) => p.title },
        {
          key: "design_type",
          label: "Loại ấn phẩm",
          getValue: (p) => p.design_type,
        },
        {
          key: "tools_used",
          label: "Công cụ sử dụng",
          getValue: (p) => p.tools_used,
        },
        {
          key: "behance_link",
          label: "Link Behance",
          getValue: (p) => p.behance_link,
        },
      ];
    }

    return [
      { key: "title", label: "Tiêu đề", getValue: (p) => p.title },
      { key: "description", label: "Mô tả", getValue: (p) => p.description },
    ];
  };

  const calculateOverlap = (product1, product2, fields) => {
    let matchCount = 0;
    const details = [];

    fields.forEach((field) => {
      const value1 = field.getValue(product1);
      const value2 = field.getValue(product2);
      const isMatch =
        value1 &&
        value2 &&
        String(value1).toLowerCase().trim() ===
          String(value2).toLowerCase().trim();

      if (isMatch) matchCount++;
      details.push({
        label: field.label,
        key: field.key,
        value1: value1 || "N/A",
        value2: value2 || "N/A",
        isMatch,
      });
    });

    const percentage =
      fields.length > 0 ? Math.round((matchCount / fields.length) * 100) : 0;
    return { matchCount, totalCount: fields.length, percentage, details };
  };

  if (!currentProduct || !productData) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
        <div className="max-w-7xl mx-auto px-4">
          <BackButton />
          <div className="bg-red-50 border border-red-200 rounded-lg p-4 flex items-center gap-3">
            <Icons.AlertCircle className="text-red-600" />
            <p className="text-red-700">Không có dữ liệu sản phẩm để so sánh</p>
          </div>
        </div>
      </div>
    );
  }

  const comparisonFields = getFullComparisonFields(productData, majorName);
  const overlap = selectedMatch
    ? calculateOverlap(productData, selectedMatch, comparisonFields)
    : null;

  //
  const normalizeImageItem = (item) => {
    if (!item) return null;

    if (typeof item === "string") {
      return item;
    }

    return (
      item.image_url ||
      item.url ||
      item.secure_url ||
      item.thumbnail ||
      item.path ||
      null
    );
  };

  const getProductImages = (product) => {
    if (!product) return [];

    const rawImages = [
      product.thumbnail,
      ...(Array.isArray(product.images) ? product.images : []),
      ...(Array.isArray(product.product_images) ? product.product_images : []),
      ...(Array.isArray(product.gallery) ? product.gallery : []),
      ...(Array.isArray(product.gallery_images) ? product.gallery_images : []),
    ];

    return Array.from(
      new Set(rawImages.map(normalizeImageItem).filter(Boolean)),
    );
  };

  const normalizeImageUrl = (url) => {
    return String(url || "")
      .split("?")[0]
      .trim()
      .replace(/\/$/, "")
      .toLowerCase();
  };

  const isSameImageUrl = (imageUrl, otherImages) => {
    const current = normalizeImageUrl(imageUrl);

    return (otherImages || []).some(
      (other) => normalizeImageUrl(other) === current,
    );
  };

  const getImageAiDuplicate = (src, duplicateImages, side) => {
    const current = normalizeImageUrl(src);
    const key = side === "a" ? "image_a" : "image_b";

    return (duplicateImages || []).find(
      (item) => normalizeImageUrl(item?.[key]) === current,
    );
  };

  const isAiDuplicateImage = (src, duplicateImages, side) => {
    return Boolean(getImageAiDuplicate(src, duplicateImages, side));
  };

  const getImageDuplicateBadge = (src, otherImages, duplicateImages, side) => {
    const urlDuplicated = isSameImageUrl(src, otherImages);
    const aiDuplicated = isAiDuplicateImage(src, duplicateImages, side);

    if (urlDuplicated && aiDuplicated) {
      return {
        text: "Trùng URL + AI",
        className: "bg-red-900 text-white",
      };
    }

    if (urlDuplicated) {
      return {
        text: "Trùng URL",
        className: "bg-red-800 text-white",
      };
    }

    if (aiDuplicated) {
      return {
        text: "AI nghi trùng",
        className: "bg-orange-600 text-white",
      };
    }

    return null;
  };

  const productAImages = getProductImages(productData);
  const productBImages = getProductImages(selectedMatch);
  const selectedDuplicateImages = selectedMatch?.duplicate_images || [];

  const urlDuplicatedImageCount = productAImages.filter((img) =>
    isSameImageUrl(img, productBImages),
  ).length;

  const suspectedDuplicateImageCount =
    selectedMatch?.duplicate_image_count ?? selectedDuplicateImages.length;
  //
  const GalleryColumn = ({
    title,
    subtitle,
    images,
    otherImages,
    duplicateImages,
    side,
    color,
  }) => {
    return (
      <div className="rounded-xl border border-gray-200 bg-gray-50 p-4">
        <div className="mb-4 flex items-center justify-between gap-3">
          <div>
            <h3 className="font-bold text-gray-900">{title}</h3>
            <p className="text-xs text-gray-500 line-clamp-1">{subtitle}</p>
          </div>

          <span className="rounded-full bg-white px-3 py-1 text-xs font-semibold text-gray-700">
            {images.length} ảnh
          </span>
        </div>

        {images.length > 0 ? (
          <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
            {images.map((src, index) => {
              const badge = getImageDuplicateBadge(
                src,
                otherImages,
                duplicateImages,
                side,
              );
              const aiDuplicate = getImageAiDuplicate(
                src,
                duplicateImages,
                side,
              );
              const duplicated = Boolean(badge);

              return (
                <div
                  key={`${src}-${index}`}
                  className={`relative overflow-hidden rounded-lg border bg-white ${
                    duplicated
                      ? "border-red-700 ring-2 ring-red-300"
                      : "border-gray-200"
                  }`}
                  title={aiDuplicate?.reason || ""}
                >
                  <a href={src} target="_blank" rel="noreferrer">
                    <img
                      src={src}
                      alt={`${title} ảnh ${index + 1}`}
                      className="h-32 w-full object-cover transition hover:scale-105"
                      onError={(e) => {
                        e.currentTarget.src =
                          "https://via.placeholder.com/800x450?text=No+Image";
                      }}
                    />
                  </a>

                  <div className="absolute left-2 top-2 rounded-full bg-black/70 px-2 py-1 text-xs font-semibold text-white">
                    #{index + 1}
                  </div>

                  {index === 0 && (
                    <div
                      className={`absolute right-2 top-2 rounded-full px-2 py-1 text-xs font-semibold text-white ${color}`}
                    >
                      Thumbnail
                    </div>
                  )}

                  {badge && (
                    <div
                      className={`absolute bottom-2 left-2 right-2 rounded-md px-2 py-1 text-center text-xs font-bold ${badge.className}`}
                    >
                      {badge.text}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        ) : (
          <div className="rounded-lg border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500">
            Chưa có ảnh gallery
          </div>
        )}
      </div>
    );
  };
  //

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Header */}
        <div className="mb-8">
          <BackButton />
          <h1 className="text-3xl font-bold text-gray-900 mb-2">
            So sánh sản phẩm
          </h1>
          <p className="text-gray-600">
            So sánh chi tiết giữa sản phẩm của bạn và sản phẩm khác
          </p>
        </div>

        {/* Product Selector - Chỉ hiển thị nếu có nhiều hơn 1 sản phẩm */}
        {allMatches.length > 1 && (
          <div className="mb-6 bg-white rounded-xl shadow p-4">
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Chọn sản phẩm để so sánh:
            </label>
            <select
              className="w-full md:w-96 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              value={selectedMatch?.product_id || ""}
              onChange={(e) => {
                const selected = allMatches.find(
                  (m) => String(m.product_id) === e.target.value,
                );
                if (selected) setSelectedMatch(selected);
              }}
            >
              {allMatches.map((product) => (
                <option key={product.product_id} value={product.product_id}>
                  {product.title} - {product.fullname} (
                  {getStatusBadge(product.status)?.label})
                </option>
              ))}
            </select>
          </div>
        )}

        {/* Hiển thị số lượng sản phẩm tìm thấy - ĐÃ SỬA LỖI */}
        {allMatches.length > 0 && (
          <div className="mb-4 flex gap-2 flex-wrap">
            <span className="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">
              Tổng số: {allMatches.length} sản phẩm
            </span>
            <span className="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm">
              Đã duyệt: {initialMatches?.approved?.length || 0}
            </span>
            <span className="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm">
              Chờ duyệt: {initialMatches?.unapproved?.length || 0}
            </span>
            <button
              type="button"
              onClick={handleCheckSelectedImages}
              disabled={
                loadingImageCompare ||
                !selectedMatch?.product_id ||
                selectedMatch?.image_checked
              }
              className="rounded-lg bg-orange-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
              {loadingImageCompare
                ? "Đang kiểm tra ảnh..."
                : selectedMatch?.image_checked
                  ? "Đã kiểm tra ảnh"
                  : "Kiểm tra hình ảnh"}
            </button>

            {errorImageCompare && (
              <p className="text-sm text-red-700 mt-2">{errorImageCompare}</p>
            )}

            {selectedMatch?.image_checked && !errorImageCompare && (
              <p
                className={`w-full text-sm mt-1 ${
                  /ai chưa thể|không thể|lỗi/i.test(
                    selectedMatch.image_reason || "",
                  )
                    ? "text-orange-700"
                    : (selectedMatch.duplicate_image_count ?? 0) > 0
                      ? "text-red-700"
                      : "text-emerald-700"
                }`}
              >
                {selectedMatch.image_reason ||
                  selectedMatch.overall_reason ||
                  "Đã kiểm tra hình ảnh."}
              </p>
            )}
          </div>
        )}

        {allMatches.length > 0 && (
          <div className="mb-8 bg-white rounded-xl shadow p-4">
            <div className="flex items-center justify-between gap-3 mb-4">
              <h2 className="text-lg font-bold text-gray-900">
                Danh sách sản phẩm phát hiện
              </h2>
              <span className="text-sm text-gray-500">
                Hiển thị {allMatches.length} sản phẩm
              </span>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
              {allMatches.map((product) => {
                const statusBadge = getStatusBadge(product.status);
                const isSelected =
                  selectedMatch?.product_id === product.product_id;
                const duplicateFields = product.duplicate_fields || [];

                return (
                  <button
                    key={product.product_id}
                    type="button"
                    onClick={() => setSelectedMatch(product)}
                    className={`text-left rounded-lg border p-4 transition ${
                      isSelected
                        ? "border-red-800 bg-red-50 shadow-sm"
                        : "border-gray-200 bg-white hover:border-red-400 hover:bg-red-50/40"
                    }`}
                  >
                    <div className="flex items-start justify-between gap-3">
                      <h3 className="font-semibold text-gray-900 line-clamp-2">
                        {product.title}
                      </h3>
                      <span
                        className={`shrink-0 px-2 py-1 rounded-full text-xs font-semibold ${statusBadge?.bg} ${statusBadge?.text}`}
                      >
                        {statusBadge?.label || product.status}
                      </span>
                    </div>

                    <p className="text-sm text-gray-600 mt-2">
                      {product.fullname || "Chưa có tác giả"}
                    </p>

                    <div className="mt-3 flex items-center gap-2 flex-wrap">
                      <span className="px-2 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold">
                        AI nội dung: {product.ai_similarity ?? 0}%
                      </span>
                      <span className="px-2 py-1 rounded-full bg-orange-50 text-orange-700 text-xs font-semibold">
                        AI hình ảnh: {product.image_similarity ?? 0}%
                      </span>
                      <span
                        className={`px-2 py-1 rounded-full text-xs font-semibold ${
                          (product.overall_similarity ?? 0) >= 85
                            ? "bg-red-800 text-white"
                            : "bg-emerald-50 text-emerald-700"
                        }`}
                      >
                        Tổng hợp:{" "}
                        {product.overall_similarity ??
                          product.ai_similarity ??
                          0}
                        %
                      </span>
                      <span
                        className={`px-2 py-1 rounded-full text-xs font-semibold ${
                          (product.duplicate_image_count ?? 0) > 0
                            ? "bg-red-800 text-white"
                            : "bg-gray-100 text-gray-600"
                        }`}
                      >
                        Ảnh nghi trùng: {product.duplicate_image_count ?? 0}
                      </span>
                      <span
                        className={`px-2 py-1 rounded-full text-xs font-semibold ${
                          duplicateFields.length > 0
                            ? "bg-red-800 text-white"
                            : "bg-gray-100 text-gray-600"
                        }`}
                      >
                        Trùng {duplicateFields.length} trường
                      </span>
                    </div>

                    <p className="text-xs text-gray-600 mt-3 line-clamp-2">
                      {(product.image_checked && product.image_reason) ||
                        product.overall_reason ||
                        product.duplicate_message ||
                        "Không có trường chính trùng"}
                    </p>
                  </button>
                );
              })}
            </div>
          </div>
        )}

        {/* Side by Side Comparison */}
        {selectedMatch && overlap && (
          <>
            {/* Overlap Summary Section */}
            <div
              className={`bg-gradient-to-r rounded-2xl shadow-lg p-6 mb-8 text-white ${
                overlap.matchCount > 0
                  ? "from-red-700 to-red-900"
                  : "from-blue-500 to-blue-600"
              }`}
            >
              <h2 className="text-xl font-bold mb-4">
                📊 Tổng quan độ trùng khớp
              </h2>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="bg-white/20 rounded-xl p-4 backdrop-blur-sm">
                  <p className="text-sm opacity-90">Độ trùng khớp</p>
                  <p className="text-4xl font-bold mt-1">
                    {overlap.percentage}%
                  </p>
                  <p className="text-sm mt-2">
                    {overlap.matchCount} / {overlap.totalCount} thuộc tính trùng
                    khớp
                  </p>
                </div>
                <div className="bg-white/20 rounded-xl p-4 backdrop-blur-sm col-span-2">
                  <p className="text-sm opacity-90">Chi tiết trùng khớp</p>
                  <div className="flex gap-2 mt-2 flex-wrap">
                    {overlap.details.map((detail, idx) => (
                      <span
                        key={`${detail.key}-${idx}`}
                        className={`px-2 py-1 rounded-full text-xs font-medium ${
                          detail.isMatch
                            ? "bg-red-950 text-white"
                            : "bg-gray-500 text-white"
                        }`}
                      >
                        {detail.label}: {detail.isMatch ? "✓" : "✗"}
                      </span>
                    ))}
                  </div>
                </div>
              </div>
            </div>

            {/* Two Column Comparison */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
              {/* Product A - Current Product */}
              <div className="bg-white rounded-2xl shadow-lg overflow-hidden border-2 border-blue-200">
                <div className="bg-blue-600 text-white p-4">
                  <h2 className="text-xl font-bold">Sản phẩm A</h2>
                  <p className="text-sm opacity-90">Sản phẩm của bạn</p>
                </div>
                <div className="p-6">
                  <div className="mb-4 pb-4 border-b">
                    <div className="flex items-start justify-between">
                      <div>
                        <h3 className="text-lg font-bold text-gray-900">
                          {productData.title}
                        </h3>
                        <p className="text-sm text-gray-600">
                          Tác giả: {productData.fullname}
                        </p>
                        <p className="text-xs text-gray-500 mt-1">
                          Ngày tạo: {formatDate(productData.created_at)}
                        </p>
                      </div>
                      <span className="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                        {getStatusBadge(productData.status)?.label ||
                          "Chờ duyệt"}
                      </span>
                    </div>
                  </div>
                  <div className="space-y-3">
                    {comparisonFields.map((field) => (
                      <div
                        key={field.key}
                        className="bg-gray-50 rounded-lg p-3"
                      >
                        <p className="text-xs text-gray-500 uppercase font-semibold mb-1">
                          {field.label}
                        </p>
                        <p className="text-gray-900 font-medium">
                          {field.getValue(productData) || "Chưa cập nhật"}
                        </p>
                      </div>
                    ))}
                  </div>
                  <div className="mt-4 pt-4 border-t">
                    <p className="text-sm text-gray-600">
                      {productData.description}
                    </p>
                  </div>
                </div>
              </div>

              {/* Product B - Selected Match */}
              <div className="bg-white rounded-2xl shadow-lg overflow-hidden border-2 border-purple-200">
                <div className="bg-purple-600 text-white p-4">
                  <h2 className="text-xl font-bold">Sản phẩm B</h2>
                  <p className="text-sm opacity-90">Sản phẩm so sánh</p>
                </div>
                <div className="p-6">
                  <div className="mb-4 pb-4 border-b">
                    <div className="flex items-start justify-between">
                      <div>
                        <h3 className="text-lg font-bold text-gray-900">
                          {selectedMatch.title}
                        </h3>
                        <p className="text-sm text-gray-600">
                          Tác giả: {selectedMatch.fullname}
                        </p>
                        <p className="text-xs text-gray-500 mt-1">
                          Ngày tạo: {formatDate(selectedMatch.created_at)}
                        </p>
                      </div>
                      <span
                        className={`px-3 py-1 rounded-full text-xs font-semibold ${getStatusBadge(selectedMatch.status)?.bg} ${getStatusBadge(selectedMatch.status)?.text}`}
                      >
                        {getStatusBadge(selectedMatch.status)?.label}
                      </span>
                    </div>
                  </div>
                  <div className="space-y-3">
                    {comparisonFields.map((field) => {
                      const detail = overlap.details.find(
                        (d) => d.key === field.key,
                      );
                      return (
                        <div
                          key={field.key}
                          className={`rounded-lg p-3 ${
                            detail?.isMatch
                              ? "bg-red-100 border-2 border-red-700"
                              : "bg-gray-50"
                          }`}
                        >
                          <div className="flex justify-between items-start">
                            <div className="flex-1">
                              <p className="text-xs text-gray-500 uppercase font-semibold mb-1">
                                {field.label}
                              </p>
                              <p className="text-gray-900 font-medium">
                                {field.getValue(selectedMatch) ||
                                  "Chưa cập nhật"}
                              </p>
                            </div>
                            {detail?.isMatch && (
                              <span className="text-red-800 text-sm font-bold ml-2">
                                ✓ Trùng
                              </span>
                            )}
                          </div>
                        </div>
                      );
                    })}
                  </div>
                  <div className="mt-4 pt-4 border-t">
                    <p className="text-sm text-gray-600">
                      {selectedMatch.description}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            {/* Detailed Overlap Table */}
            <div className="bg-white rounded-2xl shadow-lg overflow-hidden">
              <div className="bg-gray-800 text-white p-4">
                <h2 className="text-xl font-bold">
                  📋 So sánh chi tiết từng thuộc tính
                </h2>
                <p className="text-sm opacity-90">
                  Hiển thị tất cả {overlap.totalCount} thuộc tính được so sánh
                </p>
              </div>
              {overlap.totalCount > 0 ? (
                <div className="overflow-x-auto">
                  <table className="w-full">
                    <thead className="bg-gray-100">
                      <tr>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Thuộc tính
                        </th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Sản phẩm A (Của bạn)
                        </th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Sản phẩm B (So sánh)
                        </th>
                        <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                          Trùng khớp
                        </th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200">
                      {overlap.details.map((detail, idx) => (
                        <tr
                          key={`${detail.key}-${idx}`}
                          className={detail.isMatch ? "bg-red-100" : ""}
                        >
                          <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {detail.label}
                          </td>
                          <td className="px-6 py-4 text-sm text-gray-700">
                            {detail.value1}
                          </td>
                          <td className="px-6 py-4 text-sm text-gray-700">
                            {detail.value2}
                          </td>
                          <td className="px-6 py-4 text-center text-sm">
                            {detail.isMatch ? (
                              <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-800 text-white">
                                ✓ Trùng
                              </span>
                            ) : (
                              <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                ✗ Khác
                              </span>
                            )}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="p-8 text-center">
                  <Icons.AlertCircle className="w-12 h-12 text-gray-400 mx-auto mb-3" />
                  <p className="text-gray-600 font-medium">
                    Không có dữ liệu để so sánh
                  </p>
                  <p className="text-gray-500 text-sm mt-1">
                    Các trường dữ liệu chưa được điền đầy đủ
                  </p>
                </div>
              )}
            </div>

            {/* Thumbnail Section */}
            {/* Gallery Comparison Section */}
            {(productAImages.length > 0 || productBImages.length > 0) && (
              <div className="bg-white rounded-2xl shadow-lg overflow-hidden mt-6">
                <div className="bg-gray-800 text-white p-4">
                  <h2 className="text-xl font-bold">
                    🖼️ So sánh hình ảnh / gallery sản phẩm
                  </h2>
                  <p className="text-sm opacity-90 mt-1">
                    Hiển thị thumbnail và gallery để xem trực quan trước khi
                    duyệt hoặc từ chối.
                  </p>
                </div>

                <div className="p-6">
                  <div className="mb-4 grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div className="rounded-lg bg-blue-50 p-4">
                      <p className="text-sm font-semibold text-blue-700">
                        Sản phẩm A
                      </p>
                      <p className="text-2xl font-bold text-blue-900">
                        {productAImages.length} ảnh
                      </p>
                    </div>

                    <div className="rounded-lg bg-purple-50 p-4">
                      <p className="text-sm font-semibold text-purple-700">
                        Sản phẩm B
                      </p>
                      <p className="text-2xl font-bold text-purple-900">
                        {productBImages.length} ảnh
                      </p>
                    </div>

                    <div className="rounded-lg bg-red-50 p-4">
                      <p className="text-sm font-semibold text-red-700">
                        Ảnh trùng URL
                      </p>
                      <p className="text-2xl font-bold text-red-900">
                        {urlDuplicatedImageCount}
                      </p>
                    </div>

                    <div className="rounded-lg bg-orange-50 p-4">
                      <p className="text-sm font-semibold text-orange-700">
                        Ảnh AI nghi trùng
                      </p>
                      <p className="text-2xl font-bold text-orange-900">
                        {suspectedDuplicateImageCount}
                      </p>
                    </div>
                  </div>

                  <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <GalleryColumn
                      title="Sản phẩm A"
                      subtitle={productData.title}
                      images={productAImages}
                      otherImages={productBImages}
                      duplicateImages={selectedDuplicateImages}
                      side="a"
                      color="bg-blue-600"
                    />

                    <GalleryColumn
                      title="Sản phẩm B"
                      subtitle={selectedMatch.title}
                      images={productBImages}
                      otherImages={productAImages}
                      duplicateImages={selectedDuplicateImages}
                      side="b"
                      color="bg-purple-600"
                    />
                  </div>

                  <div className="mt-4 rounded-lg bg-yellow-50 border border-yellow-200 p-4 text-sm text-yellow-800">
                    Lưu ý: phần này đánh dấu cả ảnh trùng URL và ảnh do AI nghi
                    trùng về mặt nội dung/bố cục. Người duyệt vẫn nên mở ảnh để
                    kiểm tra lại trước khi quyết định duyệt hoặc từ chối.
                  </div>
                </div>
              </div>
            )}

            {/* AI Similarity Info (if available) */}
            {selectedMatch.ai_similarity !== null &&
              selectedMatch.ai_similarity !== undefined && (
                <div className="bg-white rounded-2xl shadow-lg overflow-hidden mt-6">
                  <div className="bg-gray-800 text-white p-4">
                    <h2 className="text-xl font-bold">🤖 Đánh giá từ AI</h2>
                  </div>
                  <div className="p-6">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div
                        className={`rounded-lg p-4 ${
                          selectedMatch.ai_similarity >= 85
                            ? "bg-red-100 border-2 border-red-700"
                            : "bg-blue-50"
                        }`}
                      >
                        <p className="text-sm font-semibold text-blue-700">
                          AI nội dung
                        </p>
                        <p className="text-2xl font-bold text-blue-900">
                          {selectedMatch.ai_similarity ?? 0}%
                        </p>
                        <p className="text-xs text-gray-600 mt-1">
                          {selectedMatch.ai_level}
                        </p>
                      </div>

                      <div
                        className={`rounded-lg p-4 ${
                          (selectedMatch.image_similarity ?? 0) >= 85
                            ? "bg-red-100 border-2 border-red-700"
                            : "bg-orange-50"
                        }`}
                      >
                        <p className="text-sm font-semibold text-orange-700">
                          AI hình ảnh
                        </p>
                        <p className="text-2xl font-bold text-orange-900">
                          {selectedMatch.image_similarity ?? 0}%
                        </p>
                        <p className="text-xs text-gray-600 mt-1">
                          {selectedMatch.image_level || "Thấp"}
                        </p>
                      </div>

                      <div
                        className={`rounded-lg p-4 ${
                          (selectedMatch.overall_similarity ?? 0) >= 85
                            ? "bg-red-100 border-2 border-red-700"
                            : "bg-emerald-50"
                        }`}
                      >
                        <p className="text-sm font-semibold text-emerald-700">
                          Tổng hợp
                        </p>
                        <p className="text-2xl font-bold text-emerald-900">
                          {selectedMatch.overall_similarity ??
                            selectedMatch.ai_similarity ??
                            0}
                          %
                        </p>
                        <p className="text-xs text-gray-600 mt-1">
                          {selectedMatch.overall_level ||
                            selectedMatch.ai_level}
                        </p>
                      </div>
                    </div>

                    {(selectedMatch.ai_reason ||
                      selectedMatch.image_reason ||
                      selectedMatch.overall_reason) && (
                      <div className="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                        {selectedMatch.ai_reason && (
                          <div className="bg-gray-50 rounded-lg p-4">
                            <p className="text-sm text-gray-600 font-semibold mb-1">
                              Lý do nội dung
                            </p>
                            <p className="text-gray-700 leading-relaxed">
                              {selectedMatch.ai_reason}
                            </p>
                          </div>
                        )}

                        {selectedMatch.image_reason && (
                          <div className="bg-orange-50 rounded-lg p-4">
                            <p className="text-sm text-orange-700 font-semibold mb-1">
                              Lý do hình ảnh
                            </p>
                            <p className="text-gray-700 leading-relaxed">
                              {selectedMatch.image_reason}
                            </p>
                          </div>
                        )}

                        {selectedMatch.overall_reason && (
                          <div className="bg-emerald-50 rounded-lg p-4">
                            <p className="text-sm text-emerald-700 font-semibold mb-1">
                              Lý do tổng hợp
                            </p>
                            <p className="text-gray-700 leading-relaxed">
                              {selectedMatch.overall_reason}
                            </p>
                          </div>
                        )}
                      </div>
                    )}
                  </div>
                </div>
              )}
          </>
        )}

        {allMatches.length === 0 && (
          <div className="bg-white p-10 text-center rounded-xl">
            <Icons.Search className="w-12 h-12 text-gray-400 mx-auto mb-3" />
            <h3 className="font-semibold text-gray-900">
              Không tìm thấy sản phẩm tương tự
            </h3>
            <p className="text-gray-500 text-sm mt-1">
              Hiện tại chưa có sản phẩm nào để so sánh
            </p>
          </div>
        )}
      </div>
    </div>
  );
}
