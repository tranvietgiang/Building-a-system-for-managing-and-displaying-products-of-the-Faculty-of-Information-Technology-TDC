import { useState } from "react";
import AdminLayout from "../../layouts/AdminLayout";
import DashboardScreen from "./screens/DashboardScreen";
import UserScreen from "./screens/UserManager/UsersScreen";
import ProductScreen from "./screens/ProductsScreen";
import MajorScreen from "./screens/MajorsScreen";
import SettingScreen from "./screens/SettingsScreen";

const AdminScreen = () => {
  const [activeSection, setActiveSection] = useState("dashboard");

  const menuMap = {
    dashboard: {
      title: "Dashboard",
      component: <DashboardScreen />,
    },
    users: {
      title: "Quản lý người dùng",
      component: <UserScreen />,
    },
    products: {
      title: "Quản lý sản phẩm",
      component: <ProductScreen />,
    },
    majors: {
      title: "Quản lý chuyên ngành",
      component: <MajorScreen />,
    },
    settings: {
      title: "Cài đặt hệ thống",
      component: <SettingScreen />,
    },
  };

  const currentPage = menuMap[activeSection] || menuMap.dashboard;

  return (
    <AdminLayout
      activeSection={activeSection}
      setActiveSection={setActiveSection}
      title={currentPage.title}
    >
      {currentPage.component}
    </AdminLayout>
  );
};

export default AdminScreen;
