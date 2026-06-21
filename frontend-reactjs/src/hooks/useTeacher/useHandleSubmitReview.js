import { useCallback } from "react";
import teacherApi from "../../api/teacher.api";

export const useHandleSubmitReview = (
  productId,
  reviewComment,
  toast,
  setIsSubmitting,
  setReviewComment,
  mutate,
) =>
  useCallback(async () => {
    const comment = reviewComment.trim();

    if (!comment) {
      toast.warning("Vui lòng nhập nhận xét!");
      return;
    }

    setIsSubmitting(true);
    try {
      const response = await teacherApi.submitReview(productId, comment);
      setReviewComment("");
      await mutate();
      toast.success(response?.message || "Đã gửi nhận xét thành công!");
    } catch (error) {
      console.error(error);
      toast.error(
        error.response?.data?.message ||
          "Có lỗi xảy ra, vui lòng thử lại!",
      );
    } finally {
      setIsSubmitting(false);
    }
  }, [
    productId,
    reviewComment,
    toast,
    setIsSubmitting,
    setReviewComment,
    mutate,
  ]);
