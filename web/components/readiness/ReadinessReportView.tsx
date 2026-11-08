import type { ReadinessClassification, ReadinessReport } from "@/lib/api";

/** FR-08.3: the three classifications, by the names the requirements give them. */
export const CLASSIFICATION: Record<ReadinessClassification, { label: string; icon: string; cls: string }> = {
  ready:      { label: "Ready for Real Lab",    icon: "✅", cls: "bg-mint text-green-d" },
  developing: { label: "Approaching Readiness", icon: "📈", cls: "bg-lemon text-ink" },
  not_ready:  { label: "Not Ready",             icon: "💪", cls: "bg-coral text-ink" },
};

/** FR-08.1: the six dimensions. */
export const DIMENSIONS: { key: keyof ReadinessReport["dimensions"]; label: string; icon: string }[] = [
  { key: "procedural_accuracy",      label: "Procedural Accuracy",      icon: "🧭" },
  { key: "safety_compliance",        label: "Safety Compliance",        icon: "🛡️" },
  { key: "reagent_management",       label: "Reagent Management",       icon: "⚗️" },
  { key: "time_efficiency",          label: "Time Efficiency",          icon: "⏱️" },
  { key: "conceptual_understanding", label: "Conceptual Understanding", icon: "💡" },
  { key: "error_recovery",           label: "Error Recovery",           icon: "🔁" },
];

export function ClassificationBadge({ value, size = "sm" }: { value: ReadinessClassification; size?: "sm" | "lg" }) {
  const c = CLASSIFICATION[value];
  return (
    <span className={`inline-flex items-center gap-1 rounded-full font-800 ${c.cls}
                      ${size === "lg" ? "px-5 py-2 text-base font-fredoka font-600" : "px-2.5 py-0.5 text-xs"}`}>
      <span aria-hidden>{c.icon}</span>{c.label}
    </span>
  );
}

function barColour(v: number): string {
  if (v >= 70) return "linear-gradient(90deg,#4CAF82,#2E7D57)";
  if (v >= 40) return "linear-gradient(90deg,#FFD54F,#FF8A50)";
  return "linear-gradient(90deg,#FF8A50,#FF6D00)";
}

/** The narrative and the six bars; shared by the student page and the teacher's wf_S12. */
export default function ReadinessReportView({ report, compact = false }: { report: ReadinessReport; compact?: boolean }) {
  const estimated = new Set(report.estimated ?? []);

  return (
    <div className="space-y-4">
      <div className={`bg-white rounded-2xl ${compact ? "" : "shadow-card"} p-4`}>
        <p className="font-nunito font-700 text-sm text-ink leading-relaxed">{report.narrative}</p>
        <p className="text-[11px] text-muted font-700 mt-2">
          {new Date(report.generated_at).toLocaleDateString(undefined, { day: "numeric", month: "short", year: "numeric" })}
          {" · "}{report.ai_generated ? "Classified by AI" : "Classified by the built-in rule (AI unavailable)"}
        </p>
      </div>

      <div className={`grid gap-2.5 ${compact ? "sm:grid-cols-2" : ""}`}>
        {DIMENSIONS.map(d => {
          const v = report.dimensions[d.key];
          return (
            <div key={d.key} className={`bg-white rounded-2xl ${compact ? "border border-black/5" : "shadow-card"} p-3.5`}>
              <div className="flex justify-between items-baseline mb-1.5 gap-2">
                <span className="font-nunito font-800 text-sm text-ink">
                  <span aria-hidden>{d.icon} </span>{d.label}
                  {estimated.has(d.key) && <span className="text-muted" title="Estimated for now"> *</span>}
                </span>
                <span className="font-fredoka font-600 text-sm text-blue-d">{v}%</span>
              </div>
              <div className="h-2.5 bg-sky rounded-full overflow-hidden" role="progressbar"
                   aria-label={d.label} aria-valuenow={v} aria-valuemin={0} aria-valuemax={100}>
                <div className="h-full rounded-full transition-all duration-700" style={{ width: `${v}%`, background: barColour(v) }} />
              </div>
            </div>
          );
        })}
      </div>

      {estimated.size > 0 && (
        <p className="text-[11px] text-muted font-700">
          * Estimated from quiz and session history for now. These two become exact once experiments record every step.
        </p>
      )}
    </div>
  );
}
