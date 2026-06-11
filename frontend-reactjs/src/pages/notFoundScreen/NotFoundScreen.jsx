import React from "react";
import { useNavigate } from "react-router-dom";

const NotFoundPage = () => {
  const navigate = useNavigate();

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
      <div className="max-w-2xl w-full text-center">
        {/* Icon 404 */}
        <div className="mb-8 relative">
          <div className="text-9xl font-bold text-blue-600 opacity-20">404</div>
          <div className="absolute inset-0 flex items-center justify-center">
            <svg
              className="w-32 h-32 text-blue-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={1.5}
                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
          </div>
        </div>

        {/* Title */}
        <h1 className="text-3xl md:text-4xl font-bold text-gray-800 mb-4">
          Không tìm thấy trang
        </h1>

        {/* Description */}
        <p className="text-gray-600 text-lg mb-8">
          Trang bạn đang tìm kiếm không tồn tại hoặc đã được di chuyển. Vui lòng
          kiểm tra lại đường dẫn hoặc quay về trang chủ.
        </p>

        {/* Action buttons */}
        <div className="flex flex-col sm:flex-row gap-4 justify-center">
          <button
            onClick={() => navigate(-1)}
            className="px-6 py-3 bg-white text-gray-700 rounded-xl hover:bg-gray-50 transition font-medium flex items-center justify-center gap-2 shadow-md"
          >
            <svg
              className="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M10 19l-7-7m0 0l7-7m-7 7h18"
              />
            </svg>
            Quay lại
          </button>

          <button
            onClick={() => navigate("/nckh-visitor")}
            className="px-6 py-3 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition font-medium flex items-center justify-center gap-2 shadow-md"
          >
            <svg
              className="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
              />
            </svg>
            Về trang chủ
          </button>
        </div>

        {/* Help links */}
        <div className="mt-12 pt-8 border-t border-gray-200">
          <p className="text-gray-500 text-sm mb-4">
            Có thể bạn muốn truy cập:
          </p>
          <div className="flex flex-wrap gap-4 justify-center text-sm">
            <a
              href="/"
              className="text-blue-600 hover:text-blue-800 hover:underline"
            >
              Trang chủ
            </a>
            <span className="text-gray-300">•</span>
            <a
              href="/products"
              className="text-blue-600 hover:text-blue-800 hover:underline"
            >
              Sản phẩm
            </a>
            <span className="text-gray-300">•</span>
            <a
              href="/huong-dan"
              className="text-blue-600 hover:text-blue-800 hover:underline"
            >
              Hướng dẫn
            </a>
            <span className="text-gray-300">•</span>
            <a
              href="/contact"
              className="text-blue-600 hover:text-blue-800 hover:underline"
            >
              Liên hệ
            </a>
          </div>
        </div>

        {/* Error code for developers */}
        <p className="mt-8 text-xs text-gray-400">Error 404 | Page Not Found</p>
      </div>
    </div>
  );
};

export default NotFoundPage;
