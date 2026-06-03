import { useEffect, useState } from "react";
import { uploadApi } from "../../api";

export default function useUploadPublishedCount() {
  const [upload_count, setCount] = useState(0);
  const [upload_loading, setLoading] = useState(false);
  const [upload_error, setError] = useState(null);

  useEffect(() => {
    const fetchCount = async () => {
      setLoading(true);
      setError(null);

      try {
        const res = await uploadApi.countPublishedProducts();

        // backend bạn trả về: { success, data }
        setCount(res.data);
      } catch (err) {
        // console.error(err);

        if (
          err.response?.status === 404 ||
          err.response?.data.uploadCount_result === false
        ) {
          // console.log(err.response?.data.message);
          // console.log(err);
          setError(err.response?.data.message);
        } else {
          setError("Không tải được dữ liệu");
        }
      } finally {
        setLoading(false);
      }
    };

    fetchCount();
  }, []);

  return {
    upload_count,
    upload_loading,
    upload_error,
  };
}
