import { useEffect, useMemo, useState } from "react";
import {
  Bot,
  Gauge,
  Loader2,
  Search,
  ShieldCheck,
  Sparkles,
  ToggleLeft,
} from "lucide-react";
import adminApi from "../../../api/admin.api";

const DEFAULT_SETTINGS = {
  ai_chatbox_enabled: true,
  ai_product_check_enabled: true,
  ai_search_enabled: true,
  ai_dashboard_insights_enabled: true,
  product_search_enabled: true,
};

const settingItems = [
  {
    key: "ai_chatbox_enabled",
    title: "AI Chatbox",
    description:
      "Allow visitors and users to send questions to the AI chat assistant.",
    icon: Bot,
  },
  {
    key: "ai_product_check_enabled",
    title: "Product AI Check",
    description:
      "Run AI moderation and duplicate-style checks when products are posted or reviewed.",
    icon: ShieldCheck,
  },
  {
    key: "ai_search_enabled",
    title: "AI Search",
    description: "Allow semantic product search that can call OpenAI.",
    icon: Sparkles,
  },
  {
    key: "product_search_enabled",
    title: "Product Search",
    description:
      "Allow regular product search across student, visitor, and admin screens.",
    icon: Search,
  },
  {
    key: "ai_dashboard_insights_enabled",
    title: "Dashboard AI Insights",
    description:
      "Allow the admin dashboard to request AI summary and recommendations.",
    icon: Gauge,
  },
];

const SettingsScreen = () => {
  const [settings, setSettings] = useState(DEFAULT_SETTINGS);
  const [loading, setLoading] = useState(true);
  const [savingKey, setSavingKey] = useState("");
  const [error, setError] = useState("");

  const enabledCount = useMemo(
    () => settingItems.filter((item) => settings[item.key]).length,
    [settings],
  );

  useEffect(() => {
    let alive = true;

    const loadSettings = async () => {
      setLoading(true);
      setError("");

      try {
        const res = await adminApi.getSystemSettings();
        if (!alive) return;
        setSettings({ ...DEFAULT_SETTINGS, ...(res.data || {}) });
      } catch (err) {
        console.error("Could not load system settings:", err);
        if (alive) {
          setError("Could not load system settings.");
        }
      } finally {
        if (alive) setLoading(false);
      }
    };

    loadSettings();

    return () => {
      alive = false;
    };
  }, []);

  const toggleSetting = async (key) => {
    const nextValue = !settings[key];
    const previous = settings;

    setSettings((current) => ({ ...current, [key]: nextValue }));
    setSavingKey(key);
    setError("");

    try {
      const res = await adminApi.updateSystemSettings({ [key]: nextValue });
      setSettings({ ...DEFAULT_SETTINGS, ...(res.data || {}) });
    } catch (err) {
      console.error("Could not update system setting:", err);
      setSettings(previous);
      setError("Could not update setting. Please try again.");
    } finally {
      setSavingKey("");
    }
  };

  return (
    <div className="space-y-5">
      <div className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <div className="inline-flex items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600">
              <ToggleLeft size={14} />
              System Controls
            </div>
            <h2 className="mt-3 text-xl font-bold text-slate-900">
              Tùy chỉnh chi phí và tính năng
            </h2>
            <p className="mt-1 max-w-2xl text-sm text-slate-500">
              Bật hoặc tắt các tính năng tìm kiếm và AI có lưu lượng truy cập
              cao mà không cần lập trình.
            </p>
          </div>
          <div className="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            <span className="font-semibold text-slate-900">{enabledCount}</span>{" "}
            / {settingItems.length} enabled
          </div>
        </div>
      </div>

      {error && (
        <div className="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
          {error}
        </div>
      )}

      <div className="grid gap-4 lg:grid-cols-2">
        {settingItems.map((item) => {
          const Icon = item.icon;
          const enabled = Boolean(settings[item.key]);
          const saving = savingKey === item.key;

          return (
            <div
              key={item.key}
              className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
            >
              <div className="flex items-start justify-between gap-4">
                <div className="flex min-w-0 gap-3">
                  <div
                    className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-lg ${
                      enabled
                        ? "bg-emerald-50 text-emerald-700"
                        : "bg-slate-100 text-slate-500"
                    }`}
                  >
                    <Icon size={22} />
                  </div>
                  <div className="min-w-0">
                    <h3 className="text-base font-bold text-slate-900">
                      {item.title}
                    </h3>
                    <p className="mt-1 text-sm leading-6 text-slate-500">
                      {item.description}
                    </p>
                  </div>
                </div>

                <button
                  type="button"
                  onClick={() => toggleSetting(item.key)}
                  disabled={loading || Boolean(savingKey)}
                  className={`relative h-7 w-12 shrink-0 rounded-full transition ${
                    enabled ? "bg-emerald-600" : "bg-slate-300"
                  } disabled:cursor-not-allowed disabled:opacity-60`}
                  aria-pressed={enabled}
                  title={enabled ? "Disable" : "Enable"}
                >
                  <span
                    className={`absolute top-1 flex h-5 w-5 items-center justify-center rounded-full bg-white shadow transition ${
                      enabled ? "left-6" : "left-1"
                    }`}
                  >
                    {saving && (
                      <Loader2
                        size={12}
                        className="animate-spin text-slate-500"
                      />
                    )}
                  </span>
                </button>
              </div>

              <div className="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 text-xs font-medium">
                <span
                  className={enabled ? "text-emerald-700" : "text-slate-500"}
                >
                  {enabled ? "Enabled" : "Disabled"}
                </span>
                <span className="text-slate-400">
                  {enabled ? "Requests allowed" : "Requests blocked"}
                </span>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};

export default SettingsScreen;
