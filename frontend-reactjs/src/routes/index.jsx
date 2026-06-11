import { BrowserRouter, Routes, Route } from "react-router-dom";
import Login from "../pages/auth/Login";
import GuestRoute from "./guest.route";
import RoleRoutes from "./protected.routes";
import VisitorScreen from "../pages/visitorScreen/VisitorScreen";
import VisitorDetailScreen from "../pages/visitorScreen/VisitorDetailScreen";
import NotFoundPage from "../pages/notFoundScreen/NotFoundScreen";
import Profile from "../layouts/ProfileLayout";
import GuideScreen from "../pages/guideScreen/GuideScreen";

function AppRoutes() {
  return (
    <BrowserRouter>
      <Routes>
        <Route
          path="/login"
          element={
            <GuestRoute>
              <Login />
            </GuestRoute>
          }
        />

        <Route path="/nckh-visitor" element={<VisitorScreen />} />
        <Route path="/huong-dan" element={<GuideScreen />} />

        <Route path="/visitor-detail" element={<VisitorDetailScreen />} />
        <Route path="/visitor-detail/:id" element={<VisitorDetailScreen />} />

        <Route path="/nckh-profile" element={<Profile />} />

        <Route path="*" element={<RoleRoutes />} />
      </Routes>
    </BrowserRouter>
  );
}

export default AppRoutes;
