"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import Image from "next/image";
import { useRouter } from "next/navigation";
import Shell from "@/components/layout/Shell";
import GameStep from "@/components/game/GameStep";
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
  type Mood,
  type StartSessionResponse,
  type StepActionResponse,
} from "@/lib/api";

/**
 * wf_S04, the game runner (docs/experiment-engine.md).
 *
 * FR-02.5  the page sends what the student did; the server judges every move.
 * FR-03.4  steps come in order; the server refuses a step out of order.
 * FR-03.5  each move is saved as it happens, so a refresh resumes mid-step (NFR-05).
 * FR-03.7  Ask AI answers about the current step; with the AI service down the API
 *          answers with the pre-authored line (NFR-06), as does every avatar line.
 */
type Progress = StepActionResponse["progress"];

export default function ExperimentRunnerPage({ params }: { params: { id: string } }) {
  const { id } = params;
  const router = useRouter();
  const toast = useToast();
  const { user } = useAuth();

  const [detail, setDetail] = useState<ExperimentDetailResponse | null>(null);
  const [sessionId, setSessionId] = useState<number | null>(null);
  const [done, setDone] = useState(0);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [finished, setFinished] = useState(false);

  // The avatar: what it says, how it feels, and what the server has shown so far.
  const [line, setLine] = useState<string | null>(null);
  const [mood, setMood] = useState<Mood>("point");
  const [moodKey, setMoodKey] = useState(0);
  const [progress, setProgress] = useState<Progress>({});
  const [reveal, setReveal] = useState<Record<string, unknown> | null>(null);
  const [lastMistake, setLastMistake] = useState<string | null>(null);
  const [hinted, setHinted] = useState(false);

  // Ask AI (FR-03.7)
  const [answer, setAnswer] = useState<GuidanceResponse | null>(null);
  const [question, setQuestion] = useState("");
  const [asking, setAsking] = useState(false);

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
        setDone(s.session.completed_step_ids?.length ?? 0);
        // NFR-05: pick up inside the step where the student left off.
        if (d.resume?.step_state && d.resume.session_id === s.session.id) setProgress(d.resume.step_state.progress ?? {});
      } catch (err) {
        if (!cancelled) setError(err instanceof Error ? err.message : "Could not open this experiment.");
      }
    })();
    return () => { cancelled = true; };
  }, [id, router]);

  const steps = useMemo(() => detail?.steps ?? [], [detail]);
  const total = steps.length;
  const current = steps[Math.min(done, Math.max(total - 1, 0))];
  const pct = total ? Math.round((done / total) * 100) : 0;

  // Each new step: the avatar says its opening line, and the bench is cleared.
  useEffect(() => {
    if (!current) return;
    setLine(current.intro ?? current.instruction_text);
    setMood("point");
    setReveal(null);
    setLastMistake(null);
    setHinted(false);
    setAnswer(null);
  }, [current]);

  async function act(action: Record<string, string | number>) {
    if (!current || !sessionId || busy) return;
    setBusy(true);
    try {
      const res = await api.post<StepActionResponse>(`/sessions/${sessionId}/steps/${current.id}/actions`, { action, hinted });
      // A right move part-way through a step has no line of its own; never leave an
      // earlier telling-off on screen after the student has put it right.
      setLine(res.message ?? (res.outcome === "partial" ? "Good! Keep going. 👍" : line));
      setMood(res.mood);
      setMoodKey(k => k + 1);
      setReveal(res.reveal);
      setLastMistake(res.mistake_code);

      if (res.step_complete) {
        setProgress({});
        if (res.quiz_unlocked) {
          setFinished(true);
          beakySay("Brilliant work, scientist! Time for the quiz! 🎉");
        } else {
          // Let the student enjoy the success line before the next step opens.
          setTimeout(() => setDone(res.steps_completed), 1100);
        }
      } else {
        setProgress(res.progress ?? {});
      }
    } catch (err) {
      if (err instanceof ApiError && err.code === "out_of_order") {
        toast("Your progress was updated in another window. Reloading…", "info");
        const s = await api.post<StartSessionResponse>(`/experiments/${id}/start`, { presentation_mode: "standard" });
        setDone(s.session.completed_step_ids?.length ?? 0);
      } else {
        toast(err instanceof Error ? err.message : "Could not save that move. Check your connection.", "error");
      }
    } finally {
      setBusy(false);
    }
  }

  async function ask(e: React.FormEvent) {
    e.preventDefault();
    if (!current || !question.trim()) return;
    setAsking(true);
    setHinted(true);   // recorded with the next move, for the readiness report
    try {
      setAnswer(await api.post<GuidanceResponse>(`/steps/${current.id}/guidance`, { question: question.trim() }));
      setQuestion("");
      setMood("think");
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
    return <Shell><div className="flex items-center justify-center min-h-[60vh] text-4xl animate-spin">⚗️</div></Shell>;
  }

  const exp = detail.experiment;

  if (finished) {
    return (
      <Shell>
        <Confetti fire />
        <div className="p-4 md:p-6 max-w-lg mx-auto text-center space-y-5">
          <Image src={avatarSrc(user?.avatar_key)} alt="" width={96} height={96}
                 className="rounded-full mx-auto mt-6 border-4 border-white shadow-card mood-party" />
          <h1 className="font-fredoka font-600 text-2xl text-ink">Experiment complete!</h1>
          <p className="text-sub font-700 text-sm">
            You finished all {total} steps of {exp.title}. Pass the quiz at 80% to earn {exp.base_points} XP.
          </p>
          <button onClick={() => router.push(`/experiments/${id}/quiz?session=${sessionId}`)}
                  className="w-full py-4 rounded-card font-fredoka font-600 text-white text-lg"
                  style={{ background: "linear-gradient(135deg,#9575CD,#7E57C2)", boxShadow: "0 8px 24px rgba(149,117,205,.4)" }}>
            📝 Take the Quiz
          </button>
          <Link href="/experiments" className="block text-sub font-800 text-sm">Back to experiments</Link>
        </div>
      </Shell>
    );
  }

  const worried = mood === "worried";

  return (
    <Shell>
      <div className="p-4 md:p-6 max-w-3xl mx-auto space-y-4">
        <div className="flex items-center gap-3">
          <Link href={`/experiments/${id}`} className="text-blue-d font-800 text-sm">‹ Exit</Link>
          <h1 className="font-fredoka font-600 text-lg text-ink truncate">{exp.emoji} {exp.title}</h1>
        </div>

        {/* Progress */}
        <div className="bg-white rounded-card shadow-card p-4">
          <div className="flex justify-between text-xs font-800 text-sub mb-1.5">
            <span>Step {Math.min(done + 1, total)} of {total}</span><span>{pct}%</span>
          </div>
          <div className="h-2.5 bg-sky rounded-full overflow-hidden" role="progressbar" aria-valuenow={pct} aria-valuemin={0} aria-valuemax={100}>
            <div className="h-full rounded-full transition-all duration-500" style={{ width: `${pct}%`, background: "linear-gradient(90deg,#4FC3F7,#4CAF82)" }} />
          </div>
        </div>

        {/* The guide: the student's own avatar, with what it says now. */}
        <div className="flex items-start gap-3">
          <Image key={moodKey} src={avatarSrc(user?.avatar_key)} alt="Your guide" width={64} height={64}
                 className={`rounded-full border-4 border-white shadow-card object-cover w-16 h-16 flex-shrink-0 mood-${mood}`} />
          <div className={`relative flex-1 rounded-2xl rounded-tl-sm p-4 shadow-card ${worried ? "bg-coral" : mood === "oops" ? "bg-lemon" : "bg-white"}`}
               aria-live="polite">
            <p className="font-nunito font-800 text-[15px] text-ink leading-relaxed">{line}</p>
          </div>
        </div>

        {/* The step */}
        <div className="bg-white rounded-card shadow-card p-5 space-y-4">
          <div className="flex gap-3">
            <span className="w-9 h-9 rounded-full bg-blue text-white font-900 flex items-center justify-center flex-shrink-0">{current.step_order}</span>
            <div>
              <p className="font-fredoka font-600 text-base text-ink">{current.title}</p>
              <p className="text-sm text-sub font-700 mt-0.5 leading-relaxed">{current.instruction_text}</p>
            </div>
          </div>
          <GameStep key={current.id} step={current} progress={progress} reveal={reveal}
                    lastMistake={lastMistake} busy={busy} onAct={act} />
        </div>

        {/* Ask AI (FR-03.7) */}
        <details className="bg-white rounded-card shadow-card p-4 group">
          <summary className="cursor-pointer font-fredoka font-600 text-ink list-none flex items-center gap-2">
            <span aria-hidden>💬</span> Ask AI about this step
            <span className="ml-auto text-sub text-xs group-open:rotate-180 transition-transform" aria-hidden>▾</span>
          </summary>
          <form onSubmit={ask} className="flex gap-2 mt-3">
            <label htmlFor="ask" className="sr-only">Your question</label>
            <input id="ask" value={question} onChange={e => setQuestion(e.target.value)} maxLength={300}
                   placeholder="e.g. Why do we swirl the flask?"
                   className="flex-1 min-w-0 px-3 py-2 rounded-xl border-2 border-blue/20 bg-sky text-sm font-700 focus:outline-none focus:border-blue" />
            <button type="submit" disabled={asking || !question.trim()}
                    className="px-3 py-2 rounded-xl bg-blue-d text-white text-sm font-800 disabled:opacity-50">{asking ? "…" : "Ask"}</button>
          </form>
          {answer && (
            <div className="mt-3 bg-sky rounded-2xl p-3">
              <span className={`text-[10px] font-900 px-2 py-0.5 rounded-full ${answer.source === "ai" ? "bg-white text-blue-d" : "bg-lemon text-orange-d"}`}>
                {answer.source === "ai" ? "AI" : "Lab notes"}
              </span>
              <p className="text-sm text-ink font-700 mt-1.5 leading-relaxed">{answer.text}</p>
            </div>
          )}
        </details>

        {exp.chemical_equation && (
          <div className="bg-lemon rounded-card p-4">
            <p className="font-fredoka font-600 text-xs text-orange-d uppercase tracking-wide">🔬 The science</p>
            <p className="font-fredoka text-ink mt-1">{exp.chemical_equation}</p>
          </div>
        )}
        <p className="text-[11px] text-muted font-700 text-center">Every move saves as you go.</p>
      </div>
    </Shell>
  );
}
