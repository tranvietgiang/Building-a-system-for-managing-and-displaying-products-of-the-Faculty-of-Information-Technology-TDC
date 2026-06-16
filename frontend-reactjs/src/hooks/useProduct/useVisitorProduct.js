import { useEffect, useState } from "react";
import { productApi } from "../../api";
import { toast } from "react-toastify";

const toProductList = (response) => {
  if (Array.isArray(response)) return response;
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response?.products)) return response.products;
  if (Array.isArray(response?.data?.data)) return response.data.data;
  if (Array.isArray(response?.data?.products)) return response.data.products;
  return [];
};

export default function useVisitorProduct() {
  const [productVisitor, setProductAll] = useState([]);
  const [loadingVisitor, setLoading] = useState(false);
  const [errorVisitor, setError] = useState(null);

  useEffect(() => {
    const toastId = "product-detail-toast-visitor-all";
    const getVisitorProducts = async () => {
      try {
        setLoading(true);
        const res = await productApi.getVisitorProducts();
        toast.success("Tải dữ liệu thành công", { toastId });
        setProductAll(toProductList(res));
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
  }, []);
  return { productVisitor, loadingVisitor, errorVisitor };
}
