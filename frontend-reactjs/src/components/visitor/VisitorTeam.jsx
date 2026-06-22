const VisitorTeam = ({ product, theme }) => {
  const members = Array.isArray(product?.team_members)
    ? product.team_members.filter(Boolean)
    : [];

  return (
    <div className="space-y-4">
      <div className={`flex items-center gap-5 rounded-xl p-5 ${theme.light}`}>
        <div className={`flex h-16 w-16 items-center justify-center rounded-full text-2xl font-bold text-white shadow-md ${theme.buttonBg}`}>
          {product?.student?.charAt(0)?.toUpperCase() || "N"}
        </div>
        <div>
          <h3 className="text-lg font-bold text-gray-800">
            {product?.student || "Chưa cập nhật"}
          </h3>
          <p className="text-sm text-gray-500">
            MSSV: {product?.studentId || "Chưa cập nhật"}
          </p>
          <p className={`mt-1 text-sm font-semibold ${theme.text}`}>
            Nhóm trưởng
          </p>
        </div>
      </div>

      {members.map((member, index) => {
        const [studentId, ...nameParts] = String(member).split("-");
        const name = nameParts.join("-").trim();
        const displayName = name || studentId.trim();

        return (
          <div
            key={`${member}-${index}`}
            className="flex items-center gap-4 rounded-xl bg-gray-50 p-4"
          >
            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-gray-200 text-lg font-bold text-gray-600">
              {displayName.charAt(0).toUpperCase()}
            </div>
            <div>
              <h3 className="font-semibold text-gray-800">{displayName}</h3>
              {name && (
                <p className="text-sm text-gray-500">MSSV: {studentId.trim()}</p>
              )}
              <p className="text-sm text-gray-500">Thành viên</p>
            </div>
          </div>
        );
      })}

      {product?.advisor && (
        <div className="flex items-center gap-4 rounded-xl bg-gray-50 p-4">
          <div className="flex h-12 w-12 items-center justify-center rounded-full bg-gray-200 text-xl">
            👨‍🏫
          </div>
          <div>
            <h3 className="font-semibold text-gray-800">{product.advisor}</h3>
            <p className="text-sm text-gray-500">Giảng viên hướng dẫn</p>
          </div>
        </div>
      )}
    </div>
  );
};

export default VisitorTeam;
