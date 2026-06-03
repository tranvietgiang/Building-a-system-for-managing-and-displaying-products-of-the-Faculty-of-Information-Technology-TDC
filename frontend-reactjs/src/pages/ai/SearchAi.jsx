import React, {
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
} from "react";
import { useNavigate } from "react-router-dom";
import { STATUS } from "../../utils/constants";
import {
  Bot,
  CalendarDays,
  Eye,
  Heart,
  Loader2,
  Search,
  Sparkles,
  X,
} from "lucide-react";
import useSearchAi from "../../hooks/ai/useSearchAi";
import useDebounce from "../../hooks/common/useDebounce";
import { ROLE } from "../../utils/constants";
import useProductSearch from "../../hooks/useProduct/useProductSearch";

const getStatusLabel = (status) => {
  switch (status) {
    case STATUS.APPROVED:
      return "Đã duyệt";
    case STATUS.PENDING:
      return "Chờ duyệt";
    case STATUS.REJECTED:
      return "Từ chối";
    default:
      return "Không rõ";
  }
};

const getStatusClass = (status) => {
  switch (status) {
    case "approved":
      return "bg-emerald-50 text-emerald-700 border-emerald-200";
    case "pending":
      return "bg-amber-50 text-amber-700 border-amber-200";
    case "rejected":
      return "bg-rose-50 text-rose-700 border-rose-200";
    default:
      return "bg-slate-50 text-slate-600 border-slate-200";
  }
};

const formatDate = (date) => {
  if (!date) return "";
  return new Date(date).toLocaleDateString("vi-VN");
};

const getRoleSearchConfig = (role, majorName) => {
  if (role === ROLE.STUDENT) {
    return {
      title: "Tìm kiếm đồ án trong ngành của bạn",
      description: `AI chỉ tìm những dữ liệu sinh viên được phép xem${
        majorName ? ` trong ngành ${majorName}` : ""
      }.`,
      placeholder: majorName
        ? `Tìm đồ án ${majorName}: AI Python, web Laravel, tài liệu tham khảo...`
        : "Tìm đồ án trong ngành của bạn...",
      suggestions: [
        `Đồ án mới nhất trong ngành ${majorName || "của tôi"}`,
        `Đồ án nhiều lượt xem trong ngành ${majorName || "của tôi"}`,
        `Tài liệu tham khảo phù hợp ngành ${majorName || "của tôi"}`,
      ],
    };
  }

  if (role === ROLE.TEACHER) {
    return {
      title: "Tìm kiếm đồ án ngành phụ trách",
      description: `AI chỉ tìm những dữ liệu giảng viên được phép xem${
        majorName ? ` trong ngành ${majorName}` : ""
      }.`,
      placeholder: majorName
        ? `Tìm đồ án ${majorName}: chờ duyệt, đã duyệt, nhiều lượt xem...`
        : "Tìm đồ án trong ngành phụ trách...",
      suggestions: [
        `Đồ án chờ duyệt trong ngành ${majorName || "phụ trách"}`,
        `Đồ án đã duyệt nhiều lượt xem ngành ${majorName || "phụ trách"}`,
        `Đồ án cần nhận xét trong ngành ${majorName || "phụ trách"}`,
      ],
    };
  }

  if (role === ROLE.ADMIN) {
    return {
      title: "Tìm kiếm toàn hệ thống",
      description:
        "AI có thể tìm theo ngành, trạng thái, danh mục và công nghệ trong toàn bộ hệ thống.",
      placeholder: "Tìm đồ án AI, CNTT, MMT, Graphic, pending, approved...",
      suggestions: [
        "Top đồ án nhiều lượt xem",
        "Đồ án AI dùng Python",
        "Đồ án pending mới nhất",
      ],
    };
  }

  return {
    title: "Tìm kiếm đồ án công khai",
    description:
      "Khách chỉ xem các đồ án đã được duyệt và thông tin công khai.",
    placeholder:
      "Tìm đồ án đã duyệt: AI Python, web Laravel, thiết kế Figma...",
    suggestions: [
      "Đồ án đã duyệt nhiều lượt xem",
      "Đồ án AI dùng Python",
      "Đồ án thiết kế bằng Figma",
    ],
  };
};

