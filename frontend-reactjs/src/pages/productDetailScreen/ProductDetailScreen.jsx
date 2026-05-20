import { lazy, Suspense } from "react";
import { useLocation } from "react-router-dom";
import { detectMajorKey } from "../../utils/detectMajorKey";
import useProductDetail from "../../hooks/useProduct/useProductDetail";
import useMajorName from "../../hooks/common/useMajorName";
import { getMajorTheme } from "../../utils/uploadProductScreen/uploadRegistry";

const NotFoundPage = lazy(() => import("../notFoundScreen/NotFoundScreen"));
const AIDetailScreen = lazy(
  () => import("../../components/student-detail/AiDetail"),
);
const ITDetailScreen = lazy(
  () => import("../../components/student-detail/ItDetail"),
);
const NetworkDetailScreen = lazy(
  () => import("../../components/student-detail/NetworkDetail"),
);
const GraphicDetailScreen = lazy(
  () => import("../../components/student-detail/GraphicDetail"),
);

const DetailFallback = () => <p className="p-6">Đang tải...</p>;

export default function ProductDetailScreen() {
  const location = useLocation();
  const id = location.state?.productId;

  const { product, loading, error } = useProductDetail(id);
  const { majorName } = useMajorName(product?.major?.major_id);

  if (loading) return <DetailFallback />;
  if (error) return <p className="text-red-500">Có lỗi xảy ra</p>;
  if (!product) return <p>Sản phẩm không tồn tại</p>;
  if (!majorName) return <DetailFallback />;

  const theme = getMajorTheme(majorName);
  const props = { product, theme };

  const componentMap = {
    ai: <AIDetailScreen {...props} />,
    cntt: <ITDetailScreen {...props} />,
    mmt: <NetworkDetailScreen {...props} />,
    tkdh: <GraphicDetailScreen {...props} />,
  };

  const component = componentMap[detectMajorKey(majorName)] ?? <NotFoundPage />;

  return <Suspense fallback={<DetailFallback />}>{component}</Suspense>;
}
