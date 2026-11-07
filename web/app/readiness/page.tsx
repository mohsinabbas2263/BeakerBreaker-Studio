"use client";

import useSWR from "swr";
import Shell from "@/components/layout/Shell";
import { fetcher } from "@/lib/fetcher";
import type { ReadinessReportResponse } from "@/lib/api";

const DIM_LABELS: Record<string, string> = {
  procedural_accuracy:    "Procedural Accuracy",
  safety_compliance:      "Safety Compliance",
  reagent_management:     "Reagent Management",
  time_efficiency:        "Time Efficiency",
  conceptual_understanding: "Conceptual Understanding",
  error_recovery:         "Error Recovery",
};

const CLASS_COLOR: Record<string, string> = {
  ready:       "bg-mint text-green-d",
  developing:  "bg-lemon text-[#7a5a00]",
  not_ready:   "bg-coral text-orange-d",
};

const CLASS_LABEL: Record<string, string> = {
  ready:      "Lab Ready ✅",
  developing: "Developing 📈",
  not_ready:  "Needs Practice 💪",
};

export default function ReadinessPage() {
  const { data, isLoading, error } = useSWR<ReadinessReportResponse>("/readiness", fetcher);

  if (isLoading) return <Shell><div className="flex justify-center py-24 text-4xl animate-spin">⚗️</div></Shell>;

  if (error) {
    const code = error?.code;
    return (
      <Shell>
        <div className="p-6 max-w-lg mx-auto text-center">
          <span className="text-6xl">🧠</span>
          <h1 className="font-fredoka font-600 text-xl text-ink mt-4 mb-2">AI Readiness Report</h1>
          {code === "not_eligible"
            ? <p className="text-sub font-700 text-sm">Complete at least 3 experiments to unlock your readiness report.</p>
            : <p className="text-sub font-700 text-sm">Report not available yet.</p>}
        </div>
      </Shell>
    );
  }

  const r = data?.report;
  if (!r) return null;

  return (
    <Shell>
      <div className="p-4 md:p-6 max-w-lg mx-auto">
        <h1 className="font-fredoka font-600 text-2xl text-ink mb-1">AI Readiness Report 🧠</h1>
        <p className="text-xs text-muted font-700 mb-5">
          Generated {new Date(r.generated_at).toLocaleDateString()}
          {r.ai_generated && " · AI analysis"}
        </p>

        {/* Classification */}
        <div className={`rounded-2xl px-5 py-4 mb-5 text-center font-fredoka font-600 text-lg ${CLASS_COLOR[r.classification]}`}>
          {CLASS_LABEL[r.classification]}
        </div>

        {/* Narrative */}
        <div className="bg-white rounded-card shadow-card p-5 mb-5">
          <p className="font-nunito font-700 text-sm text-ink leading-relaxed">{r.narrative}</p>
        </div>

        {/* Dimensions */}
        <h2 className="font-fredoka font-600 text-lg text-ink mb-3">Your Dimensions</h2>
        <div className="flex flex-col gap-3">
          {Object.entries(r.dimensions).map(([key, val]) => (
            <div key={key} className="bg-white rounded-2xl shadow-card p-4">
              <div className="flex justify-between mb-2">
                <span className="font-nunito font-800 text-sm text-ink">{DIM_LABELS[key] ?? key}</span>
                <span className="font-fredoka font-600 text-sm text-blue-d">{val}%</span>
              </div>
              <div className="h-2.5 bg-sky rounded-full overflow-hidden">
                <div className="h-full rounded-full transition-all duration-700"
                     style={{ width: `${val}%`,
                              background: val >= 70 ? "linear-gradient(90deg,#4CAF82,#2E7D57)"
                                        : val >= 40 ? "linear-gradient(90deg,#FFD54F,#FF8A50)"
                                                    : "linear-gradient(90deg,#FF8A50,#FF6D00)" }} />
              </div>
            </div>
          ))}
        </div>
      </div>
    </Shell>
  );
}
