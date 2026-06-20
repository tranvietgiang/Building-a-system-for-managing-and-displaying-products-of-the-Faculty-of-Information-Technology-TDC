import React, { useContext } from "react";
import { useBackToPage } from "../../hooks/common/useRouters";
import { AuthContext } from "../../contexts/AuthContext";
import { mapCurrentStudent } from "../../utils/userMapper";
import useMajorName from "../../hooks/common/useMajorName";
import useUploadPublishedCount from "../../hooks/useUpload/useUploadPublishedCount";
import useUploadProductForm from "../../hooks/uploadProductScreen/useUploadCNTTForm";
import { useNavigate } from "react-router-dom";
import UploadProductForm_CNTT from "../../components/uploadProductScreen/UploadProductForm_CNTT";
import useTitle from "../../hooks/common/useTitle";
const UploadProductScreen = () => {
  const goBack = useBackToPage();
  const { user } = useContext(AuthContext);
  const { majorName } = useMajorName(user?.major_id);
  const currentStudent = mapCurrentStudent(user, majorName);
  const navigate = useNavigate();
  const { upload_count, upload_loading, upload_error } =
    useUploadPublishedCount();

  useTitle("Trang đăng sản phẩm");

  const {
    formData,
    tags,
    tagInput,
    setTagInput,
    images,
    files,
    thumbnailIndex,
    loading,
    errors,
    currentStep,
    touchedSteps,
    selectedImage,
    submitStatus,
    steps,
    statusApi,
    isStepValid,
    isAllStepsCompleted,
    handleNextStep,
    handlePrevStep,
    handleChange,
    handleSelectCategory,
    handleAddTag,
    removeTag,
    handleImageUpload,
    removeImage,
    setAsThumbnail,
    handleFileUpload,
    removeFile,
    handleSubmit,
    setSelectedImage,
    setSubmitStatus,

    //view draft
    drafts,
    openViewDraft,
    setOpenViewDraft,
    handleSaveDraft,
    handleLoadDraft,
    handleViewDraft,
    handleDeleteDraft,
  } = useUploadProductForm(currentStudent);

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
      {selectedImage && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4 animate-fadeIn"
          onClick={() => setSelectedImage(null)}
        >
          <button
            onClick={() => setSelectedImage(null)}
            className="absolute right-4 top-4 z-10 rounded-full bg-black/20 p-2 text-white/80 transition hover:bg-black/40 hover:text-white"
          >
            <svg
              className="h-8 w-8"
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

          <div
            className="relative max-h-[90vh] max-w-7xl animate-scaleIn"
            onClick={(e) => e.stopPropagation()}
          >
            <img
              src={selectedImage.url}
              alt={selectedImage.name}
              className="max-h-[90vh] max-w-full rounded-lg object-contain shadow-2xl"
            />

            <div className="absolute bottom-4 left-1/2 flex -translate-x-1/2 items-center gap-3 rounded-full bg-black/50 px-4 py-2 text-sm text-white backdrop-blur">
              <span className="max-w-[200px] truncate">
                {selectedImage.name}
              </span>
              <span className="h-1 w-1 rounded-full bg-white/50"></span>
              <span>{selectedImage.size}MB</span>
              {selectedImage.id === images[thumbnailIndex]?.id && (
                <>
                  <span className="h-1 w-1 rounded-full bg-white/50"></span>
                  <span className="flex items-center gap-1">
                    <span>👑</span> Ảnh đại diện
                  </span>
                </>
              )}
            </div>
          </div>
        </div>
      )}

      {submitStatus === "success" && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 animate-fadeIn">
          <div className="w-full max-w-md animate-scaleIn rounded-2xl bg-white p-6">
            <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
              <svg
                className="h-8 w-8 text-green-500"
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
            </div>

            <h3 className="mb-2 text-center text-xl font-bold text-gray-900">
              Đăng sản phẩm thành công!
            </h3>

            <p className="mb-6 text-center text-gray-600">
              Sản phẩm của bạn đã được gửi đến giảng viên chuyên ngành{" "}
              {currentStudent?.major} để duyệt. Bạn sẽ nhận được thông báo khi
              sản phẩm được phê duyệt.
            </p>

            <div className="mb-6 rounded-lg bg-blue-50 p-3">
              <p className="flex items-center gap-2 text-sm text-blue-800">
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
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
                Thời gian duyệt dự kiến: 24-48 giờ
              </p>
            </div>

            <button
              onClick={() => navigate("/sinh-vien")}
              className="w-full rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-3 font-medium text-white transition hover:from-blue-700 hover:to-indigo-700"
            >
              Xem sản phẩm của tôi
            </button>
          </div>
        </div>
      )}

      {submitStatus === statusApi?.status && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 animate-fadeIn">
          <div className="w-full max-w-md animate-scaleIn rounded-2xl bg-white p-6">
            <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
              <svg
                className="h-8 w-8 text-red-500"
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
            </div>

            <h3 className="mb-2 text-center text-xl font-bold text-gray-900">
              Upload thất bại
            </h3>

            <p className="mb-6 text-center text-gray-600">
              {statusApi?.message ||
                "Có lỗi xảy ra khi gửi sản phẩm. Kiểm tra lại API hoặc dữ liệu rồi thử lại"}
            </p>

            <button
              onClick={() => setSubmitStatus(null)}
              className="w-full rounded-xl bg-gradient-to-r from-red-500 to-rose-500 px-4 py-3 font-medium text-white transition hover:from-red-600 hover:to-rose-600"
            >
              Đóng
            </button>
          </div>
        </div>
      )}

      <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <button
          onClick={() => {
            if (loading) return;
            goBack();
          }}
          disabled={loading}
          className={`rounded-lg px-4 py-2 ${
            loading
              ? "cursor-not-allowed bg-gray-300 text-gray-500"
              : "bg-gray-200 hover:bg-gray-300"
          }`}
        >
          Quay lại
        </button>
        <div className="mb-8 text-center">
          <div className="mb-4 inline-flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 shadow-lg">
            <svg
              className="h-10 w-10 text-white"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M12 4v16m8-8H4"
              />
            </svg>
          </div>

          <h1 className="bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-4xl font-bold text-transparent">
            Đăng sản phẩm mới
          </h1>

          <p className="mx-auto mt-2 max-w-2xl text-gray-600">
            Chia sẻ sản phẩm công nghệ của bạn với cộng đồng. Điền đầy đủ thông
            tin để giảng viên duyệt nhanh nhất.
          </p>
        </div>
        <div className="mb-8">
          <div className="flex items-center justify-between">
            {steps.map((step, index) => {
              const isValid = isStepValid(step.id);
              const isTouched = touchedSteps[step.id];

              return (
                <React.Fragment key={step.id}>
                  <div className="flex flex-col items-center">
                    <div
                      className={`flex h-12 w-12 items-center justify-center rounded-full text-xl font-semibold transition-all duration-300 ${
                        currentStep === step.id
                          ? "scale-110 bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg"
                          : isValid && isTouched
                            ? "bg-green-500 text-white"
                            : "bg-gray-200 text-gray-500"
                      }`}
                    >
                      {isValid && isTouched && step.id !== currentStep ? (
                        <svg
                          className="h-6 w-6"
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
                      ) : (
                        step.icon
                      )}
                    </div>

                    <span
                      className={`mt-2 text-sm font-medium ${
                        currentStep === step.id
                          ? "text-gray-900"
                          : "text-gray-500"
                      }`}
                    >
                      {step.name}
                    </span>
                  </div>

                  {index < steps.length - 1 && (
                    <div
                      className={`mx-4 h-1 flex-1 rounded ${
                        currentStep > index + 1
                          ? "bg-gradient-to-r from-blue-600 to-indigo-600"
                          : "bg-gray-200"
                      }`}
                    />
                  )}
                </React.Fragment>
              );
            })}
          </div>
        </div>
        <div className="mb-6 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 p-6 text-white shadow-xl">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <div className="flex h-16 w-16 items-center justify-center rounded-full border-2 border-white/50 bg-white/20 text-2xl font-bold backdrop-blur">
                {currentStudent?.name?.charAt(0) || "S"}
              </div>

              <div>
                <h2 className="text-xl font-semibold">
                  {currentStudent?.name}
                </h2>
                <p className="text-blue-100">
                  {currentStudent?.class} - {currentStudent?.major}
                </p>
              </div>
            </div>

            <div className="text-right">
              <div className="text-3xl font-bold">
                {upload_loading ? "..." : upload_error ? "Lỗi" : upload_count}
              </div>

              <div className="text-blue-200">
                {upload_loading
                  ? "Đang tải..."
                  : upload_error
                    ? "Không tải được dữ liệu"
                    : "sản phẩm đã đăng"}
              </div>
            </div>
          </div>
        </div>

        {/* form input upload */}
        <UploadProductForm
          formData={formData}
          handleChange={handleChange}
          handleSubmit={handleSubmit}
          errors={errors}
          currentStep={currentStep}
          majorName={currentStudent?.majorName}
          handleSelectCategory={handleSelectCategory}
          handleImageUpload={handleImageUpload}
          images={images}
          thumbnailIndex={thumbnailIndex}
          removeImage={removeImage}
          setAsThumbnail={setAsThumbnail}
          handleFileUpload={handleFileUpload}
          files={files}
          removeFile={removeFile}
          tagInput={tagInput}
          setTagInput={setTagInput}
          handleAddTag={handleAddTag}
          tags={tags}
          removeTag={removeTag}
          handlePrevStep={handlePrevStep}
          handleNextStep={handleNextStep}
          loading={loading}
          isAllStepsCompleted={isAllStepsCompleted}
          currentStudent={currentStudent}
          setSelectedImage={setSelectedImage}
          // view draft
          drafts={drafts}
          openViewDraft={openViewDraft}
          setOpenViewDraft={setOpenViewDraft}
          handleSaveDraft={handleSaveDraft}
          handleViewDraft={handleViewDraft}
          handleLoadDraft={handleLoadDraft}
          handleDeleteDraft={handleDeleteDraft}
        />
      </div>

      <style jsx>{`
        @keyframes fadeIn {
          from {
            opacity: 0;
          }
          to {
            opacity: 1;
          }
        }

        @keyframes scaleIn {
          from {
            transform: scale(0.9);
            opacity: 0;
          }
          to {
            transform: scale(1);
            opacity: 1;
          }
        }

        .animate-fadeIn {
          animation: fadeIn 0.2s ease-out;
        }

        .animate-scaleIn {
          animation: scaleIn 0.3s ease-out;
        }
      `}</style>
    </div>
  );
};

export default UploadProductScreen;
