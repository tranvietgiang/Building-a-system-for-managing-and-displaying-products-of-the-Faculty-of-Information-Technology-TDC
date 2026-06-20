import { useEffect, useState } from "react";
import {
  CheckCircle2,
  KeyRound,
  Loader2,
  Mail,
  RefreshCw,
  Search,
  ShieldCheck,
} from "lucide-react";
import adminApi from "../../../api/admin.api";

const SupportScreen = () => {
  const [identifier, setIdentifier] = useState("");
  const [temporaryPassword, setTemporaryPassword] = useState("");
  const [user, setUser] = useState(null);
  const [loadingLookup, setLoadingLookup] = useState(false);
  const [loadingSend, setLoadingSend] = useState(false);
  const [loadingRequests, setLoadingRequests] = useState(false);
  const [processingId, setProcessingId] = useState(null);
  const [passwordRequestId, setPasswordRequestId] = useState(null);
  const [requestPasswords, setRequestPasswords] = useState({});
  const [requests, setRequests] = useState([]);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");

  const fetchRequests = async () => {
    setLoadingRequests(true);
    try {
      const res = await adminApi.getSupportRequests({ status: "pending" });
      setRequests(res.data || []);
    } finally {
      setLoadingRequests(false);
    }
  };

  useEffect(() => {
    fetchRequests();
  }, []);

  const clearStatus = () => {
    setMessage("");
    setError("");
  };

  const lookupUser = async (event) => {
    event.preventDefault();
    if (!identifier.trim()) return;

    clearStatus();
    setUser(null);
    setLoadingLookup(true);

    try {
      const res = await adminApi.lookupPasswordRecoveryUser({
        identifier: identifier.trim(),
      });
      setUser(res.data);
      setMessage("Đã tìm thấy email của tài khoản.");
    } catch (err) {
      setError(
        err.response?.data?.message || "Không tìm thấy tài khoản phù hợp.",
      );
    } finally {
      setLoadingLookup(false);
    }
  };

  const sendRecovery = async (request = null, passwordOverride = "") => {
    const targetIdentifier = request?.identifier || identifier.trim();
    if (!targetIdentifier) return;

    clearStatus();
    setLoadingSend(true);
    setProcessingId(request?.support_id || null);

    try {
      const payload = { identifier: targetIdentifier };
      if (request?.support_id) {
        payload.support_id = request.support_id;
      }
      const newPassword = passwordOverride.trim() || temporaryPassword.trim();
      if (newPassword) {
        payload.temporary_password = newPassword;
      }

      const res = await adminApi.sendPasswordRecovery(payload);
      setUser(res.data);
      setMessage("Đã cấp mật khẩu mới và gửi email khôi phục cho người dùng.");
      setTemporaryPassword("");
      if (request?.support_id) {
        setPasswordRequestId(null);
        setRequestPasswords((prev) => {
          const next = { ...prev };
          delete next[request.support_id];
          return next;
        });
      }
      fetchRequests();
    } catch (err) {
      setError(err.response?.data?.message || "Không thể gửi email lúc này.");
    } finally {
      setLoadingSend(false);
      setProcessingId(null);
    }
  };

  const updateRequestPassword = (supportId, value) => {
    setRequestPasswords((prev) => ({
      ...prev,
      [supportId]: value,
    }));
  };

  const markProcessed = async (request) => {
    if (!request?.support_id) return;

    clearStatus();
    setProcessingId(request.support_id);

    try {
      await adminApi.markSupportProcessed(request.support_id);
      setMessage("Đã đánh dấu yêu cầu liên hệ là đã xử lý.");
      fetchRequests();
    } catch (err) {
      setError(err.response?.data?.message || "Không thể cập nhật yêu cầu.");
    } finally {
      setProcessingId(null);
    }
  };

  return (
    <div className="space-y-5">
      <div className="grid gap-5 xl:grid-cols-[430px_1fr]">
        <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
          <div className="flex items-start gap-3">
            <div className="flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
              <KeyRound size={22} />
            </div>
            <div>
              <h3 className="text-lg font-bold">Khôi phục mật khẩu</h3>
              <p className="mt-1 text-sm leading-6 text-slate-600">
                Nhập email hoặc mã tài khoản, ví dụ 23211TT2984, để hiển thị
                email và gửi mật khẩu tạm thời.
              </p>
            </div>
          </div>

          <form onSubmit={lookupUser} className="mt-5 space-y-4">
            <label className="block">
              <span className="mb-2 block text-sm font-semibold text-slate-700">
                Email / MSSV / MSGV
              </span>
              <div className="relative">
                <Search
                  className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                  size={18}
                />
                <input
                  value={identifier}
                  onChange={(event) => {
                    setIdentifier(event.target.value);
                    setUser(null);
                    clearStatus();
                  }}
                  placeholder="23211TT2984 hoặc email@tdc.edu.vn"
                  className="h-11 w-full rounded-lg border border-slate-200 pl-10 pr-3 outline-none focus:border-emerald-500"
                  maxLength={255}
                />
              </div>
            </label>

            <label className="block">
              <span className="mb-2 block text-sm font-semibold text-slate-700">
                Mật khẩu tạm thời
              </span>
              <input
                type="text"
                value={temporaryPassword}
                onChange={(event) => setTemporaryPassword(event.target.value)}
                placeholder="Bỏ trống để hệ thống tự tạo"
                className="h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-500"
                maxLength={100}
              />
            </label>

            <div className="flex flex-wrap gap-2">
              <button
                type="submit"
                disabled={loadingLookup || !identifier.trim()}
                className="inline-flex h-11 items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 font-semibold text-emerald-700 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-60"
              >
                {loadingLookup ? (
                  <Loader2 size={18} className="animate-spin" />
                ) : (
                  <Search size={18} />
                )}
                Tra cứu email
              </button>

              <button
                type="button"
                onClick={() => sendRecovery()}
                disabled={loadingSend || !identifier.trim()}
                className="inline-flex h-11 items-center gap-2 rounded-lg bg-emerald-600 px-4 font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
              >
                {loadingSend ? (
                  <Loader2 size={18} className="animate-spin" />
                ) : (
                  <Mail size={18} />
                )}
                Gửi email khôi phục
              </button>
            </div>
          </form>

          {(message || error) && (
            <div
              className={`mt-4 rounded-lg px-4 py-3 text-sm font-semibold ${
                error
                  ? "bg-rose-50 text-rose-700"
                  : "bg-emerald-50 text-emerald-700"
              }`}
            >
              {error || message}
            </div>
          )}
        </section>

        <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
          <div className="flex items-start gap-3">
            <div className="flex h-11 w-11 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
              <ShieldCheck size={22} />
            </div>
            <div>
              <h3 className="text-lg font-bold">Yêu cầu chi tiết</h3>
              <p className="mt-1 text-sm leading-6 text-slate-600">
                Email hiển thị ở đây là email đang lưu trong tài khoản người
                dùng.
              </p>
            </div>
          </div>

          {user ? (
            <div className="mt-5 overflow-hidden rounded-lg border border-slate-200">
              <dl className="divide-y divide-slate-100 text-sm">
                <div className="grid gap-1 px-4 py-3 sm:grid-cols-[150px_1fr]">
                  <dt className="font-semibold text-slate-500">Mã tài khoản</dt>
                  <dd className="font-mono font-semibold text-slate-800">
                    {user.user_id}
                  </dd>
                </div>
                <div className="grid gap-1 px-4 py-3 sm:grid-cols-[150px_1fr]">
                  <dt className="font-semibold text-slate-500">Họ tên</dt>
                  <dd className="text-slate-700">{user.name}</dd>
                </div>
                <div className="grid gap-1 px-4 py-3 sm:grid-cols-[150px_1fr]">
                  <dt className="font-semibold text-slate-500">Email</dt>
                  <dd className="font-semibold text-emerald-700">
                    {user.email}
                  </dd>
                </div>
                <div className="grid gap-1 px-4 py-3 sm:grid-cols-[150px_1fr]">
                  <dt className="font-semibold text-slate-500">Vai trò</dt>
                  <dd className="text-slate-700">{user.role}</dd>
                </div>
                <div className="grid gap-1 px-4 py-3 sm:grid-cols-[150px_1fr]">
                  <dt className="font-semibold text-slate-500">Lớp</dt>
                  <dd className="text-slate-700">{user.class || "-"}</dd>
                </div>
              </dl>
            </div>
          ) : (
            <div className="mt-5 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
              Chưa có tài khoản nào được tra cứu.
            </div>
          )}
        </section>
      </div>

      <section className="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
          <div>
            <h3 className="text-lg font-bold">Hàng đợi yêu cầu hỗ trợ</h3>
            <p className="mt-1 text-sm text-slate-500">
              Yêu cầu được xử lý theo thứ tự gửi trước xử lý trước.
            </p>
          </div>
          <button
            type="button"
            onClick={fetchRequests}
            disabled={loadingRequests}
            className="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 px-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
          >
            <RefreshCw
              size={17}
              className={loadingRequests ? "animate-spin" : ""}
            />
            Làm mới
          </button>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full min-w-[1080px] text-left text-sm">
            <thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-3">#</th>
                <th className="px-4 py-3">Thời gian gửi</th>
                <th className="px-4 py-3">Loại</th>
                <th className="px-4 py-3">Mã/Tài khoản</th>
                <th className="px-4 py-3">Email</th>
                <th className="px-4 py-3">Tiêu đề</th>
                <th className="px-4 py-3">Họ tên</th>
                <th className="px-4 py-3 text-right">Thao tác</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {loadingRequests ? (
                <tr>
                  <td
                    className="px-4 py-8 text-center text-slate-500"
                    colSpan="8"
                  >
                    Đang tải yêu cầu...
                  </td>
                </tr>
              ) : requests.length === 0 ? (
                <tr>
                  <td
                    className="px-4 py-8 text-center text-slate-500"
                    colSpan="8"
                  >
                    Không có yêu cầu hỗ trợ đang chờ.
                  </td>
                </tr>
              ) : (
                requests.map((request, index) => (
                  <tr key={request.support_id}>
                    <td className="px-4 py-3 font-semibold text-slate-500">
                      {index + 1}
                    </td>
                    <td className="px-4 py-3 text-slate-600">
                      {new Date(request.created_at).toLocaleString()}
                    </td>
                    <td className="px-4 py-3">
                      <span className="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
                        {request.type === "contact" ? "Liên hệ" : "Mật khẩu"}
                      </span>
                    </td>
                    <td className="px-4 py-3 font-semibold text-slate-900">
                      {request.identifier}
                    </td>
                    <td className="px-4 py-3 text-emerald-700">
                      {request.email || "Chưa tìm thấy"}
                    </td>
                    <td className="max-w-[280px] px-4 py-3 text-slate-600">
                      <p className="font-semibold text-slate-800">
                        {request.subject || "-"}
                      </p>
                      {request.message && (
                        <p className="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">
                          {request.message}
                        </p>
                      )}
                    </td>
                    <td className="px-4 py-3 text-slate-600">
                      {request.name || "-"}
                    </td>
                    <td className="px-4 py-3 text-right">
                      {request.type === "contact" ? (
                        <button
                          type="button"
                          onClick={() => markProcessed(request)}
                          disabled={processingId === request.support_id}
                          className="inline-flex h-9 items-center gap-2 rounded-lg bg-slate-700 px-3 font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                          {processingId === request.support_id ? (
                            <Loader2 size={16} className="animate-spin" />
                          ) : (
                            <CheckCircle2 size={16} />
                          )}
                          Hoàn tất
                        </button>
                      ) : (
                        <div className="flex flex-col items-end gap-2">
                          <div className="flex flex-wrap justify-end gap-2">
                            <button
                              type="button"
                              onClick={() => sendRecovery(request)}
                              disabled={loadingSend}
                              className="inline-flex h-9 items-center gap-2 rounded-lg bg-emerald-600 px-3 font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                              {processingId === request.support_id &&
                              passwordRequestId !== request.support_id ? (
                                <Loader2 size={16} className="animate-spin" />
                              ) : (
                                <Mail size={16} />
                              )}
                              Xử lý
                            </button>
                            <button
                              type="button"
                              onClick={() =>
                                setPasswordRequestId((current) =>
                                  current === request.support_id
                                    ? null
                                    : request.support_id,
                                )
                              }
                              className="inline-flex h-9 items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 font-semibold text-amber-700 transition hover:bg-amber-100"
                            >
                              <KeyRound size={16} />
                              Mật khẩu mới
                            </button>
                          </div>

                          {passwordRequestId === request.support_id && (
                            <div className="flex w-[260px] max-w-full gap-2">
                              <input
                                type="text"
                                value={
                                  requestPasswords[request.support_id] || ""
                                }
                                onChange={(event) =>
                                  updateRequestPassword(
                                    request.support_id,
                                    event.target.value,
                                  )
                                }
                                placeholder="Nhập mật khẩu mới"
                                className="h-9 min-w-0 flex-1 rounded-lg border border-slate-200 px-3 text-sm outline-none focus:border-amber-500"
                                maxLength={100}
                              />
                              <button
                                type="button"
                                onClick={() =>
                                  sendRecovery(
                                    request,
                                    requestPasswords[request.support_id] || "",
                                  )
                                }
                                disabled={
                                  loadingSend ||
                                  !requestPasswords[request.support_id]?.trim()
                                }
                                className="h-9 rounded-lg bg-amber-600 px-3 text-sm font-semibold text-white transition hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-60"
                              >
                                {processingId === request.support_id ? (
                                  <Loader2 size={16} className="animate-spin" />
                                ) : (
                                  "Cập nhật"
                                )}
                              </button>
                            </div>
                          )}
                        </div>
                      )}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </section>
    </div>
  );
};

export default SupportScreen;
