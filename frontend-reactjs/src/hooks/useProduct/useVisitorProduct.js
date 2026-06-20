import { useEffect, useMemo, useState } from "react";
import { productApi } from "../../api";
import { toast } from "react-toastify";

const toProductList = (response) => {
  if (Array.isArray(response)) return response;
  if (Array.isArray(response?.products)) return response.products;
  if (Array.isArray(response?.data?.data)) return response.data.data;
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response?.data?.products)) return response.data.products;
  return [];
};

const toPagination = (response) => {
  const paginator = response?.data?.current_page ? response.data : response?.data;

  return {
    current_page: paginator?.current_page || 1,
    from: paginator?.from || 0,
    last_page: paginator?.last_page || 1,
    per_page: paginator?.per_page || 9,
    to: paginator?.to || 0,
    total: paginator?.total ?? response?.count ?? 0,
  };
};

export default function useVisitorProduct(params = {}) {
  const [productVisitor, setProductAll] = useState([]);
  const [paginationVisitor, setPaginationVisitor] = useState(null);
  const [visitorStats, setVisitorStats] = useState(null);
  const [loadingVisitor, setLoading] = useState(false);
  const [errorVisitor, setError] = useState(null);
  const paramsKey = useMemo(() => JSON.stringify(params), [params]);

  useEffect(() => {
    const toastId = "product-detail-toast-visitor-all";
    const getVisitorProducts = async () => {
      try {
        setLoading(true);
        setError(null);
        const res = await productApi.getVisitorProducts(JSON.parse(paramsKey));
        toast.success("Tải dữ liệu thành công", { toastId });
        setProductAll(toProductList(res));
        setPaginationVisitor(toPagination(res));
        setVisitorStats(res?.stats || null);
      } catch (error) {
        console.error(error);
        setError(error);
        setProductAll([]);
        setPaginationVisitor(null);
        setVisitorStats(null);
      } finally {
        setLoading(false);
      }
    };

    getVisitorProducts();

    return () => {
      toast.dismiss(toastId);
    };
  }, [paramsKey]);

  return {
    productVisitor,
    paginationVisitor,
    visitorStats,
    loadingVisitor,
    errorVisitor,
  };
}
