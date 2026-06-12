import { useState } from "react";
import { Link } from "react-router-dom";
import {
  ArrowLeft,
  CheckCircle2,
  KeyRound,
  Loader2,
  Mail,
  ShieldCheck,
} from "lucide-react";
import logoTdc from "../../assets/logo-tdc-orginal.webp";
import authApi from "../../api/auth.api";

export default function ForgotPassword() {
  const [identifier, setIdentifier] = useState("");
  const [loading, setLoading] = useState(false);
  const [sent, setSent] = useState(false);
  const [error, setError] = useState("");

  const handleSubmit = async (event) => {
    event.preventDefault();
    if (!identifier.trim()) return;

    setError("");
    setLoading(true);
    try {
      await authApi.submitPasswordRecovery({ identifier: identifier.trim() });
      setSent(true);
    } catch (err) {
      setError(
        err.response?.data?.message ||
          "Khong the gui yeu cau luc nay. Vui long thu lai sau.",
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="min-h-screen bg-[#f6f8fc] font-['Inter'] text-slate-900">
      <div className="grid min-h-screen lg:grid-cols-[0.9fr_1.1fr]">
        <section className="relative hidden overflow-hidden bg-[#003087] px-10 py-12 text-white lg:flex lg:flex-col lg:justify-between">
          <div className="absolute inset-0 opacity-10">
            <div className="absolute left-16 top-20 h-64 w-64 rounded-full bg-white blur-3xl" />
            <div className="absolute bottom-16 right-12 h-80 w-80 rounded-full bg-[#C8102E] blur-3xl" />
          </div>

          <Link
            to="/nckh-visitor"
            className="relative z-10 inline-flex w-fit items-center gap-3"
          >
            <span className="flex h-12 w-12 items-center justify-center rounded-xl border border-white/20 bg-white/10">
              <img src={logoTdc} alt="TDC" className="h-8 w-auto" />
            </span>
            <span>
              <span className="block text-sm font-semibold">
                Trường Cao Đẳng
              </span>
              <span className="block text-xs text-white/75">
                Công Nghệ Thủ Đức
              </span>
            </span>
          </Link>

          <div className="relative z-10 max-w-xl">
            <div className="mb-5 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-white/90">
              <ShieldCheck size={16} />
              Khôi phục tài khoản an toàn
            </div>
            <h1 className="text-4xl font-bold leading-tight xl:text-5xl">
              Lấy lại quyền truy cập hệ thống
            </h1>
            <p className="mt-5 max-w-lg text-base leading-7 text-white/75">
              Nhập email hoặc mã số tài khoản đã đăng ký. Bộ phận quản trị sẽ
              kiểm tra và gửi hướng dẫn đặt lại mật khẩu cho bạn.
            </p>
          </div>

          <div className="relative z-10 grid grid-cols-2 gap-4 border-t border-white/10 pt-6">
            <div>
              <p className="text-2xl font-bold">TDC</p>
              <p className="mt-1 text-sm text-white/65">
                Hệ thống quản lý sản phẩm sinh viên
              </p>
            </div>
            <div>
              <p className="text-2xl font-bold">24h</p>
              <p className="mt-1 text-sm text-white/65">
                Thời gian phản hồi dự kiến
              </p>
            </div>
          </div>
        </section>

        <section className="flex items-center justify-center px-5 py-10 sm:px-8">
          <div className="w-full max-w-md">
            <Link
              to="/login"
              className="mb-8 inline-flex items-center gap-2 text-sm font-semibold text-[#003087] transition hover:text-[#C8102E]"
            >
              <ArrowLeft size={18} />
              Quay lại đăng nhập
            </Link>

            <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/70 sm:p-8">
              <div className="mb-7">
                <div className="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#003087] text-white shadow-lg shadow-blue-900/20">
                  {sent ? <CheckCircle2 size={28} /> : <KeyRound size={28} />}
                </div>
                <h2 className="text-2xl font-bold text-[#003087] sm:text-3xl">
                  Quên mật khẩu
                </h2>
                <p className="mt-2 text-sm leading-6 text-slate-500">
                  {sent
                    ? "Yêu cầu của bạn đã được ghi nhận. Vui lòng kiểm tra email hoặc liên hệ quản trị viên nếu cần hỗ trợ thêm."
                    : "Nhập email, MSSV hoặc MSGV để bắt đầu quy trình khôi phục mật khẩu."}
                </p>
              </div>

              {!sent ? (
                <form onSubmit={handleSubmit} className="space-y-5">
                  <div>
                    <label className="mb-2 block text-sm font-semibold text-slate-700">
                      Email hoặc mã số tài khoản
                    </label>
                    <div className="relative">
                      <Mail
                        size={19}
                        className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                      />
                      <input
                        type="text"
                        value={identifier}
                        onChange={(event) => setIdentifier(event.target.value)}
                        placeholder="VD: 20240001 hoặc email@tdc.edu.vn"
                        className="w-full rounded-xl border border-slate-300 py-3 pl-10 pr-3 text-sm outline-none transition focus:border-[#003087] focus:ring-4 focus:ring-[#003087]/10"
                        maxLength={100}
                      />
                    </div>
                  </div>

                  <button
                    type="submit"
                    disabled={loading || !identifier.trim()}
                    className="flex w-full items-center justify-center gap-2 rounded-xl bg-[#003087] px-4 py-3.5 text-sm font-bold text-white transition hover:bg-[#00266d] disabled:cursor-not-allowed disabled:opacity-55"
                  >
                    {loading ? (
                      <>
                        <Loader2 size={19} className="animate-spin" />
                        Đang gửi yêu cầu...
                      </>
                    ) : (
                      "Gửi yêu cầu khôi phục"
                    )}
                  </button>
                  {error && (
                    <div className="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
                      {error}
                    </div>
                  )}
                </form>
              ) : (
                <div className="space-y-4">
                  <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    Nếu thông tin khớp với tài khoản trong hệ thống, hướng dẫn
                    đặt lại mật khẩu sẽ được gửi đến bạn.
                  </div>
                  <button
                    type="button"
                    onClick={() => {
                      setIdentifier("");
                      setSent(false);
                    }}
                    className="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700 transition hover:border-[#003087] hover:text-[#003087]"
                  >
                    Gửi yêu cầu khác
                  </button>
                </div>
              )}
            </div>

            <p className="mt-6 text-center text-xs leading-5 text-slate-400">
              © 2026 Trường Cao Đẳng Công Nghệ Thủ Đức
            </p>
          </div>
        </section>
      </div>
    </main>
  );
}
