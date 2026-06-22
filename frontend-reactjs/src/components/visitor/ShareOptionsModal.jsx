import { toast } from "react-toastify";

const ShareOptionsModal = ({ product, url, onClose, onShared }) => {
  if (!product || !url) return null;

  const encodedUrl = encodeURIComponent(url);
  const encodedTitle = encodeURIComponent(product.title || "Sản phẩm sinh viên");
  const options = [
    {
      name: "Facebook",
      icon: "f",
      color: "bg-blue-600",
      href: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`,
    },
    {
      name: "WhatsApp",
      icon: "☏",
      color: "bg-green-500",
      href: `https://wa.me/?text=${encodedTitle}%20${encodedUrl}`,
    },
    {
      name: "Telegram",
      icon: "➤",
      color: "bg-sky-500",
      href: `https://t.me/share/url?url=${encodedUrl}&text=${encodedTitle}`,
    },
    {
      name: "X",
      icon: "X",
      color: "bg-black",
      href: `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`,
    },
    {
      name: "LinkedIn",
      icon: "in",
      color: "bg-blue-700",
      href: `https://www.linkedin.com/sharing/share-offsite/?url=${encodedUrl}`,
    },
    {
      name: "Email",
      icon: "✉",
      color: "bg-slate-600",
      href: `mailto:?subject=${encodedTitle}&body=${encodedUrl}`,
    },
  ];

  const openOption = (href) => {
    window.open(href, "_blank", "noopener,noreferrer,width=720,height=640");
    onShared();
    onClose();
  };

  const copyLink = async () => {
    try {
      await navigator.clipboard.writeText(url);
    } catch {
      const input = document.createElement("textarea");
      input.value = url;
      input.style.position = "fixed";
      input.style.opacity = "0";
      document.body.appendChild(input);
      input.select();
      document.execCommand("copy");
      document.body.removeChild(input);
    }

    toast.success("Đã sao chép liên kết sản phẩm");
    onShared();
    onClose();
  };

  return (
    <div
      className="fixed inset-0 z-[100] flex items-center justify-center bg-black/45 p-4"
      onMouseDown={(event) => event.target === event.currentTarget && onClose()}
    >
      <div className="w-full max-w-lg rounded-2xl bg-white p-5 shadow-2xl">
        <div className="mb-4 flex items-start justify-between gap-4">
          <div>
            <h3 className="text-lg font-semibold text-slate-900">Chia sẻ sản phẩm</h3>
            <p className="mt-1 line-clamp-1 text-sm text-slate-500">{product.title}</p>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="rounded-lg px-2.5 py-1.5 text-xl text-slate-500 hover:bg-slate-100"
            aria-label="Đóng"
          >
            ×
          </button>
        </div>

        <div className="grid grid-cols-3 gap-3 sm:grid-cols-6">
          {options.map((option) => (
            <button
              key={option.name}
              type="button"
              onClick={() => openOption(option.href)}
              className="flex flex-col items-center gap-2 rounded-xl p-2 text-xs text-slate-700 hover:bg-slate-50"
            >
              <span className={`flex h-11 w-11 items-center justify-center rounded-xl text-lg font-bold text-white ${option.color}`}>
                {option.icon}
              </span>
              <span>{option.name}</span>
            </button>
          ))}
        </div>

        <div className="mt-5 flex items-center gap-2 rounded-xl border border-slate-200 p-2">
          <input
            readOnly
            value={url}
            className="min-w-0 flex-1 bg-transparent px-2 text-sm text-slate-600 outline-none"
          />
          <button
            type="button"
            onClick={copyLink}
            className="shrink-0 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700"
          >
            Sao chép
          </button>
        </div>
      </div>
    </div>
  );
};

export default ShareOptionsModal;
