import { useCallback, useEffect, useState } from "react";
import { productApi } from "../../api";
import { useNavigate } from "react-router-dom";
import { toast } from "react-toastify";

export default function useProductDetailTeacher(productId) {
  const [product, setProduct] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const navigate = useNavigate();

  const fetchProductDetail = useCallback(
    async (showSuccessToast = false) => {
      if (!productId) return null;

      try {
        if (showSuccessToast) setLoading(true);
        setError(null);
        const response = await productApi.getProductByIdTeacher(productId);

        setProduct(response);
        if (showSuccessToast) {
          toast.success("Tải dữ liệu chi tiết sản phẩm thành công", {
            toastId: "product-detail-toast",
          });
        }

        return response;
      } catch (err) {
        if (
          err.response?.status === 404 ||
          err.response?.data?.product_result === false
        ) {
          setProduct(null);
          setError(err.response?.data?.message || "Không tìm thấy sản phẩm");
          navigate("/not-found");
        } else {
          setError("Không tải được chi tiết sản phẩm");
        }

        throw err;
      } finally {
        if (showSuccessToast) setLoading(false);
      }
    },
    [productId, navigate],
  );

  useEffect(() => {
    fetchProductDetail(true).catch(() => {});

    return () => toast.dismiss("product-detail-toast");
  }, [fetchProductDetail]);

  const mutate = useCallback(
    () => fetchProductDetail(false),
    [fetchProductDetail],
  );

  return { product, loading, error, mutate };
}
