import React, { useState, useContext, useMemo } from "react";
import { useNavigate } from "react-router-dom";
import { AuthContext } from "../../contexts/AuthContext";
import useTitle from "../../hooks/common/useTitle";
import useMajorName from "../../hooks/common/useMajorName";
import useProductAll from "../../hooks/useProduct/useProductAll";
import { useStudentStats } from "../../hooks/student/useStudentStats";
import { mapCurrentStudent } from "../../utils/userMapper";
import StudentHeader from "../../components/student/StudentHeader";
import ProductCard from "../../components/student/ProductCard";
import { STATUS } from "../../utils/constants";
import { getMajorTheme } from "../../utils/uploadProductScreen/uploadRegistry";
import ChatBoxAi from "../../pages/ChatBoxAi/ChatBoxAi";
import SearchAi from "../ai/SearchAi";

const StudentScreen = () => {
  const [activeTab, setActiveTab] = useState("all");
  const [currentPage, setCurrentPage] = useState(1);
  const ITEMS_PER_PAGE = 6;
  useTitle("Trang chủ sinh viên");
  const navigate = useNavigate();

  const { user } = useContext(AuthContext);
  const { majorName } = useMajorName(user?.major_id);
  localStorage.setItem("majorName", majorName);

  const { products, loading, error } = useProductAll();
  const currentStudent = mapCurrentStudent(user, majorName);
  const aiBox = JSON.parse(sessionStorage.getItem("auth_user"));
  // console.log(user);

  const productData = products?.data;
  // console.log("productData", productData);

  const theme = getMajorTheme(majorName);

  const productsArray = useMemo(
    () => (Array.isArray(productData) ? productData : []),
    [productData],
  );

  const { stats, animatedStats, filteredProducts } = useStudentStats(
    productsArray,
    activeTab,
  );

  const totalPages = Math.ceil(filteredProducts.length / ITEMS_PER_PAGE);
  const paginatedProducts = useMemo(() => {
    const start = (currentPage - 1) * ITEMS_PER_PAGE;
    return filteredProducts.slice(start, start + ITEMS_PER_PAGE);
  }, [filteredProducts, currentPage]);

  const handleTabChange = (tab) => {
    setActiveTab(tab);
    setCurrentPage(1);
  };

  const handleViewDetail = (id) => {
    navigate("/product-detail", { state: { productId: id } });
  };

  const handleEdit = (product) => {
    navigate("/edit-product", { state: { product } });
  };

  const handleDelete = (id) => {
    if (window.confirm("Bạn có chắc muốn xóa sản phẩm này?")) {
      // Gọi API xóa ở đây
      console.log("Xóa sản phẩm:", id);
    }
  };

  if (loading) return <p className="p-6">Đang tải...</p>;
  if (error) return <p className="p-6 text-red-500">Có lỗi xảy ra</p>;
  if (!user) return <p>Loading...</p>;
  return (
    <div className="min-h-screen bg-slate-50">
      <StudentHeader
        majorName={majorName}
        currentStudent={currentStudent}
        stats={stats}
        animatedStats={animatedStats}
        activeTab={activeTab}
        setActiveTab={handleTabChange}
        theme={theme}
        onUploadClick={() => navigate("/upload-product")}
      />

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <SearchAi embedded user={user} majorName={majorName} />

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {paginatedProducts.map((item, idx) => (
            <ProductCard
              key={item.product_id}
              product={item}
              index={(currentPage - 1) * ITEMS_PER_PAGE + idx + 1}
              majorName={majorName}
              theme={theme}
              onViewDetail={handleViewDetail}
              onEdit={handleEdit}
              onDelete={handleDelete}
            />
          ))}
        </div>

        {totalPages > 1 && (
          <div className="flex justify-center items-center gap-2 mt-8 flex-wrap">
            <button
              onClick={() => setCurrentPage((prev) => Math.max(prev - 1, 1))}
              disabled={currentPage === 1}
              className="px-3 py-2 border rounded-md bg-white hover:bg-slate-50 disabled:opacity-50"
            >
              Trước
            </button>

            {Array.from({ length: totalPages }, (_, index) => index + 1).map(
              (page) => (
                <button
                  key={page}
                  onClick={() => setCurrentPage(page)}
                  className={`px-4 py-2 rounded-md border text-sm ${
                    currentPage === page
                      ? "bg-slate-900 text-white border-slate-900"
                      : "bg-white text-slate-700 hover:bg-slate-50"
                  }`}
                >
                  {page}
                </button>
              ),
            )}

            <button
              onClick={() =>
                setCurrentPage((prev) => Math.min(prev + 1, totalPages))
              }
              disabled={currentPage === totalPages}
              className="px-3 py-2 border rounded-md bg-white hover:bg-slate-50 disabled:opacity-50"
            >
              Sau
            </button>
          </div>
        )}

        {filteredProducts.length === 0 && (
          <div className="text-center py-16 bg-white rounded-xl shadow-sm border border-slate-200">
            <svg
              className="mx-auto h-12 w-12 text-slate-300"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={1.5}
                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
              />
            </svg>
            <h3 className="mt-2 text-sm font-medium text-slate-900">
              Chưa có sản phẩm
            </h3>
            <p className="mt-1 text-sm text-slate-500">
              {activeTab === STATUS.PENDING &&
                "Bạn chưa có sản phẩm nào đang chờ duyệt."}
              {activeTab === STATUS.APPROVED &&
                "Bạn chưa có sản phẩm nào được duyệt."}
              {activeTab === STATUS.REJECTED &&
                "Bạn chưa có sản phẩm nào bị từ chối."}
              {activeTab === "all" &&
                "Bắt đầu bằng cách đăng sản phẩm đầu tiên của bạn."}
            </p>
            <button
              onClick={() => navigate("/upload-product")}
              className={`mt-4 px-4 py-2 ${theme.buttonBg} text-white rounded-lg transition`}
            >
              + Đăng sản phẩm
            </button>
          </div>
        )}

        {aiBox && <ChatBoxAi user={aiBox} />}
      </div>
    </div>
  );
};

export default StudentScreen;
