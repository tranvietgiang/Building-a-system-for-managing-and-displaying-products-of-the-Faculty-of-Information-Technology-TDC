import { Navigate } from "react-router-dom";
import { useContext } from "react";
import { AuthContext } from "../contexts/AuthContext";

const ProtectedRoute = ({ children, allowedRoles = [] }) => {
  const { user, token, loading } = useContext(AuthContext);

  if (loading) return <div>Loading...</div>;

  if (!user || !token) {
    return <Navigate to="/dang-nhap" replace />;
  }

  if (allowedRoles.length > 0 && !allowedRoles.includes(user.role)) {
    return <Navigate to="/dang-nhap" replace />;
  }

  return children;
};

export default ProtectedRoute;
