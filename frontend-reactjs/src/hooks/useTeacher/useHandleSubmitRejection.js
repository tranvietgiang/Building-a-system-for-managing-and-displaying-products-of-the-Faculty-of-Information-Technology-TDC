export const useHandleSubmitRejection = (
  feedback,
  setIsSubmitting,
  toast,
  setShowFeedbackModal,
  setFeedback,
  navigate,
  teacherReject,
) => {
  const submitRejection = async (id) => {
    if (!feedback.trim()) {
      toast.warning("Vui lòng nhập lý do từ chối!");
      return;
    }

    setIsSubmitting(true);

    try {
      const res = await teacherReject({
        product_id: id,
        feedback,
      });

      // console.log("hi", res);

      setShowFeedbackModal(false);
      setFeedback("");

      if (res?.result) {
        toast.success("Đã gửi phản hồi từ chối!", {
          autoClose: 1500,
          onClose: () => {
            navigate("/nckh-teacher");
          },
        });
      }
    } catch (error) {
      console.error(error);
      toast.error("❌ Có lỗi xảy ra, vui lòng thử lại!");
    } finally {
      setIsSubmitting(false);
    }
  };

  return submitRejection;
};
