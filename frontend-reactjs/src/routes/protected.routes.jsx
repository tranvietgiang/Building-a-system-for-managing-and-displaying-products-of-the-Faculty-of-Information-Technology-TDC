import { Navigate, Routes, Route } from "react-router-dom";
import { lazy, Suspense } from "react";
import ProtectedRoute from "./protected.route";
import { ROLE } from "../utils/constants";
import NotFoundScreen from "../pages/notFoundScreen/NotFoundScreen";
import { ROUTES } from "../utils/routes";

/* ================= LAZY LOAD PAGES ================= */

const StudentScreen = lazy(() => import("../pages/student/StudentScreen"));
const TeacherScreen = lazy(() => import("../pages/teacher/TeacherScreen"));
const AdminScreen = lazy(() => import("../pages/admin/AdminScreen"));

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
        {/* ADMIN */}
        <Route
          path={ROUTES.ADMIN}
          element={
            <ProtectedRoute allowedRoles={[ROLE.ADMIN]}>
              <AdminScreen />
            </ProtectedRoute>
          }
        />

        {/* STUDENT */}
        <Route
          path={ROUTES.STUDENT}
          element={
            <ProtectedRoute allowedRoles={[ROLE.STUDENT]}>
              <StudentScreen />
            </ProtectedRoute>
          }
        />

        {/* TEACHER */}
        <Route
          path={ROUTES.TEACHER}
          element={
            <ProtectedRoute allowedRoles={[ROLE.TEACHER]}>
              <TeacherScreen />
            </ProtectedRoute>
          }
        />

        {/* UPLOAD */}
        <Route
          path={ROUTES.UPLOAD_PRODUCT}
          element={
            <ProtectedRoute allowedRoles={[ROLE.STUDENT]}>
              <UploadProductScreen />
            </ProtectedRoute>
          }
        />

        <Route
          path={ROUTES.EDIT_PRODUCT}
          element={
            <ProtectedRoute allowedRoles={[ROLE.STUDENT]}>
              <UploadProductScreen />
            </ProtectedRoute>
          }
        />

        {/* PRODUCT DETAIL - STUDENT */}
        <Route
          path={ROUTES.STUDENT_DETAIL}
          element={
            <ProtectedRoute allowedRoles={[ROLE.STUDENT]}>
              <ProductDetailScreen />
            </ProtectedRoute>
          }
        />

        {/* PRODUCT DETAIL - TEACHER */}
        <Route
          path={ROUTES.TEACHER_DETAIL}
          element={
            <ProtectedRoute allowedRoles={[ROLE.TEACHER]}>
              <TeacherProductDetailScreen />
            </ProtectedRoute>
          }
        />

        {/* COMPARE AI PRODUCTS */}
        <Route
          path={ROUTES.COMPARE_AI}
          element={
            <ProtectedRoute allowedRoles={[ROLE.TEACHER]}>
              <CompareProductAi />
            </ProtectedRoute>
          }
        />
        <Route
          path={`${ROUTES.TEACHER_DETAIL}/:id`}
          element={
            <ProtectedRoute allowedRoles={[ROLE.TEACHER]}>
              <TeacherProductDetailScreen />
            </ProtectedRoute>
          }
        />

        <Route path="/nckh-admin" element={<Navigate to={ROUTES.ADMIN} replace />} />
        <Route path="/nckh-student" element={<Navigate to={ROUTES.STUDENT} replace />} />
        <Route path="/nckh-teacher" element={<Navigate to={ROUTES.TEACHER} replace />} />
        <Route path="/upload" element={<Navigate to={ROUTES.UPLOAD_PRODUCT} replace />} />
        <Route path="/edit-product" element={<Navigate to={ROUTES.EDIT_PRODUCT} replace />} />
        <Route path="/detail" element={<Navigate to={ROUTES.STUDENT_DETAIL} replace />} />
        <Route path="/detail-teacher" element={<Navigate to={ROUTES.TEACHER_DETAIL} replace />} />
        <Route path="/nckh-compare" element={<Navigate to={ROUTES.COMPARE_AI} replace />} />

        {/* 404 */}
        <Route path="*" element={<NotFoundScreen />} />
      </Routes>
    </Suspense>
  );
}

export default RoleRoutes;
