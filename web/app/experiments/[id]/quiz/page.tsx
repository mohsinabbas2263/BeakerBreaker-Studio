"use client";

import { useCallback, useEffect, useState } from "react";
import Link from "next/link";
import { useSearchParams, useParams } from "next/navigation";
import Shell from "@/components/layout/Shell";
import Confetti from "@/components/ui/Confetti";
import { beakySay } from "@/components/ui/Beaky";
import { useToast } from "@/components/ui/Toast";
import { api, ApiError, type Badge, type User } from "@/lib/api";
import { announceBadges } from "@/lib/celebrate";

/**
 * wf_S05, the knowledge quiz.
 *
 * FR-04.1  opens only after every step; the API answers 403 steps_incomplete before then.
 * FR-04.2  each answer comes back marked, with an explanation where it was wrong.
 * FR-04.3  points arrive once, on the first pass at 80 or above.
 * FR-04.5  a retry fetches a fresh order.
 * FR-02.5  this page sends only the chosen options. The score is the server's.
 */

interface QuizQuestion { id: number; prompt: string; options: string[] }

interface QuizShowResponse {
  experiment: { id: number; title: string };
  pass_mark: number;
  _quiz_session: string;
  questions: QuizQuestion[];
}

interface Feedback {
  question_id: number;
  chosen_option: number;
  correct: boolean;
  explanation: string | null;
  correct_option: number | null;
}

interface QuizSubmitResult {
  score: number;
  passed: boolean;
  pass_mark: number;
  points_awarded: number;
  feedback: Feedback[];
  may_reattempt: boolean;
  badges_awarded?: Badge[];
}

