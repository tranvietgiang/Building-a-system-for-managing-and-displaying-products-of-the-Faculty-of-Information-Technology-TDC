import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import {
  ArrowLeft,
  CheckCircle2,
  Loader2,
  Mail,
  MapPin,
  Phone,
  Send,
} from "lucide-react";
import logoTdc from "../../assets/logo-tdc-orginal.webp";
import { authApi } from "../../api";
import useScrollControls from "../../hooks/common/useScrollControls";
import ScrollButtons from "../../components/common/ScrollButtons";

export default function ContactScreen() {
  const navigate = useNavigate();
  const { handleTop, handleBottom } = useScrollControls();
  const [contactName, setContactName] = useState("");
  const [contactEmail, setContactEmail] = useState("");
  const [contactPhone, setContactPhone] = useState("");
  const [contactSubject, setContactSubject] = useState("");
  const [contactMessage, setContactMessage] = useState("");
  const [contactError, setContactError] = useState("");
  const [contactSuccess, setContactSuccess] = useState("");
  const [contactLoading, setContactLoading] = useState(false);

  const handleContactSubmit = async (event) => {
    event.preventDefault();

    const payload = {
      name: contactName.trim(),
      email: contactEmail.trim(),
      phone: contactPhone.trim(),
      subject: contactSubject.trim(),
      message: contactMessage.trim(),
    };

    if (!payload.name || !payload.email || !payload.subject || !payload.message) {
      setContactError("Vui lòng nhập đầy đủ họ tên, email, tiêu đề và nội dung.");
      setContactSuccess("");
      return;
    }

    setContactLoading(true);
    setContactError("");
    setContactSuccess("");

    try {
      await authApi.submitContact(payload);
      setContactSuccess(
        "Yêu cầu liên hệ đã được ghi nhận. Bộ phận quản trị sẽ kiểm tra và phản hồi qua email của bạn.",
      );
      setContactName("");
      setContactEmail("");
      setContactPhone("");
      setContactSubject("");
      setContactMessage("");
    } catch (error) {
      setContactError(
        error.response?.data?.message ||
          "Chưa gửi được yêu cầu lúc này. Vui lòng thử lại sau.",
      );
    } finally {
      setContactLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#F8FAFC] text-slate-900">
      <header className="sticky top-0 z-50 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur">
        <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:h-20 lg:px-8">
          <Link to="/nckh-visitor" className="flex items-center gap-3">
            <img src={logoTdc} alt="TDC" className="h-12 w-auto" />
            <div className="hidden sm:block">
              <p className="text-lg font-bold leading-tight text-[#003087]">
                Liên hệ hỗ trợ
              </p>
              <p className="text-xs text-slate-500">
                Khoa Công Nghệ Thông Tin | TDC
              </p>
            </div>
          </Link>

          <nav className="hidden items-center gap-6 md:flex">
            <button
              type="button"
              onClick={() => handleTop("/nckh-visitor")}
              className="text-sm font-medium text-slate-600 transition hover:text-[#003087]"
            >
              Trang chủ
            </button>
            <button
              type="button"
              onClick={() => handleBottom("/nckh-visitor", "san-pham")}
              className="text-sm font-medium text-slate-600 transition hover:text-[#003087]"
            >
              Sản phẩm
            </button>
            <Link
              to="/nganh-hoc"
              className="text-sm font-medium text-slate-600 transition hover:text-[#003087]"
            >
              Ngành học
            </Link>
            <Link
              to="/huong-dan"
              className="text-sm font-medium text-slate-600 transition hover:text-[#003087]"
            >
              Hướng dẫn
            </Link>
            <Link to="/lien-he" className="text-sm font-semibold text-[#003087]">
              Liên hệ
            </Link>
          </nav>

          <button
            onClick={() => navigate("/login")}
            className="rounded-md border border-[#003087] px-4 py-2 text-sm font-semibold text-[#003087] transition hover:bg-[#003087] hover:text-white"
          >
            Đăng nhập
          </button>
        </div>
      </header>

      <main>
        <section className="bg-[#003087] text-white">
          <div className="mx-auto grid max-w-7xl gap-8 px-4 py-14 sm:px-6 lg:grid-cols-[1fr_0.8fr] lg:px-8 lg:py-16">
            <div>
              <button
                type="button"
                onClick={() => navigate("/nckh-visitor")}
                className="mb-5 inline-flex items-center gap-2 rounded-md border border-white/30 px-3 py-1.5 text-sm text-blue-100 transition hover:bg-white/10"
              >
                <ArrowLeft size={16} />
                Quay lại trang chủ
              </button>
              <h1 className="max-w-3xl text-3xl font-bold leading-tight md:text-5xl">
                Gửi yêu cầu liên hệ đến bộ phận hỗ trợ
              </h1>
              <p className="mt-4 max-w-2xl text-base leading-7 text-blue-100 md:text-lg">
                Nếu bạn cần hỗ trợ tài khoản, sản phẩm nghiên cứu hoặc thông tin
                trên hệ thống, hãy gửi nội dung tại đây để quản trị viên tiếp
                nhận và xử lý.
              </p>
            </div>

            <div className="rounded-lg border border-white/15 bg-white/10 p-5">
              <div className="flex items-start gap-3 border-b border-white/15 pb-4">
                <CheckCircle2 className="mt-0.5 shrink-0" size={26} />
                <div>
                  <p className="font-semibold">Thông tin phản hồi</p>
                  <p className="mt-1 text-sm leading-6 text-blue-100">
                    Yêu cầu sẽ được lưu vào hàng đợi hỗ trợ của admin. Vui lòng
                    nhập email chính xác để nhận phản hồi.
                  </p>
                </div>
              </div>
              <div className="mt-4 space-y-3 text-sm text-blue-50">
                <p className="flex items-center gap-3">
                  <Mail size={18} />
                  fit@tdc.edu.vn
                </p>
                <p className="flex items-center gap-3">
                  <Phone size={18} />
                  028 3731 3652
                </p>
                <p className="flex items-start gap-3">
                  <MapPin className="mt-0.5 shrink-0" size={18} />
                  53 Võ Văn Ngân, TP. Thủ Đức, TP. Hồ Chí Minh
                </p>
              </div>
            </div>
          </div>
        </section>

        <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
          <form
            onSubmit={handleContactSubmit}
            className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
          >
            <div className="grid gap-4 md:grid-cols-2">
              <label className="block">
                <span className="mb-2 block text-sm font-semibold text-slate-700">
                  Họ tên
                </span>
                <input
                  value={contactName}
                  onChange={(event) => setContactName(event.target.value)}
                  className="h-11 w-full rounded-md border border-slate-200 px-3 text-sm outline-none focus:border-[#003087]"
                  placeholder="Nguyễn Văn A"
                  maxLength={255}
                />
              </label>

              <label className="block">
                <span className="mb-2 block text-sm font-semibold text-slate-700">
                  Email
                </span>
                <input
                  type="email"
                  value={contactEmail}
                  onChange={(event) => setContactEmail(event.target.value)}
                  className="h-11 w-full rounded-md border border-slate-200 px-3 text-sm outline-none focus:border-[#003087]"
                  placeholder="email@tdc.edu.vn"
                  maxLength={255}
                />
              </label>

              <label className="block">
                <span className="mb-2 block text-sm font-semibold text-slate-700">
                  Số điện thoại
                </span>
                <input
                  value={contactPhone}
                  onChange={(event) => setContactPhone(event.target.value)}
                  className="h-11 w-full rounded-md border border-slate-200 px-3 text-sm outline-none focus:border-[#003087]"
                  placeholder="Không bắt buộc"
                  maxLength={30}
                />
              </label>

              <label className="block">
                <span className="mb-2 block text-sm font-semibold text-slate-700">
                  Tiêu đề
                </span>
                <input
                  value={contactSubject}
                  onChange={(event) => setContactSubject(event.target.value)}
                  className="h-11 w-full rounded-md border border-slate-200 px-3 text-sm outline-none focus:border-[#003087]"
                  placeholder="Cần hỗ trợ..."
                  maxLength={255}
                />
              </label>
            </div>

            <label className="mt-4 block">
              <span className="mb-2 block text-sm font-semibold text-slate-700">
                Nội dung
              </span>
              <textarea
                value={contactMessage}
                onChange={(event) => setContactMessage(event.target.value)}
                className="min-h-36 w-full resize-y rounded-md border border-slate-200 px-3 py-3 text-sm outline-none focus:border-[#003087]"
                placeholder="Mô tả vấn đề bạn cần hỗ trợ"
                maxLength={2000}
              />
            </label>

            {contactSuccess && (
              <div className="mt-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                {contactSuccess}
              </div>
            )}

            {contactError && (
              <div className="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                {contactError}
              </div>
            )}

            <button
              type="submit"
              disabled={contactLoading}
              className="mt-5 inline-flex h-11 items-center justify-center gap-2 rounded-md bg-[#003087] px-5 text-sm font-semibold text-white transition hover:bg-[#00266b] disabled:cursor-not-allowed disabled:opacity-60"
            >
              {contactLoading ? (
                <Loader2 className="animate-spin" size={17} />
              ) : (
                <Send size={17} />
              )}
              {contactLoading ? "Đang gửi..." : "Gửi liên hệ"}
            </button>
          </form>
        </section>
      </main>

      <footer className="bg-[#003087] py-6 text-center text-xs text-blue-100">
        © 2025 Trường Cao Đẳng Công Nghệ Thủ Đức (TDC)
      </footer>
      <ScrollButtons />
    </div>
  );
}
