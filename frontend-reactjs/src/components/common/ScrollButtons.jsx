import { ArrowDown, ArrowUp } from "lucide-react";
import useScrollControls from "../../hooks/common/useScrollControls";

export default function ScrollButtons({ bottomTarget = null }) {
  const { handlePageTop, handlePageBottom } = useScrollControls();

  return (
    <div className="fixed bottom-24 right-4 z-50 flex flex-col gap-2 sm:right-6">
      <button
        type="button"
        onClick={handlePageTop}
        title="Lên đầu trang"
        className="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-[#003087] shadow-md transition hover:bg-[#003087] hover:text-white"
      >
        <ArrowUp size={18} />
      </button>
      <button
        type="button"
        onClick={() => handlePageBottom(bottomTarget)}
        title="Xuống cuối trang"
        className="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-[#003087] shadow-md transition hover:bg-[#003087] hover:text-white"
      >
        <ArrowDown size={18} />
      </button>
    </div>
  );
}
