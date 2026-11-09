"use client";

import { useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { beakySay } from "@/components/ui/Beaky";
import { useToast } from "@/components/ui/Toast";
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
 * The game runner's state and moves (docs/experiment-engine.md), shared by the
 * standard runner (wf_S04) and AR mode, so both offer exactly the same controls
 * (FR-05.5) and every move goes to the same server judge (FR-02.5).
 *
 * FR-03.4  steps come in order; the server refuses a step out of order.
 * FR-03.5  each move is saved as it happens, so a refresh resumes mid-step (NFR-05).
 * FR-03.7  ask() answers about the current step; with the AI service down the API
 *          answers with the pre-authored line (NFR-06), as does every avatar line.
 */
export type Progress = StepActionResponse["progress"];
export type PresentationMode = "standard" | "ar";

export function useGameRunner(id: string | null, mode: PresentationMode, enabled = true) {
  const router = useRouter();
  const toast = useToast();

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
    if (!id || !enabled) return;
    let cancelled = false;
    (async () => {
      try {
        const d = await api.get<ExperimentDetailResponse>(`/experiments/${id}`);
        if (cancelled) return;
        if (!d.access.allowed) {
          router.replace(`/experiments/${id}`);
          return;
        }
        const s = await api.post<StartSessionResponse>(`/experiments/${id}/start`, { presentation_mode: mode });
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
  }, [id, mode, enabled, router]);

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
    if (!current || !sessionId || busy || !id) return;
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
        const s = await api.post<StartSessionResponse>(`/experiments/${id}/start`, { presentation_mode: mode });
        setDone(s.session.completed_step_ids?.length ?? 0);
      } else {
        toast(err instanceof Error ? err.message : "Could not save that move. Check your connection.", "error");
      }
    } finally {
      setBusy(false);
    }
  }

  async function ask(e?: React.FormEvent) {
    e?.preventDefault();
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

  return {
    detail, sessionId, steps, total, current, done, pct, error, busy, finished,
    line, mood, moodKey, progress, reveal, lastMistake,
    answer, question, setQuestion, asking, act, ask,
  };
}
