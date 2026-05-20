import { useEffect, useState } from "react";
import { Edit3, Plus, Search, Trash2 } from "lucide-react";
import adminApi from "../../../../api/admin.api";

const emptyForm = {
  user_id: "",
  name: "",
  email: "",
  password: "",
  role: "student",
  major_id: "",
  class: "",
};

const roles = [
  { value: "", label: "Tất cả vai trò" },
  { value: "student", label: "Sinh viên" },
  { value: "teacher", label: "Giảng viên" },
  { value: "admin", label: "Admin" },
];

const UsersScreen = () => {
  const [users, setUsers] = useState([]);
  const [majors, setMajors] = useState([]);
  const [filters, setFilters] = useState({ q: "", role: "", major_id: "" });
  const [form, setForm] = useState(emptyForm);
  const [editingId, setEditingId] = useState(null);
  const [loading, setLoading] = useState(true);

  const fetchUsers = async () => {
    setLoading(true);
    try {
      const res = await adminApi.getUsers({ ...filters, per_page: 50 });
      setUsers(res.data?.data || []);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    adminApi.getMajors().then((res) => setMajors(res.data || []));
  }, []);

  useEffect(() => {
    fetchUsers();
  }, [filters.role, filters.major_id]);

  const handleSearch = (event) => {
    event.preventDefault();
    fetchUsers();
  };

  const submitUser = async (event) => {
    event.preventDefault();
    const payload = {
      ...form,
      major_id: form.major_id || null,
      class: form.class || null,
    };

    if (editingId) {
      if (!payload.password) delete payload.password;
      delete payload.user_id;
      await adminApi.updateUser(editingId, payload);
    } else {
      await adminApi.createUser(payload);
    }

    setForm(emptyForm);
    setEditingId(null);
    fetchUsers();
  };

  const editUser = (user) => {
    setEditingId(user.user_id);
    setForm({
      user_id: user.user_id,
      name: user.name || "",
      email: user.email || "",
      password: "",
      role: user.role || "student",
      major_id: user.major_id || "",
      class: user.class || "",
    });
  };

  const deleteUser = async (userId) => {
    if (!window.confirm("Xóa người dùng này?")) return;
    await adminApi.deleteUser(userId);
    fetchUsers();
  };

  return (
    <div className="space-y-5">
      <form onSubmit={handleSearch} className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div className="grid gap-3 md:grid-cols-[1fr_180px_220px_auto]">
          <label className="relative">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
            <input
              value={filters.q}
              onChange={(event) => setFilters((prev) => ({ ...prev, q: event.target.value }))}
              placeholder="Tìm mã, tên hoặc email..."
              className="h-11 w-full rounded-lg border border-slate-200 pl-10 pr-3 outline-none focus:border-emerald-500"
            />
          </label>
          <select
            value={filters.role}
            onChange={(event) => setFilters((prev) => ({ ...prev, role: event.target.value }))}
            className="h-11 rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-500"
          >
            {roles.map((role) => (
              <option key={role.value} value={role.value}>{role.label}</option>
            ))}
          </select>
          <select
            value={filters.major_id}
            onChange={(event) => setFilters((prev) => ({ ...prev, major_id: event.target.value }))}
            className="h-11 rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-500"
          >
            <option value="">Tất cả chuyên ngành</option>
            {majors.map((major) => (
              <option key={major.major_id} value={major.major_id}>{major.major_name}</option>
            ))}
          </select>
          <button type="submit" className="h-11 rounded-lg bg-emerald-600 px-5 font-semibold text-white hover:bg-emerald-700">
            Tìm
          </button>
        </div>
      </form>

      <div className="grid gap-5 xl:grid-cols-[410px_1fr]">
        <form onSubmit={submitUser} className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
          <h3 className="text-lg font-bold">{editingId ? "Cập nhật người dùng" : "Thêm người dùng"}</h3>
          <div className="mt-4 grid gap-3">
            <input
              value={form.user_id}
              onChange={(event) => setForm((prev) => ({ ...prev, user_id: event.target.value }))}
              placeholder="Mã người dùng"
              required
              disabled={!!editingId}
              className="h-11 rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-500 disabled:bg-slate-100"
            />
            <input
              value={form.name}
              onChange={(event) => setForm((prev) => ({ ...prev, name: event.target.value }))}
              placeholder="Họ tên"
              required
              className="h-11 rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-500"
            />
            <input
              type="email"
              value={form.email}
              onChange={(event) => setForm((prev) => ({ ...prev, email: event.target.value }))}
              placeholder="Email"
              required
              className="h-11 rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-500"
            />
            <input
              type="password"
              value={form.password}
              onChange={(event) => setForm((prev) => ({ ...prev, password: event.target.value }))}
              placeholder={editingId ? "Mật khẩu mới nếu cần đổi" : "Mật khẩu"}
              required={!editingId}
              className="h-11 rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-500"
            />
            <select
              value={form.role}
              onChange={(event) => setForm((prev) => ({ ...prev, role: event.target.value }))}
              className="h-11 rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-500"
            >
              <option value="student">Sinh viên</option>
              <option value="teacher">Giảng viên</option>
              <option value="admin">Admin</option>
            </select>
            <select
              value={form.major_id}
              onChange={(event) => setForm((prev) => ({ ...prev, major_id: event.target.value }))}
              className="h-11 rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-500"
            >
              <option value="">Không chọn chuyên ngành</option>
              {majors.map((major) => (
                <option key={major.major_id} value={major.major_id}>{major.major_name}</option>
              ))}
            </select>
            <input
              value={form.class}
              onChange={(event) => setForm((prev) => ({ ...prev, class: event.target.value }))}
              placeholder="Lớp"
              className="h-11 rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-500"
            />
          </div>
          <div className="mt-4 flex gap-2">
            <button type="submit" className="inline-flex h-11 items-center gap-2 rounded-lg bg-emerald-600 px-4 font-semibold text-white hover:bg-emerald-700">
              <Plus size={18} />
              {editingId ? "Lưu" : "Thêm"}
            </button>
            {editingId && (
              <button
                type="button"
                onClick={() => {
                  setEditingId(null);
                  setForm(emptyForm);
                }}
                className="h-11 rounded-lg border border-slate-200 px-4 font-semibold text-slate-600 hover:bg-slate-50"
              >
                Hủy
              </button>
            )}
          </div>
        </form>

        <div className="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
          <div className="overflow-x-auto">
            <table className="w-full min-w-[780px] text-left text-sm">
              <thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
                <tr>
                  <th className="px-4 py-3">Người dùng</th>
                  <th className="px-4 py-3">Vai trò</th>
                  <th className="px-4 py-3">Chuyên ngành</th>
                  <th className="px-4 py-3">Lớp</th>
                  <th className="px-4 py-3 text-right">Thao tác</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {loading ? (
                  <tr><td className="px-4 py-8 text-center text-slate-500" colSpan="5">Đang tải...</td></tr>
                ) : users.length === 0 ? (
                  <tr><td className="px-4 py-8 text-center text-slate-500" colSpan="5">Chưa có người dùng phù hợp.</td></tr>
                ) : users.map((user) => (
                  <tr key={user.user_id}>
                    <td className="px-4 py-3">
                      <p className="font-semibold">{user.name}</p>
                      <p className="text-xs text-slate-500">{user.user_id} - {user.email}</p>
                    </td>
                    <td className="px-4 py-3">
                      <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase text-slate-700">{user.role}</span>
                    </td>
                    <td className="px-4 py-3 text-slate-600">{user.major_name || "Không có"}</td>
                    <td className="px-4 py-3 text-slate-600">{user.class || "-"}</td>
                    <td className="px-4 py-3 text-right">
                      <button type="button" onClick={() => editUser(user)} className="mr-1 inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100" title="Sửa">
                        <Edit3 size={17} />
                      </button>
                      <button type="button" onClick={() => deleteUser(user.user_id)} className="inline-flex h-9 w-9 items-center justify-center rounded-lg text-rose-600 hover:bg-rose-50" title="Xóa">
                        <Trash2 size={17} />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
};

export default UsersScreen;
