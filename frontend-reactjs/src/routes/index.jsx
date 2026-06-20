import { BrowserRouter, Navigate, Routes, Route, useParams } from "react-router-dom";
import Login from "../pages/auth/Login";
import ForgotPassword from "../pages/auth/forget";
import GuestRoute from "./guest.route";
import RoleRoutes from "./protected.routes";
import VisitorScreen from "../pages/visitorScreen/VisitorScreen";
import VisitorDetailScreen from "../pages/visitorScreen/VisitorDetailScreen";
import NotFoundPage from "../pages/notFoundScreen/NotFoundScreen";
import Profile from "../layouts/ProfileLayout";
import GuideScreen from "../pages/guideScreen/GuideScreen";
import ContactScreen from "../pages/contactScreen/ContactScreen";
import MajorScreen from "../pages/majorScreen/MajorScreen";
import { LEGACY_ROUTES, ROUTES } from "../utils/routes";

const LegacyRedirect = ({ to }) => {
  const params = useParams();
  const target = Object.entries(params).reduce(
    (path, [key, value]) => path.replace(`:${key}`, value),
    to,
  );

  return <Navigate to={target} replace />;
};

function AppRoutes() {
  return (
    <BrowserRouter>
      <Routes>
        {LEGACY_ROUTES.map(([from, to]) => (
          <Route key={from} path={from} element={<LegacyRedirect to={to} />} />
        ))}

        <Route
          path={ROUTES.LOGIN}
          element={
            <GuestRoute>
              <Login />
            </GuestRoute>
          }
        />
        <Route
          path={ROUTES.FORGOT_PASSWORD}
          element={
            <GuestRoute>
              <ForgotPassword />
            </GuestRoute>
          }
        />

        <Route path={ROUTES.VISITOR} element={<VisitorScreen />} />
        <Route path={ROUTES.MAJORS} element={<MajorScreen />} />
        <Route path={ROUTES.GUIDE} element={<GuideScreen />} />
        <Route path={ROUTES.CONTACT} element={<ContactScreen />} />

        <Route path={ROUTES.VISITOR_DETAIL} element={<VisitorDetailScreen />} />
        <Route path={`${ROUTES.VISITOR_DETAIL}/:id`} element={<VisitorDetailScreen />} />

        <Route path={ROUTES.PROFILE} element={<Profile />} />

        <Route path="*" element={<RoleRoutes />} />
      </Routes>
    </BrowserRouter>
  );
}

export default AppRoutes;
