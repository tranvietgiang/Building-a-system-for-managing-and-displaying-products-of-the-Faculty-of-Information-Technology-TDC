import { useEffect, useState } from "react";
import { Edit3, Plus, Trash2 } from "lucide-react";
import adminApi from "../../../api/admin.api";

const emptyForm = { major_name: "", major_code: "", description: "" };

const MajorsScreen = () => {
  const [majors, setMajors] = useState([]);
  const [form, setForm] = useState(emptyForm);
  const [editingId, setEditingId] = useState(null);
  const [loading, setLoading] = useState(true);

  const fetchMajors = async () => {
    setLoading(true);
    try {
      const res = await adminApi.getMajors();
      setMajors(res.data || []);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchMajors();
  }, []);

  const submitMajor = async (event) => {
    event.preventDefault();
    if (editingId) {
      await adminApi.updateMajor(editingId, form);
    } else {
      await adminApi.createMajor(form);
    }
    setForm(emptyForm);
    setEditingId(null);
    fetchMajors();
  };

  const editMajor = (major) => {
    setEditingId(major.major_id);
    setForm({
      major_name: major.major_name || "",
      major_code: major.major_code || "",
      description: major.description || "",
    });
  };

  const deleteMajor = async (majorId) => {
    if (!window.confirm("Xóa chuyên ngành này?")) return;
    await adminApi.deleteMajor(majorId);
    fetchMajors();
  };

  return (
    <div className="grid gap-5 xl:grid-cols-[390px_1fr]">
      <form onSubmit={submitMajor} className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h3 className="text-lg font-bold">{editingId ? "Cập nhật chuyên ngành" : "Thêm chuyên ngành"}</h3>
        <div className="mt-4 space-y-3">
          <input
            value={form.major_name}
            onChange={(event) => setForm((prev) => ({ ...prev, major_name: event.target.value }))}
            placeholder="Tên chuyên ngành"
            required
            className="h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-500"
          />
          <input
            value={form.major_code}
            onChange={(event) => setForm((prev) => ({ ...prev, major_code: event.target.value }))}
            placeholder="Mã chuyên ngành"
            required
            className="h-11 w-full rounded-lg border border-slate-200 px-3 outline-none focus:border-emerald-500"
          />
          <textarea
            value={form.description}
            onChange={(event) => setForm((prev) => ({ ...prev, description: event.target.value }))}
            placeholder="Mô tả"
            rows="5"
            className="w-full rounded-lg border border-slate-200 px-3 py-3 outline-none focus:border-emerald-500"
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
        <table className="w-full text-left text-sm">
          <thead className="border-b border-slate-200 bg-slate-50 text-xs uppercase text-slate-500">
            <tr>
              <th className="px-4 py-3">Chuyên ngành</th>
              <th className="px-4 py-3">Mã</th>
              <th className="px-4 py-3">Sản phẩm</th>
              <th className="px-4 py-3 text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {loading ? (
              <tr><td className="px-4 py-8 text-center text-slate-500" colSpan="4">Đang tải...</td></tr>
            ) : majors.map((major) => (
              <tr key={major.major_id}>
                <td className="px-4 py-3">
                  <p className="font-semibold">{major.major_name}</p>
                  <p className="mt-1 max-w-xl text-xs text-slate-500">{major.description}</p>
                </td>
                <td className="px-4 py-3 font-semibold text-slate-600">{major.major_code}</td>
                <td className="px-4 py-3 text-slate-600">{major.products_count ?? 0}</td>
                <td className="px-4 py-3 text-right">
                  <button type="button" onClick={() => editMajor(major)} className="mr-1 inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100" title="Sửa">
                    <Edit3 size={17} />
                  </button>
                  <button type="button" onClick={() => deleteMajor(major.major_id)} className="inline-flex h-9 w-9 items-center justify-center rounded-lg text-rose-600 hover:bg-rose-50" title="Xóa">
                    <Trash2 size={17} />
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default MajorsScreen;
