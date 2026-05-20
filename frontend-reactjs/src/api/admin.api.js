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

const adminApi = {
  getDashboard: () => axiosClient.get("/admin/dashboard"),

  getUsers: (params) => axiosClient.get(`/admin/users${buildQuery(params)}`),
  createUser: (payload) => axiosClient.post("/admin/users", payload),
  updateUser: (userId, payload) =>
    axiosClient.put(`/admin/users/${userId}`, payload),
  deleteUser: (userId) => axiosClient.delete(`/admin/users/${userId}`),

  getProducts: (params) =>
    axiosClient.get(`/admin/products${buildQuery(params)}`),
  updateProductStatus: (productId, status) =>
    axiosClient.patch(`/admin/products/${productId}/status`, { status }),
  deleteProduct: (productId) =>
    axiosClient.delete(`/admin/products/${productId}`),

  getMajors: () => axiosClient.get("/admin/majors"),
  createMajor: (payload) => axiosClient.post("/admin/majors", payload),
  updateMajor: (majorId, payload) =>
    axiosClient.put(`/admin/majors/${majorId}`, payload),
  deleteMajor: (majorId) => axiosClient.delete(`/admin/majors/${majorId}`),

  getCategories: () => axiosClient.get("/admin/categories"),
};

export default adminApi;
