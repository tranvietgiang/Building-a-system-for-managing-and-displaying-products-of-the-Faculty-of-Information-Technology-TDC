import axiosClient from "./axiosClient";

const productApi = {
  getProductById: (id) => axiosClient.get(`/product/${id}`),
  getProductAll: () => axiosClient.get("/products?per_page=100"),
  getProductByIdTeacher: (id) => axiosClient.get(`/teacher/product/${id}`),
  getProductRejectTeacher: (id) =>
    axiosClient.get(`/teacher/products/${id}/reject`),
  getProductApproveTeacher: (id) =>
    axiosClient.get(`/teacher/products/${id}/approve`),
  deleteProduct: (id) => axiosClient.post("/student/delete", { product_id: id }),
  getVisitorProducts: () => axiosClient.get("/visitor/products"),
  getVisitorProductById: (id) => axiosClient.get(`/visitor/product/${id}`),
};

export default productApi;
