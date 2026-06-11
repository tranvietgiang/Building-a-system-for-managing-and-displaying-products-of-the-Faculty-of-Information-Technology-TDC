import React, {
  useMemo,
  useState,
  useCallback,
  useEffect,
  useRef,
  Suspense,
} from "react";

import { Icons } from "../../components/common/Icon";
import { Link, useNavigate } from "react-router-dom";
import useMajorAll from "../../hooks/common/useMajorAll";
import useVisitorProduct from "../../hooks/useProduct/useVisitorProduct";
import ChatBoxAi from "../../pages/chatBoxAi/ChatBoxAi";
import useSearchAi from "../../hooks/ai/useSearchAi";
import useDebounce from "../../hooks/common/useDebounce";
import useProductSearch from "../../hooks/useProduct/useProductSearch";
import { productApi } from "../../api";
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

  // giữ UI input
  const [searchTerm, setSearchTerm] = useState("");

  // pagination
  const [currentPage, setCurrentPage] = useState(1);
  const ITEMS_PER_PAGE = 9;

  const navigate = useNavigate();

  const { majorAll, loadingMajorAll } = useMajorAll();
  const { productVisitor, loadingVisitor, errorVisitor } = useVisitorProduct();
  const { searchAi, clearSearch, searchResult, searchError, loadingSearchAi } =
    useSearchAi();
  const {
    searchProducts,
    clearProductSearch,
    productSearchResult,
    productSearchError,
    loadingProductSearch,
  } = useProductSearch({ visitor: true });

  const debouncedSearchTerm = useDebounce(searchTerm, 700);
  const lastSearchRef = useRef("");

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
    if (aiEnabled && searchResult) {
      return (searchResult.products ?? []).map(normalizeAiProduct);
    }

    if (!aiEnabled && productSearchResult) {
      return (productSearchResult.products ?? []).map(normalizeSearchProduct);
    }

    return productVisitor ?? [];
  }, [aiEnabled, productVisitor, productSearchResult, searchResult]);

  const handleSearchSubmit = async (e) => {
    e.preventDefault();
    const keyword = searchTerm.trim();
    const searchKey = `${aiEnabled ? "ai" : "normal"}:${keyword}`;
    if (!keyword || lastSearchRef.current === searchKey) return;

    lastSearchRef.current = searchKey;
    setCurrentPage(1);
    if (aiEnabled) {
      await searchAi(keyword);
      return;
    }

    await searchProducts({ q: keyword, per_page: 30 });
  };

  const handleClearSearch = () => {
    setSearchTerm("");
    lastSearchRef.current = "";
    clearSearch();
    clearProductSearch();
    setCurrentPage(1);
  };

  const handleSuggestionSearch = async (suggestion) => {
    setSearchTerm(suggestion);
    const searchKey = `${aiEnabled ? "ai" : "normal"}:${suggestion}`;
    if (lastSearchRef.current === searchKey) return;

    lastSearchRef.current = searchKey;
    setCurrentPage(1);
    if (aiEnabled) {
      await searchAi(suggestion);
      return;
    }

    await searchProducts({ q: suggestion, per_page: 30 });
  };

  useEffect(() => {
    const keyword = debouncedSearchTerm.trim();

    if (!keyword) {
      if (lastSearchRef.current) {
        lastSearchRef.current = "";
        clearSearch();
        clearProductSearch();
      }
      return;
    }

    const searchKey = `${aiEnabled ? "ai" : "normal"}:${keyword}`;
    if (keyword.length < 2 || lastSearchRef.current === searchKey) return;

    lastSearchRef.current = searchKey;
    if (aiEnabled) {
      searchAi(keyword);
      return;
    }

    searchProducts({ q: keyword, per_page: 30 });
  }, [
    aiEnabled,
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

    lastSearchRef.current = `${aiEnabled ? "ai" : "normal"}:${keyword}`;
    if (aiEnabled) {
      searchAi(keyword);
      return;
    }

    searchProducts({ q: keyword, per_page: 30 });
  }, [aiEnabled]);

  const activeSearchResult = aiEnabled ? searchResult : productSearchResult;
  const activeSearchError = aiEnabled ? searchError : productSearchError;
  const activeSearchLoading = aiEnabled
    ? loadingSearchAi
    : loadingProductSearch;

  const handleViewDetail = useCallback(
    async (id) => {
      if (!id) return;

      try {
        await productApi.incrementView(id);
      } catch (error) {
        console.error(error);
      }

      navigate(`/visitor-detail/${id}`, { state: { productId: id } });
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
    productsSource,
    selectedMajor,
    sortBy,
    likedProducts,
    searchTerm,
    activeSearchResult,
  ]);

  const totalPages = Math.ceil(filteredProducts.length / ITEMS_PER_PAGE);

  const paginatedProducts = useMemo(() => {
    const start = (currentPage - 1) * ITEMS_PER_PAGE;
    return filteredProducts.slice(start, start + ITEMS_PER_PAGE);
  }, [filteredProducts, currentPage]);

  const stats = [
    { value: "128", label: "Sản phẩm tiêu biểu" },
    { value: "342", label: "Sinh viên tham gia" },
    { value: "45", label: "Giảng viên hướng dẫn" },
    { value: "24", label: "Giải thưởng đạt được" },
  ];

  const activeClass =
    "px-4 py-2 font-medium text-sm text-[#003087] border-b-2 border-[#003087]";

  const normalClass =
    "px-4 py-2 font-medium text-sm text-gray-500 hover:text-[#003087] border-b-2 border-transparent";

  return (
    <div className="min-h-screen bg-[#F8FAFC]">
      {/* HEADER */}
      <header className="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm">
        <div className="container mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16 md:h-20">
            <div className="flex items-center gap-3">
              <Icons.Logo />

              <div className="hidden sm:block">
                <h1 className="text-lg md:text-xl font-bold text-[#003087] leading-tight">
                  Trưng bày sản phẩm sinh viên
                </h1>

                <p className="text-xs text-gray-500 -mt-0.5">
                  Khoa Công Nghệ Thông Tin | TDC
                </p>
              </div>
            </div>

            <nav className="hidden md:flex items-center gap-6">
              {[
                "Trang chủ",
                "Sản phẩm",
                "Ngành học",
                "Hướng dẫn",
                "Liên hệ",
              ].map((item) => (
                <Link
                  key={item}
                  to={item === "Hướng dẫn" ? "/huong-dan" : "/nckh-visitor"}
                  className="text-gray-600 hover:text-[#003087] font-medium transition-colors text-sm"
                >
                  {item}
                </Link>
              ))}
            </nav>

            <div className="flex items-center gap-3">
              <button
                onClick={() => navigate("/login")}
                className="px-5 py-2 text-[#003087] border border-[#003087] rounded-md font-medium text-sm hover:bg-[#003087] hover:text-white transition-all"
              >
                Đăng nhập
              </button>
            </div>
          </div>
        </div>
      </header>

      <main>
        {/* HERO */}
        <section className="bg-[#003087] text-white py-12 md:py-16">
          <div className="container mx-auto px-4 sm:px-6 lg:px-8">
            <div className="max-4xl mx-auto text-center">
              <p className="text-blue-100 text-sm md:text-base mb-3">
                Khoa Công Nghệ Thông Tin
              </p>

              <h1 className="text-3xl md:text-4xl lg:text-5xl font-bold leading-tight">
                Triển lãm sản phẩm sinh viên
              </h1>

              <p className="text-base md:text-lg text-blue-100 mt-4 max-2xl mx-auto">
                Khám phá các đồ án, dự án nghiên cứu và sản phẩm sáng tạo
                <br className="hidden md:block" />
                của sinh viên 4 chuyên ngành công nghệ tại TDC
              </p>

              <div className="flex flex-col sm:flex-row gap-3 justify-center mt-8">
                <button className="px-6 py-2.5 bg-white text-[#003087] rounded-md font-semibold text-sm hover:bg-gray-100 transition-all">
                  Xem tất cả sản phẩm
                </button>

                <a
                  href="https://fit.tdc.edu.vn/"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="px-6 py-2.5 border border-white text-white rounded-md font-semibold text-sm hover:bg-white/10 transition-all"
                >
                  Tìm hiểu thêm
                </a>
              </div>
            </div>

            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-12 max-4xl mx-auto">
              {stats.map((stat, idx) => (
                <div key={idx} className="text-center">
                  <div className="text-2xl md:text-3xl font-bold">
                    {stat.value}
                  </div>

                  <div className="text-xs md:text-sm text-blue-100 mt-1">
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
                className="flex-1 bg-transparent outline-none text-gray-700 text-sm"
                value={searchTerm}
                onChange={(e) => {
                  setSearchTerm(e.target.value);
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
                setSelectedMajor(e.target.value);
                setCurrentPage(1);
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
              onChange={(e) => setSortBy(e.target.value)}
            >
              <option value="newest">🆕 Mới nhất</option>
              <option value="most_viewed">👁️ Xem nhiều nhất</option>
              <option value="most_liked">❤️ Yêu thích nhất</option>
            </select>

            <label className="flex h-11 items-center justify-center gap-2 rounded-md bg-gray-50 px-3 text-xs font-medium text-gray-600">
              <span>Thường</span>
              <button
                type="button"
                onClick={() => setAiEnabled((prev) => !prev)}
                className={`relative h-6 w-11 rounded-full transition ${
                  aiEnabled ? "bg-[#003087]" : "bg-gray-300"
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

            <button
              type="submit"
              disabled={activeSearchLoading || !searchTerm.trim()}
              className="px-5 py-2.5 bg-[#003087] text-white rounded-md font-semibold text-sm hover:bg-[#00266b] transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {activeSearchLoading
                ? "Đang tìm..."
                : aiEnabled
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
            <div className="mt-3 flex flex-wrap items-center justify-between gap-2 text-sm text-gray-600">
              <p>
                {aiEnabled ? "AI" : "Tìm thường"} tìm thấy{" "}
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
        <section className="container mx-auto px-4 sm:px-6 lg:px-8 py-8 border-b border-gray-200">
          <div className="flex flex-wrap justify-center gap-1">
            <button
              onClick={() => setSelectedMajor("all")}
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
                  onClick={() => setSelectedMajor(major.major_id)}
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
              ? aiEnabled
                ? "AI đang tìm kiếm..."
                : "Đang tìm thường..."
              : "Đang tải..."}
          </p>
        ) : errorVisitor ? (
          <p className="p-6 text-center text-red-500">Có lỗi xảy ra</p>
        ) : (
          <section className="container mx-auto px-4 sm:px-6 lg:px-8 pb-16">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {paginatedProducts.map((product) => (
                <div
                  key={product.id}
                  className="bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-all duration-300 border border-gray-100"
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
                  className="px-3 py-2 border rounded-md bg-white hover:bg-gray-50"
                >
                  ‹
                </button>

                {Array.from({ length: totalPages }, (_, i) => i + 1).map(
                  (page) => (
                    <button
                      key={page}
                      onClick={() => setCurrentPage(page)}
                      className={`px-4 py-2 rounded-md border text-sm ${
                        currentPage === page
                          ? "bg-[#003087] text-white border-[#003087]"
                          : "bg-white text-gray-700 hover:bg-gray-50"
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
                  className="px-3 py-2 border rounded-md bg-white hover:bg-gray-50"
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
