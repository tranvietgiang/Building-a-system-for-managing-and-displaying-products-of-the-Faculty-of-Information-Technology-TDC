import { useEffect, useState } from "react";
import { productApi } from "../../api";
import { toast } from "react-toastify";
export default function useVisitorProduct(id) {
  const [productVisitorDetail, setProductAll] = useState(null);
  const [loadingVisitorDetail, setLoading] = useState(Boolean(id));
  const [errorVisitorDetail, setError] = useState(null);

  useEffect(() => {
    if (!id) return;

    const toastId = "product-detail-toast-visitor-detail";
    const getVisitorProducts = async () => {
      try {
        setLoading(true);
        const res = await productApi.getVisitorProductById(id);
        toast.success("Tải dữ chi tiết thành công", { toastId });
        setProductAll(res || null);
      } catch (error) {
        console.error(error);
        setError(error);
      } finally {
        setLoading(false);
      }
    };

    getVisitorProducts();

    // 👇 cleanup khi đổi route / unmount
    return () => {
      toast.dismiss(toastId);
    };
  }, [id]);
  return { productVisitorDetail, loadingVisitorDetail, errorVisitorDetail };
}
