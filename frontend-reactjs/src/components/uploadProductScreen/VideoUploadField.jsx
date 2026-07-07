import { Trash2, Upload, Video } from "lucide-react";

const VideoUploadField = ({
  videoFile,
  handleVideoUpload,
  removeVideo,
  error,
  disabled = false,
}) => (
  <div>
    <label className="mb-2 block text-sm font-semibold text-gray-700">
      Video sản phẩm{" "}
      <span className="font-normal text-gray-400">(không bắt buộc)</span>
    </label>

    {!videoFile ? (
      <div className="relative">
        <input
          id="video-upload"
          type="file"
          accept=".mp4,.mov,.avi,.webm,.mkv,video/*"
          onChange={handleVideoUpload}
          className="hidden"
          disabled={disabled}
        />
        <label
          htmlFor="video-upload"
          className={`group block w-full cursor-pointer rounded-xl border-2 border-dashed border-sky-200 bg-sky-50 p-6 transition hover:bg-sky-100 ${
            disabled ? "cursor-not-allowed opacity-50" : ""
          }`}
        >
          <div className="flex flex-col items-center gap-3 text-center">
            <span className="flex h-12 w-12 items-center justify-center rounded-full bg-white text-sky-600 shadow-sm transition-transform group-hover:scale-105">
              <Video className="h-6 w-6" aria-hidden="true" />
            </span>
            <div>
              <p className="text-base font-medium text-gray-700">
                Chọn video demo hoặc video đồ họa
              </p>
              <p className="mt-1 text-sm text-gray-500">
                MP4, MOV, AVI, WEBM, MKV • Tối đa 50MB
              </p>
            </div>
          </div>
        </label>
      </div>
    ) : (
      <div className="overflow-hidden rounded-xl border border-sky-200 bg-white">
        <video
          src={videoFile.url}
          controls
          className="aspect-video w-full bg-black object-contain"
        />
        <div className="flex flex-col gap-3 border-t border-sky-100 p-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="min-w-0">
            <p className="truncate text-sm font-medium text-gray-800">
              {videoFile.name || "Video sản phẩm"}
            </p>
            <p className="text-xs text-gray-500">
              {videoFile.size ? `${videoFile.size}MB` : "Video đã lưu"}
            </p>
          </div>
          <div className="flex shrink-0 gap-2">
            <input
              id="video-replace"
              type="file"
              accept=".mp4,.mov,.avi,.webm,.mkv,video/*"
              onChange={handleVideoUpload}
              className="hidden"
              disabled={disabled}
            />
            <label
              htmlFor="video-replace"
              className={`inline-flex h-10 items-center gap-2 rounded-lg bg-sky-600 px-3 text-sm font-medium text-white transition hover:bg-sky-700 ${
                disabled ? "cursor-not-allowed opacity-50" : "cursor-pointer"
              }`}
              title="Đổi video"
            >
              <Upload className="h-4 w-4" aria-hidden="true" />
              Đổi
            </label>
            <button
              type="button"
              onClick={removeVideo}
              disabled={disabled}
              className="inline-flex h-10 items-center gap-2 rounded-lg bg-red-50 px-3 text-sm font-medium text-red-600 transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50"
              title="Xóa video"
            >
              <Trash2 className="h-4 w-4" aria-hidden="true" />
              Xóa
            </button>
          </div>
        </div>
      </div>
    )}

    {error && <p className="mt-2 text-sm text-red-600">{error}</p>}
  </div>
);

export default VideoUploadField;
