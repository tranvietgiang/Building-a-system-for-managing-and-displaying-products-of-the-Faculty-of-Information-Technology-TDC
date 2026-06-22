const VisitorReviews = ({ reviews = [] }) => {
  if (!Array.isArray(reviews) || reviews.length === 0) {
    return (
      <div className="py-8 text-center text-gray-400">
        <div className="mb-2 text-4xl">💬</div>
        <p>Chưa có đánh giá nào</p>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {reviews.map((review, index) => {
        const comment = typeof review === "string" ? review : review?.comment;
        const teacherName =
          typeof review === "string" ? null : review?.teacher_name;

        return (
          <div key={review?.review_id || index} className="rounded-xl bg-gray-50 p-4">
            <div className="mb-2 flex items-center gap-2">
              <span className="text-yellow-500">⭐</span>
              <span className="text-sm text-gray-500">Đánh giá của giảng viên</span>
            </div>
            <p className="leading-relaxed text-gray-700">“{comment}”</p>
            {teacherName && (
              <p className="mt-2 text-sm font-semibold text-gray-600">
                Giảng viên: {teacherName}
              </p>
            )}
          </div>
        );
      })}
    </div>
  );
};

export default VisitorReviews;