export default function SearchAi({
  embedded = false,
  user: userProp = null,
  majorName = "",
}) {
  const navigate = useNavigate();
  const sessionUser = useMemo(() => {
    try {
      return JSON.parse(sessionStorage.getItem("auth_user"));
    } catch {
      return null;
    }
  }, []);

  const user = userProp ?? sessionUser;
  const role = user?.role ?? "guest";
  const userKey = user?.user_id ?? user?.id ?? "guest";
  const historyKey = `ai_search_history_${role}_${userKey}`;

  const [keyword, setKeyword] = useState("");
  const [aiEnabled, setAiEnabled] = useState(false);
  const [currentPage, setCurrentPage] = useState(1);
  const [searchHistory, setSearchHistory] = useState(() => {
    try {
      return JSON.parse(localStorage.getItem(historyKey)) || [];
    } catch {
      return [];
    }
  });
  const { searchAi, clearSearch, searchResult, searchError, loadingSearchAi } =
    useSearchAi();
  const {
    searchProducts,
    clearProductSearch,
    productSearchResult,
    productSearchError,
    loadingProductSearch,
  } = useProductSearch({ visitor: role === "guest" });
  const debouncedKeyword = useDebounce(keyword, 700);
  const lastSearchRef = useRef("");
  const searchConfig = getRoleSearchConfig(role, majorName);
  const activeResult = aiEnabled ? searchResult : productSearchResult;
  const activeError = aiEnabled ? searchError : productSearchError;
  const activeLoading = aiEnabled ? loadingSearchAi : loadingProductSearch;
  const products = useMemo(
    () => (Array.isArray(activeResult?.products) ? activeResult.products : []),
    [activeResult],
  );
  const ITEMS_PER_PAGE = 6;
  const totalPages = Math.ceil(products.length / ITEMS_PER_PAGE);
  const paginatedProducts = useMemo(() => {
    const start = (currentPage - 1) * ITEMS_PER_PAGE;
    return products.slice(start, start + ITEMS_PER_PAGE);
  }, [products, currentPage]);

  const saveSearchHistory = useCallback(
    (value) => {
      const nextKeyword = String(value || "").trim();
      if (!nextKeyword) return;

      setSearchHistory((prev) => {
        const nextHistory = [
          nextKeyword,
          ...prev.filter((item) => item !== nextKeyword),
        ].slice(0, 8);

        localStorage.setItem(historyKey, JSON.stringify(nextHistory));
        return nextHistory;
      });
    },
    [historyKey],
  );

  const runSearch = async (value) => {
    const nextKeyword = String(value || "").trim();
    const searchKey = `${aiEnabled ? "ai" : "normal"}:${nextKeyword}`;
    if (!nextKeyword || lastSearchRef.current === searchKey) return;

    lastSearchRef.current = searchKey;
    const result = aiEnabled
      ? await searchAi(nextKeyword)
      : await searchProducts({ q: nextKeyword, per_page: 30 });
    setCurrentPage(1);
    if (result) saveSearchHistory(nextKeyword);
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    await runSearch(keyword);
  };

  const handleClear = () => {
    setKeyword("");
    lastSearchRef.current = "";
    setCurrentPage(1);
    clearSearch();
    clearProductSearch();
  };

  const handleSuggestionClick = async (suggestion) => {
    setKeyword(suggestion);
    await runSearch(suggestion);
  };

  const handleViewDetail = (productId) => {
    if (role === ROLE.TEACHER) {
      navigate("/detail-teacher", { state: { productId } });
      return;
    }

    if (role === ROLE.STUDENT) {
      navigate("/detail", { state: { productId } });
      return;
    }

    navigate("/visitor-detail", { state: { productId } });
  };

  useEffect(() => {
    const nextKeyword = debouncedKeyword.trim();

    if (!nextKeyword) {
      if (lastSearchRef.current) {
        lastSearchRef.current = "";
        clearSearch();
      }
      return;
    }

    const searchKey = `${aiEnabled ? "ai" : "normal"}:${nextKeyword}`;
    if (nextKeyword.length < 2 || lastSearchRef.current === searchKey) return;

    lastSearchRef.current = searchKey;
    const request = aiEnabled
      ? searchAi(nextKeyword)
      : searchProducts({ q: nextKeyword, per_page: 30 });

    request.then((result) => {
      setCurrentPage(1);
      if (result) saveSearchHistory(nextKeyword);
    });
  }, [
    aiEnabled,
    debouncedKeyword,
    clearSearch,
    searchAi,
    searchProducts,
    saveSearchHistory,
  ]);

  useEffect(() => {
    lastSearchRef.current = "";
    clearSearch();
    clearProductSearch();

    const nextKeyword = keyword.trim();
    if (nextKeyword.length < 2) return;

    const request = aiEnabled
      ? searchAi(nextKeyword)
      : searchProducts({ q: nextKeyword, per_page: 30 });

    lastSearchRef.current = `${aiEnabled ? "ai" : "normal"}:${nextKeyword}`;
    request.then((result) => {
      setCurrentPage(1);
      if (result) saveSearchHistory(nextKeyword);
    });
  }, [aiEnabled]);

  const Wrapper = embedded ? "section" : "main";

  return (
    <Wrapper className={embedded ? "mb-8" : "min-h-screen bg-slate-50"}>
      <div className="border-b border-slate-200 bg-white">
        <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
          <div className="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
              <div className="mb-2 inline-flex items-center gap-2 rounded-md border border-sky-100 bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700">
                {aiEnabled ? <Bot size={14} /> : <Search size={14} />}
                {aiEnabled ? "AI Search" : "Scout Search"}
              </div>
              <h1 className="text-2xl font-semibold text-slate-900">
                {searchConfig.title}
              </h1>
              <p className="mt-1 max-w-2xl text-sm text-slate-500">
                {searchConfig.description}
              </p>
            </div>

            <form onSubmit={handleSubmit} className="w-full lg:max-w-xl">
              <div className="mb-2 flex justify-end">
                <label className="inline-flex items-center gap-2 text-xs font-medium text-slate-600">
                  <span>Scout</span>
                  <button
                    type="button"
                    onClick={() => setAiEnabled((prev) => !prev)}
                    className={`relative h-6 w-11 rounded-full transition ${
                      aiEnabled ? "bg-sky-600" : "bg-slate-300"
                    }`}
                    aria-pressed={aiEnabled}
                    title={aiEnabled ? "Tắt AI Search" : "Bật AI Search"}
                  >
                    <span
                      className={`absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition ${
                        aiEnabled ? "left-5" : "left-0.5"
                      }`}
                    />
                  </button>
                  <span>AI</span>
                </label>
              </div>
              <div className="flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-2 shadow-sm">
                <Search className="ml-2 h-5 w-5 shrink-0 text-slate-400" />
                <input
                  value={keyword}
                  onChange={(event) => setKeyword(event.target.value)}
                  placeholder={searchConfig.placeholder}
                  className="min-w-0 flex-1 bg-transparent px-1 py-2 text-sm text-slate-800 outline-none placeholder:text-slate-400"
                />
                {keyword && (
                  <button
                    type="button"
                    onClick={handleClear}
                    className="rounded-md p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    title="Xóa tìm kiếm"
                  >
                    <X size={16} />
                  </button>
                )}
                <button
                  type="submit"
                  disabled={activeLoading || !keyword.trim()}
                  className="inline-flex items-center gap-2 rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                >
                  {activeLoading ? (
                    <Loader2 size={16} className="animate-spin" />
                  ) : aiEnabled ? (
                    <Sparkles size={16} />
                  ) : (
                    <Search size={16} />
                  )}
                  Tìm
                </button>
              </div>
            </form>
          </div>

          <div className="mt-4 flex flex-wrap gap-2">
            {searchConfig.suggestions.map((suggestion) => (
              <button
                key={suggestion}
                type="button"
                onClick={() => handleSuggestionClick(suggestion)}
                className="rounded-md border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700"
              >
                {suggestion}
              </button>
            ))}
          </div>

          {searchHistory.length > 0 && (
            <div className="mt-3 flex flex-wrap items-center gap-2">
              <span className="text-xs font-medium text-slate-500">
                Lịch sử:
              </span>
              {searchHistory.map((historyItem) => (
                <button
                  key={historyItem}
                  type="button"
                  onClick={() => handleSuggestionClick(historyItem)}
                  className="rounded-md bg-white px-2.5 py-1 text-xs text-slate-500 border border-slate-200 hover:text-slate-900 hover:bg-slate-50"
                >
                  {historyItem}
                </button>
              ))}
            </div>
          )}
        </div>
      </div>

      <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {activeError && (
          <div className="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {activeError}
          </div>
        )}

        {activeResult && (
          <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
              <p className="text-sm font-medium text-slate-800">
                Tìm thấy {activeResult.count ?? products.length} kết quả
              </p>
              <p className="text-xs text-slate-500">
                {aiEnabled
                  ? `Từ khóa AI hiểu: ${activeResult.intent?.keyword || keyword}`
                  : `Tìm thường bằng Scout: ${keyword}`}
              </p>
            </div>
            {aiEnabled && activeResult.intent?.major_code && (
              <span className="rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-600">
                Ngành: {activeResult.intent.major_code}
              </span>
            )}
          </div>
        )}

        {!activeResult && !activeLoading && !embedded && (
          <div className="rounded-lg border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
            <Search className="mx-auto h-10 w-10 text-slate-300" />
            <h2 className="mt-3 text-base font-semibold text-slate-800">
              Sẵn sàng tìm kiếm
            </h2>
            <p className="mx-auto mt-1 max-w-md text-sm text-slate-500">
              Mặc định dùng Scout. Bật AI nếu muốn hệ thống hiểu câu hỏi tự nhiên.
            </p>
          </div>
        )}

        {activeLoading && (
          <div className="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
            {Array.from({ length: 3 }).map((_, index) => (
              <div
                key={index}
                className="h-72 animate-pulse rounded-lg border border-slate-200 bg-white"
              />
            ))}
          </div>
        )}

        {activeResult && !activeLoading && products.length === 0 && (
          <div className="rounded-lg border border-slate-200 bg-white px-6 py-12 text-center">
            <h2 className="text-base font-semibold text-slate-800">
              Không tìm thấy đồ án phù hợp
            </h2>
            <p className="mt-1 text-sm text-slate-500">
              Thử tìm bằng công nghệ, ngành học, danh mục hoặc trạng thái khác.
            </p>
          </div>
        )}

        {products.length > 0 && !activeLoading && (
          <div className="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
            {paginatedProducts.map((product, idx) => (
              <article
                key={product.product_id}
                className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
              >
                <div className="px-4 pt-4">
                  <span className="inline-flex rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">
                    #{(currentPage - 1) * ITEMS_PER_PAGE + idx + 1}
                  </span>
                </div>
                <button
                  onClick={() => handleViewDetail(product.product_id)}
                  className="mt-3 block h-44 w-full bg-slate-100 text-left"
                >
                  {product.thumbnail ? (
                    <img
                      src={product.thumbnail}
                      alt={product.title}
                      className="h-full w-full object-cover"
                    />
                  ) : (
                    <div className="flex h-full items-center justify-center text-sm text-slate-400">
                      Chưa có ảnh
                    </div>
                  )}
                </button>

                <div className="p-4">
                  <div className="mb-3 flex flex-wrap items-center gap-2">
                    <span
                      className={`rounded-md border px-2 py-1 text-xs font-medium ${getStatusClass(product.status)}`}
                    >
                      {getStatusLabel(product.status)}
                    </span>
                    <span className="rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">
                      {product.major_code || product.major_name || "Đồ án"}
                    </span>
                    {product.category_name && (
                      <span className="rounded-md bg-sky-50 px-2 py-1 text-xs font-medium text-sky-700">
                        {product.category_name}
                      </span>
                    )}
                  </div>

                  <h2 className="line-clamp-2 min-h-[3rem] text-base font-semibold text-slate-900">
                    {product.title}
                  </h2>
                  <p className="mt-2 line-clamp-2 min-h-[2.5rem] text-sm text-slate-500">
                    {product.description || "Chưa có mô tả"}
                  </p>

                  <div className="mt-4 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                    <span className="inline-flex items-center gap-1">
                      <Eye size={14} />
                      {product.views ?? 0}
                    </span>
                    <span className="inline-flex items-center gap-1">
                      <Heart size={14} />
                      {product.likes ?? 0}
                    </span>
                    {product.submitted_at && (
                      <span className="inline-flex items-center gap-1">
                        <CalendarDays size={14} />
                        {formatDate(product.submitted_at)}
                      </span>
                    )}
                  </div>

                  <div className="mt-4 flex flex-wrap gap-2 border-t border-slate-100 pt-3">
                    {[
                      product.model_used,
                      product.ai_framework,
                      product.ai_language,
                      product.programming_language,
                      product.cntt_framework,
                      product.database_used,
                      product.network_protocol,
                      product.simulation_tool,
                      product.design_type,
                      product.tools_used,
                    ]
                      .filter(Boolean)
                      .slice(0, 4)
                      .map((item) => (
                        <span
                          key={item}
                          className="rounded-md bg-slate-50 px-2 py-1 text-xs text-slate-600"
                        >
                          {item}
                        </span>
                      ))}
                  </div>

                  <button
                    onClick={() => handleViewDetail(product.product_id)}
                    className="mt-4 w-full rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                  >
                    Xem chi tiết
                  </button>
                </div>
              </article>
            ))}
          </div>
        )}

        {totalPages > 1 && !activeLoading && (
          <div className="flex justify-center items-center gap-2 mt-8 flex-wrap">
            <button
              onClick={() => setCurrentPage((prev) => Math.max(prev - 1, 1))}
              disabled={currentPage === 1}
              className="px-3 py-2 border rounded-md bg-white hover:bg-slate-50 disabled:opacity-50"
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
                      ? "bg-slate-900 text-white border-slate-900"
                      : "bg-white text-slate-700 hover:bg-slate-50"
                  }`}
                >
                  {page}
                </button>
              ),
            )}
            <button
              onClick={() =>
                setCurrentPage((prev) => Math.min(prev + 1, totalPages))
              }
              disabled={currentPage === totalPages}
              className="px-3 py-2 border rounded-md bg-white hover:bg-slate-50 disabled:opacity-50"
            >
              Sau
            </button>
          </div>
        )}
      </div>
    </Wrapper>
  );
}
