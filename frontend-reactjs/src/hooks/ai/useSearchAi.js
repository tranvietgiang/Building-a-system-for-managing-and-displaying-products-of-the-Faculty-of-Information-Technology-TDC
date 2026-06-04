import { useCallback, useState } from "react";
import { aiApi } from "../../api";

const getErrorMessage = (err) =>
  err?.response?.data?.message ||
  err?.response?.data?.error ||
  err?.message ||
  "Không thể tìm kiếm lúc này.";

export default function useSearchAi() {
  const [loadingSearchAi, setLoadingSearchAi] = useState(false);
  const [searchResult, setSearchResult] = useState(null);
  const [searchError, setSearchError] = useState("");

  const searchAi = useCallback(async (message) => {
    const keyword = String(message || "").trim();

    if (!keyword) {
      setSearchError("Vui lòng nhập nội dung tìm kiếm.");
      setSearchResult(null);
      return null;
    }

    setLoadingSearchAi(true);
    setSearchError("");

    try {
      const res = await aiApi.searchProducts(keyword);
      setSearchResult(res);
      return res;
    } catch (err) {
      console.error("Lỗi khi tìm kiếm bằng AI:", err);
      setSearchError(getErrorMessage(err));
      setSearchResult(null);
      return null;
    } finally {
      setLoadingSearchAi(false);
    }
  }, []);

  const clearSearch = useCallback(() => {
    setSearchResult(null);
    setSearchError("");
  }, []);

  return {
    searchAi,
    clearSearch,
    searchResult,
    searchError,
    loadingSearchAi,
  };
}
