export const getStatusBadge = (status) => {
  const statusMap = {
    approved: {
      bg: "bg-green-100",
      text: "text-green-700",
      label: "Đã duyệt",
      icon: "CheckCircle",
    },
    pending: {
      bg: "bg-yellow-100",
      text: "text-yellow-700",
      label: "Chờ duyệt",
      icon: "Clock",
    },
    rejected: {
      bg: "bg-red-100",
      text: "text-red-700",
      label: "Từ chối",
      icon: "AlertCircle",
    },
  };
  return statusMap[status] || statusMap.pending;
};
