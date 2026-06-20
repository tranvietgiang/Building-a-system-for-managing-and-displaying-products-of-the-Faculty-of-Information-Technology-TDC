import axiosClient from "./axiosClient";

const uploadApi = {
  countPublishedProducts: () => axiosClient.get("/upload/count-published"),
  uploadProduct: (data) => axiosClient.post("/upload", data),
};

export default uploadApi;
