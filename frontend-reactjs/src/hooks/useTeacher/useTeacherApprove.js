import { useState } from "react";
import { teacherApi } from "../../api";
import { toast } from "react-toastify";

export default function useTeacherApprove() {
  const [loading_approve, setLoading] = useState(false);
  const [error_approve, setError] = useState(null);

  const teacherApprove = async (productId) => {
    setLoading(true);
    setError(null);

    try {
      const res = await teacherApi.approve(productId);

      if (!res.result) {
        const errorObj = {
          result: false,
          message: res?.message,
        };

        toast.error(res?.message || "Có lỗi xảy ra");

        return errorObj;
      }

      return res;
    } catch (err) {
      const data = err?.response?.data;

      const errorObj = {
        result: false,
        message: data?.message,
      };

      toast.error(data?.message || "Không duyệt được sản phẩm");

      console.error(err);
      return errorObj;
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
