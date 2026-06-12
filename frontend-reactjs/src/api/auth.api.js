import axiosClient from "./axiosClient";

const authApi = {
  login: (data) => axiosClient.post("/login", data),
  logout: () => axiosClient.post("/logout"),
  submitPasswordRecovery: (data) =>
    axiosClient.post("/support/password-recovery", data),
  submitContact: (data) => axiosClient.post("/support/contact", data),
  // me: () => axiosClient.get("/me"),
};

export default authApi;
