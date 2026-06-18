import { useCallback, useState } from "react";
import { productApi } from "../../api";

const MAX_SEARCH_KEYWORD_LENGTH = 300;

const getErrorMessage = (err) =>
  err?.response?.data?.message ||
  err?.response?.data?.error ||
  err?.message ||
  "Không thể tìm kiếm lúc này.";

export default function useProductSearch({ visitor = false } = {}) {
  const [loadingProductSearch, setLoadingProductSearch] = useState(false);
  const [productSearchResult, setProductSearchResult] = useState(null);
  const [productSearchError, setProductSearchError] = useState("");

  const searchProducts = useCallback(
    async (params = {}) => {
      const keyword = String(params.q ?? params.keyword ?? "").trim();

      if (!keyword) {
        setProductSearchError("Vui lòng nhập nội dung tìm kiếm.");
        setProductSearchResult(null);
        return null;
      }

      if (keyword.length > MAX_SEARCH_KEYWORD_LENGTH) {
        setProductSearchError(
          `Noi dung tim kiem khong duoc vuot qua ${MAX_SEARCH_KEYWORD_LENGTH} ky tu.`,
        );
        setProductSearchResult(null);
        return null;
      }

      setLoadingProductSearch(true);
      setProductSearchError("");

      try {
        const res = visitor
          ? await productApi.searchVisitorProducts({ ...params, q: keyword })
          : await productApi.searchProducts({ ...params, q: keyword });

        setProductSearchResult(res);
        return res;
      } catch (err) {
        console.error("Lỗi khi tìm kiếm thường:", err);
        setProductSearchError(getErrorMessage(err));
        setProductSearchResult(null);
        return null;
      } finally {
        setLoadingProductSearch(false);
      }
    },
    [visitor],
  );

  const clearProductSearch = useCallback(() => {
    setProductSearchResult(null);
    setProductSearchError("");
  }, []);

  return {
    searchProducts,
    clearProductSearch,
    productSearchResult,
    productSearchError,
    loadingProductSearch,
  };
}
