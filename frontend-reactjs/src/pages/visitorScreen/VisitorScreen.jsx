import React, {
  useMemo,
  useState,
  useCallback,
  useEffect,
  useRef,
  Suspense,
} from "react";

import { Icons } from "../../components/common/Icon";
import { useNavigate, useSearchParams } from "react-router-dom";
import useMajorAll from "../../hooks/common/useMajorAll";
import useVisitorProduct from "../../hooks/useProduct/useVisitorProduct";
import ChatBoxAi from "../../pages/chatBoxAi/ChatBoxAi";
import useSearchAi from "../../hooks/ai/useSearchAi";
import useDebounce from "../../hooks/common/useDebounce";
import useProductSearch from "../../hooks/useProduct/useProductSearch";
import useScrollControls from "../../hooks/common/useScrollControls";
import ScrollButtons from "../../components/common/ScrollButtons";
import { productApi, systemSettingsApi } from "../../api";
import PublicHeader from "../../layouts/PublicHeader";
import { getInvalidCharacterMessage } from "../../utils/sanitizeInput";

const MAX_SEARCH_KEYWORD_LENGTH = 300;
const VisitorHeroScene = React.lazy(
  () => import("../../components/visitor/VisitorHeroScene"),
);

const HeartIcon = ({ filled = false }) => (
  <svg
    className={`w-4 h-4 ${
      filled ? "text-[#C8102E] fill-current" : "text-gray-400"
    }`}
    fill={filled ? "currentColor" : "none"}
    stroke="currentColor"
    viewBox="0 0 24 24"
  >
    <path
      strokeLinecap="round"
      strokeLinejoin="round"
      strokeWidth="2"
      d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
    />
  </svg>
);

const majorIcons = {
  "Artificial Intelligence": "🧠",
  "Công nghệ thông tin": "💻",
  "Mạng máy tính": "🌐",
  "Thiết kế đồ họa": "🎨",
};

const normalizeAiProduct = (product) => ({
  id: product.product_id,
  title: product.title,
  cate_id: product.cate_id,
  description: product.description,
  thumbnail: product.thumbnail,
  year: product.submitted_at
    ? new Date(product.submitted_at).getFullYear()
    : null,
  student: product.student || "Ẩn danh",
  studentId: product.studentId || null,
  major_id: product.major_id,
  major: product.major_name,
  type: product.category_name,
  views: Number(product.views || 0),
  likes: Number(product.likes || 0),
  advisor: product.advisor || null,
});

const normalizeSearchProduct = (product) => ({
  id: product.product_id,
  title: product.title,
  cate_id: product.cate_id,
  description: product.description,
  thumbnail: product.thumbnail,
  year: product.submitted_at
    ? new Date(product.submitted_at).getFullYear()
    : null,
  student: product.student_name || product.student || "Ẩn danh",
  studentId: product.student_id || product.studentId || null,
  major_id: product.major_id,
  major: product.major_name || product.major,
  type: product.category_name || product.type,
  views: Number(product.views || 0),
  likes: Number(product.likes || 0),
  advisor: product.advisor || null,
});

