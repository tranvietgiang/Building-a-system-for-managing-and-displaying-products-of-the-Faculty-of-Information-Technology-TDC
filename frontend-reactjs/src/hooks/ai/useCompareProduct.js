import { useCallback, useState } from "react";
import { aiApi } from "../../api/";

export default function useCompareProduct(product_id) {
  const [loadingCompare, setLoadingCompare] = useState(false);
  const [errorCompare, setErrorCompare] = useState("");
  const [result, setResult] = useState(null);

  const checkCompareProduct = useCallback(async () => {
    if (!product_id) return null;

    setLoadingCompare(true);
    setErrorCompare("");

    try {
      const res = await aiApi.compareAiProduct(product_id);

      setResult(res);
      return res;
    } catch (err) {
      console.error(err);
      setErrorCompare("Lỗi server khi so sánh sản phẩm");
      return null;
    } finally {
      setLoadingCompare(false);
    }
  }, [product_id]);

  return {
    result,
    loadingCompare,
    errorCompare,
    checkCompareProduct,
  };
}
