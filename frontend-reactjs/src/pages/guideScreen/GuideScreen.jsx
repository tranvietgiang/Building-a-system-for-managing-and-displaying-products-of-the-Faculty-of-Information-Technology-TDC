import { Link, useNavigate } from "react-router-dom";
import {
  ArrowRight,
  BookOpen,
  CheckCircle2,
  ClipboardCheck,
  FileText,
  Image,
  Link as LinkIcon,
  LogIn,
  PackagePlus,
  Send,
  ShieldCheck,
  UserCheck,
} from "lucide-react";
import logoTdc from "../../assets/logo-tdc-orginal.webp";
import useScrollControls from "../../hooks/common/useScrollControls";
import ScrollButtons from "../../components/common/ScrollButtons";

const steps = [
  {
    title: "Đăng nhập tài khoản sinh viên",
    description:
      "Sinh viên đăng nhập bằng MSSV và mật khẩu được cấp để hệ thống nhận đúng ngành, lớp và thông tin cá nhân.",
    icon: LogIn,
  },
  {
    title: "Vào chức năng đăng sản phẩm",
    description:
      "Tại trang sinh viên, chọn nút Đăng sản phẩm mới để mở biểu mẫu theo chuyên ngành của tài khoản.",
    icon: PackagePlus,
  },
  {
    title: "Nhập thông tin sản phẩm",
    description:
      "Điền tên sản phẩm, mô tả, danh mục, giảng viên hướng dẫn và các trường chuyên ngành nếu biểu mẫu yêu cầu.",
    icon: FileText,
  },
  {
    title: "Bổ sung tài nguyên minh chứng",
    description:
      "Tải ảnh đại diện, hình ảnh sản phẩm, file báo cáo và thêm liên kết demo, GitHub, Drive hoặc Behance nếu có.",
    icon: Image,
  },
  {
    title: "Kiểm tra và gửi duyệt",
    description:
      "Rà soát lại nội dung, đảm bảo liên kết truy cập được, sau đó gửi sản phẩm để giảng viên phụ trách xét duyệt.",
    icon: Send,
  },
  {
    title: "Theo dõi trạng thái",
    description:
      "Sản phẩm sẽ hiển thị ở mục Chờ duyệt, Đã duyệt hoặc Từ chối. Nếu bị từ chối, sinh viên chỉnh sửa rồi gửi lại.",
    icon: ClipboardCheck,
  },
];

const checklist = [
  "Tên sản phẩm ngắn gọn, đúng nội dung đồ án hoặc dự án.",
  "Mô tả nêu rõ mục tiêu, công nghệ sử dụng và điểm nổi bật.",
  "Ảnh đại diện rõ nét, đúng sản phẩm, không dùng ảnh không liên quan.",
  "File báo cáo hoặc tài liệu minh chứng đã đặt đúng định dạng.",
  "Các link demo, GitHub, Drive hoặc Behance mở được ở chế độ phù hợp.",
  "Thông tin giảng viên hướng dẫn và danh mục sản phẩm đã chọn đúng.",
];

const statuses = [
  {
    name: "Chờ duyệt",
    text: "Sản phẩm đã gửi và đang đợi giảng viên kiểm tra.",
    color: "border-yellow-200 bg-yellow-50 text-yellow-800",
  },
  {
    name: "Đã duyệt",
    text: "Sản phẩm hợp lệ và có thể được hiển thị ở trang trưng bày.",
    color: "border-emerald-200 bg-emerald-50 text-emerald-800",
  },
  {
    name: "Từ chối",
    text: "Sản phẩm cần chỉnh sửa theo nhận xét trước khi gửi lại.",
    color: "border-rose-200 bg-rose-50 text-rose-800",
  },
];

