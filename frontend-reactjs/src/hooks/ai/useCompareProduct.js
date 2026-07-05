import { useCallback, useState } from "react";
import { aiApi } from "../../api/";

export default function useCompareProduct(productId) {
  const [loadingCompare, setLoadingCompare] = useState(false);
  const [loadingImageCompare, setLoadingImageCompare] = useState(false);

  const [errorCompare, setErrorCompare] = useState("");
  const [errorImageCompare, setErrorImageCompare] = useState("");

  const [result, setResult] = useState(null);
  const [imageResult, setImageResult] = useState(null);

  const checkCompareProduct = useCallback(async () => {
    if (!productId) return null;

    setLoadingCompare(true);
    setErrorCompare("");

    try {
      const response = await aiApi.compareAiProduct(productId);

      const data = response.data ?? response;
      setResult(data);

      return data;
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

  const checkCompareProductImages = useCallback(
    async (matchProductId, textSimilarity = 0) => {
      if (!productId || !matchProductId) return null;

      setLoadingImageCompare(true);
      setErrorImageCompare("");

      try {
        const response = await aiApi.compareProductImages(
          productId,
          matchProductId,
          textSimilarity,
        );

        const data = response.data ?? response;

        if (data.success === false) {
          throw new Error(data.message || "Không thể kiểm tra hình ảnh.");
        }

        setImageResult(data);
        return data;
      } catch (error) {
        const message =
          error.response?.data?.message ||
          error.message ||
          "Không thể kiểm tra hình ảnh.";

        console.error(error);
        setErrorImageCompare(message);
        throw error;
      } finally {
        setLoadingImageCompare(false);
      }
    },
    [productId],
  );

  return {
    result,
    imageResult,

    loadingCompare,
    loadingImageCompare,

    errorCompare,
    errorImageCompare,

    checkCompareProduct,
    checkCompareProductImages,
  };
}
