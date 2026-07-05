import { Icons } from "../common/Icon";

const parseTeamMembers = (value) => {
  if (Array.isArray(value)) return value.filter(Boolean);

  if (typeof value !== "string" || !value.trim()) return [];

  try {
    const parsed = JSON.parse(value);
    if (Array.isArray(parsed)) return parsed.filter(Boolean);
  } catch {
    // Old data can be stored as one member per line.
  }

  return value
    .split(/\r?\n/)
    .map((member) => member.trim())
    .filter(Boolean);
};

const getMemberInfo = (member) => {
  if (member && typeof member === "object") {
    const id = member.mssv || member.student_id || member.user_id || "";
    const name = member.name || member.fullname || member.full_name || "";

    return {
      id: String(id || "").trim(),
      name: String(name || id || "Chưa cập nhật").trim(),
    };
  }

  const text = String(member || "").trim();
  const [firstPart, ...nameParts] = text.split(/\s*-\s*/);
  const possibleName = nameParts.join(" - ").trim();

  if (possibleName) {
    return {
      id: firstPart.trim(),
      name: possibleName,
    };
  }

  return {
    id: "",
    name: text || "Chưa cập nhật",
  };
};

export default function ProjectTeamCard({ product, theme }) {
  const members = parseTeamMembers(product?.team_members);
  const leaderName = product?.fullname || product?.student || "Chưa cập nhật";
  const leaderId = product?.user_id || product?.studentId || "";
  const advisorName = product?.advisor_name || product?.advisor || "Chưa cập nhật";

  return (
    <div className="bg-white rounded-2xl shadow-lg p-6">
      <h2 className={`text-lg font-bold mb-4 ${theme.text} flex items-center gap-2`}>
        <Icons.Users />
        Nhóm thực hiện
      </h2>

      <div className="space-y-4">
        <div className="flex items-center justify-between gap-4 rounded-xl bg-gray-50 p-4">
          <div className="flex min-w-0 items-center gap-3">
            <div
              className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-r ${theme?.gradient} text-lg font-bold text-white shadow-md`}
            >
              {leaderName.charAt(0).toUpperCase()}
            </div>
            <div className="min-w-0">
              <p className="truncate font-semibold text-gray-900">{leaderName}</p>
              <p className="text-sm text-gray-500">
                {leaderId ? `MSSV: ${leaderId}` : "Sinh viên thực hiện"}
              </p>
            </div>
          </div>
          <span className={`${theme.light} ${theme.text} shrink-0 rounded-full px-3 py-1 text-xs font-semibold`}>
            Nhóm trưởng
          </span>
        </div>

        {members.length > 0 && (
          <div>
            <p className="mb-2 text-sm font-semibold text-gray-700">
              Sinh viên cùng thực hiện
            </p>
            <div className="space-y-2">
              {members.map((member, index) => {
                const info = getMemberInfo(member);

                return (
                  <div
                    key={`${info.id}-${info.name}-${index}`}
                    className="flex items-center gap-3 rounded-lg border border-gray-100 px-4 py-3"
                  >
                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-semibold text-gray-600">
                      {index + 1}
                    </span>
                    <div className="min-w-0">
                      <p className="truncate text-sm font-semibold text-gray-800">
                        {info.name}
                      </p>
                      {info.id && (
                        <p className="text-xs text-gray-500">MSSV: {info.id}</p>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        )}

        <div className="border-t border-gray-100 pt-4">
          <p className="text-sm text-gray-500">Giảng viên hướng dẫn</p>
          <p className="mt-1 font-semibold text-gray-900">{advisorName}</p>
        </div>
      </div>
    </div>
  );
}
