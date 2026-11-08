"use client";

import { Suspense, useState } from "react";
import Image from "next/image";
import useSWR from "swr";
import TeacherShell from "@/components/layout/TeacherShell";
import ReadinessReportView, { ClassificationBadge } from "@/components/readiness/ReadinessReportView";
import ClassPicker, { NoClasses, useTeacherClass } from "@/components/teacher/ClassPicker";
import { useToast } from "@/components/ui/Toast";
import { api, type ClassReadinessResponse, type ReadinessReportResponse } from "@/lib/api";
import { avatarSrc } from "@/lib/avatars";
import { fetcher } from "@/lib/fetcher";

/**
 * wf_S12, UC-05. FR-08.2: a report is generated when the teacher asks, and FR-08.4
 * holds it back until the student has completed 3 experiments. Each new report is
 * kept, so this shows the latest.
 */
function Readiness() {
  const toast = useToast();
  const { classes, selected, select, isLoading } = useTeacherClass();
  const key = selected ? `/teacher/classes/${selected.id}/readiness` : null;
  const { data, error, mutate } = useSWR<ClassReadinessResponse>(key, fetcher);
  const [open, setOpen] = useState<number | null>(null);
  const [busy, setBusy] = useState<number | null>(null);

  async function generate(studentId: number) {
    setBusy(studentId);
    try {
      const res = await api.post<ReadinessReportResponse>(`/teacher/students/${studentId}/readiness`);
      await mutate();
      setOpen(studentId);
      toast(res.report.ai_generated ? "Report ready 🧠" : "Report ready (AI was unavailable, so the built-in rule classified it)", "success");
    } catch (err) {
      toast(err instanceof Error ? err.message : "Could not generate the report.", "error");
    } finally {
      setBusy(null);
    }
  }

  if (isLoading) return <div className="flex justify-center py-16 text-4xl animate-spin">⚗️</div>;
  if (classes.length === 0) return <NoClasses />;

  const required = data?.required ?? 3;

  return (
    <>
      <div className="bg-white rounded-card shadow-card p-4 flex flex-wrap items-center gap-4">
        <ClassPicker classes={classes} selected={selected} onSelect={select} />
        <p className="text-xs font-700 text-sub">A report needs {required} completed experiments.</p>
      </div>

      {error && !data && <p className="bg-coral rounded-2xl p-4 text-sm font-800 text-ink">Could not load this class.</p>}

      <ul className="flex flex-col gap-2.5">
        {data?.students.map(s => {
          const eligible = s.completed >= required;
          const expanded = open === s.id && s.report;
          return (
            <li key={s.id} className="bg-white rounded-card shadow-card">
              <div className="flex flex-wrap items-center gap-3 p-4">
                <Image src={avatarSrc(s.avatar_key)} alt="" width={40} height={40} className="rounded-full object-cover" />
                <div className="flex-1 min-w-[140px]">
                  <p className="font-800 text-ink">{s.display_name}</p>
                  <div className="flex items-center gap-2 mt-1">
                    <div className="w-20 h-1.5 bg-sky rounded-full overflow-hidden" aria-hidden>
                      <div className="h-full bg-green rounded-full" style={{ width: `${Math.min(100, (100 * s.completed) / required)}%` }} />
                    </div>
                    <span className="text-[11px] font-700 text-sub">
                      {Math.min(s.completed, required)} of {required} experiments
                    </span>
                  </div>
                </div>

                {s.report
                  ? <ClassificationBadge value={s.report.classification} />
                  : <span className="text-xs font-700 text-muted">No report yet</span>}

                <div className="flex gap-2">
                  {s.report && (
                    <button type="button" onClick={() => setOpen(o => (o === s.id ? null : s.id))} aria-expanded={open === s.id}
                            className="px-3 py-2 rounded-xl bg-sky text-blue-d text-xs font-800 hover:bg-blue/20">
                      {open === s.id ? "Hide" : "View"}
                    </button>
                  )}
                  <button type="button" onClick={() => generate(s.id)} disabled={!eligible || busy !== null}
                          title={eligible ? undefined : `Needs ${required - s.completed} more completed experiment(s)`}
                          className="px-3 py-2 rounded-xl bg-green hover:bg-green-d text-white text-xs font-800
                                     disabled:bg-muted/40 disabled:text-sub disabled:cursor-not-allowed transition-colors">
                    {busy === s.id ? "Generating…" : s.report ? "Generate new" : "Generate"}
                  </button>
                </div>
              </div>
              {expanded && (
                <div className="px-4 pb-4">
                  <ReadinessReportView report={s.report!} compact />
                </div>
              )}
            </li>
          );
        })}
      </ul>
      {data && data.students.length === 0 && (
        <p className="text-center text-sm text-sub font-700 py-10">No students in this class yet.</p>
      )}
    </>
  );
}

export default function TeacherReadinessPage() {
  return (
    <TeacherShell>
      <div className="p-4 md:p-6 space-y-4">
        <h1 className="font-fredoka font-600 text-2xl text-ink">AI Readiness 🧠</h1>
        <Suspense fallback={<div className="flex justify-center py-16 text-4xl animate-spin">⚗️</div>}>
          <Readiness />
        </Suspense>
      </div>
    </TeacherShell>
  );
}
