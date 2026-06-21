import { useCallback, useState } from "react";
import { aiApi } from "../../api/";

export default function useCompareProduct(productId) {
  const [loadingCompare, setLoadingCompare] = useState(false);
  const [errorCompare, setErrorCompare] = useState("");
  const [result, setResult] = useState(null);

  const checkCompareProduct = useCallback(async () => {
    if (!productId) return null;

    setLoadingCompare(true);
    setErrorCompare("");

    try {
      const response = await aiApi.compareAiProduct(productId);
      setResult(response);
      return response;
    } catch (error) {
      const message =
        error.response?.data?.message || "Lỗi server khi so sánh sản phẩm";

      console.error(error);
      setErrorCompare(message);
      throw error;
    } finally {
      setLoadingCompare(false);
    }
  }, [productId]);

  return {
    result,
    loadingCompare,
    errorCompare,
    checkCompareProduct,
  };
}
