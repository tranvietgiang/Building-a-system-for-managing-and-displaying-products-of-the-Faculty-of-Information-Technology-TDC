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
      setMessage("Da tim thay email cua tai khoan.");
    } catch (err) {
      setError(
        err.response?.data?.message || "Khong tim thay tai khoan phu hop.",
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
      setMessage(
        "Da cap mat khau moi va gui email khoi phuc cho nguoi dung.",
      );
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
      setError(err.response?.data?.message || "Khong the gui email luc nay.");
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
      setMessage("Da danh dau yeu cau lien he la da xu ly.");
      fetchRequests();
    } catch (err) {
      setError(err.response?.data?.message || "Khong the cap nhat yeu cau.");
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
              <h3 className="text-lg font-bold">Password recovery</h3>
              <p className="mt-1 text-sm leading-6 text-slate-600">
                Nhap email hoac ma tai khoan, vi du 23211TT2984, de hien email
                va gui mat khau tam thoi.
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
                Mat khau tam thoi
              </span>
              <input
                type="text"
                value={temporaryPassword}
                onChange={(event) => setTemporaryPassword(event.target.value)}
                placeholder="Bo trong de he thong tu tao"
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
                Tra cuu email
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
                Gui email
              </button>
            </div>
          </form>

          {message && (
            <div className="mt-4 flex gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
              <CheckCircle2 size={18} />
              <span>{message}</span>
            </div>
          )}

          {error && (
            <div className="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
              {error}
            </div>
          )}
        </section>

        <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
          <div className="flex items-start gap-3">
            <div className="flex h-11 w-11 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
              <ShieldCheck size={22} />
            </div>
            <div>
              <h3 className="text-lg font-bold">Request detail</h3>
              <p className="mt-1 text-sm leading-6 text-slate-600">
                Email hien thi o day la email dang luu trong tai khoan nguoi
                dung.
              </p>
            </div>
          </div>

          {user ? (
            <div className="mt-5 overflow-hidden rounded-lg border border-slate-200">
              <dl className="divide-y divide-slate-100 text-sm">
                <div className="grid gap-1 px-4 py-3 sm:grid-cols-[150px_1fr]">
                  <dt className="font-semibold text-slate-500">Ma tai khoan</dt>
                  <dd className="font-semibold text-slate-900">{user.user_id}</dd>
                </div>
                <div className="grid gap-1 px-4 py-3 sm:grid-cols-[150px_1fr]">
                  <dt className="font-semibold text-slate-500">Ho ten</dt>
                  <dd className="text-slate-700">{user.name}</dd>
                </div>
                <div className="grid gap-1 px-4 py-3 sm:grid-cols-[150px_1fr]">
                  <dt className="font-semibold text-slate-500">Email</dt>
                  <dd className="font-semibold text-emerald-700">{user.email}</dd>
                </div>
                <div className="grid gap-1 px-4 py-3 sm:grid-cols-[150px_1fr]">
                  <dt className="font-semibold text-slate-500">Vai tro</dt>
                  <dd className="text-slate-700">{user.role}</dd>
                </div>
                <div className="grid gap-1 px-4 py-3 sm:grid-cols-[150px_1fr]">
                  <dt className="font-semibold text-slate-500">Lop</dt>
                  <dd className="text-slate-700">{user.class || "-"}</dd>
                </div>
              </dl>
            </div>
          ) : (
            <div className="mt-5 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
              Chua co tai khoan nao duoc tra cuu.
            </div>
          )}
        </section>
      </div>

      <section className="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
          <div>
            <h3 className="text-lg font-bold">Support request queue</h3>
            <p className="mt-1 text-sm text-slate-500">
              Requests are ordered first in, first out.
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
            Refresh
          </button>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full min-w-[1080px] text-left text-sm">
            <thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-3">#</th>
                <th className="px-4 py-3">Submitted</th>
                <th className="px-4 py-3">Type</th>
                <th className="px-4 py-3">Identifier</th>
                <th className="px-4 py-3">Email</th>
                <th className="px-4 py-3">Subject</th>
                <th className="px-4 py-3">Name</th>
                <th className="px-4 py-3 text-right">Action</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {loadingRequests ? (
                <tr>
                  <td className="px-4 py-8 text-center text-slate-500" colSpan="8">
                    Loading requests...
                  </td>
                </tr>
              ) : requests.length === 0 ? (
                <tr>
                  <td className="px-4 py-8 text-center text-slate-500" colSpan="8">
                    No pending support requests.
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
                        {request.type === "contact" ? "Contact" : "Password"}
                      </span>
                    </td>
                    <td className="px-4 py-3 font-semibold text-slate-900">
                      {request.identifier}
                    </td>
                    <td className="px-4 py-3 text-emerald-700">
                      {request.email || "Not matched yet"}
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
                          Done
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
                              Process
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
                                value={requestPasswords[request.support_id] || ""}
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
