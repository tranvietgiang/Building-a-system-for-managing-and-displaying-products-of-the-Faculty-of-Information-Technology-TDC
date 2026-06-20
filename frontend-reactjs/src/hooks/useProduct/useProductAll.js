import { useEffect, useMemo, useState } from "react";
import { productApi } from "../../api";
import { toast } from "react-toastify";

export default function useProductAll(params = {}) {
  const [products, setProductAll] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const paramsKey = useMemo(() => JSON.stringify(params), [params]);

  useEffect(() => {
    const toastId = "product-detail-toast-sv-all";
    const getProductAll = async () => {
      try {
        setLoading(true);
        setError(null);
        const res = await productApi.getProductAll(JSON.parse(paramsKey));
        toast.success("Tải dữ liệu thành công", { toastId });
        setProductAll(res || null);
      } catch (error) {
        console.error(error);
        setError(error);
      } finally {
        setLoading(false);
      }
    };

    getProductAll();

    return () => {
      toast.dismiss(toastId);
    };
  }, [paramsKey]);

  return { products, loading, error };
}
