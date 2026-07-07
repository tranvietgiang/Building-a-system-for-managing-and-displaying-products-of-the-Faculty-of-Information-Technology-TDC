import axiosClient from "./axiosClient";

const aiApi = {
  sendMessage: (message) => axiosClient.post("/ai/send", message),

  searchProducts: (message) => axiosClient.post("/ai/search", { message }),

  compareAiProduct: (id) => axiosClient.get(`/ai/compare/${id}`),

  // Chỉ kiểm tra hình ảnh khi teacher/admin chọn 1 sản phẩm nghi trùng
  compareProductImages: (productId, matchProductId, textSimilarity = 0) =>
    axiosClient.post(
      `/teacher/product/${productId}/compare-images/${matchProductId}`,
      {
        text_similarity: textSimilarity,
      },
    ),
};

export default aiApi;
