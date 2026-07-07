import { useState } from "react";
import AdminLayout from "../../layouts/AdminLayout";
import DashboardScreen from "./screens/DashboardScreen";
import UserScreen from "./screens/UserManager/UsersScreen";
import ProductScreen from "./screens/ProductsScreen";
import MajorScreen from "./screens/MajorsScreen";
import SettingScreen from "./screens/SettingsScreen";
import SupportScreen from "./screens/SupportScreen";
import ChatboxTrainingScreen from "./screens/ChatboxTrainingScreen";

const AdminScreen = () => {
  const [activeSection, setActiveSection] = useState("dashboard");

  const menuMap = {
    dashboard: {
      title: "Dashboard",
      component: <DashboardScreen />,
    },
    "chatbox-training": {
      title: "Huấn luyện chatbot",
      component: <ChatboxTrainingScreen />,
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
    support: {
      title: "Support",
      component: <SupportScreen />,
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
