import { Link, useNavigate } from "react-router-dom";
import {
  ArrowLeft,
  BrainCircuit,
  Code2,
  MonitorCog,
  Network,
  Palette,
} from "lucide-react";
import logoTdc from "../../assets/logo-tdc-orginal.webp";
import useMajorAll from "../../hooks/common/useMajorAll";
import useScrollControls from "../../hooks/common/useScrollControls";
import ScrollButtons from "../../components/common/ScrollButtons";

const majorMeta = {
  "Công nghệ thông tin": {
    icon: Code2,
    tone: "bg-blue-50 text-blue-700 border-blue-100",
    description:
      "Phát triển phần mềm, ứng dụng web, cơ sở dữ liệu và các giải pháp số phục vụ học tập, quản lý và đời sống.",
  },
  "Trí tuệ nhân tạo": {
    icon: BrainCircuit,
    tone: "bg-emerald-50 text-emerald-700 border-emerald-100",
    description:
      "Ứng dụng học máy, xử lý dữ liệu, thị giác máy tính và các mô hình thông minh vào sản phẩm thực tế.",
  },
  "Artificial Intelligence": {
    icon: BrainCircuit,
    tone: "bg-emerald-50 text-emerald-700 border-emerald-100",
    description:
      "Ứng dụng học máy, xử lý dữ liệu, thị giác máy tính và các mô hình thông minh vào sản phẩm thực tế.",
  },
  "Mạng máy tính": {
    icon: Network,
    tone: "bg-cyan-50 text-cyan-700 border-cyan-100",
    description:
      "Thiết kế, triển khai, quản trị hệ thống mạng, bảo mật hạ tầng và các mô hình kết nối doanh nghiệp.",
  },
  "Thiết kế đồ họa": {
    icon: Palette,
    tone: "bg-rose-50 text-rose-700 border-rose-100",
    description:
      "Xây dựng nhận diện, ấn phẩm truyền thông, giao diện số và các sản phẩm sáng tạo thị giác.",
  },
};

const fallbackMajors = [
  { major_id: "cntt", major_name: "Công nghệ thông tin", major_code: "CNTT" },
  { major_id: "ai", major_name: "Trí tuệ nhân tạo", major_code: "AI" },
  { major_id: "mmt", major_name: "Mạng máy tính", major_code: "MMT" },
  { major_id: "tkdh", major_name: "Thiết kế đồ họa", major_code: "TKDH" },
];

const getMajorMeta = (majorName) =>
  majorMeta[majorName] || {
    icon: MonitorCog,
    tone: "bg-slate-50 text-slate-700 border-slate-100",
    description:
      "Khám phá các sản phẩm học tập, đồ án và dự án nghiên cứu nổi bật của sinh viên trong ngành.",
  };

export default function MajorScreen() {
  const navigate = useNavigate();
  const { handleTop, handleBottom } = useScrollControls();
  const { majorAll, loadingMajorAll } = useMajorAll();
  const majors = majorAll?.length ? majorAll : fallbackMajors;

  return (
    <div className="min-h-screen bg-[#F8FAFC] text-slate-900">
      <header className="sticky top-0 z-50 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur">
        <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:h-20 lg:px-8">
          <Link to="/nckh-visitor" className="flex items-center gap-3">
            <img src={logoTdc} alt="TDC" className="h-12 w-auto" />
            <div className="hidden sm:block">
              <p className="text-lg font-bold leading-tight text-[#003087]">
                Ngành học
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
            <Link to="/nganh-hoc" className="text-sm font-semibold text-[#003087]">
              Ngành học
            </Link>
            <Link
              to="/huong-dan"
              className="text-sm font-medium text-slate-600 transition hover:text-[#003087]"
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
          <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8 lg:py-16">
            <button
              type="button"
              onClick={() => navigate("/nckh-visitor")}
              className="mb-5 inline-flex items-center gap-2 rounded-md border border-white/30 px-3 py-1.5 text-sm text-blue-100 transition hover:bg-white/10"
            >
              <ArrowLeft size={16} />
              Quay lại trang chủ
            </button>
            <h1 className="max-w-3xl text-3xl font-bold leading-tight md:text-5xl">
              4 ngành học của Khoa Công Nghệ Thông Tin
            </h1>
            <p className="mt-4 max-w-2xl text-base leading-7 text-blue-100 md:text-lg">
              Khám phá các chuyên ngành đào tạo và những hướng sản phẩm nghiên
              cứu tiêu biểu của sinh viên TDC.
            </p>
          </div>
        </section>

        <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
          {loadingMajorAll ? (
            <div className="rounded-lg border border-slate-200 bg-white px-5 py-10 text-center text-sm text-slate-500">
              Đang tải danh sách ngành học...
            </div>
          ) : (
            <div className="grid gap-5 md:grid-cols-2">
              {majors.slice(0, 4).map((major) => {
                const meta = getMajorMeta(major.major_name);
                const Icon = meta.icon;

                return (
                  <article
                    key={major.major_id}
                    className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                  >
                    <div className="flex items-start gap-4">
                      <div
                        className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border ${meta.tone}`}
                      >
                        <Icon size={24} />
                      </div>
                      <div className="min-w-0">
                        <div className="flex flex-wrap items-center gap-2">
                          <h2 className="text-lg font-bold text-slate-900">
                            {major.major_name}
                          </h2>
                          <span className="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">
                            {major.major_code}
                          </span>
                        </div>
                        <p className="mt-3 text-sm leading-6 text-slate-600">
                          {meta.description}
                        </p>
                        <button
                          type="button"
                          onClick={() =>
                            navigate(`/nckh-visitor?major=${major.major_id}#san-pham`)
                          }
                          className="mt-4 rounded-md border border-[#003087] px-3 py-2 text-sm font-semibold text-[#003087] transition hover:bg-[#003087] hover:text-white"
                        >
                          Xem sản phẩm ngành
                        </button>
                      </div>
                    </div>
                  </article>
                );
              })}
            </div>
          )}
        </section>
      </main>

      <footer className="bg-[#003087] py-6 text-center text-xs text-blue-100">
        © 2025 Trường Cao Đẳng Công Nghệ Thủ Đức (TDC)
      </footer>
      <ScrollButtons />
    </div>
  );
}
