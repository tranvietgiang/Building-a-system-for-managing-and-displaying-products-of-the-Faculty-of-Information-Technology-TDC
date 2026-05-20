import { ShieldCheck, Server, UsersRound } from "lucide-react";

const settings = [
  {
    title: "Phân quyền",
    description: "Route admin được bảo vệ bằng Sanctum và middleware role:admin.",
    icon: ShieldCheck,
  },
  {
    title: "Quy trình duyệt",
    description: "Admin có thể chuyển sản phẩm sang pending, approved hoặc rejected.",
    icon: UsersRound,
  },
  {
    title: "Dữ liệu",
    description: "Người dùng, sản phẩm, chuyên ngành và danh mục dùng trực tiếp từ Laravel API.",
    icon: Server,
  },
];

const SettingsScreen = () => (
  <div className="grid gap-4 md:grid-cols-3">
    {settings.map((item) => {
      const Icon = item.icon;
      return (
        <div key={item.title} className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
          <div className="flex h-11 w-11 items-center justify-center rounded-lg bg-slate-100 text-slate-700">
            <Icon size={22} />
          </div>
          <h3 className="mt-4 text-lg font-bold">{item.title}</h3>
          <p className="mt-2 text-sm leading-6 text-slate-600">{item.description}</p>
        </div>
      );
    })}
  </div>
);

export default SettingsScreen;
