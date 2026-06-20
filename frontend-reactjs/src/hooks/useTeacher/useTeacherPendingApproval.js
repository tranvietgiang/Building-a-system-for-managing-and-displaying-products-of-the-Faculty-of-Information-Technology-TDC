import { useEffect, useMemo, useState } from "react";
import { teacherApi } from "../../api";

export default function useTeacherPendingApproval(params = {}) {
  const [ProductsData, setTeacher] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const paramsKey = useMemo(() => JSON.stringify(params), [params]);

  useEffect(() => {
    const getTeacherData = async () => {
      setLoading(true);
      setError(null);

      try {
        const res = await teacherApi.getData(JSON.parse(paramsKey));
        const payload = res?.data?.data ?? res?.data ?? res;
        setTeacher(payload || null);
      } catch (err) {
        if (
          err.response?.status === 404 ||
          err.response?.data?.teacher_data_result === false
        ) {
          setTeacher(null);
          setError(err.response?.data?.message || "Không tìm thấy sản phẩm");
        } else {
          setError("Không tải được dữ liệu sản phẩm");
        }
        console.error(err);
      } finally {
        setLoading(false);
      }
    };

    getTeacherData();
  }, [paramsKey]);

  return {
    ProductsData,
    loading,
    error,
  };
}
