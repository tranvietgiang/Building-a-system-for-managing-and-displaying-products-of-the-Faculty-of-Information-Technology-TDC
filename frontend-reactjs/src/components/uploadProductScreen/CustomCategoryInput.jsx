export default function CustomCategoryInput({
  formData,
  handleChange,
  errors,
  focusClass = "focus:border-indigo-500 focus:ring-indigo-100",
}) {
  return (
    <div className="mt-4 rounded-xl border border-dashed border-gray-300 bg-gray-50 p-4">
      <label className="mb-2 block text-sm font-semibold text-gray-700">
        Danh mục khác
      </label>
      <input
        type="text"
        name="custom_category_name"
        value={formData.custom_category_name || ""}
        onChange={handleChange}
        maxLength={100}
        placeholder="Nhập danh mục nếu không có trong mẫu"
        className={`w-full rounded-xl border-2 border-gray-200 bg-white px-4 py-3 text-sm outline-none transition ${focusClass}`}
      />
      <p className="mt-2 text-xs text-gray-500">
        Nếu nhập ô này hệ thống sẽ dùng danh mục bạn nhập thay cho danh mục mẫu.
      </p>
      {errors.custom_category_name && (
        <p className="mt-2 text-sm text-red-600">
          {errors.custom_category_name}
        </p>
      )}
    </div>
  );
}
