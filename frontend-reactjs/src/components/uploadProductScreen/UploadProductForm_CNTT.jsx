import { useContext, useState } from "react";
import useCategory from "../../hooks/common/useCategory";
import { confirmToast } from "../common/ConfirmToast";
import useMajorName from "../../hooks/common/useMajorName";
import { AuthContext } from "../../contexts/AuthContext";
import LoadingSpinner from "../common/LoadingOverlay";
import { Icons } from "../../components/common/Icon";

const UploadProductForm_CNTT = ({
  formData,
  handleChange,
  handleSubmit,
  errors,
  currentStep,
  handleSelectCategory,
  handleImageUpload,
  images,
  thumbnailIndex,
  removeImage,
  setAsThumbnail,
  handleFileUpload,
  files,
  removeFile,
  tagInput,
  setTagInput,
  handleAddTag,
  tags,
  handlePrevStep,
  handleNextStep,
  loading,
  isAllStepsCompleted,
  setSelectedImage,
  handleSaveDraft,
  drafts,
  openViewDraft,
  setOpenViewDraft,
  handleViewDraft,
  handleLoadDraft,
  handleDeleteDraft,
  currentStudent,
}) => {
  const [confirmed, setConfirmed] = useState(false);
  const { user } = useContext(AuthContext);
  const { majorName } = useMajorName(user?.major_id);

  const { categories, isLoadingCategories, categoryError } = useCategory();
  // State cho loading upload ảnh
  const [uploadingImage, setUploadingImage] = useState(false);
  const [uploadingFile, setUploadingFile] = useState(false);

  // State cho loading submit sản phẩm
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Wrap handleImageUpload để hiển thị loading
  const handleImageUploadWithLoading = async (e) => {
    setUploadingImage(true);
    try {
      await handleImageUpload(e);
    } catch (error) {
      console.error("Upload image error:", error);
    } finally {
      setUploadingImage(false);
    }
  };

  // Wrap handleFileUpload để hiển thị loading
  const handleFileUploadWithLoading = async (e) => {
    setUploadingFile(true);
    try {
      await handleFileUpload(e);
    } catch (error) {
      console.error("Upload file error:", error);
    } finally {
      setUploadingFile(false);
    }
  };

  // Wrap handleSubmit để hiển thị loading khi đăng sản phẩm
  const handleSubmitWithLoading = async (e) => {
    setIsSubmitting(true);
    try {
      await handleSubmit(e);
    } catch (error) {
      console.error("Submit error:", error);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <>
      {/* Loading overlay khi upload ảnh hoặc file */}
      {(uploadingImage || uploadingFile) && (
        <LoadingSpinner
          fullScreen={true}
          message={
            uploadingImage ? "Đang tải ảnh lên..." : "Đang tải file lên..."
          }
          size="md"
        />
      )}

      {/* Loading overlay khi đăng sản phẩm */}
      {(isSubmitting || loading) && (
        <LoadingSpinner
          fullScreen={true}
          message="Đang gửi sản phẩm đến giảng viên..."
          size="md"
        />
      )}

      <div>
        <form
          onKeyDown={(e) => {
            if (e.key === "Enter" && currentStep !== 3) {
              e.preventDefault();
            }
          }}
          onSubmit={(e) => {
            handleSubmitWithLoading(e);
          }}
          className="space-y-6"
        >
          {/* Step 1: Thông tin cơ bản */}
          <div
            className={`overflow-hidden rounded-2xl bg-white shadow-xl transition-all duration-500 ${
              currentStep === 1
                ? "scale-100 opacity-100"
                : "hidden scale-95 opacity-50"
            }`}
          >
            <div className="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
              <h2 className="flex items-center gap-2 text-xl font-semibold text-white">
                <span>📋</span> Thông tin cơ bản
              </h2>
            </div>

            <div className="space-y-6 p-6">
              <div>
                <label className="mb-2 block text-sm font-semibold text-gray-700">
                  Tên sản phẩm <span className="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  name="title"
                  value={formData.title}
                  onChange={handleChange}
                  className={`w-full rounded-xl border-2 px-4 py-3 ${
                    errors.title
                      ? "border-red-300 bg-red-50"
                      : "border-gray-200"
                  }`}
                  placeholder="VD: App Quản Lý Công Việc - TaskFlow"
                />
                {errors.title && (
                  <p className="mt-2 text-sm text-red-600">{errors.title}</p>
                )}
              </div>

              <div>
                <label className="mb-2 block text-sm font-semibold text-gray-700">
                  Mô tả ngắn
                  {" "}<span className="ml-1 text-xs font-normal text-gray-400">(có thể bỏ qua)</span>
                </label>
                <textarea
                  name="description"
                  value={formData.description}
                  onChange={handleChange}
                  rows={3}
                  className={`w-full rounded-xl border-2 px-4 py-3 ${
                    errors.description
                      ? "border-red-300 bg-red-50"
                      : "border-gray-200"
                  }`}
                  placeholder="Mô tả ngắn gọn về sản phẩm..."
                />
                {errors.description && (
                  <p className="mt-2 text-sm text-red-600">
                    {errors.description}
                  </p>
                )}
              </div>

              <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div className="space-y-2">
                  <label className="flex items-center gap-1 text-sm font-semibold text-gray-700">
                    Chuyên ngành <span className="text-red-500">*</span>
                  </label>

                  <div className="flex items-center justify-between rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm hover:border-gray-400 transition">
                    <div className="flex items-center gap-3">
                      <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-100 text-gray-600">
                        🎓
                      </div>

                      <div>
                        <p className="text-sm text-gray-500">Ngành</p>
                        <p className="font-medium text-gray-800">
                          {majorName || "Lỗi"}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                {/* select category */}
                {isLoadingCategories ? (
                  <div className="flex items-center justify-center p-4">
                    <div className="text-center">
                      <div className="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin mx-auto mb-2"></div>
                      <p className="text-sm text-gray-500">
                        Đang tải danh mục...
                      </p>
                    </div>
                  </div>
                ) : categoryError ? (
                  <p className="text-sm text-red-500">Lỗi tải danh mục</p>
                ) : (
                  <div>
                    <label className="mb-2 block text-sm font-semibold text-gray-700">
                      Danh mục <span className="text-red-500">*</span>
                    </label>

                    <div className="space-y-2">
                      {(categories || []).map((cat) => {
                        const isSelected = formData.cate_id === cat.cate_id;

                        return (
                          <button
                            key={cat.cate_id}
                            type="button"
                            onClick={() => handleSelectCategory(cat.cate_id)}
                            className={`flex w-full items-center gap-3 rounded-xl border-2 p-4 transition-all ${
                              isSelected
                                ? "border-indigo-500 bg-indigo-50 ring-4 ring-indigo-100"
                                : "border-gray-200 hover:border-gray-300 hover:bg-gray-50"
                            }`}
                          >
                            <div
                              className={`flex h-10 w-10 items-center justify-center rounded-lg ${
                                isSelected ? "bg-indigo-200" : "bg-gray-100"
                              }`}
                            >
                              <span className="text-xl">🗂️</span>
                            </div>

                            <div className="flex-1 text-left">
                              <p
                                className={`font-medium ${
                                  isSelected
                                    ? "text-indigo-700"
                                    : "text-gray-700"
                                }`}
                              >
                                {cat.category_name}
                              </p>

                              {isSelected && (
                                <p className="mt-0.5 text-xs text-indigo-600">
                                  ✔ Đang chọn
                                </p>
                              )}
                            </div>
                          </button>
                        );
                      })}
                    </div>

                    {errors.cate_id && (
                      <p className="mt-2 text-sm text-red-600">
                        {errors.cate_id}
                      </p>
                    )}
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Step 2: Hình ảnh & Files */}
          <div
            className={`overflow-hidden rounded-2xl bg-white shadow-xl transition-all duration-500 ${
              currentStep === 2
                ? "scale-100 opacity-100"
                : "hidden scale-95 opacity-50"
            }`}
          >
            <div className="bg-gradient-to-r from-purple-600 to-pink-600 px-6 py-4">
              <h2 className="flex items-center gap-2 text-xl font-semibold text-white">
                <span> {/* <Icons.image /> */}</span> Hình ảnh & Files
              </h2>
            </div>

            <div className="space-y-8 p-6">
              <div>
                <label className="mb-2 block text-sm font-semibold text-gray-700">
                  Hình ảnh sản phẩm <span className="text-red-500">*</span>
                </label>

                <div className="relative">
                  <input
                    type="file"
                    multiple
                    accept="image/*"
                    onChange={handleImageUploadWithLoading}
                    className="hidden"
                    id="image-upload"
                    disabled={uploadingImage}
                  />
                  <label
                    htmlFor="image-upload"
                    className={`group relative block w-full cursor-pointer rounded-2xl border-2 border-dashed border-purple-200 bg-gradient-to-br from-purple-50 to-pink-50 p-12 transition-all hover:from-purple-100 hover:to-pink-100 ${
                      uploadingImage ? "opacity-50 cursor-not-allowed" : ""
                    }`}
                  >
                    <div className="text-center">
                      <div className="mb-4 inline-flex rounded-full bg-white p-4 shadow-lg transition-transform group-hover:scale-110">
                        {uploadingImage ? (
                          <div className="w-8 h-8 border-4 border-purple-500 border-t-transparent rounded-full animate-spin"></div>
                        ) : (
                          <svg
                            className="h-8 w-8 text-purple-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                          >
                            <path
                              strokeLinecap="round"
                              strokeLinejoin="round"
                              strokeWidth={2}
                              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                          </svg>
                        )}
                      </div>
                      <p className="mb-2 text-lg font-medium text-gray-700">
                        {uploadingImage
                          ? "Đang tải ảnh lên..."
                          : "Kéo thả hoặc click để tải ảnh lên"}
                      </p>
                      <p className="text-sm text-gray-500">
                        Hỗ trợ: JPG, PNG, GIF • Tối đa 10 ảnh • Mỗi ảnh ≤ 5MB
                      </p>
                    </div>
                  </label>
                </div>

                {images.length > 0 && (
                  <div className="mt-6">
                    <p className="mb-3 flex items-center justify-between text-sm font-medium text-gray-700">
                      <span>📸 Hình ảnh đã tải lên ({images.length}/10)</span>
                      <span className="text-xs text-gray-500">
                        Click vào ảnh để phóng to
                      </span>
                    </p>

                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                      {images.map((image, index) => (
                        <div key={image.id} className="relative">
                          <div className="group relative">
                            <div
                              className={`aspect-square cursor-pointer overflow-hidden rounded-xl border-4 transition-all ${
                                index === thumbnailIndex
                                  ? "scale-105 border-purple-500 shadow-xl"
                                  : "border-transparent hover:border-gray-300"
                              }`}
                            >
                              <img
                                src={image.url}
                                alt={image.name}
                                className="h-full w-full object-cover"
                                onClick={() => setSelectedImage(image)}
                              />
                            </div>

                            <div className="absolute inset-0 flex items-center justify-center gap-2 rounded-xl bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                              <button
                                type="button"
                                onClick={(e) => {
                                  e.stopPropagation();
                                  setSelectedImage(image);
                                }}
                                className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 text-white transition hover:bg-blue-600"
                                title="Phóng to"
                              >
                                🔍
                              </button>

                              <button
                                type="button"
                                onClick={(e) => {
                                  e.stopPropagation();
                                  removeImage(image.id);
                                }}
                                className="flex h-8 w-8 items-center justify-center rounded-full bg-red-500 text-white transition hover:bg-red-600"
                                title="Xóa"
                              >
                                ❌
                              </button>
                            </div>

                            {index === thumbnailIndex && (
                              <div className="absolute -right-2 -top-2 flex h-8 w-8 items-center justify-center rounded-full bg-purple-500 text-sm font-bold text-white shadow-lg">
                                👑
                              </div>
                            )}
                          </div>

                          <div className="mt-2 flex gap-2">
                            <button
                              type="button"
                              onClick={() => setAsThumbnail(index)}
                              className={`w-full rounded-lg px-2 py-1 text-xs ${
                                index === thumbnailIndex
                                  ? "bg-purple-100 font-medium text-purple-700"
                                  : "bg-gray-100 text-gray-600 hover:bg-gray-200"
                              }`}
                            >
                              {index === thumbnailIndex
                                ? "Ảnh đại diện"
                                : "Đặt đại diện"}
                            </button>
                          </div>

                          <div className="mt-1 truncate text-xs text-gray-500">
                            {image.name} • {image.size}MB
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                )}

                {errors.images && (
                  <p className="mt-2 text-sm text-red-600">{errors.images}</p>
                )}
              </div>

              <div className="hidden">
                <label className="mb-2 block text-sm font-semibold text-gray-700">
                  File đính kèm
                 {" "}<span className="ml-1 text-xs font-normal text-gray-400">(có thể bỏ qua)</span></label>

                <div className="relative">
                  <input
                    type="file"
                    multiple
                    onChange={handleFileUploadWithLoading}
                    className="hidden"
                    id="file-upload"
                    disabled={uploadingFile}
                  />
                  <label
                    htmlFor="file-upload"
                    className={`group relative block w-full cursor-pointer rounded-2xl border-2 border-dashed border-indigo-200 bg-gradient-to-br from-indigo-50 to-blue-50 p-8 transition-all hover:from-indigo-100 hover:to-blue-100 ${
                      uploadingFile ? "opacity-50 cursor-not-allowed" : ""
                    }`}
                  >
                    <div className="text-center">
                      <div className="mb-3 inline-flex rounded-full bg-white p-3 shadow-lg transition-transform group-hover:scale-110">
                        {uploadingFile ? (
                          <div className="w-6 h-6 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
                        ) : (
                          <svg
                            className="h-6 w-6 text-indigo-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                          >
                            <path
                              strokeLinecap="round"
                              strokeLinejoin="round"
                              strokeWidth={2}
                              d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.414 6.586a6 6 0 106.364 6.364l6.364-6.364"
                            />
                          </svg>
                        )}
                      </div>
                      <p className="mb-1 text-base font-medium text-gray-700">
                        {uploadingFile
                          ? "Đang tải file lên..."
                          : "Tải lên báo cáo, source code, tài liệu"}
                      </p>
                      <p className="text-sm text-gray-500">
                        PDF, DOC, ZIP, RAR • Tối đa 5 files • Mỗi file ≤ 50MB
                      </p>
                    </div>
                  </label>
                </div>

                {files.length > 0 && (
                  <div className="mt-6 space-y-3">
                    <p className="text-sm font-medium text-gray-700">
                      📁 Files đã tải lên ({files.length}/5)
                    </p>

                    {files.map((file) => (
                      <div
                        key={file.id}
                        className="group flex items-center justify-between rounded-xl bg-gray-50 p-4 transition hover:bg-gray-100"
                      >
                        <div className="flex flex-1 items-center gap-3">
                          <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100 font-bold text-indigo-600">
                            {file.type}
                          </div>

                          <div className="flex-1">
                            <p className="font-medium text-gray-900">
                              {file.name}
                            </p>
                            <p className="text-xs text-gray-500">
                              {file.size} MB
                            </p>
                          </div>
                        </div>

                        <button
                          type="button"
                          onClick={() => removeFile(file.id)}
                          className="flex h-8 w-8 items-center justify-center rounded-lg bg-red-100 text-red-600 opacity-0 transition hover:bg-red-200 group-hover:opacity-100"
                        >
                          <svg
                            className="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                          >
                            <path
                              strokeLinecap="round"
                              strokeLinejoin="round"
                              strokeWidth={2}
                              d="M6 18L18 6M6 6l12 12"
                            />
                          </svg>
                        </button>
                      </div>
                    ))}
                  </div>
                )}

                {errors.files && (
                  <p className="mt-2 text-sm text-red-600">{errors.files}</p>
                )}
              </div>
            </div>
          </div>

          {/* Step 3: Tags & Liên kết + Thông tin CNTT */}
          <div
            className={`overflow-hidden rounded-2xl bg-white shadow-xl transition-all duration-500 ${
              currentStep === 3
                ? "scale-100 opacity-100"
                : "hidden scale-95 opacity-50"
            }`}
          >
            <div className="bg-gradient-to-r from-green-600 to-teal-600 px-6 py-4">
              <h2 className="flex items-center gap-2 text-xl font-semibold text-white">
                <span>🔗</span> Công nghệ & Liên kết
              </h2>
            </div>

            <div className="space-y-6 p-6">
              {/* Tags */}
              <div>
                <label className="mb-2 block text-sm font-semibold text-gray-700">
                  Công nghệ sử dụng
                 {" "}<span className="ml-1 text-xs font-normal text-gray-400">(có thể bỏ qua)</span></label>
                <input
                  type="text"
                  value={tagInput}
                  onChange={(e) => setTagInput(e.target.value)}
                  onKeyDown={handleAddTag}
                  className="w-full rounded-xl border-2 border-gray-200 px-4 py-3"
                  placeholder="Nhập công nghệ và nhấn Enter"
                />

                {tags.length > 0 && (
                  <div className="mt-4 flex flex-wrap gap-2">
                    {tags.map((tag) => (
                      <span
                        key={tag}
                        className="rounded-xl bg-green-500 px-3 py-1 text-sm text-white"
                      >
                        #{tag}
                      </span>
                    ))}
                  </div>
                )}
              </div>

              {/* Trường riêng CNTT */}
              <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                  <label className="mb-2 block text-sm font-semibold text-gray-700">
                    Ngôn ngữ lập trình
                  </label>
                  <input
                    type="text"
                    name="programming_language"
                    value={formData.programming_language || ""}
                    onChange={handleChange}
                    className="w-full rounded-xl border-2 border-gray-200 px-4 py-3"
                    placeholder="VD: JavaScript, PHP"
                  />
                  {errors.programming_language && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.programming_language}
                    </p>
                  )}
                </div>

                <div>
                  <label className="mb-2 block text-sm font-semibold text-gray-700">
                    Framework
                  </label>
                  <input
                    type="text"
                    name="framework"
                    value={formData.framework || ""}
                    onChange={handleChange}
                    className="w-full rounded-xl border-2 border-gray-200 px-4 py-3"
                    placeholder="VD: React, Laravel"
                  />
                  {errors.framework && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.framework}
                    </p>
                  )}
                </div>

                <div className="md:col-span-2">
                  <label className="mb-2 block text-sm font-semibold text-gray-700">
                    Database sử dụng
                  </label>
                  <input
                    type="text"
                    name="database_used"
                    value={formData.database_used || ""}
                    onChange={handleChange}
                    className="w-full rounded-xl border-2 border-gray-200 px-4 py-3"
                    placeholder="VD: MySQL, MongoDB"
                  />
                  {errors.database_used && (
                    <p className="mt-2 text-sm text-red-600">
                      {errors.database_used}
                    </p>
                  )}
                </div>
              </div>

              {/* Link */}
              <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                  <label className="mb-2 block text-sm font-semibold text-gray-700">
                    GitHub Link
                   {" "}<span className="ml-1 text-xs font-normal text-gray-400">(có thể bỏ qua)</span></label>
                  <input
                    type="url"
                    name="github_link"
                    value={formData.github_link}
                    onChange={handleChange}
                    className="w-full rounded-xl border-2 border-gray-200 px-4 py-3"
                    placeholder="https://github.com/..."
                  />
                </div>

                <div>
                  <label className="mb-2 block text-sm font-semibold text-gray-700">
                    Demo Link
                   {" "}<span className="ml-1 text-xs font-normal text-gray-400">(có thể bỏ qua)</span></label>
                  <input
                    type="url"
                    name="demo_link"
                    value={formData.demo_link}
                    onChange={handleChange}
                    className="w-full rounded-xl border-2 border-gray-200 px-4 py-3"
                    placeholder="https://demo.com"
                  />
                </div>
              </div>

              {/* Awards */}
              <div>
                <label className="mb-2 block text-sm font-semibold text-gray-700">
                  Thành tích / Giải thưởng
                </label>
                <textarea
                  name="awards"
                  rows={3}
                  value={formData.awards || ""}
                  onChange={handleChange}
                  className="w-full rounded-xl border-2 border-gray-200 px-4 py-3"
                  placeholder="VD: Giải nhất NCKH cấp khoa..."
                />
              </div>
            </div>
          </div>

          {/* Navigation buttons */}
          <div className="flex items-center justify-between gap-4">
            <button
              type="button"
              onClick={handlePrevStep}
              className={`rounded-xl border-2 border-gray-300 px-6 py-3 font-medium text-gray-700 transition hover:bg-gray-50 ${
                currentStep === 1 ? "invisible" : ""
              }`}
              disabled={isSubmitting || loading}
            >
              ← Quay lại
            </button>

            <div className="flex items-center gap-3">
              <button
                type="button"
                onClick={handleSaveDraft}
                className="rounded-xl border-2 border-gray-300 px-6 py-3 font-medium text-gray-700 transition hover:bg-gray-50"
                disabled={isSubmitting || loading}
              >
                Lưu nháp
              </button>

              <button
                type="button"
                onClick={handleViewDraft}
                className="rounded-xl border-2 border-gray-300 px-6 py-3 font-medium text-gray-700 transition hover:bg-gray-50"
                disabled={isSubmitting || loading}
              >
                Xem nháp
              </button>

              {currentStep < 3 ? (
                <button
                  type="button"
                  onClick={(e) => {
                    e.preventDefault();
                    handleNextStep();
                  }}
                  className="flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-3 font-medium text-white shadow-lg transition hover:from-blue-700 hover:to-indigo-700 hover:shadow-xl"
                  disabled={isSubmitting || loading}
                >
                  Tiếp theo
                  <svg
                    className="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M9 5l7 7-7 7"
                    />
                  </svg>
                </button>
              ) : (
                <button
                  type="submit"
                  disabled={
                    isSubmitting ||
                    loading ||
                    !isAllStepsCompleted() ||
                    !confirmed
                  }
                  className={`flex items-center gap-2 rounded-xl px-8 py-3 font-medium shadow-lg hover:shadow-xl ${
                    isAllStepsCompleted() &&
                    confirmed &&
                    !isSubmitting &&
                    !loading
                      ? "bg-gradient-to-r from-green-600 to-teal-600 text-white hover:from-green-700 hover:to-teal-700"
                      : "cursor-not-allowed bg-gray-300 text-gray-500"
                  }`}
                >
                  <svg
                    className="h-5 w-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M5 13l4 4L19 7"
                    />
                  </svg>
                  Gửi duyệt
                </button>
              )}
            </div>
          </div>

          {/* Warning messages */}
          {currentStep === 3 && !isAllStepsCompleted() && (
            <p className="mt-2 text-sm text-red-600">
              ⚠️ Vui lòng hoàn thành bước 1 và 2 trước khi gửi duyệt
            </p>
          )}

          {currentStep === 3 && isAllStepsCompleted() && (
            <div className="mt-4 rounded-lg border border-yellow-300 bg-yellow-50 p-4 text-sm text-yellow-800">
              ⚠️ Vui lòng kiểm tra thông tin trước khi gửi
              <div className="mt-2 flex items-center gap-2">
                <input
                  type="checkbox"
                  id="confirmCheck"
                  checked={confirmed}
                  onChange={(e) => setConfirmed(e.target.checked)}
                  className="h-4 w-4 rounded border-gray-300 text-yellow-600 focus:ring-yellow-500"
                  disabled={isSubmitting || loading}
                />
                <label htmlFor="confirmCheck">
                  Tôi đã đọc và xác nhận thông tin
                </label>
              </div>
            </div>
          )}

          <div className="rounded-xl bg-gradient-to-r from-blue-50 to-indigo-50 p-4 text-center">
            <p className="text-sm text-gray-600">
              <span className="font-semibold text-blue-600">📌 Lưu ý:</span> Sản
              phẩm của bạn sẽ được gửi đến giảng viên chuyên ngành
              <span className="font-semibold">{currentStudent?.major}</span> để
              xét duyệt trong vòng 24-48 giờ.
            </p>
          </div>
        </form>

        {/* screen view draft */}
        {openViewDraft && (
          <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div className="w-full max-w-lg rounded-xl bg-white p-6">
              <h2 className="mb-4 text-lg font-semibold">📂 Bản nháp</h2>

              {drafts.length === 0 ? (
                <p className="text-gray-500">Không có bản nháp</p>
              ) : (
                <div className="space-y-3 max-h-80 overflow-y-auto">
                  {drafts.map((draft) => (
                    <div
                      key={draft.id}
                      className="flex items-center justify-between gap-2 cursor-pointer rounded-lg border p-3 hover:bg-gray-50"
                      onClick={() => handleLoadDraft(draft)}
                    >
                      <div>
                        <p className="font-medium">
                          {draft.formData?.title || "Chưa có tiêu đề"}
                        </p>
                        <p className="text-xs text-gray-500">
                          {new Date(draft.createdAt).toLocaleString()}
                        </p>
                      </div>

                      <button
                        type="button"
                        onClick={(e) => {
                          e.stopPropagation();
                          confirmToast({
                            message: "Bạn chắc chắn muốn xóa bản nháp?",
                            onConfirm: () => handleDeleteDraft(draft.id),
                          });
                        }}
                        className="rounded-lg bg-red-100 px-3 py-1 text-sm text-red-600 hover:bg-red-200"
                      >
                        Xóa
                      </button>
                    </div>
                  ))}
                </div>
              )}

              <button
                type="button"
                onClick={() => setOpenViewDraft(false)}
                className="mt-4 w-full rounded-lg bg-gray-200 py-2 hover:bg-gray-300"
              >
                Đóng
              </button>
            </div>
          </div>
        )}
      </div>
    </>
  );
};

export default UploadProductForm_CNTT;
