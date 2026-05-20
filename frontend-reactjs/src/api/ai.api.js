import axiosClient from "./axiosClient";

const aiApi = {
  sendMessage: (message) => axiosClient.post("/ai/send", message),
  searchProducts: (message) => axiosClient.post("/ai/search", { message }),
  compareAiProduct: (id) => axiosClient.get(`/ai/compare/${id}`),
  // getMatchingAiProducts: (id) => axiosClient.get(`/product/${id}/matching-ai`),
};

export default aiApi;
