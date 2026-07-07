import { Video } from "lucide-react";

const ProductVideoSection = ({
  videoUrl,
  theme,
  title = "Video sản phẩm",
}) => {
  if (!videoUrl) return null;

  const headingClass = theme?.text || theme?.textColor || "text-gray-900";

  return (
    <div className="overflow-hidden rounded-2xl bg-white shadow-lg">
      <div className="border-b border-gray-100 p-6">
        <h2 className={`flex items-center gap-2 text-lg font-bold ${headingClass}`}>
          <Video className="h-5 w-5" aria-hidden="true" />
          {title}
        </h2>
      </div>
      <div className="p-6">
        <video
          src={videoUrl}
          controls
          preload="metadata"
          className="aspect-video w-full rounded-xl bg-black object-contain"
        />
      </div>
    </div>
  );
};

export default ProductVideoSection;
