import { useCallback, useState } from "react";
import { productApi } from "../../api";
import { toast } from "react-toastify";

export default function useDeleteProduct() {
  const [deleteLoading, setLoading] = useState(false);
  const [deleteStatus, setStatus] = useState(false);

  const deleteProduct = useCallback(async (productId) => {
    const toastId = "product-delete-toast-sv";

    try {
      setLoading(true);
      const res = await productApi.deleteProduct(productId);
      setStatus(Boolean(res?.deleted ?? res?.status));
      toast.success(res?.message || "Xóa sản phẩm thành công", { toastId });
      return true;
    } catch (error) {
      console.error(error);
      setStatus(false);
      toast.error(
        error?.response?.data?.message || "Không xóa được sản phẩm",
        { toastId },
      );
      return false;
    } finally {
      setLoading(false);
    }
  }, []);

  return { deleteLoading, deleteStatus, deleteProduct };
}
