import { useNavigate } from "react-router-dom";

export function useViewDetail(url) {
  const navigate = useNavigate(); // 1. Lấy hàm navigate từ React Router
  return (id) => {
    const baseUrl = url.startsWith("/") ? url : `/${url}`;
    navigate(`${baseUrl}/${id}`, { state: { productId: id } });
  };
}
