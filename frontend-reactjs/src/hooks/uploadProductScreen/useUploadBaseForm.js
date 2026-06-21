import { toast } from "react-toastify";
import { uploadApi } from "../useUpload/uploadApi.api";
import { AuthContext } from "../../contexts/AuthContext";
import useMajorNameCode from "../common/useMajorCode";
import { useState, useContext, useCallback } from "react";
import {
  getDrafts,
  restoreDraftImages,
  saveDrafts,
} from "../../utils/uploadProductScreen/draftStorage";

export default function useUploadBaseForm({
  initialData,
  editData,
  editImages,
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
  const [tags, setTags] = useState(editTags || []);
  const [tagInput, setTagInput] = useState("");
  const [errors, setErrors] = useState({});
  const [currentStep, setCurrentStep] = useState(1);
  const [thumbnailIndex, setThumbnailIndex] = useState(0);
  const [loading, setLoading] = useState(false);
  const [submitStatus, setSubmitStatus] = useState(null);
  const [drafts, setDrafts] = useState([]);
  const [loadedDraftId, setLoadedDraftId] = useState(null);
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
  const studentId = user?.user_id;
  const scopedDraftKey = studentId
    ? `${draftKey}:student:${studentId}`
    : null;

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
    });

    return Object.keys(err).length === 0;
  };

  const isAllStepsCompleted = () => {
    return isStepValid(1) && isStepValid(2) && isStepValid(3);
  };

  const validateAllSteps = () => {
    return {
      ...validateStep({ step: 1, formData, images }),
      ...validateStep({ step: 2, formData, images }),
      ...validateStep({ step: 3, formData, images }),
    };
  };

  /* ================= STEP ================= */
  const handleNextStep = useCallback(() => {
    const err = validateStep({
      step: currentStep,
      formData,
      images,
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
  }, [currentStep, formData, images]);

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
    setImages((prev) => {
      const removedIndex = prev.findIndex((image) => image.id === id);
      const nextImages = prev.filter((image) => image.id !== id);

      setThumbnailIndex((currentIndex) => {
        if (nextImages.length === 0) return 0;
        if (removedIndex === -1) return Math.min(currentIndex, nextImages.length - 1);
        if (removedIndex === currentIndex) return 0;
        if (removedIndex < currentIndex) return currentIndex - 1;
        return Math.min(currentIndex, nextImages.length - 1);
      });

      return nextImages;
    });
  };

  const setAsThumbnail = (index) => {
    setThumbnailIndex(index);
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
  const handleSaveDraft = async () => {
    if (!scopedDraftKey) {
      toast.error("Không xác định được sinh viên để lưu bản nháp");
      return;
    }

    const draft = {
      id: Date.now(),
      studentId,
      formData,
      images,
      tags: [...tags],
      currentStep,
      thumbnailIndex,
      createdAt: new Date().toISOString(),
    };

    try {
      const old = await getDrafts(scopedDraftKey, draftKey);
      await saveDrafts(scopedDraftKey, [draft, ...old]);
      toast.success("Đã lưu nháp");
    } catch (error) {
      console.error("Save draft error:", error);
      toast.error("Không thể lưu bản nháp. Vui lòng kiểm tra dung lượng trình duyệt");
    }
  };

  const handleViewDraft = async () => {
    if (!scopedDraftKey) {
      toast.error("Không xác định được sinh viên để mở bản nháp");
      return;
    }

    try {
      const old = await getDrafts(scopedDraftKey, draftKey);
      setDrafts(old);
      setOpenViewDraft(true);
    } catch (error) {
      console.error("View drafts error:", error);
      toast.error("Không thể mở danh sách bản nháp");
    }
  };

  const handleLoadDraft = (draft) => {
    setLoadedDraftId(draft.id ?? null);
    setFormData({ ...(initialData || {}), ...(draft.formData || {}) });
    setImages(restoreDraftImages(draft.images));
    setTags(draft.tags || []);
    setCurrentStep(Math.min(3, Math.max(1, draft.currentStep || 1)));
    setThumbnailIndex(
      Math.min(
        Math.max(0, draft.thumbnailIndex || 0),
        Math.max(0, (draft.images?.length || 1) - 1),
      ),
    );
    setErrors({});
    setOpenViewDraft(false);

    toast.success("Đã tải bản nháp");
  };

  const handleDeleteDraft = async (id) => {
    if (!scopedDraftKey) return;

    try {
      const old = await getDrafts(scopedDraftKey, draftKey);
      const next = old.filter((x) => x.id !== id);

      await saveDrafts(scopedDraftKey, next);
      setDrafts(next);
      setLoadedDraftId((currentId) => (currentId === id ? null : currentId));
      toast.success("Đã xóa bản nháp");
    } catch (error) {
      console.error("Delete draft error:", error);
      toast.error("Không thể xóa bản nháp");
    }
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
            payload.append(
              "image_meta[]",
              JSON.stringify({
                name: img.name,
                is_thumbnail: index === thumbnailIndex,
              }),
            );
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

        if (loadedDraftId !== null && scopedDraftKey) {
          try {
            const old = await getDrafts(scopedDraftKey, draftKey);
            const next = old.filter((draft) => draft.id !== loadedDraftId);

            await saveDrafts(scopedDraftKey, next);
            setDrafts(next);
            setLoadedDraftId(null);
          } catch (draftError) {
            console.error("Delete published draft error:", draftError);
            toast.warning(
              "Sản phẩm đã đăng nhưng chưa thể xóa bản nháp khỏi trình duyệt",
            );
          }
        }

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
    [
      majorCode,
      formData,
      tags,
      images,
      thumbnailIndex,
      user,
      loadedDraftId,
      scopedDraftKey,
      draftKey,
    ],
  );

  return {
    formData,
    errors,
    images,
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
