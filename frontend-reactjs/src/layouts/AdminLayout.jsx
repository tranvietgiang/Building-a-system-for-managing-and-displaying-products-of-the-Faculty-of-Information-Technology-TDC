import { useContext } from "react";
import {
  BarChart3,
  BookOpen,
  Eye,
  GraduationCap,
  LifeBuoy,
  LogOut,
  Menu,
  PackageCheck,
  Settings,
  Users,
} from "lucide-react";
import { useNavigate } from "react-router-dom";
import { AuthContext } from "../contexts/AuthContext";

const menuItems = [
  { id: "dashboard", label: "Dashboard", icon: BarChart3 },
  { id: "users", label: "Người dùng", icon: Users },
  { id: "products", label: "Sản phẩm", icon: PackageCheck },
  { id: "majors", label: "Chuyên ngành", icon: GraduationCap },
  { id: "support", label: "Support", icon: LifeBuoy },
  { id: "settings", label: "Cài đặt", icon: Settings },
];

const AdminLayout = ({ activeSection, setActiveSection, title, children }) => {
  const { user, logout } = useContext(AuthContext);
  const navigate = useNavigate();

  return (
    <div className="min-h-screen bg-slate-50 text-slate-900">
      <aside className="fixed inset-y-0 left-0 hidden w-72 border-r border-slate-200 bg-white lg:block">
        <div className="flex h-20 items-center gap-3 border-b border-slate-200 px-6">
          <div className="flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-600 text-white">
            <BookOpen size={24} />
          </div>
          <div>
            <p className="text-sm font-semibold uppercase tracking-wide text-emerald-700">
              ADM Education
            </p>
            <h1 className="text-lg font-bold">TDC </h1>
          </div>
        </div>

        <nav className="space-y-1 px-4 py-5">
          {menuItems.map((item) => {
            const Icon = item.icon;
            const isActive = activeSection === item.id;

            return (
              <button
                key={item.id}
                type="button"
                onClick={() => setActiveSection(item.id)}
                className={`flex w-full items-center gap-3 rounded-lg px-4 py-3 text-left text-sm font-semibold transition ${
                  isActive
                    ? "bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100"
                    : "text-slate-600 hover:bg-slate-100 hover:text-slate-900"
                }`}
              >
                <Icon size={19} />
                {item.label}
              </button>
            );
          })}
        </nav>
      </aside>

      <div className="lg:pl-72">
        <header className="sticky top-0 z-20 border-b border-slate-200 bg-white/95 px-4 py-4 backdrop-blur md:px-8">
          <div className="flex items-center justify-between gap-4">
            <div className="flex min-w-0 items-center gap-3">
              <button
                type="button"
                className="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600 lg:hidden"
                aria-label="Mở menu"
              >
                <Menu size={20} />
              </button>
              <div className="min-w-0">
                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">
                  Quản trị hệ thống
                </p>
                <h2 className="truncate text-xl font-bold md:text-2xl">
                  {title}
                </h2>
              </div>
            </div>

            <div className="flex items-center gap-3">
              <div className="hidden text-right sm:block">
                <p className="text-sm font-semibold">{user?.name || "Admin"}</p>
                <p className="text-xs uppercase tracking-wide text-slate-500">
                  {user?.role || "admin"}
                </p>
              </div>
              <button
                type="button"
                onClick={() => navigate("/nckh-visitor")}
                className="hidden items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 md:flex"
                title="Xem sản phẩm toàn ngành"
              >
                <Eye size={17} />
                <span>Xem sản phẩm toàn ngành</span>
              </button>
              <button
                type="button"
                onClick={() => navigate("/nckh-visitor")}
                className="flex h-10 w-10 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 md:hidden"
                title="Xem sản phẩm toàn ngành"
                aria-label="Xem sản phẩm toàn ngành"
              >
                <Eye size={18} />
              </button>
              <button
                type="button"
                onClick={logout}
                className="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600"
                title="Đăng xuất"
              >
                <LogOut size={18} />
              </button>
            </div>
          </div>

          <div className="mt-4 flex gap-2 overflow-x-auto pb-1 lg:hidden">
            {menuItems.map((item) => (
              <button
                key={item.id}
                type="button"
                onClick={() => setActiveSection(item.id)}
                className={`shrink-0 rounded-lg px-3 py-2 text-sm font-semibold ${
                  activeSection === item.id
                    ? "bg-emerald-600 text-white"
                    : "bg-slate-100 text-slate-600"
                }`}
              >
                {item.label}
              </button>
            ))}
          </div>
        </header>

        <main className="px-4 py-6 md:px-8">{children}</main>
      </div>
    </div>
  );
};

export default AdminLayout;
