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
import { getInvalidCharacterMessage } from "../../utils/sanitizeInput";

const FIELD_LABELS = {
  title: "Tên sản phẩm",
  description: "Mô tả",
  team_members: "Sinh viên cùng thực hiện",
  advisor_name: "Giảng viên hướng dẫn",
  awards: "Giải thưởng",
  custom_category_name: "Danh mục khác",
  programming_language: "Ngôn ngữ lập trình",
  database_used: "Cơ sở dữ liệu",
  model_used: "Mô hình hoặc thuật toán",
  language: "Ngôn ngữ",
  dataset_used: "Dataset",
  topology_type: "Kiểu kết nối mạng",
  simulation_tool: "Công cụ mô phỏng",
  network_protocol: "Giao thức mạng",
  design_type: "Loại ấn phẩm",
  tools_used: "Công cụ thiết kế",
};

const MAX_VIDEO_SIZE_MB = 50;
const ALLOWED_VIDEO_TYPES = [
  "video/mp4",
  "video/quicktime",
  "video/webm",
  "video/x-msvideo",
  "video/x-matroska",
];

const withoutVideoUrl = (data = {}) => {
  const rest = { ...(data || {}) };
  delete rest.video_url;
  return rest;
};

export default function useUploadBaseForm({
  initialData,
  editData,
  editImages,
  editProductId,
  editTags,
  validateStep,
  draftKey,
  stepsConfig,
} = {}) {
  const [formData, setFormData] = useState(() => ({
    ...withoutVideoUrl(initialData),
    ...withoutVideoUrl(editData),
  }));
  const [images, setImages] = useState(editImages || []);
  const [videoFile, setVideoFile] = useState(() =>
    editData?.video_url
      ? {
          id: "existing-video",
          url: editData.video_url,
          name: "Video hiện tại",
          size: "",
          existing: true,
        }
      : null,
  );
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

  const getCharacterErrors = useCallback(() => {
    const nextErrors = {};

    Object.entries(formData).forEach(([name, value]) => {
      const message = getInvalidCharacterMessage(name, value, {
        label: FIELD_LABELS[name] || name,
        multiline: name === "description" || name === "awards",
      });

      if (message) nextErrors[name] = message;
    });

    const hasInvalidTag = tags.some((tag) =>
      getInvalidCharacterMessage("tag", tag, { label: "Tag" }),
    );

    if (hasInvalidTag) {
      nextErrors.tags = "Tag không được chứa ký tự đặc biệt.";
    }

    return nextErrors;
  }, [formData, tags]);

  /* ================= FORM ================= */
  const handleChange = useCallback(
    (e) => {
      const { name, value } = e.target;

      setFormData((prev) => ({
        ...prev,
        [name]: value,
        ...(name === "custom_category_name" && value.trim()
          ? { cate_id: "" }
          : {}),
      }));

      if (errors[name]) {
        setErrors((prev) => ({
          ...prev,
          [name]: null,
          ...(name === "custom_category_name" ? { cate_id: null } : {}),
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
        custom_category_name: "",
      }));

      if (errors.cate_id) {
        setErrors((prev) => ({
          ...prev,
          cate_id: null,
          custom_category_name: null,
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

  /* ================= VIDEO ================= */
  const handleVideoUpload = useCallback(
    (e) => {
      const file = e.target.files?.[0];

      if (!file) return;

      const sizeMb = file.size / 1024 / 1024;
      const extension = file.name.split(".").pop()?.toLowerCase();
      const allowedExtensions = ["mp4", "mov", "avi", "webm", "mkv"];

      if (
        !ALLOWED_VIDEO_TYPES.includes(file.type) &&
        !allowedExtensions.includes(extension)
      ) {
        const message = "Video chỉ hỗ trợ MP4, MOV, AVI, WEBM hoặc MKV";
        setErrors((prev) => ({ ...prev, video: message }));
        toast.error(message);
        e.target.value = "";
        return;
      }

      if (sizeMb > MAX_VIDEO_SIZE_MB) {
        const message = `Video không được vượt quá ${MAX_VIDEO_SIZE_MB} MB`;
        setErrors((prev) => ({ ...prev, video: message }));
        toast.error(message);
        e.target.value = "";
        return;
      }

      setVideoFile((prev) => {
        if (prev?.file && prev.url) {
          URL.revokeObjectURL(prev.url);
        }

        return {
          id: Date.now(),
          file,
          url: URL.createObjectURL(file),
          name: file.name,
          size: sizeMb.toFixed(2),
        };
      });

      setErrors((prev) => ({
        ...prev,
        video: null,
      }));
      e.target.value = "";
    },
    [],
  );

  const removeVideo = useCallback(() => {
    setVideoFile((prev) => {
      if (prev?.file && prev.url) {
        URL.revokeObjectURL(prev.url);
      }

      return null;
    });
    setErrors((prev) => ({ ...prev, video: null }));
  }, []);

  /* ================= TAG ================= */
  const handleAddTag = useCallback(
    (e) => {
      if (e.key === "Enter" && tagInput.trim()) {
        e.preventDefault();

        const newTag = tagInput.trim();
        const tagError = getInvalidCharacterMessage("tag", newTag, {
          label: "Tag",
        });

        if (tagError) {
          toast.error(tagError, { autoClose: 2000 });
          return;
        }

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
      videoFile: videoFile && !videoFile.file ? videoFile : null,
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
    setFormData({
      ...withoutVideoUrl(initialData),
      ...withoutVideoUrl(draft.formData),
    });
    setImages(restoreDraftImages(draft.images));
    setVideoFile(draft.videoFile || null);
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

      const allErrors = {
        ...validateAllSteps(),
        ...getCharacterErrors(),
      };

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

        if (editProductId) {
          payload.append("replace_product_id", editProductId);
        }

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
          } else if (img.url) {
            payload.append("existing_images[]", img.url);

            if (index === thumbnailIndex) {
              payload.append("existing_thumbnail_url", img.url);
            }
          }
        });

        if (videoFile?.file) {
          payload.append("video", videoFile.file);
        } else if (videoFile?.url) {
          payload.append("existing_video_url", videoFile.url);
        }

        const res = await uploadApi.uploadProduct(payload);

        if (!res.success) {
          if (res.image_index !== null && res.image_index !== undefined) {
            setErrors((prev) => ({
              ...prev,
              images: res.message || "Ảnh sản phẩm không hợp lệ",
            }));
          }

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
      videoFile,
      thumbnailIndex,
      user,
      loadedDraftId,
      scopedDraftKey,
      draftKey,
      editProductId,
      getCharacterErrors,
    ],
  );

  return {
    formData,
    errors,
    images,
    videoFile,
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
    handleVideoUpload,
    removeImage,
    removeVideo,
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
