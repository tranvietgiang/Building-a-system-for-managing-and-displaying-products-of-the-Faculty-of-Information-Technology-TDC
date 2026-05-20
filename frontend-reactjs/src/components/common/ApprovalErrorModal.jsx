import React from "react";
import { Icons } from "./Icon";

export const ApprovalErrorModal = ({
  isOpen,
  reason,
  violations,
  productTitle,
  onClose,
  onContinueApprove,
  isLoading,
}) => {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
      <div className="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="bg-red-50 border-b border-red-200 p-6">
          <div className="flex items-start gap-3">
            <Icons.AlertCircle className="text-red-600 flex-shrink-0 mt-1" />
            <div>
              <h2 className="text-lg font-bold text-red-700">
                AI đã phát hiện vấn đề
              </h2>
              <p className="text-sm text-red-600 mt-1">
                Sản phẩm không đạt tiêu chuẩn duyệt
              </p>
            </div>
          </div>
        </div>

        {/* Content */}
        <div className="p-6 space-y-4">
          {/* Product Title */}
          {productTitle && (
            <div className="bg-gray-50 rounded-lg p-3">
              <p className="text-xs text-gray-500 font-semibold uppercase mb-1">
                Sản phẩm
              </p>
              <p className="text-gray-800 font-medium">{productTitle}</p>
            </div>
          )}

          {/* Main Reason */}
          {reason && (
            <div className="bg-orange-50 rounded-lg p-4 border border-orange-200">
              <p className="text-sm font-semibold text-orange-700 mb-2">
                Lý do chặn:
              </p>
              <p className="text-sm text-orange-600">{reason}</p>
            </div>
          )}

          {/* Violations List */}
          {violations && violations.length > 0 && (
            <div className="bg-red-50 rounded-lg p-4 border border-red-200">
              <p className="text-sm font-semibold text-red-700 mb-3">
                Nội dung vi phạm:
              </p>
              <ul className="space-y-2">
                {violations.map((violation, idx) => (
                  <li key={idx} className="flex items-start gap-2">
                    <span className="text-red-500 text-lg leading-none mt-0.5">
                      •
                    </span>
                    <span className="text-sm text-red-600">{violation}</span>
                  </li>
                ))}
              </ul>
            </div>
          )}

          {/* Info */}
          <div className="bg-blue-50 rounded-lg p-3 border border-blue-200">
            <p className="text-xs text-blue-700">
              <strong>Lưu ý:</strong> Bạn có thể tiếp tục duyệt để ghi đè quyết
              định của AI, hoặc quay lại để chỉnh sửa sản phẩm.
            </p>
          </div>
        </div>

        {/* Actions */}
        <div className="bg-gray-50 border-t border-gray-200 p-4 flex gap-3">
          <button
            onClick={onClose}
            disabled={isLoading}
            className="flex-1 px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-100 disabled:opacity-50 transition"
          >
            Quay lại
          </button>
          <button
            onClick={onContinueApprove}
            disabled={isLoading}
            className="flex-1 px-4 py-2 bg-orange-500 text-white rounded-lg font-medium hover:bg-orange-600 disabled:opacity-50 transition flex items-center justify-center gap-2"
          >
            {isLoading ? (
              <>
                <span className="animate-spin">⏳</span>
                Đang xử lý...
              </>
            ) : (
              <>
                <Icons.Check className="w-4 h-4" />
                Tiếp tục duyệt
              </>
            )}
          </button>
        </div>
      </div>
    </div>
  );
};
