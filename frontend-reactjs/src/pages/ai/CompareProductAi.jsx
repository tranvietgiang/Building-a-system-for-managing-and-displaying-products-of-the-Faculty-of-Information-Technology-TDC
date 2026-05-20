import React, { useEffect, useState } from "react";
import { useLocation } from "react-router-dom";
import { formatDate } from "../../utils/formatDate";
import { Icons } from "../../components/common/Icon";
import BackButton from "../../components/common/BackButton";
import useMajorName from "../../hooks/common/useMajorName";
import { getStatusBadge } from "../../utils/getStatusBadge";

export default function CompareProductAi() {
  const location = useLocation();
  const [productData, setProductData] = useState(null);
  const [selectedMatch, setSelectedMatch] = useState(null);
  const [allMatches, setAllMatches] = useState([]);

  // Lấy data từ location state hoặc từ API response
  const { currentProduct, matches: initialMatches } = location.state || {};

  const { majorName } = useMajorName(currentProduct?.major_id);

  useEffect(() => {
    // Xử lý data khi có currentProduct
    if (currentProduct) {
      setProductData({
        ...currentProduct,
        majorName,
      });
    }

    // Xử lý matches - có thể là object {approved: [], unapproved: []} hoặc array
    if (initialMatches) {
      let combined = [];
      if (Array.isArray(initialMatches)) {
        combined = initialMatches;
      } else if (initialMatches.approved || initialMatches.unapproved) {
        // Gộp cả approved và unapproved lại
        combined = [
          ...(initialMatches.approved || []),
          ...(initialMatches.unapproved || []),
        ];
      }
      setAllMatches(combined);
      if (combined.length > 0 && !selectedMatch) {
        setSelectedMatch(combined[0]);
      }
    }
  }, [currentProduct, majorName, initialMatches]);

  const getComparisonFields = (product, majorName) => {
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

  const comparisonFields = getComparisonFields(productData, majorName);
  const overlap = selectedMatch
    ? calculateOverlap(productData, selectedMatch, comparisonFields)
    : null;

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
                  (m) => m.product_id === parseInt(e.target.value),
                );
                setSelectedMatch(selected);
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
              Chưa duyệt/Từ chối: {initialMatches?.unapproved?.length || 0}
            </span>
          </div>
        )}

        {/* Side by Side Comparison */}
        {selectedMatch && overlap && (
          <>
            {/* Overlap Summary Section */}
            <div className="bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl shadow-lg p-6 mb-8 text-white">
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
                            ? "bg-green-500 text-white"
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
                              ? "bg-green-50 border border-green-200"
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
                              <span className="text-green-600 text-sm font-bold ml-2">
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
                          className={detail.isMatch ? "bg-green-50" : ""}
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
                              <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                ✓ Trùng
                              </span>
                            ) : (
                              <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
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
            {(selectedMatch.thumbnail || productData.thumbnail) && (
              <div className="bg-white rounded-2xl shadow-lg overflow-hidden mt-6">
                <div className="bg-gray-800 text-white p-4">
                  <h2 className="text-xl font-bold">🖼️ Hình ảnh sản phẩm</h2>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 p-6">
                  {productData.thumbnail && (
                    <div>
                      <p className="text-sm font-semibold text-gray-700 mb-2">
                        Sản phẩm A
                      </p>
                      <img
                        src={productData.thumbnail}
                        alt={productData.title}
                        className="w-full h-48 object-cover rounded-lg"
                        onError={(e) => {
                          e.target.src =
                            "https://via.placeholder.com/800x450?text=No+Image";
                        }}
                      />
                    </div>
                  )}
                  {selectedMatch.thumbnail && (
                    <div>
                      <p className="text-sm font-semibold text-gray-700 mb-2">
                        Sản phẩm B
                      </p>
                      <img
                        src={selectedMatch.thumbnail}
                        alt={selectedMatch.title}
                        className="w-full h-48 object-cover rounded-lg"
                        onError={(e) => {
                          e.target.src =
                            "https://via.placeholder.com/800x450?text=No+Image";
                        }}
                      />
                    </div>
                  )}
                </div>
              </div>
            )}

            {/* AI Similarity Info (if available) */}
            {selectedMatch.ai_similarity && (
              <div className="bg-white rounded-2xl shadow-lg overflow-hidden mt-6">
                <div className="bg-gray-800 text-white p-4">
                  <h2 className="text-xl font-bold">🤖 Đánh giá từ AI</h2>
                </div>
                <div className="p-6">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="bg-blue-50 rounded-lg p-4">
                      <p className="text-sm text-blue-600 font-semibold">
                        Độ tương đồng
                      </p>
                      <p className="text-2xl font-bold text-blue-700">
                        {selectedMatch.ai_similarity}%
                      </p>
                    </div>
                    <div className="bg-purple-50 rounded-lg p-4">
                      <p className="text-sm text-purple-600 font-semibold">
                        Cấp độ
                      </p>
                      <p className="text-2xl font-bold text-purple-700">
                        {selectedMatch.ai_level}
                      </p>
                    </div>
                  </div>
                  {selectedMatch.ai_reason && (
                    <div className="mt-4 bg-gray-50 rounded-lg p-4">
                      <p className="text-sm text-gray-600 font-semibold mb-1">
                        Lý do
                      </p>
                      <p className="text-gray-700 leading-relaxed">
                        {selectedMatch.ai_reason}
                      </p>
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
