import axiosClient from "./axiosClient";

const buildQuery = (params = {}) => {
  const query = new URLSearchParams();

  Object.entries(params).forEach(([key, value]) => {
    if (value !== undefined && value !== null && value !== "") {
      query.append(key, value);
    }
  });

  const queryString = query.toString();
  return queryString ? `?${queryString}` : "";
};

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
  searchVisitorProducts: (params) =>
    axiosClient.get(`/visitor/products/search${buildQuery(params)}`),
  searchProducts: (params) =>
    axiosClient.get(`/products/search${buildQuery(params)}`),
  getVisitorProductById: (id) => axiosClient.get(`/visitor/product/${id}`),
  incrementView: (id) => axiosClient.post(`/visitor/product/${id}/view`),
  incrementLike: (id) => axiosClient.post(`/visitor/product/${id}/like`),
};

export default productApi;
