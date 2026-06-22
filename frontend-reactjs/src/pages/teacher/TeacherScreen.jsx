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
import { ROUTES } from "../../utils/routes";
import ChatBoxAi from "../chatBoxAi/ChatBoxAi";
import SearchAi from "../ai/SearchAi";
import { getMajorTheme } from "../../utils/uploadProductScreen/uploadRegistry";
// ========== Extracted components ==========

const ProductCard = React.memo(
  ({ product, type, onViewDetail, onOpenImageViewer, index, theme }) => {
    const statusColor = getStatusColor(type);
    const statusText = getStatusText(type);

    return (
      <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
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
              <span className={`rounded-full px-2 py-1 text-xs ${theme.light} ${theme.text}`}>
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
                className={`rounded-lg px-4 py-1.5 text-sm text-white transition ${theme.buttonBg}`}
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
  const theme = getMajorTheme(majorName);
  const { teacherStatistic } = useTeacherStatistic();
  const teacherParams = useMemo(
    () => ({
      status: filter,
      page: currentPage,
      per_page: ITEMS_PER_PAGE,
    }),
    [filter, currentPage],
  );
  const { ProductsData, loading, error } =
    useTeacherPendingApproval(teacherParams);
  const handleViewDetail = useViewDetail(ROUTES.TEACHER_DETAIL);
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

  const productPaginator = ProductsData?.products;
  const activeProducts = productPaginator?.data ?? [];
  const counts = ProductsData?.counts ?? {};

  const stats = useMemo(
    () => [
      { label: "Tổng sản phẩm", value: counts.total ?? teacher.totalProducts },
      {
        label: "Chờ duyệt",
        value: counts.pending ?? 0,
        filter: STATUS.PENDING,
      },
      {
        label: "Đã duyệt",
        value: counts.approved ?? 0,
        filter: STATUS.APPROVED,
      },
      {
        label: "Từ chối",
        value: counts.rejected ?? 0,
        filter: STATUS.REJECTED,
      },
    ],
    [
      counts.total,
      counts.pending,
      counts.approved,
      counts.rejected,
      teacher.totalProducts,
    ],
  );

  // useCallback: ổn định hàm handler
  const handleToggleHeader = useCallback(
    () => setIsHeaderExpanded((prev) => !prev),
    [],
  );
  const handleStatClick = useCallback((nextFilter) => {
    if (!nextFilter) return;
    setCurrentPage(1);
    setFilter(nextFilter);
  }, []);

  const handleTabChange = useCallback((tab) => {
    setFilter(tab);
    setCurrentPage(1);
  }, []);

  const tabConfig = {
    pending: {
      label: "Chờ duyệt",
      color: "yellow",
      count: counts.pending ?? 0,
    },
    approved: {
      label: "Đã duyệt",
      color: "green",
      count: counts.approved ?? 0,
    },
    rejected: {
      label: "Từ chối",
      color: "red",
      count: counts.rejected ?? 0,
    },
  };

  const activeCount = tabConfig[filter]?.count ?? 0;
  const totalPages = productPaginator?.last_page ?? 1;
  const paginatedProducts = activeProducts;

  const pagination = totalPages > 1 && (
    <div className="flex justify-center items-center gap-2 mt-8 flex-wrap">
      <button
        onClick={() => setCurrentPage((prev) => Math.max(prev - 1, 1))}
        disabled={currentPage === 1}
        className="px-3 py-2 border rounded-md bg-white hover:bg-gray-50 disabled:opacity-50"
      >
        Trước
      </button>

      <span className="px-4 py-2 text-sm text-gray-600">
        Trang {currentPage} / {totalPages}
      </span>

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
    <div className="min-h-screen bg-slate-50">
      {/* Header */}
      <div className={`sticky top-0 z-10 bg-gradient-to-r ${theme.headerGradient} text-white shadow-md`}>
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="py-3 flex items-center justify-between gap-4">
            <div className="flex items-center gap-3">
              <button
                onClick={handleToggleHeader}
                className="rounded-lg p-2 transition-colors hover:bg-white/15"
              >
                <svg
                  className={`h-4 w-4 text-white/80 transition-transform duration-200 ${isHeaderExpanded ? "" : "rotate-180"}`}
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

              <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-white/15 ring-1 ring-white/20">
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
                <h1 className="text-base font-semibold text-white">
                  Trang giảng viên
                </h1>
                {!isHeaderExpanded && (
                  <p className="text-xs text-white/70">
                    Quản lý sản phẩm sinh viên
                  </p>
                )}
              </div>
            </div>

            <div className="flex items-center gap-3">
              <div className="hidden items-center gap-3 rounded-full bg-white/10 px-3 py-1.5 ring-1 ring-white/15 md:flex">
                <div className="flex h-7 w-7 items-center justify-center rounded-full bg-white/20">
                  <span className="text-white text-xs font-medium">
                    {teacher.name?.charAt(0) || "G"}
                  </span>
                </div>
                <div className="flex items-center gap-2 text-sm">
                  <span className="font-medium text-white">
                    {teacher.name}
                  </span>
                  <span className="h-1 w-1 rounded-full bg-white/40"></span>
                  <span className="text-xs text-white/75">{majorName}</span>
                </div>
              </div>
              <UserDropdown />
            </div>
          </div>

          {isHeaderExpanded && (
            <div className="animate-fadeIn pb-4">
              <div className="grid grid-cols-2 gap-3 border-t border-white/15 pt-3 md:grid-cols-4">
                {stats.map((stat) => {
                  const isActive = stat.filter && filter === stat.filter;
                  return (
                    <div
                      key={stat.label}
                      onClick={() => handleStatClick(stat.filter)}
                      className={`cursor-pointer rounded-lg border bg-white p-3 transition-colors ${
                        isActive
                          ? "border-slate-500"
                          : "border-slate-200 hover:border-slate-300"
                      }`}
                    >
                      <p className="text-xs font-medium text-slate-500">
                        {stat.label}
                      </p>
                      <p className="mt-1 text-xl font-semibold text-slate-800">
                        {stat.value}
                      </p>
                    </div>
                  );
                })}
              </div>

              <div className="flex flex-wrap items-center gap-3 mt-4">
                <div className="flex gap-1.5">
                  {Object.entries(tabConfig).map(
                    ([tab, { label, count }]) => (
                      <button
                        key={tab}
                        onClick={() => handleTabChange(tab)}
                        className={`px-4 py-1.5 rounded-lg font-medium text-xs transition-all duration-200 ${
                          filter === tab
                            ? "bg-white text-slate-900 shadow-sm"
                            : "border border-white/20 bg-white/10 text-white hover:bg-white/20"
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
      <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
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
                  {activeCount > 0 && (
                    <span className="text-sm font-normal text-gray-500">
                      (Hiển thị {activeProducts.length} / {activeCount} sản phẩm)
                    </span>
                  )}
                </h2>
                {activeProducts.length === 0 ? (
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
                        theme={theme}
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
                  {activeCount > 0 && (
                    <span className="text-sm font-normal text-gray-500">
                      (Hiển thị {activeProducts.length} / {activeCount} sản phẩm)
                    </span>
                  )}
                </h2>
                {activeProducts.length === 0 ? (
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
                        theme={theme}
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
                  {activeCount > 0 && (
                    <span className="text-sm font-normal text-gray-500">
                      (Hiển thị {activeProducts.length} / {activeCount} sản phẩm)
                    </span>
                  )}
                </h2>
                {activeProducts.length === 0 ? (
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
                        theme={theme}
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