export default function QuizPage() {
  // Next 15: a page's params arrive as a Promise; the hook reads them directly.
  const { id } = useParams<{ id: string }>();
  const sessionParam = useSearchParams().get("session");
  const toast = useToast();

  const [quiz, setQuiz]         = useState<QuizShowResponse | null>(null);
  const [answers, setAnswers]   = useState<Record<number, number>>({});
  const [result, setResult]     = useState<QuizSubmitResult | null>(null);
  const [blocked, setBlocked]   = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const load = useCallback(async () => {
    setQuiz(null);
    setAnswers({});
    setResult(null);
    try {
      setQuiz(await api.get<QuizShowResponse>(`/experiments/${id}/quiz`));
    } catch (err) {
      setBlocked(err instanceof ApiError && err.code === "steps_incomplete"
        ? "Finish every step of the experiment first. The quiz opens after the last step."
        : (err instanceof Error ? err.message : "Could not load the quiz."));
    }
  }, [id]);

  useEffect(() => { load(); }, [load]);

  async function submit() {
    if (!quiz) return;
    setSubmitting(true);
    try {
      const res = await api.post<QuizSubmitResult>(`/experiments/${id}/quiz`, {
        answers,
        session_id: sessionParam ? Number(sessionParam) : undefined,
        _quiz_session: quiz._quiz_session,
      });
      setResult(res);
      announceBadges(res.badges_awarded, toast);
      window.scrollTo({ top: 0, behavior: "smooth" });

      if (res.points_awarded > 0) {
        beakySay(`+${res.points_awarded} XP! You're a real chemist! ⭐`);
        refreshStoredUser();
      } else if (res.passed) {
        beakySay("Passed again! Practice makes perfect. 💪");
      } else {
        beakySay("Not quite. Read the explanations and try again! 📖");
      }
    } catch (err) {
      toast(err instanceof Error ? err.message : "Could not submit. Check your connection.", "error");
    } finally {
      setSubmitting(false);
    }
  }

  if (blocked) {
    return (
      <Shell>
        <div className="p-6 max-w-lg mx-auto text-center">
          <p className="text-4xl mb-3">🔒</p>
          <p className="font-fredoka font-600 text-lg text-ink">{blocked}</p>
          <Link href={`/experiments/${id}/run`} className="text-blue-d font-800 text-sm mt-3 inline-block">Go to the experiment →</Link>
        </div>
      </Shell>
    );
  }

  if (!quiz) {
    return (
      <Shell>
        <div className="flex items-center justify-center min-h-[60vh] text-4xl animate-spin">⚗️</div>
      </Shell>
    );
  }

  const answered = Object.keys(answers).length;
  const total = quiz.questions.length;
  const feedbackFor = (qid: number) => result?.feedback.find(f => f.question_id === qid);

  return (
    <Shell>
      <Confetti fire={!!result?.passed} />
      <div className="p-4 md:p-6 max-w-lg mx-auto space-y-5">
        <div className="flex items-center gap-3">
          <Link href={`/experiments/${id}`} className="text-blue-d font-800 text-sm">‹ Exit Quiz</Link>
          <h1 className="font-fredoka font-600 text-lg text-ink truncate">{quiz.experiment.title}</h1>
        </div>

        {/* Result */}
        {result && (
          <div className="bg-white rounded-card shadow-card p-5 text-center relative overflow-hidden">
            <div className="mx-auto w-28 h-28 rounded-full flex items-center justify-center"
                 style={{ background: `conic-gradient(${result.passed ? "#4CAF82" : "#FF8A50"} ${result.score * 3.6}deg, #E8F4FD 0deg)` }}>
              <div className="w-20 h-20 rounded-full bg-white flex items-center justify-center">
                <span className="font-fredoka font-600 text-2xl text-ink">{result.score}%</span>
              </div>
            </div>
            <p className={`font-fredoka font-600 text-xl mt-3 ${result.passed ? "text-green-d" : "text-orange-d"}`}>
              {result.passed ? "Passed! 🎉" : `Not yet. You need ${result.pass_mark}%.`}
            </p>
            {result.points_awarded > 0 && (
              <>
                <p className="text-sm font-800 text-orange-d mt-1">+{result.points_awarded} XP earned</p>
                <span className="xp-float absolute left-1/2 top-10 -translate-x-1/2 font-fredoka font-600 text-2xl text-orange-d">
                  +{result.points_awarded} XP
                </span>
              </>
            )}
            {result.passed && result.points_awarded === 0 && (
              <p className="text-xs font-700 text-sub mt-1">XP for this experiment was already earned on your first pass.</p>
            )}
            <div className="flex gap-2 mt-4">
              <button onClick={load}
                      className="flex-1 py-3 rounded-2xl bg-sky text-blue-d font-fredoka font-600">
                🔁 Try again
              </button>
              <Link href="/experiments"
                    className="flex-1 py-3 rounded-2xl text-white font-fredoka font-600"
                    style={{ background: "linear-gradient(135deg,#4FC3F7,#29B6F6)" }}>
                More experiments
              </Link>
            </div>
          </div>
        )}

        {/* Progress */}
        {!result && (
          <div>
            <div className="flex justify-between text-xs font-800 text-sub mb-1.5">
              <span>{answered} of {total} answered</span>
              <span>Pass mark: {quiz.pass_mark}%</span>
            </div>
            <div className="h-2 bg-sky rounded-full overflow-hidden">
              <div className="h-full rounded-full transition-all duration-500"
                   style={{ width: `${total ? (answered / total) * 100 : 0}%`, background: "linear-gradient(90deg,#9575CD,#7E57C2)" }} />
            </div>
          </div>
        )}

        {/* Questions */}
        <div className="flex flex-col gap-4">
          {quiz.questions.map((q, qi) => {
            const fb = feedbackFor(q.id);
            return (
              <fieldset key={q.id} className="bg-white rounded-card shadow-card p-4" disabled={!!result}>
                <legend className="sr-only">Question {qi + 1}</legend>
                <p className="font-nunito font-800 text-sm text-ink mb-3">
                  <span className="text-purple font-900 mr-1">Q{qi + 1}.</span> {q.prompt}
                </p>
                <div className="flex flex-col gap-2">
                  {q.options.map((opt, oi) => {
                    const chosen = answers[q.id] === oi;
                    const isRight = fb && (fb.correct ? fb.chosen_option === oi : fb.correct_option === oi);
                    const isWrong = fb && !fb.correct && fb.chosen_option === oi;
                    return (
                      <label key={oi}
                             className={`flex items-center gap-2.5 px-3 py-2.5 rounded-xl border-2 cursor-pointer text-sm font-700
                                         transition-colors
                                         ${isRight ? "border-green bg-mint" : isWrong ? "border-red bg-coral"
                                           : chosen ? "border-purple bg-[#F3E5F5]" : "border-sky hover:border-blue/40"}`}>
                        <input type="radio" name={`q-${q.id}`} className="accent-purple"
                               checked={chosen}
                               onChange={() => setAnswers(a => ({ ...a, [q.id]: oi }))} />
                        <span className="flex-1">{opt}</span>
                        {isRight && <span aria-label="correct">✅</span>}
                        {isWrong && <span aria-label="incorrect">❌</span>}
                      </label>
                    );
                  })}
                </div>
                {fb && !fb.correct && fb.explanation && (
                  <p className="mt-3 text-xs font-700 text-ink bg-lemon rounded-xl p-3 leading-relaxed">
                    💡 {fb.explanation}
                  </p>
                )}
              </fieldset>
            );
          })}
        </div>

        {!result && (
          <button onClick={submit} disabled={submitting || answered < total}
                  className="w-full py-4 rounded-card font-fredoka font-600 text-white text-lg disabled:opacity-50"
                  style={{ background: "linear-gradient(135deg,#9575CD,#7E57C2)" }}>
            {submitting ? "Marking…" : answered < total ? `Answer all ${total} questions` : "Submit answers"}
          </button>
        )}
      </div>
    </Shell>
  );
}

/** Keep the sidebar's XP in step after an award. */
async function refreshStoredUser() {
  try {
    const me = await api.get<{ user: User }>("/me");
    localStorage.setItem("bb_user", JSON.stringify(me.user));
  } catch { /* the next sign in refreshes it anyway */ }
}
