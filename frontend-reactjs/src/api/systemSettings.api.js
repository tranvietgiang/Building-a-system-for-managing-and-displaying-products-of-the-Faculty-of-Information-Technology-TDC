import axiosClient from "./axiosClient";

const systemSettingsApi = {
  getPublicSettings: () => axiosClient.get("/system-settings"),
  getAdminSettings: () => axiosClient.get("/admin/system-settings"),
  updateAdminSettings: (payload) =>
    axiosClient.patch("/admin/system-settings", payload),
};

export default systemSettingsApi;
