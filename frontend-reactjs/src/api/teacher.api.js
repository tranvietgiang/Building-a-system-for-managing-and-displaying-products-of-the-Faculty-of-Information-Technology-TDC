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

const teacherApi = {
  getStatistic: () => axiosClient.get("/teacher/statistic"),
  getData: (params) => axiosClient.get(`/teacher${buildQuery(params)}`),
  approve: (productId, data = {}) =>
    axiosClient.post(`/teacher/product/${productId}/approve`, data),
  reject: (data) => axiosClient.post("/teacher/product/reject", data),
};

export default teacherApi;
