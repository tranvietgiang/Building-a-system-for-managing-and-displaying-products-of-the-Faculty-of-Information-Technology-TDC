import React, { useState, useContext, useCallback, useMemo } from "react";
import UserDropdown from "../../shared/UserDropdown";
import "./style.css";
import useTitle from "../../hooks/common/useTitle";
import { AuthContext } from "../../contexts/AuthContext";
import useMajorName from "../../hooks/common/useMajorName";
import useTeacherStatistic from "../../hooks/useTeacher/useTeacherStatistic";
import useTeacherPendingApproval from "../../hooks/useTeacher/useTeacherPendingApproval";
import { useViewDetail } from "../../hooks/common/useViewDetail";
import useImageViewer from "../../shared/useImageViewer";
import { getStatusColor } from "../../components/common/getStatusColor";
import { getStatusText } from "../../components/common/getStatusText";
import { formatDate } from "../../utils/formatDate";
import { STATUS } from "../../utils/constants";
import ChatBoxAi from "../ChatBoxAi/ChatBoxAi";
import SearchAi from "../ai/SearchAi";
// ========== Extracted components ==========

const ProductCard = React.memo(
  ({ product, type, onViewDetail, onOpenImageViewer, index }) => {
    const statusColor = getStatusColor(type);
    const statusText = getStatusText(type);

    return (
      <div className="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 overflow-hidden border border-gray-100">
        <div className="flex flex-col sm:flex-row gap-4 p-4">
          <div className="relative w-full sm:w-32 h-32 flex-shrink-0">
            {index && (
              <span className="absolute top-1 left-1 z-10 px-2 py-0.5 text-xs font-semibold rounded-md bg-white/95 text-gray-700 shadow-sm">
                #{index}
              </span>
            )}
            <img
              src={product.thumbnail}
              alt={product.title}
              onClick={() => onOpenImageViewer(product.thumbnail)}
              className="w-full h-full object-cover rounded-lg cursor-pointer hover:opacity-90 transition"
            />
            {type !== "pending" && (
              <div
                className={`absolute top-1 right-1 px-2 py-0.5 text-xs font-medium rounded-full ${statusColor}`}
              >
                {statusText}
              </div>
            )}
          </div>

          <div className="flex-1">
            <div className="flex flex-wrap justify-between items-start gap-2">
              <h3 className="text-lg font-semibold text-gray-900 line-clamp-1">
                {product.title}
              </h3>
              {type === "pending" && (
                <span
                  className={`px-3 py-1 text-xs font-medium rounded-full ${statusColor}`}
                >
                  {statusText}
                </span>
              )}
            </div>

            <p className="text-sm text-gray-600 mt-1 line-clamp-2">
              {product.description}
            </p>

            <div className="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-sm">
              <span className="text-gray-600">
                Sinh viên: {product.student_fullname}
              </span>
              <span className="text-gray-400 hidden sm:inline">|</span>
              <span className="text-gray-600">
                {product.student_class ?? "Chưa có lớp"}
              </span>
              <span className="text-gray-400 hidden sm:inline">|</span>
              <span className="text-gray-600">{product.major_name}</span>
            </div>

            <div className="flex flex-wrap items-center gap-2 mt-2">
              <span className="px-2 py-1 bg-purple-100 text-purple-700 rounded-full text-xs">
                {product.category_name}
              </span>
              <span className="text-xs text-gray-500">
                Ngày gửi: {formatDate(product?.created_at)}
              </span>
              {product.approved_at && type === "approved" && (
                <span className="text-xs text-gray-500">
                  Duyệt: {formatDate(product?.approved_at)}
                </span>
              )}
            </div>

            <div className="flex flex-wrap items-center gap-3 mt-3">
              <button
                onClick={() => onViewDetail(product.product_id)}
                className="px-4 py-1.5 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 transition"
              >
                Xem chi tiết
              </button>
              {product.github_link && (
                <a
                  href={product.github_link}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1 transition"
                >
                  <svg
                    className="w-4 h-4"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" />
                  </svg>
                  GitHub
                </a>
              )}
              {product.demo_link && (
                <a
                  href={product.demo_link}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1 transition"
                >
                  <svg
                    className="w-4 h-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                    />
                  </svg>
                  Demo
                </a>
              )}
            </div>
          </div>
        </div>
      </div>
    );
  },
);
const currentStudent = JSON.parse(sessionStorage.getItem("auth_user"));

