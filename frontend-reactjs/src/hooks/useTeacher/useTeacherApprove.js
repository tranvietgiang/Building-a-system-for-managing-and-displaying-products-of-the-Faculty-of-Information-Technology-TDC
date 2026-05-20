import { useState } from "react";
import { teacherApi } from "../../api";
import { toast } from "react-toastify";

export default function useTeacherApprove() {
  const [loading_approve, setLoading] = useState(false);
  const [error_approve, setError] = useState(null);

  const teacherApprove = async (productId, moderationPayload = {}) => {
    setLoading(true);
    setError(null);

    try {
      const res = await teacherApi.approve(productId, moderationPayload);

      if (!res.result) {
        const errorObj = {
          result: false,
          blocked_by_ai: res?.blocked_by_ai || false,
          reason: res?.reason,
          message: res?.message,
          violations: res?.violations || [],
          moderation: res?.moderation,
        };

        if (!res?.blocked_by_ai) {
          toast.error(res?.reason || res?.message || "Có lỗi xảy ra");
        }

        return errorObj;
      }

      return res;
    } catch (err) {
      const data = err?.response?.data;

      // ✅ 422 = AI blocked
      const isAiBlocked =
        err?.response?.status === 422 || data?.blocked_by_ai === true;

      const errorObj = {
        result: false,
        blocked_by_ai: isAiBlocked,
        reason: data?.reason,
        message: data?.message,
        violations: data?.violations || [],
        moderation: data?.moderation,
      };

      // ✅ Chỉ toast nếu KHÔNG phải AI blocked (AI blocked sẽ show modal)
      if (!isAiBlocked) {
        toast.error(
          data?.reason || data?.message || "Không duyệt được sản phẩm",
        );
      }

      console.error(err);
      return errorObj; // ✅ return thay vì throw để useHandleApprove nhận được
    } finally {
      setLoading(false);
    }
  };

  return {
    loading_approve,
    error_approve,
    teacherApprove,
  };
}
