import { useState } from "react";
import { ApprovalErrorModal } from "../../components/common/ApprovalErrorModal";

export const useHandleApprove = (
  confirmToast,
  setIsSubmitting,
  toast,
  navigate,
  teacherApprove,
) => {
  const [showErrorModal, setShowErrorModal] = useState(false);
  const [approvalError, setApprovalError] = useState(null);
  const [pendingProductId, setPendingProductId] = useState(null);
  const [pendingPayload, setPendingPayload] = useState({});
  const [isForceApproving, setIsForceApproving] = useState(false);
  const [productTitle, setProductTitle] = useState("");

  const handleContinueApprove = async () => {
    if (!pendingProductId) return;

    setIsForceApproving(true);
    try {
      // Add force_approve flag to bypass AI moderation
      const result = await teacherApprove(pendingProductId, {
        ...pendingPayload,
        force_approve: true,
      });

      if (result?.result) {
        setShowErrorModal(false);
        setApprovalError(null);
        toast.success("Duyệt sản phẩm thành công", {
          autoClose: 1500,
          onClose: () => {
            navigate("/nckh-teacher");
          },
        });
      } else {
        // Show new error if override also fails
        setApprovalError(result);
      }
    } catch (err) {
      toast.error("Có lỗi xảy ra, vui lòng thử lại!");
      console.error(err);
    } finally {
      setIsForceApproving(false);
    }
  };

  const handleApprove = (productId, title, moderationPayload = {}) => {
    confirmToast({
      message: "Bạn có chắc chắn muốn duyệt sản phẩm này?",
      onConfirm: async () => {
        setIsSubmitting(true);

        try {
          const result = await teacherApprove(productId, moderationPayload);

          // Check if AI blocked the approval
          if (result?.blocked_by_ai) {
            setShowErrorModal(true);
            setApprovalError(result);
            setPendingProductId(productId);
            setPendingPayload(moderationPayload);
            setProductTitle(title);
            return;
          }

          if (!result?.result) {
            // Non-AI errors are already toasted by useTeacherApprove
            return;
          }

          toast.success("Duyệt sản phẩm thành công", {
            autoClose: 1500,
            onClose: () => {
              navigate("/nckh-teacher");
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
    errorModalComponent: (
      <ApprovalErrorModal
        isOpen={showErrorModal}
        reason={approvalError?.reason}
        violations={approvalError?.violations}
        productTitle={productTitle}
        onClose={() => {
          setShowErrorModal(false);
          setApprovalError(null);
          setPendingProductId(null);
        }}
        onContinueApprove={handleContinueApprove}
        isLoading={isForceApproving}
      />
    ),
  };
};
