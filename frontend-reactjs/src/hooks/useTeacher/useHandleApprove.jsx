export const useHandleApprove = (
  confirmToast,
  setIsSubmitting,
  toast,
  navigate,
  teacherApprove,
) => {
  const handleApprove = (productId) => {
    confirmToast({
      message: "Bạn có chắc chắn muốn duyệt sản phẩm này?",
      onConfirm: async () => {
        setIsSubmitting(true);

        try {
          const result = await teacherApprove(productId);

          if (!result?.result) {
            return;
          }

          toast.success("Duyệt sản phẩm thành công", {
            autoClose: 1500,
            onClose: () => {
              navigate("/giang-vien");
            },
          });
        } catch (err) {
          toast.error("Có lỗi xảy ra, vui lòng thử lại!");
          console.error(err);
        } finally {
          setIsSubmitting(false);
        }
      },
    });
  };

  return {
    handleApprove,
  };
};