const getRoleSearchConfig = (role, majorName) => {
  if (role === "student") {
    return {
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

  if (role === "teacher") {
    return {
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

  return {
    placeholder:
      "Tìm đồ án đã duyệt: AI Python, web Laravel, thiết kế Figma...",
    suggestions: [
      "Đồ án đã duyệt nhiều lượt xem",
      "Đồ án AI dùng Python",
      "Đồ án thiết kế bằng Figma",
    ],
  };
};

export default function VisitorScreen() {
  const [likedProducts, setLikedProducts] = useState({});
  const [selectedMajor, setSelectedMajor] = useState("all");
  const [sortBy, setSortBy] = useState("newest");
  const [aiEnabled, setAiEnabled] = useState(false);
  const [systemSettings, setSystemSettings] = useState({
    ai_search_enabled: true,
  });

  // giữ UI input
  const [searchTerm, setSearchTerm] = useState("");
  const [localSearchError, setLocalSearchError] = useState("");

  // pagination
  const [currentPage, setCurrentPage] = useState(1);
  const ITEMS_PER_PAGE = 9;

  const navigate = useNavigate();
  const [searchParams, setSearchParams] = useSearchParams();
  const majorParam = searchParams.get("major");
  const { handleBottom } = useScrollControls();

  const { majorAll, loadingMajorAll } = useMajorAll();
  const visitorProductParams = useMemo(
    () => ({
      page: currentPage,
      per_page: ITEMS_PER_PAGE,
      major_id: selectedMajor === "all" ? undefined : selectedMajor,
      sort_by: sortBy,
    }),
    [currentPage, selectedMajor, sortBy],
  );
  const {
    productVisitor,
    paginationVisitor,
    visitorStats,
    loadingVisitor,
    errorVisitor,
  } = useVisitorProduct(visitorProductParams);
  const { searchAi, clearSearch, searchResult, searchError, loadingSearchAi } =
    useSearchAi();
  const {
    searchProducts,
    clearProductSearch,
    productSearchResult,
    productSearchError,
    loadingProductSearch,
  } = useProductSearch({ visitor: true });

  const canUseAiSearch = systemSettings.ai_search_enabled !== false;
  const effectiveAiEnabled = aiEnabled && canUseAiSearch;

  useEffect(() => {
    systemSettingsApi
      .getPublicSettings()
      .then((res) => {
        const nextSettings = {
          ai_search_enabled: res.data?.ai_search_enabled !== false,
        };

        setSystemSettings(nextSettings);

        if (!nextSettings.ai_search_enabled) {
          setAiEnabled(false);
        }
      })
      .catch(() => {
        setSystemSettings({ ai_search_enabled: true });
      });
  }, []);

  useEffect(() => {
    if (!majorParam) {
      setSelectedMajor("all");
      return;
    }

    setSelectedMajor(majorParam);
    setCurrentPage(1);
    window.setTimeout(() => {
      document.getElementById("san-pham")?.scrollIntoView({
        behavior: "smooth",
        block: "start",
      });
    }, 100);
  }, [majorParam]);

  const handleSelectMajor = useCallback(
    (majorId) => {
      setSelectedMajor(majorId);
      setCurrentPage(1);

      if (majorId === "all") {
        setSearchParams({});
        return;
      }

      setSearchParams({ major: String(majorId) });
    },
    [setSearchParams],
  );

  const debouncedSearchTerm = useDebounce(searchTerm, 700);
  const lastSearchRef = useRef("");
  const productCardObserverRef = useRef(null);
  const productCardRefs = useRef(new Map());
  const [visibleProductIds, setVisibleProductIds] = useState(() => new Set());

  const currentUser = useMemo(() => {
    try {
      return JSON.parse(sessionStorage.getItem("auth_user"));
    } catch {
      return null;
    }
  }, []);

  const currentMajorName = useMemo(() => {
    if (!currentUser?.major_id) return "";

    return (
      majorAll?.find(
        (major) => String(major.major_id) === String(currentUser.major_id),
      )?.major_name || ""
    );
  }, [currentUser, majorAll]);

  const searchConfig = getRoleSearchConfig(
    currentUser?.role ?? "guest",
    currentMajorName,
  );

  const productsSource = useMemo(() => {
    if (effectiveAiEnabled && searchResult) {
      return (searchResult.products ?? []).map(normalizeAiProduct);
    }

    if (!effectiveAiEnabled && productSearchResult) {
      return (productSearchResult.products ?? []).map(normalizeSearchProduct);
    }

    return productVisitor ?? [];
  }, [effectiveAiEnabled, productVisitor, productSearchResult, searchResult]);

  const getSearchParams = useCallback(
    (keyword, page = 1) => ({
      q: keyword,
      page,
      per_page: ITEMS_PER_PAGE,
      major_id: selectedMajor === "all" ? undefined : selectedMajor,
      sort_by: sortBy,
    }),
    [selectedMajor, sortBy],
  );

  const handleSearchSubmit = async (e) => {
    e.preventDefault();
    const keyword = searchTerm.trim();
    const characterError = getInvalidCharacterMessage("search", keyword, {
      label: "Nội dung tìm kiếm",
    });

    if (characterError) {
      setLocalSearchError(characterError);
      return;
    }

    setLocalSearchError("");
    const useAiSearch = effectiveAiEnabled;
    const searchKey = `${useAiSearch ? "ai" : "normal"}:${keyword}`;
    if (!keyword || lastSearchRef.current === searchKey) return;

    lastSearchRef.current = searchKey;
    setCurrentPage(1);
    if (useAiSearch) {
      await searchAi(keyword);
      return;
    }

    await searchProducts(getSearchParams(keyword, 1));
  };

  const handleClearSearch = () => {
    setSearchTerm("");
    setLocalSearchError("");
    lastSearchRef.current = "";
    clearSearch();
    clearProductSearch();
    setCurrentPage(1);
  };

  const handleSuggestionSearch = async (suggestion) => {
    setSearchTerm(suggestion);
    setLocalSearchError("");
    const useAiSearch = effectiveAiEnabled;
    const searchKey = `${useAiSearch ? "ai" : "normal"}:${suggestion}`;
    if (lastSearchRef.current === searchKey) return;

    lastSearchRef.current = searchKey;
    setCurrentPage(1);
    if (useAiSearch) {
      await searchAi(suggestion);
      return;
    }

    await searchProducts(getSearchParams(suggestion, 1));
  };

  useEffect(() => {
    const keyword = debouncedSearchTerm.trim();

    if (!keyword) {
      setLocalSearchError("");
      if (lastSearchRef.current) {
        lastSearchRef.current = "";
        clearSearch();
        clearProductSearch();
      }
      return;
    }

    const characterError = getInvalidCharacterMessage("search", keyword, {
      label: "Nội dung tìm kiếm",
    });

    if (characterError) {
      setLocalSearchError(characterError);
      return;
    }

    setLocalSearchError("");

    const useAiSearch = effectiveAiEnabled;
    const searchKey = `${useAiSearch ? "ai" : "normal"}:${keyword}`;
    if (keyword.length < 2 || lastSearchRef.current === searchKey) return;

    lastSearchRef.current = searchKey;
    if (useAiSearch) {
      searchAi(keyword);
      return;
    }

    searchProducts(getSearchParams(keyword, 1));
  }, [
    effectiveAiEnabled,
    debouncedSearchTerm,
    clearProductSearch,
    clearSearch,
    searchAi,
    searchProducts,
  ]);

  useEffect(() => {
    lastSearchRef.current = "";
    clearSearch();
    clearProductSearch();

    const keyword = searchTerm.trim();
    if (keyword.length < 2) return;

    const characterError = getInvalidCharacterMessage("search", keyword, {
      label: "Nội dung tìm kiếm",
    });

    if (characterError) {
      setLocalSearchError(characterError);
      return;
    }

    const useAiSearch = effectiveAiEnabled;
    lastSearchRef.current = `${useAiSearch ? "ai" : "normal"}:${keyword}`;
    if (useAiSearch) {
      searchAi(keyword);
      return;
    }

    searchProducts(getSearchParams(keyword, 1));
  }, [effectiveAiEnabled]);

  useEffect(() => {
    const keyword = searchTerm.trim();

    if (effectiveAiEnabled || !productSearchResult || !keyword) return;

    searchProducts(getSearchParams(keyword, currentPage));
  }, [currentPage, selectedMajor, sortBy]);

  const activeSearchResult = effectiveAiEnabled
    ? searchResult
    : productSearchResult;
  const activeSearchError =
    localSearchError || (effectiveAiEnabled ? searchError : productSearchError);
  const activeSearchLoading = effectiveAiEnabled
    ? loadingSearchAi
    : loadingProductSearch;

  const activeSearchMessage =
    activeSearchResult?.message && effectiveAiEnabled
      ? activeSearchResult.message
      : "";

  const handleViewDetail = useCallback(
    (id) => {
      if (!id) return;

      navigate(`/chi-tiet-san-pham/${id}`, { state: { productId: id } });
    },
    [navigate],
  );

  const handleLike = useCallback(
    async (id) => {
      if (!id || likedProducts[id]) return;

      setLikedProducts((prev) => ({
        ...prev,
        [id]: true,
      }));

      try {
        await productApi.incrementLike(id);
      } catch (error) {
        console.error(error);
        setLikedProducts((prev) => ({
          ...prev,
          [id]: false,
        }));
      }
    },
    [likedProducts],
  );

  const filteredProducts = useMemo(() => {
    const base = productsSource;

    if (!effectiveAiEnabled) {
      return base;
    }

    return base
      .filter((p) => {
        const matchMajor =
          selectedMajor === "all" ||
          String(p.major_id) === String(selectedMajor);

        return matchMajor;
      })
      .filter((p) => {
        if (!searchTerm || activeSearchResult) return true;
        return p.title?.toLowerCase().includes(searchTerm.toLowerCase());
      })
      .sort((a, b) => {
        if (activeSearchResult && sortBy === "newest") {
          return 0;
        }

        if (sortBy === "newest") {
          return (b.year || 0) - (a.year || 0);
        }

        if (sortBy === "most_viewed") {
          return (b.views || 0) - (a.views || 0);
        }

        if (sortBy === "most_liked") {
          return (
            (b.likes || 0) +
            (likedProducts[b.id] ? 1 : 0) -
            ((a.likes || 0) + (likedProducts[a.id] ? 1 : 0))
          );
        }

        return 0;
      });
  }, [
    effectiveAiEnabled,
    productsSource,
    selectedMajor,
    sortBy,
    likedProducts,
    searchTerm,
    activeSearchResult,
  ]);

  const serverPagination = activeSearchResult?.data?.current_page
    ? activeSearchResult.data
    : paginationVisitor;

  const totalPages = effectiveAiEnabled
    ? Math.ceil(filteredProducts.length / ITEMS_PER_PAGE)
    : serverPagination?.last_page || 1;

  const paginatedProducts = useMemo(() => {
    if (!effectiveAiEnabled) {
      return filteredProducts;
    }

    const start = (currentPage - 1) * ITEMS_PER_PAGE;
    return filteredProducts.slice(start, start + ITEMS_PER_PAGE);
  }, [effectiveAiEnabled, filteredProducts, currentPage]);

  useEffect(() => {
    productCardObserverRef.current?.disconnect();

    productCardObserverRef.current = new IntersectionObserver(
      (entries, observer) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;

          const productId = entry.target.dataset.productId;
          if (productId) {
            setVisibleProductIds((prev) => {
              const next = new Set(prev);
              next.add(productId);
              return next;
            });
          }

          observer.unobserve(entry.target);
        });
      },
      {
        threshold: 0.16,
        rootMargin: "0px 0px -70px 0px",
      },
    );

    productCardRefs.current.forEach((node) => {
      productCardObserverRef.current?.observe(node);
    });

    return () => {
      productCardObserverRef.current?.disconnect();
    };
  }, [paginatedProducts]);

  const registerProductCard = useCallback((id) => {
    const key = String(id);

    return (node) => {
      const currentNode = productCardRefs.current.get(key);
      if (currentNode) {
        productCardObserverRef.current?.unobserve(currentNode);
      }

      if (!node) {
        productCardRefs.current.delete(key);
        return;
      }

      node.dataset.productId = key;
      productCardRefs.current.set(key, node);
      productCardObserverRef.current?.observe(node);
    };
  }, []);

  const stats = useMemo(() => {
    return [
      {
        value: visitorStats?.products_count || 0,
        label: "Sản phẩm tiêu biểu",
      },
      {
        value: visitorStats?.students_count || 0,
        label: "Sinh viên tham gia",
      },
      {
        value: visitorStats?.advisors_count || 0,
        label: "Giảng viên hướng dẫn",
      },
      {
        value: visitorStats?.views_count || 0,
        label: "Lượt xem sản phẩm",
      },
    ].map((stat) => ({
      ...stat,
      value: Number(stat.value || 0).toLocaleString("vi-VN"),
    }));
  }, [visitorStats]);

  const activeClass =
    "px-4 py-2 font-medium text-sm text-[#003087] border-b-2 border-[#003087]";

  const normalClass =
    "px-4 py-2 font-medium text-sm text-gray-500 hover:text-[#003087] border-b-2 border-transparent";

  return (
    <div className="min-h-screen bg-[#F8FAFC]">
      <PublicHeader title="Trưng bày sản phẩm sinh viên" />

      <main>
        {/* HERO */}
        <section className="relative overflow-hidden bg-[#003087] py-12 text-white md:py-16">
          <div className="absolute inset-0 bg-[#00245f]" />
          <Suspense fallback={null}>
            <VisitorHeroScene />
          </Suspense>
          <div className="absolute inset-0 bg-[#003087]/80" />

          <div className="container relative z-10 mx-auto px-4 sm:px-6 lg:px-8">
            <div className="mx-auto max-w-4xl text-center">
              <p className="mb-3 text-sm text-blue-100 md:text-base">
                Khoa Công Nghệ Thông Tin
              </p>

              <h1 className="text-3xl font-bold leading-tight md:text-4xl lg:text-5xl">
                Triển lãm sản phẩm sinh viên
              </h1>

              <p className="mx-auto mt-4 max-w-2xl text-base text-blue-100 md:text-lg">
                Khám phá các đồ án, dự án nghiên cứu và sản phẩm sáng tạo
                <br className="hidden md:block" />
                của sinh viên 4 chuyên ngành công nghệ tại TDC
              </p>

              <div className="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <button
                  type="button"
                  onClick={() => handleBottom("/khach-tham-quan", "san-pham")}
                  className="rounded-md bg-white px-6 py-2.5 text-sm font-semibold text-[#003087] transition-all hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-[#9ee7ff] focus:ring-offset-2 focus:ring-offset-[#003087]"
                >
                  Xem tất cả sản phẩm
                </button>

                <a
                  href="https://fit.tdc.edu.vn/"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="rounded-md border border-white px-6 py-2.5 text-sm font-semibold text-white transition-all hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-[#ffd166] focus:ring-offset-2 focus:ring-offset-[#003087]"
                >
                  Tìm hiểu thêm
                </a>
              </div>
            </div>

            <div className="mx-auto mt-12 grid max-w-4xl grid-cols-2 gap-4 md:grid-cols-4">
              {stats.map((stat, idx) => (
                <div key={idx} className="text-center">
                  <div className="text-2xl font-bold md:text-3xl">
                    {stat.value}
                  </div>

                  <div className="mt-1 text-xs text-blue-100 md:text-sm">
                    {stat.label}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>

        {/* FILTER */}
        <section className="container mx-auto px-4 sm:px-6 lg:px-8 -mt-6 relative z-20">
          <form
            onSubmit={handleSearchSubmit}
            className="bg-white rounded-lg shadow-md border border-gray-200 p-2 md:p-3 flex flex-col md:flex-row gap-3"
          >
            <div className="flex-1 flex items-center bg-gray-50 rounded-md px-4 py-2.5 gap-2">
              <Icons.Search />
              <input
                type="text"
                placeholder={searchConfig.placeholder}
                maxLength={MAX_SEARCH_KEYWORD_LENGTH}
                className="flex-1 bg-transparent outline-none text-gray-700 text-sm"
                value={searchTerm}
                onChange={(e) => {
                  setSearchTerm(e.target.value);
                  setLocalSearchError("");
                  setCurrentPage(1);
                }}
              />
              {searchTerm && (
                <button
                  type="button"
                  onClick={handleClearSearch}
                  className="text-gray-400 hover:text-gray-700 text-sm"
                >
                  Xóa
                </button>
              )}
            </div>

            <select
              className="px-4 py-2.5 bg-gray-50 rounded-md outline-none text-gray-600 text-sm border-0 cursor-pointer"
              value={selectedMajor}
              onChange={(e) => {
                handleSelectMajor(e.target.value);
              }}
              disabled={loadingMajorAll}
            >
              {loadingMajorAll ? (
                <option>Đang tải ngành...</option>
              ) : (
                <>
                  <option value="all">Tất cả ngành</option>

                  {majorAll?.map((major) => (
                    <option key={major.major_id} value={major.major_id}>
                      {majorIcons[major?.major_name]} {major.major_name}
                    </option>
                  ))}
                </>
              )}
            </select>

            <select
              className="px-4 py-2.5 bg-gray-50 rounded-md outline-none text-gray-600 text-sm border-0 cursor-pointer"
              value={sortBy}
              onChange={(e) => {
                setSortBy(e.target.value);
                setCurrentPage(1);
              }}
            >
              <option value="newest">🆕 Mới nhất</option>
              <option value="most_viewed">👁️ Xem nhiều nhất</option>
              <option value="most_liked">❤️ Yêu thích nhất</option>
            </select>

            <label className="flex h-11 items-center justify-center gap-2 rounded-md bg-gray-50 px-3 text-xs font-medium text-gray-600">
              <span>Thường</span>
              <button
                type="button"
                onClick={() => canUseAiSearch && setAiEnabled((prev) => !prev)}
                disabled={!canUseAiSearch}
                className={`relative h-6 w-11 rounded-full transition ${
                  effectiveAiEnabled ? "bg-[#003087]" : "bg-gray-300"
                } disabled:cursor-not-allowed disabled:opacity-60`}
                aria-pressed={effectiveAiEnabled}
                title={
                  canUseAiSearch
                    ? effectiveAiEnabled
                      ? "Tắt AI Search"
                      : "Bật AI Search"
                    : "AI Search đã bị quản trị viên tắt"
                }
              >
                <span
                  className={`absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition ${
                    effectiveAiEnabled ? "left-5" : "left-0.5"
                  }`}
                />
              </button>
              <span>AI</span>
            </label>

            <button
              type="submit"
              disabled={activeSearchLoading || !searchTerm.trim()}
              className="px-5 py-2.5 bg-[#003087] text-white rounded-md font-semibold text-sm hover:bg-[#00266b] transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {activeSearchLoading
                ? "Đang tìm..."
                : effectiveAiEnabled
                  ? "Tìm AI"
                  : "Tìm thường"}
            </button>
          </form>

          {activeSearchError && (
            <p className="mt-3 text-sm text-red-600 bg-red-50 border border-red-100 rounded-md px-4 py-2">
              {activeSearchError}
            </p>
          )}

          {activeSearchResult && (
            <div className="mt-3 space-y-2">
              {activeSearchMessage && (
                <p
                  className={`text-sm rounded-md px-4 py-2 border ${
                    activeSearchResult.count > 0
                      ? "text-green-700 bg-green-50 border-green-100"
                      : "text-amber-700 bg-amber-50 border-amber-100"
                  }`}
                >
                  {activeSearchMessage}
                </p>
              )}

              <div className="flex flex-wrap items-center justify-between gap-2 text-sm text-gray-600">
                <p>
                  {effectiveAiEnabled ? "AI" : "Tìm thường"} tìm thấy{" "}
                  <span className="font-semibold text-[#003087]">
                    {activeSearchResult.count ?? filteredProducts.length}
                  </span>{" "}
                  sản phẩm phù hợp
                </p>

                <button
                  type="button"
                  onClick={handleClearSearch}
                  className="text-[#003087] font-medium hover:underline"
                >
                  Quay lại danh sách ban đầu
                </button>
              </div>
            </div>
          )}

          <div className="mt-3 flex flex-wrap gap-2">
            {searchConfig.suggestions.map((suggestion) => (
              <button
                key={suggestion}
                type="button"
                onClick={() => handleSuggestionSearch(suggestion)}
                className="rounded-md border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 hover:border-[#003087]/30 hover:bg-blue-50 hover:text-[#003087] transition"
              >
                {suggestion}
              </button>
            ))}
          </div>
        </section>

        {/* MAJOR FILTER */}
        <section
          id="san-pham"
          className="container mx-auto px-4 sm:px-6 lg:px-8 py-8 border-b border-gray-200 scroll-mt-24"
        >
          <div className="flex flex-wrap justify-center gap-1">
            <button
              onClick={() => handleSelectMajor("all")}
              className={selectedMajor === "all" ? activeClass : normalClass}
            >
              📌 Tất cả
            </button>

            {loadingMajorAll ? (
              <div className="px-4 py-2 text-sm text-gray-500">
                Đang tải ngành...
              </div>
            ) : (
              majorAll?.map((major) => (
                <button
                  key={major.major_id}
                  onClick={() => handleSelectMajor(major.major_id)}
                  className={
                    String(selectedMajor) === String(major.major_id)
                      ? activeClass
                      : normalClass
                  }
                >
                  {majorIcons[major?.major_name]} {major.major_code}
                </button>
              ))
            )}
          </div>
        </section>

        {/* PRODUCTS */}
        {loadingVisitor || activeSearchLoading ? (
          <p className="p-6 text-center">
            {activeSearchLoading
              ? effectiveAiEnabled
                ? "AI đang tìm kiếm..."
                : "Đang tìm thường..."
              : "Đang tải..."}
          </p>
        ) : errorVisitor ? (
          <p className="p-6 text-center text-red-500">Có lỗi xảy ra</p>
        ) : (
          <section className="container mx-auto px-4 sm:px-6 lg:px-8 pb-16">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {paginatedProducts.map((product, index) => (
                <div
                  key={product.id}
                  ref={registerProductCard(product.id)}
                  className={`group bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-all duration-700 border border-gray-100 ${
                    visibleProductIds.has(String(product.id))
                      ? "translate-y-0 rotate-0 opacity-100"
                      : "translate-y-12 rotate-1 opacity-0"
                  }`}
                  style={{ transitionDelay: `${(index % 3) * 100}ms` }}
                >
                  <div
                    onClick={() => handleViewDetail(product?.id)}
                    className="relative h-48 bg-gray-100 overflow-hidden cursor-pointer"
                  >
                    <img
                      src={product?.thumbnail}
                      alt={product?.title}
                      className="w-full h-full object-cover hover:scale-105 transition-transform duration-500"
                    />

                    <div className="absolute top-3 left-3 flex gap-2">
                      <span className="bg-[#003087] text-white text-xs px-2 py-1 rounded">
                        {product?.year}
                      </span>

                      <span className="bg-gray-800/80 text-white text-xs px-2 py-1 rounded">
                        {product?.type}
                      </span>
                    </div>

                    <button
                      type="button"
                      onClick={(event) => {
                        event.stopPropagation();
                        handleViewDetail(product?.id);
                      }}
                      className="absolute bottom-3 right-3 translate-y-2 rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-[#003087] opacity-0 shadow-sm transition group-hover:translate-y-0 group-hover:opacity-100"
                    >
                      Xem nhanh
                    </button>
                  </div>

                  <div className="p-4">
                    <div className="mb-2">
                      <span className="text-xs text-[#003087] font-medium bg-blue-50 px-2 py-0.5 rounded">
                        {product?.major === "Công nghệ thông tin" && "💻 "}
                        {product?.major === "Trí tuệ nhân tạo" && "🧠 "}
                        {product?.major === "Mạng máy tính" && "🌐 "}
                        {product?.major === "Thiết kế đồ họa" && "🎨 "}
                        {product?.major}
                      </span>
                    </div>

                    <h3 className="text-base font-bold text-gray-800 mb-2 line-clamp-1">
                      {product?.title}
                    </h3>

                    <p className="text-gray-500 text-sm mb-3 line-clamp-2">
                      {product?.description || ""}
                    </p>

                    <div className="flex items-center gap-2 mb-3 pt-2 border-t border-gray-100">
                      <div className="w-8 h-8 rounded-full bg-[#003087] flex items-center justify-center text-white text-xs font-bold">
                        {(product?.student || "").charAt(0)}
                      </div>

                      <div>
                        <p className="text-sm font-medium text-gray-800">
                          {product?.student || ""}
                        </p>

                        <p className="text-xs text-gray-400">
                          {product?.studentId}
                        </p>
                      </div>
                    </div>

                    <div className="text-xs text-gray-500 mb-3">
                      <span className="text-gray-400">👨‍🏫 GVD:</span>{" "}
                      {product?.advisor}
                    </div>

                    <div className="flex items-center justify-between pt-2 border-t border-gray-100">
                      <div className="flex items-center gap-3 text-gray-500 text-xs">
                        <div className="flex items-center gap-1">
                          <Icons.Eye />
                          <span>{product?.views || 0}</span>
                        </div>

                        <button
                          onClick={() => handleLike(product?.id)}
                          className="flex items-center gap-1 hover:text-[#C8102E] transition"
                        >
                          <HeartIcon filled={likedProducts[product.id]} />

                          <span>
                            {(product?.likes || 0) +
                              (likedProducts[product.id] ? 1 : 0)}
                          </span>
                        </button>
                      </div>

                      <button
                        onClick={() => handleViewDetail(product?.id)}
                        className="px-3 py-1 bg-white border border-[#003087] text-[#003087] text-xs rounded-md hover:bg-[#003087] hover:text-white transition"
                      >
                        Xem chi tiết
                      </button>
                    </div>
                  </div>
                </div>
              ))}
            </div>

            {/* PAGINATION */}
            {totalPages > 1 && (
              <div className="flex justify-center items-center gap-2 mt-10 flex-wrap">
                <button
                  onClick={() =>
                    setCurrentPage((prev) => Math.max(prev - 1, 1))
                  }
                  disabled={
                    currentPage <= 1 || loadingVisitor || activeSearchLoading
                  }
                  className="px-3 py-2 border rounded-md bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  ‹
                </button>

                <span className="px-4 py-2 rounded-md border border-[#003087] bg-[#003087] text-sm font-semibold text-white">
                  Trang {currentPage} / {totalPages}
                </span>

                <button
                  onClick={() =>
                    setCurrentPage((prev) => Math.min(prev + 1, totalPages))
                  }
                  disabled={
                    currentPage >= totalPages ||
                    loadingVisitor ||
                    activeSearchLoading
                  }
                  className="px-3 py-2 border rounded-md bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  ›
                </button>
              </div>
            )}

            {filteredProducts.length === 0 && (
              <div className="text-center py-12">
                <div className="text-4xl mb-3">🔍</div>

                <p className="text-gray-500">Không tìm thấy sản phẩm phù hợp</p>
              </div>
            )}
          </section>
        )}
      </main>

      <ChatBoxAi user={null} />
      <ScrollButtons />

      {/* FOOTER */}
      <footer className="bg-[#003087] text-white pt-10 pb-6">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center text-blue-100 text-xs">
            © 2025 Trường Cao Đẳng Công Nghệ Thủ Đức (TDC)
          </div>
        </div>
      </footer>
    </div>
  );
}
