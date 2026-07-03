import { useCallback, useEffect, useMemo, useState } from "react";
import {
  BrainCircuit,
  CheckCircle2,
  Clipboard,
  Loader2,
  RefreshCw,
  Search,
  Trash2,
  XCircle,
} from "lucide-react";
import adminApi from "../../../api/admin.api";

const sourceOptions = [
  { value: "", label: "Tất cả nguồn" },
  { value: "local_search", label: "Không tìm thấy" },
  { value: "mysql_rag", label: "MySQL + AI" },
  { value: "local_search_fallback", label: "MySQL fallback" },
  { value: "local_fallback", label: "AI lỗi" },
  { value: "reply_bank", label: "Reply bank" },
  { value: "feature_guide", label: "Hướng dẫn tính năng" },
  { value: "scope_guard", label: "Chặn theo ngành" },
  { value: "openai_context", label: "OpenAI context" },
];

const roleOptions = [
  { value: "", label: "Tất cả vai trò" },
  { value: "guest", label: "Khách" },
  { value: "student", label: "Sinh viên" },
  { value: "teacher", label: "Giảng viên" },
  { value: "admin", label: "Admin" },
];

const formatDate = (value) => {
  if (!value) return "Chưa có";

  try {
    return new Intl.DateTimeFormat("vi-VN", {
      dateStyle: "short",
      timeStyle: "short",
    }).format(new Date(value));
  } catch {
    return value;
  }
};

const asArray = (value) => (Array.isArray(value) ? value : []);

const buildTrainingText = (log) => {
  const terms = asArray(log.analysis?.terms).join(", ") || "Chưa có";
  const features = asArray(log.analysis?.features).join(", ") || "Chưa có";
  const products =
    asArray(log.products)
      .map((product) => product.title || product.id)
      .filter(Boolean)
      .join("; ") || "Chưa có sản phẩm khớp";

  return [
    `Câu hỏi user: ${log.message || ""}`,
    `Vai trò: ${log.role || "guest"}`,
    `Ngành: ${log.major?.major_name || log.major_id || "Chưa rõ"}`,
    `Nguồn trả lời hiện tại: ${log.source || "unknown"}`,
    `Intent/features: ${features}`,
    `Từ khóa/terms: ${terms}`,
    `Sản phẩm đang match: ${products}`,
    "",
    "Phản hồi hiện tại:",
    log.reply || "",
    "",
    "Gợi ý cập nhật training:",
    "- Nếu câu hỏi hợp lệ nhưng không ra sản phẩm, thêm keyword/alias/semantic terms vào ChatBoxAi.",
    "- Nếu có sản phẩm đúng trong MySQL, thêm cụm từ user hay gõ vào bộ phân tích trước khi gọi AI.",
    "- Nếu câu hỏi ngoài phạm vi, thêm mẫu trả lời phù hợp vào reply bank.",
  ].join("\n");
};

const badgeClass = (active, tone = "emerald") => {
  if (!active) {
    return "border-slate-200 bg-slate-50 text-slate-600";
  }

  const tones = {
    emerald: "border-emerald-200 bg-emerald-50 text-emerald-700",
    amber: "border-amber-200 bg-amber-50 text-amber-700",
    sky: "border-sky-200 bg-sky-50 text-sky-700",
    rose: "border-rose-200 bg-rose-50 text-rose-700",
  };

  return tones[tone] || tones.emerald;
};

