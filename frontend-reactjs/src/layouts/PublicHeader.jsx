import { useState } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { LogIn, Menu, X } from "lucide-react";
import logoTdc from "../assets/logo-tdc-orginal.webp";
import useScrollControls from "../hooks/common/useScrollControls";

const navLinkClass = (isActive) =>
  `rounded-md px-3 py-2 text-left text-sm font-medium transition hover:bg-white hover:text-[#003087] lg:px-0 lg:py-0 lg:hover:bg-transparent ${
    isActive ? "text-[#003087] font-semibold" : "text-slate-600"
  }`;

export default function PublicHeader({ title }) {
  const navigate = useNavigate();
  const location = useLocation();
  const { handleTop, handleBottom } = useScrollControls();
  const [isMenuOpen, setIsMenuOpen] = useState(false);

  const closeMenu = () => setIsMenuOpen(false);

  const goHome = () => {
    closeMenu();
    handleTop("/khach-tham-quan");
  };

  const goProducts = () => {
    closeMenu();
    handleBottom("/khach-tham-quan", "san-pham");
  };

  const goLogin = () => {
    closeMenu();
    navigate("/dang-nhap");
  };

  const renderNavItems = () => (
    <>
      <button type="button" onClick={goHome} className={navLinkClass(false)}>
        Trang chủ
      </button>
      <button type="button" onClick={goProducts} className={navLinkClass(false)}>
        Sản phẩm
      </button>
      <Link
        to="/nganh-hoc"
        onClick={closeMenu}
        className={navLinkClass(location.pathname === "/nganh-hoc")}
      >
        Ngành học
      </Link>
      <Link
        to="/huong-dan"
        onClick={closeMenu}
        className={navLinkClass(location.pathname === "/huong-dan")}
      >
        Hướng dẫn
      </Link>
      <Link
        to="/lien-he"
        onClick={closeMenu}
        className={navLinkClass(location.pathname === "/lien-he")}
      >
        Liên hệ
      </Link>
    </>
  );

  return (
    <header className="sticky top-0 z-50 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur">
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="flex h-16 items-center justify-between gap-3 lg:h-20">
          <Link to="/khach-tham-quan" className="flex min-w-0 items-center gap-3">
            <img src={logoTdc} alt="TDC" className="h-12 w-auto shrink-0" />
            <div className="hidden min-w-0 sm:block">
              <p className="truncate text-lg font-bold leading-tight text-[#003087]">
                {title}
              </p>
              <p className="truncate text-xs text-slate-500">
                Khoa Công Nghệ Thông Tin | TDC
              </p>
            </div>
          </Link>

          <nav className="hidden items-center gap-6 lg:flex">{renderNavItems()}</nav>

          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={goLogin}
              className="hidden rounded-md border border-[#003087] px-4 py-2 text-sm font-semibold text-[#003087] transition hover:bg-[#003087] hover:text-white sm:inline-flex"
            >
              Đăng nhập
            </button>
            <button
              type="button"
              onClick={() => setIsMenuOpen((current) => !current)}
              className="inline-flex h-10 w-10 items-center justify-center rounded-md border border-slate-200 text-slate-700 transition hover:border-[#003087] hover:text-[#003087] lg:hidden"
              aria-label={isMenuOpen ? "Đóng menu" : "Mở menu"}
              aria-expanded={isMenuOpen}
            >
              {isMenuOpen ? <X size={20} /> : <Menu size={20} />}
            </button>
          </div>
        </div>

        {isMenuOpen && (
          <div className="border-t border-slate-100 py-3 lg:hidden">
            <nav className="grid gap-1">
              <div className="grid gap-1 rounded-md bg-slate-50 p-2">
                {renderNavItems()}
              </div>
              <button
                type="button"
                onClick={goLogin}
                className="mt-2 inline-flex h-10 items-center justify-center gap-2 rounded-md bg-[#003087] px-4 text-sm font-semibold text-white transition hover:bg-[#00266b] sm:hidden"
              >
                <LogIn size={17} />
                Đăng nhập
              </button>
            </nav>
          </div>
        )}
      </div>
    </header>
  );
}
