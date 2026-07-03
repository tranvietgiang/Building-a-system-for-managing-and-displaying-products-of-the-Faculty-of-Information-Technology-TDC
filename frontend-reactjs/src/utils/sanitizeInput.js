const DISALLOWED_TEXT_PATTERN = /[^\p{L}\p{N}\s]/gu;
const DISALLOWED_SINGLE_LINE_PATTERN = /[^\p{L}\p{N} ]/gu;
const DISALLOWED_NAME_LIST_PATTERN = /[^\p{L}\p{N} ,\n-]/gu;
const DISALLOWED_PERSON_NAME_PATTERN = /[^\p{L}\p{N} .,-]/gu;

const SKIP_SANITIZE_FIELD_NAMES = new Set([
  "search",
  "chat",
  "message",
  "title",
  "email",
  "password",
  "github_link",
  "demo_link",
  "dataset_link",
  "report_link",
  "simulation_link",
  "behance_link",
  "accuracy_score",
  "color_palette",
  "framework",
  "programming_language",
  "database_used",
  "model_used",
  "language",
  "dataset_used",
  "topology_type",
  "simulation_tool",
  "network_protocol",
  "design_type",
  "tools_used",
  "custom_category_name",
  "description",
  "awards",
  "team_members",
]);

export const sanitizeTextInput = (value, { multiline = false } = {}) => {
  const text = String(value ?? "");
  return text.replace(
    multiline ? DISALLOWED_TEXT_PATTERN : DISALLOWED_SINGLE_LINE_PATTERN,
    "",
  );
};

export const shouldSanitizeField = (name = "") => {
  if (!name) return true;
  if (SKIP_SANITIZE_FIELD_NAMES.has(name)) return false;
  return !/(email|password|link|url|github|demo|behance|color|accuracy|score)/i.test(
    name,
  );
};

export const sanitizeNamedInput = (name, value, { multiline = false } = {}) => {
  if (!shouldSanitizeField(name)) return value;
  if (name === "team_members") {
    return String(value ?? "").replace(DISALLOWED_NAME_LIST_PATTERN, "");
  }

  if (name === "advisor_name") {
    return String(value ?? "").replace(DISALLOWED_PERSON_NAME_PATTERN, "");
  }
  return sanitizeTextInput(value, { multiline });
};

export const hasInvalidTextInput = (value, { multiline = false } = {}) => {
  const text = String(value ?? "");
  const pattern = multiline
    ? DISALLOWED_TEXT_PATTERN
    : DISALLOWED_SINGLE_LINE_PATTERN;

  pattern.lastIndex = 0;
  return pattern.test(text);
};

export const hasInvalidNamedInput = (
  name,
  value,
  { multiline = false } = {},
) => {
  if (!shouldSanitizeField(name)) return false;

  const text = String(value ?? "");
  let pattern = multiline
    ? DISALLOWED_TEXT_PATTERN
    : DISALLOWED_SINGLE_LINE_PATTERN;

  if (name === "team_members") {
    pattern = DISALLOWED_NAME_LIST_PATTERN;
  }

  if (name === "advisor_name") {
    pattern = DISALLOWED_PERSON_NAME_PATTERN;
  }

  pattern.lastIndex = 0;
  return pattern.test(text);
};

export const getInvalidCharacterMessage = (
  name,
  value,
  { label = "Trường này", multiline = false } = {},
) => {
  if (!hasInvalidNamedInput(name, value, { multiline })) return "";
  return `${label} không được chứa ký tự đặc biệt.`;
};
