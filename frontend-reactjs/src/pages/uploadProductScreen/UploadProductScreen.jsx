import React, { useContext, useMemo } from "react";
import { useLocation, useNavigate } from "react-router-dom";

import BackButton from "../../components/common/BackButton";
import { AuthContext } from "../../contexts/AuthContext";
import { mapCurrentStudent } from "../../utils/userMapper";
import useMajorName from "../../hooks/common/useMajorName";
import useUploadPublishedCount from "../../hooks/useUpload/useUploadPublishedCount";
import useTitle from "../../hooks/common/useTitle";

import { getUploadResources } from "../../utils/uploadProductScreen/uploadRegistry";
const UploadProductScreen = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const { user } = useContext(AuthContext);
  const { majorName } = useMajorName(user?.major_id);
  const editProduct = location.state?.product;
  const isEditMode = location.state?.mode === "edit" && editProduct;

  const currentStudent = mapCurrentStudent(user, majorName);

  const { upload_count, upload_loading, upload_error } =
    useUploadPublishedCount();

  const resources = getUploadResources();

  // if (!resources) return <div>Không tìm thấy ngành</div>;

  const { useHook, FormComponent, title, description, gradient, icon } =
    resources;

  useTitle(isEditMode ? "Chỉnh sửa sản phẩm" : title);

  const editData = useMemo(() => {
    if (!isEditMode) return null;

    const aiDetail = editProduct.ai_detail || {};
    const itDetail = editProduct.it_detail || {};
    const networkDetail = editProduct.network_detail || {};
    const graphicDetail = editProduct.graphic_detail || {};

    return {
      title: editProduct.title || "",
      description: editProduct.description || "",
      team_members: Array.isArray(editProduct.team_members)
        ? editProduct.team_members.join("\n")
        : editProduct.team_members || "",
      advisor_name: editProduct.advisor_name || editProduct.advisor || "",
      cate_id: editProduct.cate_id || editProduct.category?.cate_id || "",
      awards: editProduct.awards || "",
      github_link: editProduct.github_link || "",
      demo_link: editProduct.demo_link || "",
      video_url: editProduct.video_url || "",
      model_used: editProduct.model_used || aiDetail.model_used || "",
      framework:
        editProduct.framework || aiDetail.framework || itDetail.framework || "",
      language: editProduct.language || aiDetail.language || "",
      dataset_used: editProduct.dataset_used || aiDetail.dataset_used || "",
      accuracy_score:
        editProduct.accuracy_score || aiDetail.accuracy_score || "",
      programming_language:
        editProduct.programming_language || itDetail.programming_language || "",
      database_used: editProduct.database_used || itDetail.database_used || "",
      simulation_tool:
        editProduct.simulation_tool || networkDetail.simulation_tool || "",
      network_protocol:
        editProduct.network_protocol || networkDetail.network_protocol || "",
      topology_type:
        editProduct.topology_type || networkDetail.topology_type || "",
      config_file: editProduct.config_file || networkDetail.config_file || "",
      design_type: editProduct.design_type || graphicDetail.design_type || "",
      tools_used: editProduct.tools_used || graphicDetail.tools_used || "",
      color_palette: Array.isArray(graphicDetail.color_palette)
        ? graphicDetail.color_palette.join(",")
        : graphicDetail.color_palette || "",
      behance_link:
        editProduct.behance_link || graphicDetail.behance_link || "",
    };
  }, [editProduct, isEditMode]);

  const editImages = useMemo(() => {
    if (!isEditMode) return [];

    const thumbnailImage = editProduct.thumbnail
      ? [
          {
            id: `thumbnail-${editProduct.product_id}`,
            url: editProduct.thumbnail,
            name: "Ảnh đại diện hiện tại",
            size: "",
          },
        ]
      : [];

    const detailImages = Array.isArray(editProduct.images)
      ? editProduct.images.map((image, index) => ({
          id: image.product_image_id || `existing-${index}`,
          url: image.image_url,
          name: `Ảnh sản phẩm ${index + 1}`,
          size: "",
        }))
      : [];

    return [...thumbnailImage, ...detailImages].filter(
      (image, index, allImages) =>
        image.url &&
        allImages.findIndex((item) => item.url === image.url) === index,
    );
  }, [editProduct, isEditMode]);

  const form = useHook({
    editData,
    editImages,
    editProductId: isEditMode ? editProduct.product_id : null,
    editTags: Array.isArray(editProduct?.tags)
      ? editProduct.tags
          .map((tag) => (typeof tag === "string" ? tag : tag.tag_name))
          .filter(Boolean)
      : [],
  });

  const {
    formData,
    tags,
    tagInput,
    setTagInput,
    images,
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
    handleVideoUpload,
    removeImage,
    removeVideo,
    setAsThumbnail,
    videoFile,
    handleSubmit,

    setSelectedImage,
    setSubmitStatus,

    drafts,
    openViewDraft,
    setOpenViewDraft,
    handleSaveDraft,
    handleViewDraft,
    handleLoadDraft,
    handleDeleteDraft,
  } = form;

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
      {/* IMAGE PREVIEW */}
      {selectedImage && (
        <div
          onClick={() => setSelectedImage(null)}
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4"
        >
          <img
            src={selectedImage.url}
            alt={selectedImage.name}
            className="max-h-[90vh] max-w-full rounded-xl object-contain"
          />
        </div>
      )}

      {/* SUCCESS */}
      {submitStatus === "success" && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
          <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <h3 className="mb-3 text-center text-2xl font-bold text-green-600">
              Đăng thành công
            </h3>

            <p className="mb-5 text-center text-gray-600">
              Sản phẩm đã gửi tới giảng viên duyệt.
            </p>

            <button
              onClick={() => navigate("/sinh-vien")}
              className={`w-full rounded-xl bg-gradient-to-r ${gradient} px-4 py-3 font-semibold text-white`}
            >
              Xem sản phẩm của tôi
            </button>
          </div>
        </div>
      )}

      {/* ERROR */}
      {submitStatus === "error" && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
          <div className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
            <h3 className="mb-3 text-center text-2xl font-bold text-red-600">
              Upload thất bại
            </h3>

            <p className="mb-5 text-center text-gray-600">
              {statusApi?.message || "Có lỗi xảy ra"}
            </p>

            <button
              onClick={() => setSubmitStatus(null)}
              className="w-full rounded-xl bg-red-500 px-4 py-3 font-semibold text-white"
            >
              Đóng
            </button>
          </div>
        </div>
      )}

      <div className="mx-auto max-w-5xl px-4">
        <BackButton loading={loading} />

        {/* HEADER */}
        <div className="mb-8 text-center">
          <div
            className={`mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-r ${gradient}`}
          >
            <span className="text-4xl">{icon}</span>
          </div>

          <h1 className="text-4xl font-bold text-gray-900">
            {isEditMode ? "Chỉnh sửa sản phẩm" : title}
          </h1>
          <p className="mt-2 text-gray-600">
            {isEditMode ? "Cập nhật thông tin sản phẩm của bạn" : description}
          </p>
        </div>

        {/* STEP BAR */}
        <div className="mb-8 flex items-center justify-between gap-3">
          {steps.map((step, index) => {
            const isValid = isStepValid(step.id);
            const isTouched = touchedSteps[step.id];

            return (
              <React.Fragment key={step.id}>
                <div className="flex flex-col items-center">
                  <div
                    className={`flex h-12 w-12 items-center justify-center rounded-full text-lg font-bold
                    ${
                      currentStep === step.id
                        ? `bg-gradient-to-r ${gradient} text-white`
                        : isValid && isTouched
                          ? "bg-green-500 text-white"
                          : "bg-gray-200 text-gray-500"
                    }`}
                  >
                    {step.icon}
                  </div>

                  <span className="mt-2 text-sm">{step.name}</span>
                </div>

                {index < steps.length - 1 && (
                  <div
                    className={`h-1 flex-1 rounded ${
                      currentStep > step.id
                        ? `bg-gradient-to-r ${gradient}`
                        : "bg-gray-200"
                    }`}
                  />
                )}
              </React.Fragment>
            );
          })}
        </div>

        {/* STUDENT INFO */}
        <div
          className={`mb-8 rounded-2xl bg-gradient-to-r ${gradient} p-6 text-white`}
        >
          <div className="flex items-center justify-between">
            <div>
              <h2 className="text-xl font-bold">{currentStudent?.name}</h2>
              <p className="text-white/80">
                {currentStudent?.class} - {currentStudent?.major}
              </p>
            </div>

            <div className="text-right">
              <div className="text-3xl font-bold">
                {upload_loading ? "..." : upload_error ? 0 : upload_count}
              </div>
              <div className="text-sm text-white/70">sản phẩm đã đăng</div>
            </div>
          </div>
        </div>

        {/* FORM */}
        <div className="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
          <label className="mb-2 block text-sm font-semibold text-slate-700">
            Sinh viên cùng thực hiện{" "}
            <span className="font-normal text-slate-400">(có thể bỏ qua)</span>
          </label>
          <textarea
            name="team_members"
            value={formData.team_members || ""}
            onChange={handleChange}
            rows={3}
            maxLength={2000}
            className="w-full rounded-xl border-2 border-slate-200 px-4 py-3 outline-none transition focus:border-slate-400"
            placeholder={
              "Mỗi sinh viên một dòng, VD:\n23211TT0001 - Nguyễn Văn B"
            }
          />
          <p className="mt-2 text-xs text-slate-500">
            Người đăng sản phẩm được ghi nhận tự động là nhóm trưởng.
          </p>

          <label className="mb-2 mt-5 block text-sm font-semibold text-slate-700">
            Giảng viên hướng dẫn{" "}
            <span className="font-normal text-slate-400">(có thể bỏ qua)</span>
          </label>
          <input
            type="text"
            name="advisor_name"
            value={formData.advisor_name || ""}
            onChange={handleChange}
            maxLength={100}
            className="w-full rounded-xl border-2 border-slate-200 bg-white px-4 py-3 outline-none transition focus:border-slate-400"
            placeholder="VD: ThS. Nguyễn Văn A"
          />
        </div>

        <FormComponent
          formData={formData}
          handleChange={handleChange}
          handleSubmit={handleSubmit}
          errors={errors}
          currentStep={currentStep}
          handleSelectCategory={handleSelectCategory}
          handleImageUpload={handleImageUpload}
          handleVideoUpload={handleVideoUpload}
          images={images}
          videoFile={videoFile}
          thumbnailIndex={thumbnailIndex}
          removeImage={removeImage}
          removeVideo={removeVideo}
          setAsThumbnail={setAsThumbnail}
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
          drafts={drafts}
          openViewDraft={openViewDraft}
          setOpenViewDraft={setOpenViewDraft}
          handleSaveDraft={handleSaveDraft}
          handleViewDraft={handleViewDraft}
          handleLoadDraft={handleLoadDraft}
          handleDeleteDraft={handleDeleteDraft}
        />
      </div>
    </div>
  );
};

export default UploadProductScreen;
