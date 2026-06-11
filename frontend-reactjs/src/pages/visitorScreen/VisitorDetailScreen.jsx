import React, { useMemo, lazy, Suspense } from "react";
import useVisitorProduct from "../../hooks/useProduct/useVisitorDetail";
import { detectMajorKey } from "../../utils/detectMajorKey";
import { useLocation, useParams } from "react-router-dom";
import useMajorName from "../../hooks/common/useMajorName";
import { getMajorTheme } from "../../utils/uploadProductScreen/uploadRegistry";
import NotFoundScreen from "../../pages/notFoundScreen/NotFoundScreen";

// Lazy load — chỉ tải màn hình được dùng
const AIDetailScreen = lazy(
  () => import("./MajorDetailForVisitor/AIDetailScreen"),
);
const ITDetailScreen = lazy(
  () => import("./MajorDetailForVisitor/ITDetailScreen"),
);
const NetworkDetailScreen = lazy(
  () => import("./MajorDetailForVisitor/NetworkDetailScreen"),
);
const GraphicDetailScreen = lazy(
  () => import("./MajorDetailForVisitor/GraphicDetailScreen"),
);

export default function VisitorDetailScreen() {
  const location = useLocation();
  const params = useParams();
  const id = params.id || location.state?.productId;

  const { productVisitorDetail, loadingVisitorDetail, errorVisitorDetail } =
    useVisitorProduct(id);

  const { majorName } = useMajorName(productVisitorDetail?.major_id);

  // useMemo: tránh tính lại theme khi majorName không đổi
  const theme = useMemo(() => getMajorTheme(majorName), [majorName]);

  // useMemo: tránh tính lại majorKey mỗi lần render
  const majorKey = useMemo(
    () =>
      detectMajorKey(
        productVisitorDetail?.major_code || productVisitorDetail?.major,
      ),
    [productVisitorDetail?.major_code, productVisitorDetail?.major],
  );

  // useMemo: object props ổn định, không tạo mới mỗi render
  const screenProps = useMemo(
    () => ({
      productVisitorDetail,
      loadingVisitorDetail,
      errorVisitorDetail,
      theme,
    }),
    [productVisitorDetail, loadingVisitorDetail, errorVisitorDetail, theme],
  );

  if (loadingVisitorDetail) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-50 to-gray-100">
        <div className="text-center">
          <div
            className={`w-16 h-16 border-4 border-${theme?.primary || "blue"}-600 border-t-transparent rounded-full animate-spin mx-auto mb-4`}
          ></div>
          <p className="text-gray-600 flex items-center gap-2">
            <svg
              className="animate-spin h-5 w-5 text-gray-600"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                className="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                strokeWidth="4"
              />
              <path
                className="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              />
            </svg>
            Đang tải dữ liệu...
          </p>
        </div>
      </div>
    );
  }

  if (errorVisitorDetail) {
    return (
      <div className="min-h-screen flex items-center justify-center text-red-500">
        Lỗi tải dữ liệu
      </div>
    );
  }

  if (!productVisitorDetail) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        Không có dữ liệu
      </div>
    );
  }

  const screenMap = {
    ai: <AIDetailScreen {...screenProps} />,
    cntt: <ITDetailScreen {...screenProps} />,
    mmt: <NetworkDetailScreen {...screenProps} />,
    tkdh: <GraphicDetailScreen {...screenProps} />,
  };

  return (
    <Suspense
      fallback={
        <div className="min-h-screen flex items-center justify-center">
          Đang tải màn hình...
        </div>
      }
    >
      {screenMap[majorKey] ?? <NotFoundScreen />}
    </Suspense>
  );
}
