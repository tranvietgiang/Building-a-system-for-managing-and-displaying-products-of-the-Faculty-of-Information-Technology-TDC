import { useEffect, useState } from "react";
import { Eye, Heart, Search, Trash2 } from "lucide-react";
import adminApi from "../../../api/admin.api";

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
  const [loading, setLoading] = useState(true);

  const fetchProducts = async () => {
    setLoading(true);
    try {
      const res = await adminApi.getProducts({ ...filters, per_page: 50 });
      setProducts(res.data?.data || []);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    adminApi.getMajors().then((res) => setMajors(res.data || []));
  }, []);

  useEffect(() => {
    fetchProducts();
  }, [filters.status, filters.major_id]);

  const handleSearch = (event) => {
    event.preventDefault();
    fetchProducts();
  };

  const changeStatus = async (productId, status) => {
    await adminApi.updateProductStatus(productId, status);
    fetchProducts();
  };

  const deleteProduct = async (productId) => {
    if (!window.confirm("Xóa sản phẩm này?")) return;
    await adminApi.deleteProduct(productId);
    fetchProducts();
  };

  return (
    <div className="space-y-5">
      <form onSubmit={handleSearch} className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div className="grid gap-3 md:grid-cols-[1fr_180px_220px_auto]">
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
          <button type="submit" className="h-11 rounded-lg bg-emerald-600 px-5 font-semibold text-white hover:bg-emerald-700">
            Tìm
          </button>
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
      </div>
    </div>
  );
};

export default ProductsScreen;
