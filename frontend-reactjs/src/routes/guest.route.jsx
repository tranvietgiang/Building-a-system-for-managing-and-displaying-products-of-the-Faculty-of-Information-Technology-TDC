import { Navigate } from "react-router-dom";
import { useContext } from "react";
import { AuthContext } from "../contexts/AuthContext";
import { ROLE } from "../utils/constants";
const GuestRoute = ({ children }) => {
  const { user, token, loading } = useContext(AuthContext);

  if (loading) return <div>Loading...</div>;

  if (token) {
    if (user.role === ROLE.ADMIN) return <Navigate to="/quan-tri" replace />;
    if (user.role === ROLE.TEACHER)
      return <Navigate to="/giang-vien" replace />;
    if (user.role === ROLE.STUDENT)
      return <Navigate to="/sinh-vien" replace />;

    // fallback nếu chưa có role
    return <Navigate to="/404" replace />;
  }

  return children;
};

export default GuestRoute;