ProductCard.displayName = "ProductCard";

const LoadingSkeleton = () => (
  <div className="space-y-4">
    {[1, 2, 3].map((i) => (
      <div key={i} className="bg-white rounded-xl shadow-md p-4 animate-pulse">
        <div className="flex gap-4">
          <div className="w-32 h-32 bg-gray-200 rounded-lg"></div>
          <div className="flex-1 space-y-3">
            <div className="h-5 bg-gray-200 rounded w-3/4"></div>
            <div className="h-4 bg-gray-200 rounded w-full"></div>
            <div className="h-4 bg-gray-200 rounded w-5/6"></div>
            <div className="flex gap-2">
              <div className="h-8 bg-gray-200 rounded w-24"></div>
              <div className="h-8 bg-gray-200 rounded w-24"></div>
            </div>
          </div>
        </div>
      </div>
    ))}
  </div>
);

const EmptyState = React.memo(({ message }) => (
  <div className="bg-white rounded-xl shadow-md p-12 text-center">
    <div className="text-6xl mb-4">📭</div>
    <p className="text-gray-500">{message}</p>
  </div>
));
EmptyState.displayName = "EmptyState";

// ========== Main Component ==========
const TeacherScreen = () => {
  const [filter, setFilter] = useState("pending");
  const [isHeaderExpanded, setIsHeaderExpanded] = useState(true);
  const [currentPage, setCurrentPage] = useState(1);
  const ITEMS_PER_PAGE = 6;
  useTitle("Trang chủ giáo viên");

  const { user } = useContext(AuthContext);
  const { majorName } = useMajorName(user?.major_id);
  const { teacherStatistic } = useTeacherStatistic();
  const { ProductsData, loading, error } = useTeacherPendingApproval();
  const handleViewDetail = useViewDetail("detail-teacher");
  const { openViewer, ImageViewerModal } = useImageViewer();

  // useMemo: chỉ tính lại khi data thay đổi
  const teacher = useMemo(
    () => ({
      name: user?.name ?? "",
      email: user?.email ?? "",
      major: majorName ?? "",
      totalProducts: teacherStatistic?.total_product ?? 0,
    }),
    [user, majorName, teacherStatistic],
  );

  const pendingProducts = useMemo(
    () => ProductsData?.pending_result ?? [],
    [ProductsData],
  );
  const approvedProducts = useMemo(
    () => ProductsData?.approved_result ?? [],
    [ProductsData],
  );
  const rejectedProducts = useMemo(
    () => ProductsData?.rejected_result ?? [],
    [ProductsData],
  );

  const stats = useMemo(
    () => [
      { label: "Tổng sản phẩm", value: teacher.totalProducts, color: "purple" },
      { label: "Chờ duyệt", value: pendingProducts.length, color: "yellow" },
      { label: "Đã duyệt", value: approvedProducts.length, color: "green" },
      { label: "Từ chối", value: rejectedProducts.length, color: "red" },
    ],
    [
      teacher.totalProducts,
      pendingProducts.length,
      approvedProducts.length,
      rejectedProducts.length,
    ],
  );

  // useCallback: ổn định hàm handler
  const handleToggleHeader = useCallback(
    () => setIsHeaderExpanded((prev) => !prev),
    [],
  );
  const handleStatClick = useCallback((label) => {
    setCurrentPage(1);
    if (label === "Chờ duyệt") setFilter(STATUS.PENDING);
    else if (label === "Đã duyệt") setFilter(STATUS.APPROVED);
    else if (label === "Từ chối") setFilter(STATUS.REJECTED);
  }, []);

  const handleTabChange = useCallback((tab) => {
    setFilter(tab);
    setCurrentPage(1);
  }, []);

  const colorMap = {
    purple: {
      bg: "from-purple-50 to-purple-50/50",
      label: "text-purple-600",
      value: "text-purple-700",
    },
    yellow: {
      bg: "from-yellow-50 to-yellow-50/50",
      label: "text-yellow-600",
      value: "text-yellow-700",
    },
    green: {
      bg: "from-green-50 to-green-50/50",
      label: "text-green-600",
      value: "text-green-700",
    },
    red: {
      bg: "from-red-50 to-red-50/50",
      label: "text-red-600",
      value: "text-red-700",
    },
  };

  const tabConfig = {
    pending: {
      label: "Chờ duyệt",
      color: "yellow",
      count: pendingProducts.length,
    },
    approved: {
      label: "Đã duyệt",
      color: "green",
      count: approvedProducts.length,
    },
    rejected: {
      label: "Từ chối",
      color: "red",
      count: rejectedProducts.length,
    },
  };

  const activeProducts = useMemo(() => {
    if (filter === STATUS.APPROVED) return approvedProducts;
    if (filter === STATUS.REJECTED) return rejectedProducts;
    return pendingProducts;
  }, [filter, approvedProducts, rejectedProducts, pendingProducts]);

  const totalPages = Math.ceil(activeProducts.length / ITEMS_PER_PAGE);
  const paginatedProducts = useMemo(() => {
    const start = (currentPage - 1) * ITEMS_PER_PAGE;
    return activeProducts.slice(start, start + ITEMS_PER_PAGE);
  }, [activeProducts, currentPage]);

  const pagination = totalPages > 1 && (
    <div className="flex justify-center items-center gap-2 mt-8 flex-wrap">
      <button
        onClick={() => setCurrentPage((prev) => Math.max(prev - 1, 1))}
        disabled={currentPage === 1}
        className="px-3 py-2 border rounded-md bg-white hover:bg-gray-50 disabled:opacity-50"
      >
        Trước
      </button>

      {Array.from({ length: totalPages }, (_, index) => index + 1).map(
        (page) => (
          <button
            key={page}
            onClick={() => setCurrentPage(page)}
            className={`px-4 py-2 rounded-md border text-sm ${
              currentPage === page
                ? "bg-gray-900 text-white border-gray-900"
                : "bg-white text-gray-700 hover:bg-gray-50"
            }`}
          >
            {page}
          </button>
        ),
      )}

      <button
        onClick={() => setCurrentPage((prev) => Math.min(prev + 1, totalPages))}
        disabled={currentPage === totalPages}
        className="px-3 py-2 border rounded-md bg-white hover:bg-gray-50 disabled:opacity-50"
      >
        Sau
      </button>
    </div>
  );

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
      {/* Header */}
      <div className="bg-white shadow-sm border-b sticky top-0 z-10">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="py-3 flex items-center justify-between gap-4">
            <div className="flex items-center gap-3">
              <button
                onClick={handleToggleHeader}
                className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
              >
                <svg
                  className={`w-4 h-4 text-gray-500 transition-transform duration-200 ${isHeaderExpanded ? "" : "rotate-180"}`}
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M19 9l-7 7-7-7"
                  />
                </svg>
              </button>

              <div className="w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                <svg
                  className="w-4 h-4 text-white"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                  />
                </svg>
              </div>

              <div>
                <h1 className="text-base font-semibold text-gray-800">
                  Giảng viên Dashboard
                </h1>
                {!isHeaderExpanded && (
                  <p className="text-xs text-gray-400">
                    Quản lý sản phẩm sinh viên
                  </p>
                )}
              </div>
            </div>

            <div className="flex items-center gap-3">
              <div className="hidden md:flex items-center gap-3 px-3 py-1.5 bg-gray-50 rounded-full">
                <div className="w-7 h-7 bg-gradient-to-r from-gray-600 to-gray-700 rounded-full flex items-center justify-center">
                  <span className="text-white text-xs font-medium">
                    {teacher.name?.charAt(0) || "G"}
                  </span>
                </div>
                <div className="flex items-center gap-2 text-sm">
                  <span className="font-medium text-gray-700">
                    {teacher.name}
                  </span>
                  <span className="w-1 h-1 bg-gray-300 rounded-full"></span>
                  <span className="text-xs text-gray-500">{majorName}</span>
                </div>
              </div>
              <UserDropdown />
            </div>
          </div>

          {isHeaderExpanded && (
            <div className="animate-fadeIn pb-4">
              <div className="grid grid-cols-2 md:grid-cols-4 gap-3 pt-3 border-t border-gray-100">
                {stats.map((stat) => {
                  const c = colorMap[stat.color];
                  return (
                    <div
                      key={stat.label}
                      onClick={() => handleStatClick(stat.label)}
                      className={`bg-gradient-to-r ${c.bg} rounded-xl p-3 cursor-pointer hover:shadow-md transition-all`}
                    >
                      <p className={`text-xs font-medium ${c.label}`}>
                        {stat.label}
                      </p>
                      <p className={`text-xl font-bold mt-0.5 ${c.value}`}>
                        {stat.value}
                      </p>
                    </div>
                  );
                })}
              </div>

              <div className="flex flex-wrap items-center gap-3 mt-4">
                <div className="flex gap-1.5">
                  {Object.entries(tabConfig).map(
                    ([tab, { label, color, count }]) => (
                      <button
                        key={tab}
                        onClick={() => handleTabChange(tab)}
                        className={`px-4 py-1.5 rounded-lg font-medium text-xs transition-all duration-200 ${
                          filter === tab
                            ? `bg-${color}-500 text-white shadow-sm`
                            : "bg-white text-gray-600 hover:bg-gray-100 border border-gray-200"
                        }`}
                      >
                        {label} ({count})
                      </button>
                    ),
                  )}
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
      {currentStudent && <ChatBoxAi user={currentStudent} />}
      {/* Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <SearchAi embedded user={user} majorName={majorName} />

        {loading && <LoadingSkeleton />}
        {error && (
          <div className="bg-red-50 text-red-700 p-4 rounded-xl">
            Lỗi: {error}
          </div>
        )}

        {!loading && !error && (
          <>
            {filter === STATUS.PENDING && (
              <div>
                <h2 className="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                  <span className="w-1 h-6 bg-yellow-500 rounded-full"></span>
                  Sản phẩm cần duyệt
                  {pendingProducts.length > 0 && (
                    <span className="text-sm font-normal text-gray-500">
                      (Hiển thị {pendingProducts.length} sản phẩm)
                    </span>
                  )}
                </h2>
                {pendingProducts.length === 0 ? (
                  <EmptyState message="Không có sản phẩm chờ duyệt" />
                ) : (
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {paginatedProducts.map((product, idx) => (
                      <ProductCard
                        key={product.product_id}
                        product={product}
                        type="pending"
                        index={(currentPage - 1) * ITEMS_PER_PAGE + idx + 1}
                        onViewDetail={handleViewDetail}
                        onOpenImageViewer={openViewer}
                      />
                    ))}
                  </div>
                )}
                {pagination}
              </div>
            )}

            {filter === STATUS.APPROVED && (
              <div>
                <h2 className="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                  <span className="w-1 h-6 bg-green-500 rounded-full"></span>
                  Sản phẩm đã duyệt
                  {approvedProducts.length > 0 && (
                    <span className="text-sm font-normal text-gray-500">
                      (Hiển thị {approvedProducts.length} sản phẩm)
                    </span>
                  )}
                </h2>
                {approvedProducts.length === 0 ? (
                  <EmptyState message="Không có sản phẩm đã duyệt" />
                ) : (
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
                    {paginatedProducts.map((product, idx) => (
                      <ProductCard
                        key={product.product_id}
                        product={product}
                        type="approved"
                        index={(currentPage - 1) * ITEMS_PER_PAGE + idx + 1}
                        onViewDetail={handleViewDetail}
                        onOpenImageViewer={openViewer}
                      />
                    ))}
                  </div>
                )}
                {pagination}
              </div>
            )}

            {filter === STATUS.REJECTED && (
              <div>
                <h2 className="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                  <span className="w-1 h-6 bg-red-500 rounded-full"></span>
                  Sản phẩm bị từ chối
                  {rejectedProducts.length > 0 && (
                    <span className="text-sm font-normal text-gray-500">
                      (Hiển thị {rejectedProducts.length} sản phẩm)
                    </span>
                  )}
                </h2>
                {rejectedProducts.length === 0 ? (
                  <EmptyState message="Không có sản phẩm bị từ chối" />
                ) : (
                  <div className="space-y-4">
                    {paginatedProducts.map((product, idx) => (
                      <ProductCard
                        key={product.product_id}
                        product={product}
                        type="rejected"
                        index={(currentPage - 1) * ITEMS_PER_PAGE + idx + 1}
                        onViewDetail={handleViewDetail}
                        onOpenImageViewer={openViewer}
                      />
                    ))}
                  </div>
                )}
                {pagination}
              </div>
            )}
          </>
        )}
      </div>

      <ImageViewerModal />
    </div>
  );
};

export default TeacherScreen;
