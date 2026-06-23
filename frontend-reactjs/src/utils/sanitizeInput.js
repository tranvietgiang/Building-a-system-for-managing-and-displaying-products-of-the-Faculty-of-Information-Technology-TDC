const DISALLOWED_TEXT_PATTERN = /[^\p{L}\p{N}\s]/gu;
const DISALLOWED_SINGLE_LINE_PATTERN = /[^\p{L}\p{N} ]/gu;

const SKIP_SANITIZE_FIELD_NAMES = new Set([
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
  return sanitizeTextInput(value, { multiline });
};