export default function GuideScreen() {
  const navigate = useNavigate();
  const { handleTop, handleBottom } = useScrollControls();

  return (
    <div className="min-h-screen bg-[#F8FAFC] text-slate-900">
      <header className="sticky top-0 z-50 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur">
        <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:h-20 lg:px-8">
          <Link to="/nckh-visitor" className="flex items-center gap-3">
            <img src={logoTdc} alt="TDC" className="h-12 w-auto" />
            <div className="hidden sm:block">
              <p className="text-lg font-bold leading-tight text-[#003087]">
                Hướng dẫn đăng sản phẩm
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
              className="text-sm font-semibold text-[#003087]"
            >
              Hướng dẫn
            </Link>
            <Link
              to="/lien-he"
              className="text-sm font-medium text-slate-600 transition hover:text-[#003087]"
            >
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
          <div className="mx-auto grid max-w-7xl gap-8 px-4 py-14 sm:px-6 lg:grid-cols-[1.2fr_0.8fr] lg:px-8 lg:py-16">
            <div>
              <div className="mb-4 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-sm text-blue-100">
                <BookOpen size={16} />
                Quy trình dành cho sinh viên
              </div>
              <h1 className="max-w-3xl text-3xl font-bold leading-tight md:text-5xl">
                Hướng dẫn quy trình đăng sản phẩm nghiên cứu
              </h1>
              <p className="mt-4 max-w-2xl text-base leading-7 text-blue-100 md:text-lg">
                Làm theo từng bước dưới đây để sản phẩm được gửi đầy đủ thông
                tin, dễ xét duyệt và sẵn sàng trưng bày khi được phê duyệt.
              </p>
              <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                <button
                  onClick={() => navigate("/login")}
                  className="inline-flex items-center justify-center gap-2 rounded-md bg-white px-5 py-2.5 text-sm font-semibold text-[#003087] transition hover:bg-slate-100"
                >
                  Bắt đầu đăng sản phẩm
                  <ArrowRight size={17} />
                </button>
                <button
                  onClick={() => handleBottom("/nckh-visitor", "san-pham")}
                  className="inline-flex items-center justify-center gap-2 rounded-md border border-white/70 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10"
                >
                  Xem sản phẩm toàn ngành
                </button>
              </div>
            </div>

            <div className="rounded-lg border border-white/15 bg-white/10 p-5">
              <div className="flex items-center gap-3 border-b border-white/15 pb-4">
                <ShieldCheck size={28} />
                <div>
                  <p className="font-semibold">Lưu ý trước khi gửi</p>
                  <p className="text-sm text-blue-100">
                    Chuẩn bị đủ thông tin giúp giảng viên duyệt nhanh hơn.
                  </p>
                </div>
              </div>
              <div className="mt-4 space-y-3 text-sm text-blue-50">
                <p className="flex items-start gap-2">
                  <UserCheck className="mt-0.5 shrink-0" size={17} />
                  Chỉ tài khoản sinh viên mới có chức năng đăng sản phẩm.
                </p>
                <p className="flex items-start gap-2">
                  <LinkIcon className="mt-0.5 shrink-0" size={17} />
                  Link minh chứng nên để quyền xem công khai hoặc theo yêu cầu
                  của giảng viên.
                </p>
                <p className="flex items-start gap-2">
                  <CheckCircle2 className="mt-0.5 shrink-0" size={17} />
                  Sản phẩm chỉ xuất hiện công khai sau khi được duyệt.
                </p>
              </div>
            </div>
          </div>
        </section>

        <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
          <div className="mb-6">
            <p className="text-sm font-semibold uppercase tracking-wide text-[#003087]">
              Quy trình
            </p>
            <h2 className="mt-2 text-2xl font-bold text-slate-900">
              6 bước đăng sản phẩm
            </h2>
          </div>

          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {steps.map((step, index) => {
              const Icon = step.icon;
              return (
                <article
                  key={step.title}
                  className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
                >
                  <div className="mb-4 flex items-center justify-between">
                    <div className="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 text-[#003087]">
                      <Icon size={22} />
                    </div>
                    <span className="text-sm font-bold text-slate-300">
                      {String(index + 1).padStart(2, "0")}
                    </span>
                  </div>
                  <h3 className="text-base font-bold text-slate-900">
                    {step.title}
                  </h3>
                  <p className="mt-2 text-sm leading-6 text-slate-600">
                    {step.description}
                  </p>
                </article>
              );
            })}
          </div>
        </section>

        <section className="border-y border-slate-200 bg-white">
          <div className="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-2 lg:px-8">
            <div>
              <p className="text-sm font-semibold uppercase tracking-wide text-[#003087]">
                Checklist
              </p>
              <h2 className="mt-2 text-2xl font-bold text-slate-900">
                Kiểm tra trước khi gửi duyệt
              </h2>
              <div className="mt-6 space-y-3">
                {checklist.map((item) => (
                  <div key={item} className="flex items-start gap-3">
                    <CheckCircle2
                      className="mt-0.5 shrink-0 text-emerald-600"
                      size={19}
                    />
                    <p className="text-sm leading-6 text-slate-700">{item}</p>
                  </div>
                ))}
              </div>
            </div>

            <div>
              <p className="text-sm font-semibold uppercase tracking-wide text-[#003087]">
                Trạng thái
              </p>
              <h2 className="mt-2 text-2xl font-bold text-slate-900">
                Sau khi gửi sản phẩm
              </h2>
              <div className="mt-6 space-y-4">
                {statuses.map((status) => (
                  <div
                    key={status.name}
                    className={`rounded-lg border p-4 ${status.color}`}
                  >
                    <p className="font-semibold">{status.name}</p>
                    <p className="mt-1 text-sm leading-6">{status.text}</p>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </section>
      </main>

      <footer className="bg-[#003087] py-6 text-center text-xs text-blue-100">
        © 2025 Trường Cao Đẳng Công Nghệ Thủ Đức (TDC)
      </footer>
      <ScrollButtons />
    </div>
  );
}
