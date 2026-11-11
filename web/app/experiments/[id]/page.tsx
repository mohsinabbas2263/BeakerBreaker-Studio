"use client";

import { useParams } from "next/navigation";
import useSWR from "swr";
import Link from "next/link";
import Shell from "@/components/layout/Shell";
import { type ExperimentDetailResponse } from "@/lib/api";
import { fetcher } from "@/lib/fetcher";

const LOCK_MSGS: Record<string, string> = {
  safety_tier_1_required:  "Complete Safety Tier 1 (Basic Lab Rules) before you can open this experiment.",
  safety_tier_required:    "You need a higher safety tier to open this experiment.",
  prerequisite_incomplete: "Complete the prerequisite experiment first.",
  unpublished:             "This experiment hasn't been published yet.",
  no_class:                "You are not in a class yet. Ask your school administrator to add you to one.",
  not_licensed:            "This experiment is not part of your class.",
};

export default function ExperimentDetailPage() {
  // Next 15: a page's params arrive as a Promise; the hook reads them directly.
  const { id } = useParams<{ id: string }>();
  const { data, isLoading } = useSWR<ExperimentDetailResponse>(`/experiments/${id}`, fetcher<ExperimentDetailResponse>);

  if (isLoading) {
    return (
      <Shell>
        <div className="flex items-center justify-center min-h-[60vh] text-4xl animate-spin">⚗️</div>
      </Shell>
    );
  }

  if (!data) {
    return (
      <Shell>
        <div className="p-6 text-center">
          <p className="text-sub font-700">Experiment not found.</p>
          <Link href="/experiments" className="text-blue-d font-800 text-sm mt-2 block">← Back</Link>
        </div>
      </Shell>
    );
  }

  const { experiment: exp, access, steps, resume } = data;
  const locked = !access.allowed;

  return (
    <Shell>
      <div className="p-4 md:p-6 max-w-2xl mx-auto space-y-5">

        {/* Back */}
        <Link href="/experiments" className="flex items-center gap-1 text-blue-d font-800 text-sm">
          ← Experiments
        </Link>

        {/* Header card */}
        <div className="rounded-[28px] p-6 text-white"
             style={{ background: exp.card_gradient || "linear-gradient(135deg,#4FC3F7,#9575CD)" }}>
          <div className="text-5xl mb-3">{exp.emoji}</div>
          <h1 className="font-fredoka font-600 text-2xl mb-1">{exp.title}</h1>
          <p className="text-sm opacity-90 font-700">{exp.topic}</p>

          <div className="flex gap-2 flex-wrap mt-4">
            <Chip label={`+${exp.base_points} XP`} />
            <Chip label={`⏱ ${exp.estimated_minutes} min`} />
            <Chip label={exp.difficulty} />
            {exp.ar_available && <Chip label="🥽 AR available" />}
          </div>
        </div>

        {/* Locked state */}
        {locked && (
          <div className="bg-coral rounded-card p-5 text-center border-2 border-orange/30">
            <p className="text-3xl mb-2">🔒</p>
            <p className="font-fredoka font-600 text-lg text-orange-d">Experiment Locked</p>
            <p className="text-sm text-sub font-700 mt-1">
              {LOCK_MSGS[access.code ?? ""] ?? (access.reason ?? "This experiment is not available yet.")}
            </p>
            {access.code === "safety_tier_1_required" && (
              <Link href="/safety"
                    className="inline-block mt-4 px-6 py-2.5 rounded-full font-fredoka font-600 text-white text-sm"
                    style={{ background: "linear-gradient(135deg,#FF8A50,#FF6D00)" }}>
                Go to Safety Training →
              </Link>
            )}
          </div>
        )}

        {/* Resume badge */}
        {!locked && resume && resume.steps_completed > 0 && (
          <div className="bg-mint rounded-card p-4 flex items-center gap-3">
            <span className="text-2xl">▶️</span>
            <div className="flex-1">
              <p className="font-fredoka font-600 text-green-d">Resume where you left off</p>
              <p className="text-xs text-sub font-700 mt-0.5">
                {resume.steps_completed} of {steps.length} steps done
              </p>
            </div>
            <Link href={`/experiments/${id}/run`}
                  className="px-4 py-2 rounded-full font-fredoka font-600 text-white text-sm"
                  style={{ background: "linear-gradient(135deg,#4CAF82,#2E7D57)" }}>
              Resume
            </Link>
          </div>
        )}

        {/* Chemical equation */}
        {exp.chemical_equation && (
          <div className="bg-white rounded-card shadow-card p-4">
            <p className="font-fredoka font-600 text-sm text-sub uppercase tracking-wide mb-2">⚗️ Reaction</p>
            <p className="font-fredoka text-ink text-lg">{exp.chemical_equation}</p>
            {exp.explanatory_note && (
              <p className="text-xs text-sub font-700 mt-2 leading-relaxed">{exp.explanatory_note}</p>
            )}
          </div>
        )}

        {/* Steps overview */}
        {steps.length > 0 && (
          <div className="bg-white rounded-card shadow-card p-4">
            <p className="font-fredoka font-600 text-sm text-sub uppercase tracking-wide mb-3">
              {steps.length} steps
            </p>
            <div className="space-y-3">
              {steps.map((step, i) => (
                <div key={step.id} className="flex items-start gap-3">
                  <span className="w-7 h-7 rounded-full bg-sky text-blue-d text-xs font-900
                                   flex items-center justify-center flex-shrink-0 mt-0.5">
                    {i + 1}
                  </span>
                  <div>
                    <p className="font-nunito font-800 text-sm text-ink">{step.title}</p>
                    <p className="text-xs text-sub font-700">{step.instruction_text}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Start / Resume CTA */}
        {!locked && (
          <div className="space-y-3">
            <Link href={`/experiments/${id}/run`}
                  className="block w-full py-4 rounded-card text-center font-fredoka font-600 text-white text-lg
                             transition-all duration-200 hover:opacity-90 hover:scale-[1.01]"
                  style={{ background: "linear-gradient(135deg,#4FC3F7,#29B6F6)",
                           boxShadow: "0 8px 24px rgba(79,195,247,.4)" }}>
              {resume && resume.steps_completed > 0 ? "▶️ Continue Experiment" : "🚀 Start Experiment"}
            </Link>

            {/* FR-05.1: AR entry point — only shown when the experiment supports it. */}
            {exp.ar_available && (
              <Link href={`/ar?exp=${id}`}
                    data-testid="try-ar-button"
                    className="block w-full py-3.5 rounded-card text-center font-fredoka font-600 text-white text-base
                               transition-all duration-200 hover:opacity-90"
                    style={{ background: "linear-gradient(135deg,#9575CD,#7E57C2)",
                             boxShadow: "0 6px 20px rgba(149,117,205,.4)" }}>
                🥽 Try in AR Mode
              </Link>
            )}
          </div>
        )}
      </div>
    </Shell>
  );
}

function Chip({ label }: { label: string }) {
  return (
    <span className="bg-white/20 rounded-full px-3 py-1 text-xs font-800">{label}</span>
  );
}
