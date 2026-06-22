import { useEffect, useState } from "react";
import { Icons } from "../common/Icon";
import { productApi } from "../../api";
import { shareVisitorProduct } from "../../utils/shareProduct";
import ShareOptionsModal from "./ShareOptionsModal";

const VisitorShareButton = ({ product }) => {
  const [shareCount, setShareCount] = useState(product?.shares || 0);
  const [showOptions, setShowOptions] = useState(false);

  useEffect(() => {
    setShareCount(product?.shares || 0);
  }, [product?.id, product?.shares]);

  const recordShare = async () => {
    if (!product?.id) return;

    try {
      const response = await productApi.incrementShare(product.id);
      if (typeof response?.shares === "number") {
        setShareCount(response.shares);
      }
    } catch (error) {
      console.error("Không thể cập nhật lượt chia sẻ", error);
    }
  };

  const handleShare = async () => {
    if (!product?.id) return;

    const url = `${window.location.origin}/chi-tiet-san-pham/${product.id}`;
    const result = await shareVisitorProduct({
      title: product.title,
      description: product.description,
      url,
    });

    if (result === "shared") {
      await recordShare();
    } else if (result === "unsupported") {
      setShowOptions(true);
    }
  };

  const shareUrl = product?.id
    ? `${window.location.origin}/chi-tiet-san-pham/${product.id}`
    : "";

  return (
    <>
      <button
        type="button"
        onClick={handleShare}
        className="flex items-center gap-2 transition-transform hover:scale-110"
      >
        <Icons.Share className="h-4 w-4" />
        <span>{shareCount.toLocaleString()} chia sẻ</span>
      </button>

      {showOptions && (
        <ShareOptionsModal
          product={product}
          url={shareUrl}
          onClose={() => setShowOptions(false)}
          onShared={recordShare}
        />
      )}
    </>
  );
};

export default VisitorShareButton;
