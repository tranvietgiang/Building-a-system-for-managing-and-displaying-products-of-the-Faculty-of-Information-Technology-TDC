import { Routes, Route } from "react-router-dom";
import { lazy, Suspense } from "react";
import ProtectedRoute from "./protected.route";
import { ROLE } from "../utils/constants";
import NotFoundScreen from "../pages/notFoundScreen/NotFoundScreen";

/* ================= LAZY LOAD PAGES ================= */

const StudentScreen = lazy(() => import("../pages/student/StudentScreen"));
const TeacherScreen = lazy(() => import("../pages/teacher/TeacherScreen"));

const CompareTeacher = lazy(() => import("../pages/ai/CompareProductAi"));

const UploadProductScreen = lazy(
  () => import("../pages/uploadProductScreen/UploadProductScreen"),
);

const ProductDetailScreen = lazy(
  () => import("../pages/productDetailScreen/ProductDetailScreen"),
);

const TeacherProductDetailScreen = lazy(
  () => import("../pages/productDetailScreen/TeacherProductDetailScreen"),
);

const CompareProductAi = lazy(() => import("../pages/ai/CompareProductAi"));

/* ================= ROUTES ================= */
function RoleRoutes() {
  return (
    <Suspense
      fallback={
        <div className="h-screen flex items-center justify-center text-gray-500">
          Đang tải...
        </div>
      }
    >
      <Routes>
        {/* STUDENT */}
        <Route
          path="/nckh-student"
          element={
            <ProtectedRoute allowedRoles={[ROLE.STUDENT]}>
              <StudentScreen />
            </ProtectedRoute>
          }
        />

        {/* TEACHER */}
        <Route
          path="/nckh-teacher"
          element={
            <ProtectedRoute allowedRoles={[ROLE.TEACHER]}>
              <TeacherScreen />
            </ProtectedRoute>
          }
        />

        {/* UPLOAD */}
        <Route
          path="/upload"
          element={
            <ProtectedRoute allowedRoles={[ROLE.STUDENT]}>
              <UploadProductScreen />
            </ProtectedRoute>
          }
        />

        <Route
          path="/edit-product"
          element={
            <ProtectedRoute allowedRoles={[ROLE.STUDENT]}>
              <UploadProductScreen />
            </ProtectedRoute>
          }
        />

        {/* PRODUCT DETAIL - STUDENT */}
        <Route
          path="/detail"
          element={
            <ProtectedRoute allowedRoles={[ROLE.STUDENT]}>
              <ProductDetailScreen />
            </ProtectedRoute>
          }
        />

        {/* PRODUCT DETAIL - TEACHER */}
        <Route
          path="/detail-teacher"
          element={
            <ProtectedRoute allowedRoles={[ROLE.TEACHER]}>
              <TeacherProductDetailScreen />
            </ProtectedRoute>
          }
        />

        {/* COMPARE AI PRODUCTS */}
        <Route
          path="/nckh-compare"
          element={
            <ProtectedRoute allowedRoles={[ROLE.TEACHER]}>
              <CompareProductAi />
            </ProtectedRoute>
          }
        />

        {/* 404 */}
        <Route path="*" element={<NotFoundScreen />} />
      </Routes>
    </Suspense>
  );
}

export default RoleRoutes;
