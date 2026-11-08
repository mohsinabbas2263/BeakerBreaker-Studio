"use client";

import { useState } from "react";
import useSWR, { mutate } from "swr";
import Shell from "@/components/layout/Shell";
import { api } from "@/lib/api";
import { fetcher } from "@/lib/fetcher";
import { beakySay } from "@/components/ui/Beaky";
import type { SafetyTiersResponse, SafetyStartResponse, SafetySubmitResponse } from "@/lib/api";

const TIER_TITLES = [
  "Basic Lab Rules",
  "Chemical Handling",
  "Emergency Procedures",
  "Advanced Safety",
];

type Phase = "overview" | "quiz" | "result";

export default function SafetyPage() {
  const { data, isLoading } = useSWR<SafetyTiersResponse>("/safety/tiers", fetcher);
  const [phase, setPhase]   = useState<Phase>("overview");
  const [tierNum, setTierNum]     = useState(1);
  const [quiz, setQuiz]           = useState<SafetyStartResponse | null>(null);
  const [answers, setAnswers]     = useState<Record<number, number>>({});
  const [result, setResult]       = useState<SafetySubmitResponse | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function startTier(tier: number) {
    const res = await api.post<SafetyStartResponse>(`/safety/tiers/${tier}/start`);
    setQuiz(res);
    setAnswers({});
    setTierNum(tier);
    setPhase("quiz");
  }

  async function submitTier() {
    if (!quiz) return;
    setSubmitting(true);
    try {
      // The options were shown shuffled; _safety_session lets the server map each
      // chosen position back to the stored answer key (FR-02.5).
      const res = await api.post<SafetySubmitResponse>(`/safety/tiers/${tierNum}/submit`, {
        answers,
        _safety_session: quiz._safety_session,
      });
      setResult(res);
      setPhase("result");
      mutate("/safety/tiers");
      if (res.passed) {
        beakySay(res.certificate ? "🎉 All 4 tiers passed! Certificate issued!" : `Tier ${tierNum} passed! 🛡️ Great job!`);
      } else {
        beakySay(`Score: ${res.score}% — need ${res.pass_mark}% to pass. Try again! 💪`);
      }
    } finally {
      setSubmitting(false);
    }
  }

  if (isLoading) return <Shell><div className="flex justify-center py-24 text-4xl animate-spin">⚗️</div></Shell>;

  const tiers = data?.tiers ?? [];

  /* ── Overview ── */
  if (phase === "overview") return (
    <Shell>
      <div className="p-4 md:p-6 max-w-lg mx-auto">
        <h1 className="font-fredoka font-600 text-2xl text-ink mb-1">Safety Training 🛡️</h1>
        <p className="text-sub font-700 text-sm mb-6">Pass all 4 tiers to earn your Safety Certificate.</p>

        {data?.certificate && (
          <div className="rounded-2xl p-4 mb-6 text-center"
               style={{ background: "linear-gradient(135deg,#FFF9C4,#FFD54F)" }}>
            <span className="text-4xl">🏅</span>
            <p className="font-fredoka font-600 text-base text-ink mt-2">Safety Certificate Earned!</p>
          </div>
        )}

        <div className="flex flex-col gap-3">
          {tiers.map(tier => (
            <div key={tier.tier_number}
                 className={`bg-white rounded-card shadow-card p-4 ${!tier.available ? "opacity-50" : ""}`}>
              <div className="flex items-center gap-3">
                <div className={`w-12 h-12 rounded-2xl flex items-center justify-center text-2xl flex-shrink-0
                                 ${tier.passed ? "bg-mint" : tier.available ? "bg-sky" : "bg-[#F5F7FA]"}`}>
                  {tier.passed ? "✅" : tier.available ? "🛡️" : "🔒"}
                </div>
                <div className="flex-1">
                  <p className="font-fredoka font-600 text-base text-ink">Tier {tier.tier_number}: {tier.title}</p>
                  <p className="text-xs text-sub font-700">
                    {tier.passed
                      ? `Passed — best score ${tier.best_score}%`
                      : `${tier.question_count} questions · pass mark ${tier.pass_mark}%`}
                  </p>
                </div>
                {tier.available && !tier.passed && (
                  <button onClick={() => startTier(tier.tier_number)}
                          className="px-4 py-2 rounded-xl font-nunito font-800 text-sm text-white
                                     transition-all hover:scale-105"
                          style={{ background: "linear-gradient(135deg,#4FC3F7,#29B6F6)" }}>
                    {tier.best_score ? "Retry" : "Start"}
                  </button>
                )}
                {tier.available && tier.passed && (
                  <button onClick={() => startTier(tier.tier_number)}
                          className="px-4 py-2 rounded-xl font-nunito font-800 text-sm text-sub
                                     bg-sky hover:bg-blue/20 transition-colors">
                    Retry
                  </button>
                )}
              </div>
            </div>
          ))}
        </div>
      </div>
    </Shell>
  );

  /* ── Quiz ── */
  if (phase === "quiz" && quiz) return (
    <Shell>
      <div className="p-4 md:p-6 max-w-lg mx-auto">
        <div className="flex items-center gap-3 mb-6">
          <button onClick={() => setPhase("overview")} className="text-sub hover:text-ink font-800">←</button>
          <h1 className="font-fredoka font-600 text-xl text-ink">Tier {tierNum}: {quiz.title}</h1>
        </div>

        {/* Progress */}
        <div className="mb-5">
          <div className="flex justify-between text-xs font-800 text-sub mb-1.5">
            <span>{Object.keys(answers).length} of {quiz.questions.length} answered</span>
            <span>Pass mark: 80%</span>
          </div>
          <div className="h-2 bg-sky rounded-full overflow-hidden">
            <div className="h-full rounded-full transition-all duration-500"
                 style={{ width: `${(Object.keys(answers).length / quiz.questions.length) * 100}%`,
                          background: "linear-gradient(90deg,#4FC3F7,#29B6F6)" }} />
          </div>
        </div>

        <div className="flex flex-col gap-5">
          {quiz.questions.map((q, qi) => (
            <div key={q.id} className="bg-white rounded-card shadow-card p-4">
              <p className="font-nunito font-800 text-sm text-ink mb-3">
                <span className="text-blue-d font-900 mr-1">Q{qi + 1}.</span> {q.prompt}
              </p>
              <div className="flex flex-col gap-2">
                {q.options.map((opt, oi) => (
                  <button key={oi} onClick={() => setAnswers(a => ({ ...a, [q.id]: oi }))}
                          className={`text-left px-4 py-3 rounded-2xl border-2 font-nunito font-700 text-sm
                                      transition-all duration-200
                                      ${answers[q.id] === oi
                                        ? "border-blue bg-sky text-blue-d"
                                        : "border-transparent bg-[#F5F7FA] text-ink hover:bg-sky"}`}>
                    <span className="font-900 mr-2 text-blue-d">{String.fromCharCode(65 + oi)}.</span>
                    {opt}
                  </button>
                ))}
              </div>
            </div>
          ))}
        </div>

        <button onClick={submitTier}
                disabled={Object.keys(answers).length < quiz.questions.length || submitting}
                className="w-full mt-6 py-4 rounded-2xl font-fredoka font-600 text-lg text-white
                           transition-all hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed"
                style={{ background: "linear-gradient(135deg,#4CAF82,#2E7D57)",
                         boxShadow: "0 8px 24px rgba(76,175,82,.4)" }}>
          {submitting ? "Checking…" : "Submit Answers"}
        </button>
      </div>
    </Shell>
  );

  /* ── Result ── */
  if (phase === "result" && result) return (
    <Shell>
      <div className="p-4 md:p-6 max-w-lg mx-auto text-center">
        <div className="text-7xl mb-4">{result.passed ? "🎉" : "💪"}</div>
        <h1 className="font-fredoka font-600 text-2xl text-ink mb-2">
          {result.passed ? "Tier Passed!" : "Not Quite Yet"}
        </h1>
        <p className="text-sub font-700 mb-6">
          Your score: <span className="text-2xl font-900 text-ink">{result.score}%</span>
          <span className="text-xs ml-2">(pass mark: {result.pass_mark}%)</span>
        </p>

        {result.points_awarded > 0 && (
          <div className="inline-flex items-center gap-2 bg-lemon px-5 py-2.5 rounded-full mb-6">
            <span className="text-xl">⭐</span>
            <span className="font-fredoka font-600 text-base text-ink">+{result.points_awarded} XP earned!</span>
          </div>
        )}

        {result.certificate && (
          <div className="rounded-2xl p-5 mb-6"
               style={{ background: "linear-gradient(135deg,#FFF9C4,#FFD54F)" }}>
            <span className="text-4xl">🏅</span>
            <p className="font-fredoka font-600 text-base text-ink mt-2">Safety Certificate Issued!</p>
            <p className="text-xs text-sub font-700 mt-1">You can now access all experiments.</p>
          </div>
        )}

        <div className="flex gap-3 justify-center">
          <button onClick={() => setPhase("overview")}
                  className="px-6 py-3 rounded-2xl bg-sky font-nunito font-800 text-sm text-blue-d
                             hover:bg-blue/20 transition-colors">
            Back to Tiers
          </button>
          {!result.passed && (
            <button onClick={() => startTier(tierNum)}
                    className="px-6 py-3 rounded-2xl font-nunito font-800 text-sm text-white
                               transition-all hover:scale-105"
                    style={{ background: "linear-gradient(135deg,#4FC3F7,#29B6F6)" }}>
              Try Again
            </button>
          )}
        </div>
      </div>
    </Shell>
  );

  return null;
}
