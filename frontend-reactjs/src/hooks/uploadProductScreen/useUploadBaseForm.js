import { toast } from "react-toastify";
import { uploadApi } from "../useUpload/uploadApi.api";
import { AuthContext } from "../../contexts/AuthContext";
import useMajorNameCode from "../common/useMajorCode";
import { useState, useContext, useCallback } from "react";

export default function useUploadBaseForm({
  initialData,
  editData,
  editImages,
  editFiles,
  editTags,
  validateStep,
  draftKey,
  stepsConfig,
} = {}) {
  const [formData, setFormData] = useState(() => ({
    ...(initialData || {}),
    ...(editData || {}),
  }));
  const [images, setImages] = useState(editImages || []);
  const [files, setFiles] = useState(editFiles || []);
  const [tags, setTags] = useState(editTags || []);
  const [tagInput, setTagInput] = useState("");
  const [errors, setErrors] = useState({});
  const [currentStep, setCurrentStep] = useState(1);
  const [thumbnailIndex, setThumbnailIndex] = useState(0);
  const [loading, setLoading] = useState(false);
  const [submitStatus, setSubmitStatus] = useState(null);
  const [drafts, setDrafts] = useState([]);
  const [openViewDraft, setOpenViewDraft] = useState(false);
  const [selectedImage, setSelectedImage] = useState(null);
  const [statusApi, setStatusApi] = useState(null);
  const [touchedSteps, setTouchedSteps] = useState({});

  const steps = stepsConfig || [
    { id: 1, name: "Thông tin sản phẩm", icon: "📋" },
    { id: 2, name: "Media", icon: "🖼️" },
    { id: 3, name: "Hoàn tất", icon: "✅" },
  ];
  const { user } = useContext(AuthContext);

  const { majorCode } = useMajorNameCode(user?.major_id);

  const showErrors = (errObj) => {
    Object.values(errObj).forEach((msg) => {
      if (msg) {
        toast.error(msg, { autoClose: 2000 });
      }
    });
  };

  /* ================= FORM ================= */
  const handleChange = useCallback(
    (e) => {
      const { name, value } = e.target;

      setFormData((prev) => ({
        ...prev,
        [name]: value,
      }));

      if (errors[name]) {
        setErrors((prev) => ({
          ...prev,
          [name]: null,
        }));
      }
    },
    [errors],
  );

  const handleSelectCategory = useCallback(
    (id) => {
      setFormData((prev) => ({
        ...prev,
        cate_id: id,
      }));

      if (errors.cate_id) {
        setErrors((prev) => ({
          ...prev,
          cate_id: null,
        }));
      }
    },
    [errors],
  );

  /* ================= VALIDATE ================= */
  const isStepValid = (step) => {
    const err = validateStep({
      step,
      formData,
      images,
      files,
    });

    return Object.keys(err).length === 0;
  };

  const isAllStepsCompleted = () => {
    return isStepValid(1) && isStepValid(2) && isStepValid(3);
  };

  const validateAllSteps = () => {
    return {
      ...validateStep({ step: 1, formData, images, files }),
      ...validateStep({ step: 2, formData, images, files }),
      ...validateStep({ step: 3, formData, images, files }),
    };
  };

  /* ================= STEP ================= */
  const handleNextStep = useCallback(() => {
    const err = validateStep({
      step: currentStep,
      formData,
      images,
      files,
    });

    if (Object.keys(err).length > 0) {
      setErrors(err);

      setTouchedSteps((prev) => ({
        ...prev,
        [currentStep]: true,
      }));

      showErrors(err);
      return;
    }

    setErrors({});

    setTouchedSteps((prev) => ({
      ...prev,
      [currentStep]: true,
    }));

    setCurrentStep((prev) => prev + 1);
  }, [currentStep, formData, images, files]);

  const handlePrevStep = useCallback(() => {
    setCurrentStep((prev) => Math.max(1, prev - 1));
  }, []);

  /* ================= IMAGE ================= */
  const handleImageUpload = useCallback(
    (e) => {
      const arr = Array.from(e.target.files || []);

      if (images.length + arr.length > 10) {
        toast.error("Chỉ được tải tối đa 10 ảnh");
        return;
      }

      const mapped = arr.map((file, index) => ({
        id: Date.now() + index,
        file,
        url: URL.createObjectURL(file),
        name: file.name,
        size: (file.size / 1024 / 1024).toFixed(2),
      }));

      setImages((prev) => [...prev, ...mapped]);

      if (errors.images) {
        setErrors((prev) => ({
          ...prev,
          images: null,
        }));
      }
    },
    [images, errors],
  );

  const removeImage = (id) => {
    setImages((prev) => prev.filter((x) => x.id !== id));
  };

  const setAsThumbnail = (index) => {
    setThumbnailIndex(index);
  };

  /* ================= FILE ================= */
  const handleFileUpload = useCallback(
    (e) => {
      const arr = Array.from(e.target.files || []);

      if (files.length + arr.length > 5) {
        toast.error("Chỉ được tải tối đa 5 file");
        return;
      }

      const mapped = arr.map((file, index) => ({
        id: Date.now() + index,
        file,
        name: file.name,
        size: (file.size / 1024 / 1024).toFixed(2),
        type: file.name.split(".").pop()?.toUpperCase() || "FILE",
      }));

      setFiles((prev) => [...prev, ...mapped]);

      if (errors.files) {
        setErrors((prev) => ({
          ...prev,
          files: null,
        }));
      }
    },
    [files, errors],
  );

  const removeFile = (id) => {
    setFiles((prev) => prev.filter((x) => x.id !== id));
  };

  /* ================= TAG ================= */
  const handleAddTag = useCallback(
    (e) => {
      if (e.key === "Enter" && tagInput.trim()) {
        e.preventDefault();

        const newTag = tagInput.trim();

        if (!tags.includes(newTag)) {
          setTags((prev) => [...prev, newTag]);
        }

        setTagInput("");
      }
    },
    [tagInput, tags],
  );

  const removeTag = (tag) => {
    setTags((prev) => prev.filter((x) => x !== tag));
  };

  /* ================= DRAFT ================= */
  const handleSaveDraft = () => {
    const draft = {
      id: Date.now(),
      formData,
      images,
      files,
      tags,
      currentStep,
      createdAt: new Date().toISOString(),
    };

    const old = JSON.parse(localStorage.getItem(draftKey)) || [];

    localStorage.setItem(draftKey, JSON.stringify([draft, ...old]));
    toast.success("Đã lưu nháp");
  };

  const handleViewDraft = () => {
    const old = JSON.parse(localStorage.getItem(draftKey)) || [];
    setDrafts(old);
    setOpenViewDraft(true);
  };

  const handleLoadDraft = (draft) => {
    setFormData(draft.formData || initialData);
    setImages(draft.images || []);
    setFiles(draft.files || []);
    setTags(draft.tags || []);
    setCurrentStep(draft.currentStep || 1);
    setOpenViewDraft(false);

    toast.success("Đã tải bản nháp");
  };

  const handleDeleteDraft = (id) => {
    const old = JSON.parse(localStorage.getItem(draftKey)) || [];
    const next = old.filter((x) => x.id !== id);

    localStorage.setItem(draftKey, JSON.stringify(next));
    setDrafts(next);

    toast.success("Đã xóa bản nháp");
  };

  /* ================= SUBMIT ================= */
  const handleSubmit = useCallback(
    async (e) => {
      e.preventDefault();

      if (!majorCode) {
        toast.error("đang tải ngành!");
        return;
      }

      const allErrors = validateAllSteps();

      if (Object.keys(allErrors).length > 0) {
        setErrors(allErrors);
        showErrors(allErrors);
        return;
      }

      setLoading(true);
      setSubmitStatus(null);

      try {
        const payload = new FormData();

        Object.keys(formData).forEach((key) => {
          payload.append(key, formData[key] || "");
        });

        payload.append("major_id", user?.major_id || "");
        payload.append("major_code", majorCode || "");

        tags.forEach((tag) => payload.append("tags[]", tag));

        images.forEach((img, index) => {
          if (img.file) {
            payload.append("images[]", img.file);
          }

          payload.append(
            "image_meta[]",
            JSON.stringify({
              name: img.name,
              is_thumbnail: index === thumbnailIndex,
            }),
          );
        });

        files.forEach((file) => {
          if (file.file) {
            payload.append("files[]", file.file);
          }
        });

        const res = await uploadApi.uploadProduct(payload);

        if (!res.success) {
          setSubmitStatus("error");
          setStatusApi(res);
          toast.error(res.message || "Upload thất bại");
          return;
        }

        setSubmitStatus("success");
        setStatusApi(res);
        toast.success(res.message || "Đăng thành công");
      } catch (error) {
        setSubmitStatus("error");
        setStatusApi(error?.response?.data || error);
        toast.error(
          error?.response?.data?.message || "Có lỗi xảy ra khi upload",
        );
      } finally {
        setLoading(false);
      }
    },
    [majorCode, formData, tags, images, files, thumbnailIndex, user],
  );

  return {
    formData,
    errors,
    images,
    files,
    tags,
    tagInput,
    currentStep,
    loading,
    submitStatus,
    drafts,
    openViewDraft,
    thumbnailIndex,
    selectedImage,
    touchedSteps,
    steps,
    statusApi,

    setTagInput,
    setOpenViewDraft,
    setThumbnailIndex,
    setSelectedImage,
    setSubmitStatus,

    handleChange,
    handleSelectCategory,
    handleNextStep,
    handlePrevStep,
    handleImageUpload,
    removeImage,
    setAsThumbnail,
    handleFileUpload,
    removeFile,
    handleAddTag,
    removeTag,
    handleSaveDraft,
    handleViewDraft,
    handleLoadDraft,
    handleDeleteDraft,
    handleSubmit,

    isStepValid,
    isAllStepsCompleted,
  };
}