const ChatboxTrainingScreen = () => {
  const [logs, setLogs] = useState([]);
  const [stats, setStats] = useState({
    total: 0,
    needs_training: 0,
    unreviewed: 0,
    reviewed: 0,
  });
  const [filters, setFilters] = useState({
    role: "",
    source: "",
    needs_training: "1",
    reviewed: "",
  });
  const [searchText, setSearchText] = useState("");
  const [submittedQuery, setSubmittedQuery] = useState("");
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(20);
  const [pagination, setPagination] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [copiedId, setCopiedId] = useState(null);
  const [updatingId, setUpdatingId] = useState(null);

  const fetchLogs = useCallback(
    async (targetPage = 1) => {
      setLoading(true);
      setError("");

      try {
        const res = await adminApi.getChatboxTrainingLogs({
          ...filters,
          q: submittedQuery,
          page: targetPage,
          per_page: perPage,
        });
        const paginator = res.data || {};

        setLogs(paginator.data || []);
        setStats(res.stats || {});
        setPagination({
          current_page: paginator.current_page || 1,
          from: paginator.from || 0,
          last_page: paginator.last_page || 1,
          per_page: paginator.per_page || perPage,
          to: paginator.to || 0,
          total: paginator.total || 0,
        });
      } catch (err) {
        setError(
          err.response?.data?.message ||
            err.message ||
            "Không thể tải log training chatbot.",
        );
      } finally {
        setLoading(false);
      }
    },
    [filters, perPage, submittedQuery],
  );

  useEffect(() => {
    setPage(1);
    fetchLogs(1);
  }, [fetchLogs]);

  const statCards = useMemo(
    () => [
      { label: "Tổng câu hỏi", value: stats.total || 0, tone: "sky" },
      {
        label: "Cần training",
        value: stats.needs_training || 0,
        tone: "amber",
      },
      { label: "Chưa xử lý", value: stats.unreviewed || 0, tone: "rose" },
      { label: "Đã xử lý", value: stats.reviewed || 0, tone: "emerald" },
    ],
    [stats],
  );

  const pageNumbers = useMemo(() => {
    const current = pagination?.current_page || page;
    const last = pagination?.last_page || 1;
    const start = Math.max(1, current - 2);
    const end = Math.min(last, current + 2);

    return Array.from({ length: end - start + 1 }, (_, index) => start + index);
  }, [page, pagination]);

  const handleSearch = (event) => {
    event.preventDefault();
    const nextQuery = searchText.trim();
    setPage(1);

    if (nextQuery === submittedQuery) {
      fetchLogs(1);
    } else {
      setSubmittedQuery(nextQuery);
    }
  };

  const updateFilter = (key, value) => {
    setFilters((prev) => ({ ...prev, [key]: value }));
  };

  const goToPage = (nextPage) => {
    const lastPage = pagination?.last_page || 1;
    const safePage = Math.min(Math.max(nextPage, 1), lastPage);

    setPage(safePage);
    fetchLogs(safePage);
  };

  const copyLog = async (log) => {
    await navigator.clipboard.writeText(buildTrainingText(log));
    setCopiedId(log.id);
    window.setTimeout(() => setCopiedId(null), 1800);
  };

  const toggleReviewed = async (log) => {
    setUpdatingId(log.id);

    try {
      const nextReviewed = !log.reviewed;
      await adminApi.updateChatboxTrainingLog(log.id, {
        reviewed: nextReviewed,
        needs_training: !nextReviewed,
      });
      fetchLogs(page);
    } finally {
      setUpdatingId(null);
    }
  };

  const deleteLog = async (log) => {
    if (!window.confirm("Xóa log training chatbot này?")) return;

    setUpdatingId(log.id);

    try {
      await adminApi.deleteChatboxTrainingLog(log.id);
      fetchLogs(page);
    } finally {
      setUpdatingId(null);
    }
  };

  return (
    <div className="space-y-5">
      <section className="grid gap-3 md:grid-cols-4">
        {statCards.map((card) => (
          <div
            key={card.label}
            className={`rounded-lg border px-4 py-3 ${badgeClass(true, card.tone)}`}
          >
            <p className="text-xs font-semibold uppercase">{card.label}</p>
            <p className="mt-2 text-2xl font-bold">{card.value}</p>
          </div>
        ))}
      </section>

      <form
        onSubmit={handleSearch}
        className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
      >
        <div className="grid gap-3 xl:grid-cols-[1fr_180px_200px_170px_160px_auto_auto]">
          <label className="relative">
            <Search
              className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
              size={18}
            />
            <input
              value={searchText}
              onChange={(event) => setSearchText(event.target.value)}
              placeholder="Tìm câu hỏi, reply hoặc source..."
              className="h-11 w-full rounded-lg border border-slate-200 pl-10 pr-3 outline-none focus:border-emerald-500"
              maxLength={300}
            />
          </label>

          <select
            value={filters.role}
            onChange={(event) => updateFilter("role", event.target.value)}
            className="h-11 rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-500"
          >
            {roleOptions.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>

          <select
            value={filters.source}
            onChange={(event) => updateFilter("source", event.target.value)}
            className="h-11 rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-500"
          >
            {sourceOptions.map((option) => (
              <option key={option.value} value={option.value}>
                {option.label}
              </option>
            ))}
          </select>

          <select
            value={filters.needs_training}
            onChange={(event) =>
              updateFilter("needs_training", event.target.value)
            }
            className="h-11 rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-500"
          >
            <option value="">Tất cả training</option>
            <option value="1">Cần training</option>
            <option value="0">Không cần</option>
          </select>

          <select
            value={filters.reviewed}
            onChange={(event) => updateFilter("reviewed", event.target.value)}
            className="h-11 rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-500"
          >
            <option value="">Tất cả xử lý</option>
            <option value="0">Chưa xử lý</option>
            <option value="1">Đã xử lý</option>
          </select>

          <button
            type="submit"
            className="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 font-semibold text-white hover:bg-emerald-700"
          >
            <Search size={18} />
            Tìm
          </button>

          <button
            type="button"
            onClick={() => fetchLogs(page)}
            disabled={loading}
            className="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-slate-200 px-4 font-semibold text-slate-600 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
          >
            <RefreshCw size={18} className={loading ? "animate-spin" : ""} />
            Tải lại
          </button>
        </div>

        <div className="mt-3 flex flex-col gap-3 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between">
          <span>
            {pagination?.total
              ? `Hiển thị ${pagination.from}-${pagination.to} / ${pagination.total} câu hỏi`
              : "Chưa có dữ liệu phân trang"}
          </span>
          <label className="flex items-center gap-2">
            Mỗi trang
            <select
              value={perPage}
              onChange={(event) => setPerPage(Number(event.target.value))}
              className="h-9 rounded-lg border border-slate-200 px-2 outline-none focus:border-emerald-500"
            >
              {[10, 20, 50, 100].map((value) => (
                <option key={value} value={value}>
                  {value}
                </option>
              ))}
            </select>
          </label>
        </div>
      </form>

      {error && (
        <div className="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
          {error}
        </div>
      )}

      <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div className="overflow-x-auto">
          <table className="w-full min-w-[1100px] text-left text-sm">
            <thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-3">Câu hỏi</th>
                <th className="px-4 py-3">Người hỏi</th>
                <th className="px-4 py-3">Phân tích</th>
                <th className="px-4 py-3">Kết quả</th>
                <th className="px-4 py-3">Trạng thái</th>
                <th className="px-4 py-3 text-right">Thao tác</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {loading ? (
                <tr>
                  <td
                    className="px-4 py-10 text-center text-slate-500"
                    colSpan="6"
                  >
                    <Loader2 className="mx-auto mb-2 animate-spin" size={22} />
                    Đang tải log training...
                  </td>
                </tr>
              ) : logs.length === 0 ? (
                <tr>
                  <td
                    className="px-4 py-10 text-center text-slate-500"
                    colSpan="6"
                  >
                    Chưa có câu hỏi phù hợp.
                  </td>
                </tr>
              ) : (
                logs.map((log) => {
                  const terms = asArray(log.analysis?.terms).slice(0, 5);
                  const products = asArray(log.products);

                  return (
                    <tr key={log.id} className="align-top">
                      <td className="max-w-md px-4 py-3">
                        <p className="break-words font-semibold text-slate-900">
                          {log.message}
                        </p>
                        <p className="mt-2 text-xs text-slate-500">
                          {formatDate(log.created_at)}
                        </p>
                        {log.reply && (
                          <p className="mt-2 line-clamp-3 break-words text-xs leading-5 text-slate-500">
                            {log.reply}
                          </p>
                        )}
                      </td>

                      <td className="px-4 py-3 text-slate-600">
                        <p className="font-semibold text-slate-800">
                          {log.user?.name || log.role || "Khách"}
                        </p>
                        <p className="mt-1 text-xs uppercase text-slate-500">
                          {log.role || "guest"}
                        </p>
                        <p className="mt-1 text-xs text-slate-500">
                          {log.major?.major_name || "Chưa rõ ngành"}
                        </p>
                      </td>

                      <td className="max-w-xs px-4 py-3">
                        <div className="flex flex-wrap gap-1">
                          {terms.length ? (
                            terms.map((term) => (
                              <span
                                key={term}
                                className="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600"
                              >
                                {term}
                              </span>
                            ))
                          ) : (
                            <span className="text-xs text-slate-400">
                              Chưa có từ khóa
                            </span>
                          )}
                        </div>
                        <p className="mt-2 text-xs text-slate-500">
                          Source: {log.source || "unknown"}
                        </p>
                      </td>

                      <td className="max-w-xs px-4 py-3">
                        <p className="font-semibold text-slate-800">
                          {log.products_count || products.length} sản phẩm
                        </p>
                        <div className="mt-2 space-y-1">
                          {products.length ? (
                            products.map((product) => (
                              <p
                                key={`${log.id}-${product.id || product.title}`}
                                className="line-clamp-1 text-xs text-slate-500"
                              >
                                {product.title || product.id}
                              </p>
                            ))
                          ) : (
                            <p className="text-xs text-slate-400">
                              Chưa match sản phẩm
                            </p>
                          )}
                        </div>
                      </td>

                      <td className="px-4 py-3">
                        <div className="flex flex-col gap-2">
                          <span
                            className={`inline-flex w-fit items-center gap-1 rounded-md border px-2 py-1 text-xs font-semibold ${badgeClass(
                              log.needs_training,
                              "amber",
                            )}`}
                          >
                            <BrainCircuit size={14} />
                            {log.needs_training
                              ? "Cần training"
                              : "Ổn hiện tại"}
                          </span>
                          <span
                            className={`inline-flex w-fit items-center gap-1 rounded-md border px-2 py-1 text-xs font-semibold ${badgeClass(
                              log.reviewed,
                              "emerald",
                            )}`}
                          >
                            {log.reviewed ? (
                              <CheckCircle2 size={14} />
                            ) : (
                              <XCircle size={14} />
                            )}
                            {log.reviewed ? "Đã xử lý" : "Chưa xử lý"}
                          </span>
                        </div>
                      </td>

                      <td className="px-4 py-3 text-right">
                        <div className="flex justify-end gap-2">
                          <button
                            type="button"
                            onClick={() => copyLog(log)}
                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                            title={
                              copiedId === log.id
                                ? "Đã copy"
                                : "Copy gói training"
                            }
                          >
                            <Clipboard size={17} />
                          </button>
                          <button
                            type="button"
                            onClick={() => toggleReviewed(log)}
                            disabled={updatingId === log.id}
                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 text-emerald-700 hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-60"
                            title={log.reviewed ? "Mở lại" : "Đã xử lý"}
                          >
                            {updatingId === log.id ? (
                              <Loader2 size={17} className="animate-spin" />
                            ) : (
                              <CheckCircle2 size={17} />
                            )}
                          </button>
                          <button
                            type="button"
                            onClick={() => deleteLog(log)}
                            disabled={updatingId === log.id}
                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg text-rose-600 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-60"
                            title="Xóa log"
                          >
                            <Trash2 size={17} />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })
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
                disabled={
                  pagination.current_page >= pagination.last_page || loading
                }
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

export default ChatboxTrainingScreen;
