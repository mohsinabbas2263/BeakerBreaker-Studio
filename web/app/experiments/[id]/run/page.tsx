"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import Link from "next/link";
import Image from "next/image";
import { useRouter } from "next/navigation";
import Shell from "@/components/layout/Shell";
import Confetti from "@/components/ui/Confetti";
import { beakySay } from "@/components/ui/Beaky";
import { useToast } from "@/components/ui/Toast";
import { useAuth } from "@/lib/auth";
import { avatarSrc } from "@/lib/avatars";
import {
  api,
  ApiError,
  type ExperimentDetailResponse,
  type GuidanceResponse,
  type StartSessionResponse,
  type StepProgressResponse,
} from "@/lib/api";

/**
 * wf_S04, the step runner.
 *
 * FR-03.4  steps come in their declared order and the next one opens only when
 *          the current one is done; the server refuses a step out of order.
 * FR-03.5  each completion is written to the server the moment it happens, so a
 *          refresh resumes at the same step (NFR-05).
 * FR-03.7  guidance for the current step comes from the API. When the AI service
 *          is down the API answers with the pre-authored line (NFR-06), so this
 *          page never has a "no guidance" state.
 */

export default function ExperimentRunnerPage({ params }: { params: { id: string } }) {
  const { id } = params;
  const router = useRouter();
  const toast = useToast();
  const { user } = useAuth();

  const [detail, setDetail]       = useState<ExperimentDetailResponse | null>(null);
  const [sessionId, setSessionId] = useState<number | null>(null);
  const [doneIds, setDoneIds]     = useState<number[]>([]);
  const [error, setError]         = useState<string | null>(null);
  const [saving, setSaving]       = useState(false);
  const [finished, setFinished]   = useState(false);

  const [guidance, setGuidance]   = useState<GuidanceResponse | null>(null);
  const [question, setQuestion]   = useState("");
  const [asking, setAsking]       = useState(false);
  const [dragOver, setDragOver]   = useState(false);

  // Load the experiment, then open or resume a session (FR-03.5).
  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const d = await api.get<ExperimentDetailResponse>(`/experiments/${id}`);
        if (cancelled) return;
        if (!d.access.allowed) {
          router.replace(`/experiments/${id}`);
          return;
        }
        const s = await api.post<StartSessionResponse>(`/experiments/${id}/start`, { presentation_mode: "standard" });
        if (cancelled) return;
        setDetail(d);
        setSessionId(s.session.id);
        setDoneIds(s.session.completed_step_ids ?? []);
      } catch (err) {
        if (!cancelled) setError(err instanceof Error ? err.message : "Could not open this experiment.");
      }
    })();
    return () => { cancelled = true; };
  }, [id, router]);

  const steps = useMemo(() => detail?.steps ?? [], [detail]);
  const stepIndex = Math.min(doneIds.length, Math.max(steps.length - 1, 0));
  const current = steps[stepIndex];
  const total = steps.length;
  const progressPct = total ? Math.round((doneIds.length / total) * 100) : 0;

  // Fetch the opener for each new step.
  useEffect(() => {
    if (!current || finished) return;
    let cancelled = false;
    setGuidance(null);
    api.post<GuidanceResponse>(`/steps/${current.id}/guidance`, {})
      .then(g => { if (!cancelled) setGuidance(g); })
      .catch(() => { /* the API always answers with text; a network failure just leaves the tip empty */ });
    return () => { cancelled = true; };
  }, [current, finished]);

  const completeStep = useCallback(async () => {
    if (!current || !sessionId || saving) return;
    setSaving(true);
    try {
      const res = await api.post<StepProgressResponse>(`/sessions/${sessionId}/steps`, { step_id: current.id });
      setDoneIds(res.session.completed_step_ids ?? [...doneIds, current.id]);

      if (res.quiz_unlocked) {
        setFinished(true);
        beakySay("Brilliant work, scientist! Time for the quiz! 🎉");
      } else {
        toast(`Step ${current.step_order} done!`, "success");
      }
    } catch (err) {
      if (err instanceof ApiError && err.code === "out_of_order") {
        // Another tab moved ahead or fell behind; reload the true progress.
        toast("Your progress was updated in another window. Reloading…", "info");
        const s = await api.post<StartSessionResponse>(`/experiments/${id}/start`, { presentation_mode: "standard" });
        setDoneIds(s.session.completed_step_ids ?? []);
      } else {
        toast(err instanceof Error ? err.message : "Could not save this step. Check your connection.", "error");
      }
    } finally {
      setSaving(false);
    }
  }, [current, sessionId, saving, doneIds, id, toast]);

  async function ask(e: React.FormEvent) {
    e.preventDefault();
    if (!current || !question.trim()) return;
    setAsking(true);
    try {
      const g = await api.post<GuidanceResponse>(`/steps/${current.id}/guidance`, { question: question.trim() });
      setGuidance(g);
      setQuestion("");
    } catch {
      toast("Could not reach your guide just now. Try again.", "error");
    } finally {
      setAsking(false);
    }
  }

  if (error) {
    return (
      <Shell>
        <div className="p-6 max-w-lg mx-auto text-center">
          <p className="text-4xl mb-3">⚠️</p>
          <p className="font-fredoka font-600 text-lg text-ink">{error}</p>
          <Link href={`/experiments/${id}`} className="text-blue-d font-800 text-sm mt-3 inline-block">← Back</Link>
        </div>
      </Shell>
    );
  }

  if (!detail || !current) {
    return (
      <Shell>
        <div className="flex items-center justify-center min-h-[60vh] text-4xl animate-spin">⚗️</div>
      </Shell>
    );
  }

  const exp = detail.experiment;
  const avatar = avatarSrc(user?.avatar_key);

  if (finished) {
    return (
      <Shell>
        <Confetti fire />
        <div className="p-4 md:p-6 max-w-lg mx-auto text-center space-y-5">
          <div className="text-7xl mt-6">{exp.emoji || "🧪"}</div>
          <h1 className="font-fredoka font-600 text-2xl text-ink">Experiment complete!</h1>
          <p className="text-sub font-700 text-sm">
            You finished all {total} steps of {exp.title}. Pass the quiz at 80% to earn {exp.base_points} XP.
          </p>
          <button
            onClick={() => router.push(`/experiments/${id}/quiz?session=${sessionId}`)}
            className="w-full py-4 rounded-card font-fredoka font-600 text-white text-lg"
            style={{ background: "linear-gradient(135deg,#9575CD,#7E57C2)", boxShadow: "0 8px 24px rgba(149,117,205,.4)" }}>
            📝 Take the Quiz
          </button>
          <Link href="/experiments" className="block text-sub font-800 text-sm">Back to experiments</Link>
        </div>
      </Shell>
    );
  }

  return (
    <Shell>
      <div className="p-4 md:p-6 max-w-3xl mx-auto space-y-4">
        {/* Header */}
        <div className="flex items-center gap-3">
          <Link href={`/experiments/${id}`} className="text-blue-d font-800 text-sm">‹ Exit</Link>
          <h1 className="font-fredoka font-600 text-lg text-ink truncate">{exp.emoji} {exp.title}</h1>
        </div>

        {/* Progress */}
        <div className="bg-white rounded-card shadow-card p-4">
          <div className="flex justify-between text-xs font-800 text-sub mb-1.5">
            <span>Step {stepIndex + 1} of {total}</span>
            <span>{progressPct}%</span>
          </div>
          <div className="h-2.5 bg-sky rounded-full overflow-hidden"
               role="progressbar" aria-valuenow={progressPct} aria-valuemin={0} aria-valuemax={100}>
            <div className="h-full rounded-full transition-all duration-500"
                 style={{ width: `${progressPct}%`, background: "linear-gradient(90deg,#4FC3F7,#4CAF82)" }} />
          </div>
          <div className="flex gap-1.5 mt-2.5 justify-center">
            {steps.map((s, i) => (
              <span key={s.id}
                    className={`h-2 rounded-full transition-all ${i < doneIds.length ? "w-2 bg-green" : i === stepIndex ? "w-5 bg-blue" : "w-2 bg-sky"}`} />
            ))}
          </div>
        </div>

        <div className="grid md:grid-cols-[1fr_260px] gap-4">
          <div className="space-y-4">
            {/* Coach */}
            <div className="bg-white rounded-card shadow-card p-4 flex gap-3">
              <Image src={avatar} alt="" width={48} height={48}
                     className="rounded-full border-3 border-sky object-cover w-12 h-12 flex-shrink-0" />
              <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2">
                  <span className="font-fredoka font-600 text-sm text-ink">Your guide</span>
                  {guidance && (
                    <span className={`text-[10px] font-900 px-2 py-0.5 rounded-full
                                      ${guidance.source === "ai" ? "bg-sky text-blue-d" : "bg-lemon text-orange-d"}`}>
                      {guidance.source === "ai" ? "AI" : "Lab notes"}
                    </span>
                  )}
                </div>
                <p className="text-sm text-ink font-700 mt-1 leading-relaxed" aria-live="polite">
                  {guidance ? guidance.text : "Thinking…"}
                </p>
                <form onSubmit={ask} className="flex gap-2 mt-3">
                  <label htmlFor="ask" className="sr-only">Ask about this step</label>
                  <input id="ask" value={question} onChange={e => setQuestion(e.target.value)}
                         maxLength={300} placeholder="Ask about this step…"
                         className="flex-1 min-w-0 px-3 py-2 rounded-xl border-2 border-blue/20 bg-sky
                                    text-sm font-700 focus:outline-none focus:border-blue" />
                  <button type="submit" disabled={asking || !question.trim()}
                          className="px-3 py-2 rounded-xl bg-blue-d text-white text-sm font-800 disabled:opacity-50">
                    {asking ? "…" : "Ask"}
                  </button>
                </form>
              </div>
            </div>

            {/* Task */}
            <div className="bg-white rounded-card shadow-card p-4 flex gap-3">
              <span className="w-9 h-9 rounded-full bg-blue text-white font-900 flex items-center justify-center flex-shrink-0">
                {current.step_order}
              </span>
              <div>
                <p className="font-fredoka font-600 text-base text-ink">{current.title}</p>
                <p className="text-sm text-sub font-700 mt-1 leading-relaxed">{current.instruction_text}</p>
              </div>
            </div>

            {/* Science */}
            {exp.chemical_equation && (
              <div className="bg-lemon rounded-card p-4">
                <p className="font-fredoka font-600 text-xs text-orange-d uppercase tracking-wide">🔬 The science</p>
                <p className="font-fredoka text-ink mt-1">{exp.chemical_equation}</p>
              </div>
            )}
          </div>

          {/* Bench: drag the glowing item to the drop zone, or press the button. */}
          <div className="bg-white rounded-card shadow-card p-4 flex flex-col gap-3">
            <div
              onDragOver={e => { e.preventDefault(); setDragOver(true); }}
              onDragLeave={() => setDragOver(false)}
              onDrop={e => { e.preventDefault(); setDragOver(false); completeStep(); }}
              className={`rounded-2xl border-3 border-dashed h-32 flex flex-col items-center justify-center text-center
                          transition-colors ${dragOver ? "border-green bg-mint" : "border-blue/40 bg-sky"}`}>
              <span className="text-4xl" aria-hidden>{exp.emoji || "⚗️"}</span>
              <span className="text-[11px] font-900 text-sub uppercase tracking-wide mt-1">Drop here</span>
            </div>

            <div className="flex flex-wrap gap-2 justify-center">
              {steps.map((s, i) => {
                const state = i < doneIds.length ? "placed" : i === stepIndex ? "active" : "queued";
                return (
                  <div key={s.id}
                       draggable={state === "active"}
                       onDragStart={e => e.dataTransfer.setData("text/plain", String(s.id))}
                       className={`w-12 h-12 rounded-xl flex items-center justify-center text-2xl select-none
                                   ${state === "active" ? "bg-sky bench-active cursor-grab" : ""}
                                   ${state === "placed" ? "bg-mint opacity-60" : ""}
                                   ${state === "queued" ? "bg-[#F5F7FA] opacity-40" : ""}`}
                       aria-hidden>
                    {state === "placed" ? "✓" : (s.item_emoji || "🧪")}
                  </div>
                );
              })}
            </div>

            <button onClick={completeStep} disabled={saving}
                    className="w-full py-3.5 rounded-2xl font-fredoka font-600 text-white text-base
                               transition-transform hover:scale-[1.02] disabled:opacity-60"
                    style={{ background: "linear-gradient(135deg,#4CAF82,#2E7D57)", boxShadow: "0 6px 20px rgba(76,175,130,.35)" }}>
              {saving ? "Saving…" : `👉 ${current.action_label || "Done with this step"}`}
            </button>
            <p className="text-[11px] text-muted font-700 text-center">Your progress saves after every step.</p>
          </div>
        </div>
      </div>
    </Shell>
  );
}
