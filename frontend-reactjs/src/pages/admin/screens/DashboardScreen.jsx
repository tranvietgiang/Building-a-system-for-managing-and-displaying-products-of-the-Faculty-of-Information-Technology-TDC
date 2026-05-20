import { useEffect, useState } from "react";
import {
  CheckCircle2,
  Clock3,
  GraduationCap,
  PackageCheck,
  Users,
  XCircle,
} from "lucide-react";
import adminApi from "../../../api/admin.api";

const StatCard = ({ title, value, icon: Icon, tone }) => (
  <div className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <div className="flex items-center justify-between gap-4">
      <div>
        <p className="text-sm font-medium text-slate-500">{title}</p>
        <p className="mt-2 text-3xl font-bold text-slate-900">{value ?? 0}</p>
      </div>
      <div className={`flex h-12 w-12 items-center justify-center rounded-lg ${tone}`}>
        <Icon size={24} />
      </div>
    </div>
  </div>
);

const DashboardScreen = () => {
  const [dashboard, setDashboard] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchDashboard = async () => {
      try {
        const res = await adminApi.getDashboard();
        setDashboard(res.data);
      } finally {
        setLoading(false);
      }
    };

    fetchDashboard();
  }, []);

  if (loading) {
    return <div className="rounded-lg bg-white p-6 text-slate-500">Đang tải dashboard...</div>;
  }

  const totals = dashboard?.totals || {};

  return (
    <div className="space-y-6">
      <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard title="Tổng người dùng" value={totals.users} icon={Users} tone="bg-cyan-50 text-cyan-700" />
        <StatCard title="Tổng sản phẩm" value={totals.products} icon={PackageCheck} tone="bg-emerald-50 text-emerald-700" />
        <StatCard title="Đang chờ duyệt" value={totals.pending_products} icon={Clock3} tone="bg-amber-50 text-amber-700" />
        <StatCard title="Chuyên ngành" value={totals.majors} icon={GraduationCap} tone="bg-violet-50 text-violet-700" />
      </section>

      <section className="grid gap-6 xl:grid-cols-[1fr_1.4fr]">
        <div className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
          <h3 className="text-lg font-bold">Tình trạng sản phẩm</h3>
          <div className="mt-5 space-y-3">
            {[
              { label: "Đã duyệt", value: totals.approved_products, icon: CheckCircle2, className: "text-emerald-700 bg-emerald-50" },
              { label: "Chờ duyệt", value: totals.pending_products, icon: Clock3, className: "text-amber-700 bg-amber-50" },
              { label: "Từ chối", value: totals.rejected_products, icon: XCircle, className: "text-rose-700 bg-rose-50" },
            ].map((item) => {
              const Icon = item.icon;
              return (
                <div key={item.label} className="flex items-center justify-between rounded-lg border border-slate-100 p-3">
                  <div className="flex items-center gap-3">
                    <span className={`flex h-9 w-9 items-center justify-center rounded-lg ${item.className}`}>
                      <Icon size={18} />
                    </span>
                    <span className="font-semibold">{item.label}</span>
                  </div>
                  <span className="text-xl font-bold">{item.value ?? 0}</span>
                </div>
              );
            })}
          </div>
        </div>

        <div className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
          <h3 className="text-lg font-bold">Sản phẩm mới nhất</h3>
          <div className="mt-4 overflow-x-auto">
            <table className="w-full min-w-[620px] text-left text-sm">
              <thead className="border-b border-slate-200 text-xs uppercase text-slate-500">
                <tr>
                  <th className="py-3 pr-4">Tên sản phẩm</th>
                  <th className="py-3 pr-4">Sinh viên</th>
                  <th className="py-3 pr-4">Chuyên ngành</th>
                  <th className="py-3">Trạng thái</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {(dashboard?.recent_products || []).map((product) => (
                  <tr key={product.product_id}>
                    <td className="py-3 pr-4 font-semibold">{product.title}</td>
                    <td className="py-3 pr-4 text-slate-600">{product.student_name || "Chưa rõ"}</td>
                    <td className="py-3 pr-4 text-slate-600">{product.major_name || "Chưa phân ngành"}</td>
                    <td className="py-3">
                      <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                        {product.status}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </div>
  );
};

export default DashboardScreen;
