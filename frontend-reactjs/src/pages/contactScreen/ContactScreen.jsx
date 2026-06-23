import { useState } from "react";
import { useNavigate } from "react-router-dom";
import {
  ArrowLeft,
  CheckCircle2,
  Loader2,
  Mail,
  MapPin,
  Phone,
  Send,
} from "lucide-react";
import { authApi } from "../../api";
import ScrollButtons from "../../components/common/ScrollButtons";
import PublicHeader from "../../layouts/PublicHeader";
import { sanitizeTextInput } from "../../utils/sanitizeInput";

const CONTACT_LIMITS = {
  name: 255,
  email: 255,
  phone: 30,
  subject: 255,
  message: 2000,
};

const CharacterCount = ({ value, max }) => (
  <span className="mt-1 block text-right text-xs text-slate-400">
    {value.length}/{max}
  </span>
);

export default function ContactScreen() {
  const navigate = useNavigate();
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
      name: sanitizeTextInput(contactName).trim(),
      email: contactEmail.trim(),
      phone: contactPhone.replace(/\D/g, "").trim(),
      subject: sanitizeTextInput(contactSubject).trim(),
      message: sanitizeTextInput(contactMessage, { multiline: true }).trim(),
    };

    if (
      !payload.name ||
      !payload.email ||
      !payload.subject ||
      !payload.message
    ) {
      setContactError("Vui lòng nhập đầy đủ họ tên, email, tiêu đề và nội dung.");
      setContactSuccess("");
      return;
    }

    const isOverLimit =
      payload.name.length > CONTACT_LIMITS.name ||
      payload.email.length > CONTACT_LIMITS.email ||
      payload.phone.length > CONTACT_LIMITS.phone ||
      payload.subject.length > CONTACT_LIMITS.subject ||
      payload.message.length > CONTACT_LIMITS.message;

    if (isOverLimit) {
      setContactError("Nội dung liên hệ vượt quá giới hạn ký tự cho phép.");
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
      <PublicHeader title="Liên hệ hỗ trợ" />

      <main>
        <section className="bg-[#003087] text-white">
          <div className="mx-auto grid max-w-7xl gap-8 px-4 py-14 sm:px-6 lg:grid-cols-[1fr_0.8fr] lg:px-8 lg:py-16">
            <div>
              <button
                type="button"
                onClick={() => navigate("/khach-tham-quan")}
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
                  onChange={(event) =>
                    setContactName(sanitizeTextInput(event.target.value))
                  }
                  className="h-11 w-full rounded-md border border-slate-200 px-3 text-sm outline-none focus:border-[#003087]"
                  placeholder="Nguyễn Văn A"
                  maxLength={CONTACT_LIMITS.name}
                  required
                />
                <CharacterCount value={contactName} max={CONTACT_LIMITS.name} />
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
                  maxLength={CONTACT_LIMITS.email}
                  required
                />
                <CharacterCount value={contactEmail} max={CONTACT_LIMITS.email} />
              </label>

              <label className="block">
                <span className="mb-2 block text-sm font-semibold text-slate-700">
                  Số điện thoại
                </span>
                <input
                  value={contactPhone}
                  onChange={(event) =>
                    setContactPhone(event.target.value.replace(/\D/g, ""))
                  }
                  className="h-11 w-full rounded-md border border-slate-200 px-3 text-sm outline-none focus:border-[#003087]"
                  placeholder="Không bắt buộc"
                  maxLength={CONTACT_LIMITS.phone}
                />
                <CharacterCount value={contactPhone} max={CONTACT_LIMITS.phone} />
              </label>

              <label className="block">
                <span className="mb-2 block text-sm font-semibold text-slate-700">
                  Tiêu đề
                </span>
                <input
                  value={contactSubject}
                  onChange={(event) =>
                    setContactSubject(sanitizeTextInput(event.target.value))
                  }
                  className="h-11 w-full rounded-md border border-slate-200 px-3 text-sm outline-none focus:border-[#003087]"
                  placeholder="Cần hỗ trợ..."
                  maxLength={CONTACT_LIMITS.subject}
                  required
                />
                <CharacterCount
                  value={contactSubject}
                  max={CONTACT_LIMITS.subject}
                />
              </label>
            </div>

            <label className="mt-4 block">
              <span className="mb-2 block text-sm font-semibold text-slate-700">
                Nội dung
              </span>
              <textarea
                value={contactMessage}
                onChange={(event) =>
                  setContactMessage(
                    sanitizeTextInput(event.target.value, { multiline: true }),
                  )
                }
                className="min-h-36 w-full resize-y rounded-md border border-slate-200 px-3 py-3 text-sm outline-none focus:border-[#003087]"
                placeholder="Mô tả vấn đề bạn cần hỗ trợ"
                maxLength={CONTACT_LIMITS.message}
                required
              />
              <CharacterCount
                value={contactMessage}
                max={CONTACT_LIMITS.message}
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
