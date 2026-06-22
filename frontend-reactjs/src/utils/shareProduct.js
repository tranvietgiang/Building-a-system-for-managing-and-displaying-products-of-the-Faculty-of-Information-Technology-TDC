export const shareVisitorProduct = async ({ title, description, url }) => {
  if (typeof navigator.share !== "function") return "unsupported";

  try {
    await navigator.share({
      title: title || "Sản phẩm sinh viên",
      text: description || "",
      url,
    });
    return "shared";
  } catch (error) {
    if (error?.name === "AbortError") return "cancelled";
    return "unsupported";
  }
};
