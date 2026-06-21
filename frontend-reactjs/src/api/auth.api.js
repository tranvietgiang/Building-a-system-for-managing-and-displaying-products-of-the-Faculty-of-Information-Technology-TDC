import axiosClient from "./axiosClient";

const authApi = {
  login: (data) => axiosClient.post("/login", data),
  logout: (data) => axiosClient.post("/logout", data),
  refresh: (refreshToken) =>
    axiosClient.post("/refresh", { refresh_token: refreshToken }),
  submitPasswordRecovery: (data) =>
    axiosClient.post("/support/password-recovery", data),
  submitContact: (data) => axiosClient.post("/support/contact", data),
  me: () => axiosClient.get("/me"),
};

export default authApi;
