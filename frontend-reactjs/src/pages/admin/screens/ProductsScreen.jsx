import { useEffect, useState } from "react";
import { Eye, Heart, Search, Trash2 } from "lucide-react";
import adminApi from "../../../api/admin.api";
import { aiApi, productApi } from "../../../api";

const statusOptions = [
  { value: "", label: "Tất cả trạng thái" },
  { value: "pending", label: "Chờ duyệt" },
  { value: "approved", label: "Đã duyệt" },
  { value: "rejected", label: "Từ chối" },
];

const ProductsScreen = () => {
  const [products, setProducts] = useState([]);
  const [majors, setMajors] = useState([]);
  const [filters, setFilters] = useState({ q: "", status: "", major_id: "" });
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(12);
  const [pagination, setPagination] = useState(null);
  const [loading, setLoading] = useState(true);
  const [aiEnabled, setAiEnabled] = useState(false);

  const fetchProducts = async (targetPage = page) => {
    setLoading(true);
    try {
      const keyword = filters.q.trim();

      if (keyword && aiEnabled) {
        const res = await aiApi.searchProducts(keyword);
        let nextProducts = res.products || [];

        if (filters.status) {
          nextProducts = nextProducts.filter((product) => product.status === filters.status);
        }

        if (filters.major_id) {
          nextProducts = nextProducts.filter(
            (product) => String(product.major_id) === String(filters.major_id),
          );
        }

        setProducts(nextProducts);
        setPagination({
          current_page: 1,
          from: nextProducts.length ? 1 : 0,
          last_page: 1,
          per_page: nextProducts.length || perPage,
          to: nextProducts.length,
          total: nextProducts.length,
        });
        return;
      }

      if (keyword) {
        const res = await productApi.searchProducts({
          q: keyword,
          status: filters.status,
          major_id: filters.major_id,
          page: targetPage,
          per_page: perPage,
        });
        const paginator = res.data || {};

        setProducts(paginator.data || res.products || []);
        setPagination({
          current_page: paginator.current_page || 1,
          from: paginator.from || 0,
          last_page: paginator.last_page || 1,
          per_page: paginator.per_page || perPage,
          to: paginator.to || 0,
          total: paginator.total ?? res.count ?? 0,
        });
        return;
      }

      const res = await adminApi.getProducts({
        ...filters,
        page: targetPage,
        per_page: perPage,
      });
      const paginator = res.data || {};

      setProducts(paginator.data || []);
      setPagination({
        current_page: paginator.current_page || 1,
        from: paginator.from || 0,
        last_page: paginator.last_page || 1,
        per_page: paginator.per_page || perPage,
        to: paginator.to || 0,
        total: paginator.total || 0,
      });
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    adminApi.getMajors().then((res) => setMajors(res.data || []));
  }, []);

  useEffect(() => {
    setPage(1);
    fetchProducts(1);
  }, [filters.status, filters.major_id, perPage, aiEnabled]);

  const handleSearch = (event) => {
    event.preventDefault();
    setPage(1);
    fetchProducts(1);
  };

  const goToPage = (nextPage) => {
    const lastPage = pagination?.last_page || 1;
    const safePage = Math.min(Math.max(nextPage, 1), lastPage);

    setPage(safePage);
    fetchProducts(safePage);
  };

  const changeStatus = async (productId, status) => {
    await adminApi.updateProductStatus(productId, status);
    fetchProducts(page);
  };

  const deleteProduct = async (productId) => {
    if (!window.confirm("Xóa sản phẩm này?")) return;
    await adminApi.deleteProduct(productId);
    fetchProducts(page);
  };

  const pageNumbers = (() => {
    const current = pagination?.current_page || page;
    const last = pagination?.last_page || 1;
    const start = Math.max(1, current - 2);
    const end = Math.min(last, current + 2);

    return Array.from({ length: end - start + 1 }, (_, index) => start + index);
  })();

  return (
    <div className="space-y-5">
      <form onSubmit={handleSearch} className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div className="grid gap-3 md:grid-cols-[1fr_180px_220px_120px_auto]">
          <label className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
            <input
              value={filters.q}
              onChange={(event) => setFilters((prev) => ({ ...prev, q: event.target.value }))}
              placeholder="Tìm theo tên sản phẩm..."
              className="h-11 w-full rounded-lg border border-slate-200 pl-10 pr-3 outline-none focus:border-emerald-500"
            />
          </label>
          <select
            value={filters.status}
            onChange={(event) => setFilters((prev) => ({ ...prev, status: event.target.value }))}
            className="h-11 rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-500"
          >
            {statusOptions.map((option) => (
              <option key={option.value} value={option.value}>{option.label}</option>
            ))}
          </select>
          <select
            value={filters.major_id}
            onChange={(event) => setFilters((prev) => ({ ...prev, major_id: event.target.value }))}
            className="h-11 rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-500"
          >
            <option value="">Tất cả chuyên ngành</option>
            {majors.map((major) => (
              <option key={major.major_id} value={major.major_id}>{major.major_name}</option>
            ))}
          </select>
          <label className="flex h-11 items-center justify-center gap-2 rounded-lg border border-slate-200 px-3 text-xs font-medium text-slate-600">
            <span>Scout</span>
            <button
              type="button"
              onClick={() => setAiEnabled((prev) => !prev)}
              className={`relative h-6 w-11 rounded-full transition ${
                aiEnabled ? "bg-emerald-600" : "bg-slate-300"
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
          </label>
          <button type="submit" className="h-11 rounded-lg bg-emerald-600 px-5 font-semibold text-white hover:bg-emerald-700">
            Tìm
          </button>
        </div>
        <div className="mt-3 flex items-center justify-between gap-3 text-sm text-slate-500">
          <span>
            {pagination?.total
              ? `Hiển thị ${pagination.from}-${pagination.to} / ${pagination.total} sản phẩm`
              : "Chưa có dữ liệu phân trang"}
          </span>
          <label className="flex items-center gap-2">
            Mỗi trang
            <select
              value={perPage}
              onChange={(event) => setPerPage(Number(event.target.value))}
              className="h-9 rounded-lg border border-slate-200 px-2 outline-none focus:border-emerald-500"
            >
              {[10, 12, 20, 50].map((value) => (
                <option key={value} value={value}>
                  {value}
                </option>
              ))}
            </select>
          </label>
        </div>
      </form>

      <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[900px] text-left text-sm">
            <thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-3">Sản phẩm</th>
                <th className="px-4 py-3">Sinh viên</th>
                <th className="px-4 py-3">Chuyên ngành</th>
                <th className="px-4 py-3">Danh mục</th>
                <th className="px-4 py-3">Tương tác</th>
                <th className="px-4 py-3">Trạng thái</th>
                <th className="px-4 py-3 text-right">Thao tác</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {loading ? (
                <tr><td className="px-4 py-8 text-center text-slate-500" colSpan="7">Đang tải...</td></tr>
              ) : products.length === 0 ? (
                <tr><td className="px-4 py-8 text-center text-slate-500" colSpan="7">Chưa có sản phẩm phù hợp.</td></tr>
              ) : (
                products.map((product) => (
                  <tr key={product.product_id} className="align-top">
                    <td className="px-4 py-3">
                      <p className="font-semibold text-slate-900">{product.title}</p>
                      <p className="mt-1 line-clamp-2 max-w-sm text-xs text-slate-500">{product.description}</p>
                    </td>
                    <td className="px-4 py-3 text-slate-600">{product.student_name || product.user_id}</td>
                    <td className="px-4 py-3 text-slate-600">{product.major_name}</td>
                    <td className="px-4 py-3 text-slate-600">{product.category_name}</td>
                    <td className="px-4 py-3">
                      <div className="flex gap-3 text-slate-500">
                        <span className="flex items-center gap-1"><Eye size={15} />{product.views}</span>
                        <span className="flex items-center gap-1"><Heart size={15} />{product.likes}</span>
                      </div>
                    </td>
                    <td className="px-4 py-3">
                      <select
                        value={product.status}
                        onChange={(event) => changeStatus(product.product_id, event.target.value)}
                        className="h-9 rounded-lg border border-slate-200 px-2 text-sm outline-none focus:border-emerald-500"
                      >
                        <option value="pending">Chờ duyệt</option>
                        <option value="approved">Đã duyệt</option>
                        <option value="rejected">Từ chối</option>
                      </select>
                    </td>
                    <td className="px-4 py-3 text-right">
                      <button
                        type="button"
                        onClick={() => deleteProduct(product.product_id)}
                        className="inline-flex h-9 w-9 items-center justify-center rounded-lg text-rose-600 hover:bg-rose-50"
                        title="Xóa sản phẩm"
                      >
                        <Trash2 size={17} />
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
        {pagination && pagination.last_page > 1 && (
          <div className="flex flex-col gap-3 border-t border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <p className="text-sm text-slate-500">
              Trang {pagination.current_page} / {pagination.last_page}
            </p>
            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={() => goToPage(pagination.current_page - 1)}
                disabled={pagination.current_page <= 1 || loading}
                className="h-9 rounded-lg border border-slate-200 px-3 text-sm font-medium text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
              >
                Trước
              </button>
              {pageNumbers.map((pageNumber) => (
                <button
                  key={pageNumber}
                  type="button"
                  onClick={() => goToPage(pageNumber)}
                  disabled={loading}
                  className={`h-9 min-w-9 rounded-lg px-3 text-sm font-semibold ${
                    pageNumber === pagination.current_page
                      ? "bg-emerald-600 text-white"
                      : "border border-slate-200 text-slate-600 hover:bg-slate-50"
                  }`}
                >
                  {pageNumber}
                </button>
              ))}
              <button
                type="button"
                onClick={() => goToPage(pagination.current_page + 1)}
                disabled={pagination.current_page >= pagination.last_page || loading}
                className="h-9 rounded-lg border border-slate-200 px-3 text-sm font-medium text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
              >
                Sau
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default ProductsScreen;
