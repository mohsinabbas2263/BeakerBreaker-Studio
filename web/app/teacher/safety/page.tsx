"use client";

import { Suspense } from "react";
import Image from "next/image";
import useSWR from "swr";
import TeacherShell from "@/components/layout/TeacherShell";
import ClassPicker, { NoClasses, useTeacherClass } from "@/components/teacher/ClassPicker";
import { avatarSrc } from "@/lib/avatars";
import { fetcher } from "@/lib/fetcher";
import type { SafetyReviewResponse, SafetyReviewStudent } from "@/lib/api";

/**
 * wf_S14, UC-26 and FR-02.9: the tier-by-tier pass matrix and each certificate,
 * visible to the teacher as soon as it is issued (refreshed every 5 seconds).
 */
const TIERS = [1, 2, 3, 4] as const;

function TierCell({ s, n }: { s: SafetyReviewStudent; n: 1 | 2 | 3 | 4 }) {
  const passed = s[`tier${n}_passed`];
  const score = s[`tier${n}_score`];
  if (passed) {
    return <span className="inline-flex flex-col items-center"><span className="text-green-d font-900" aria-label="passed">✓</span>
      <span className="text-[10px] text-sub font-700">{score}%</span></span>;
  }
  if (score !== null) {
    return <span className="inline-flex flex-col items-center"><span className="text-orange-d font-900" aria-label="not passed yet">✗</span>
      <span className="text-[10px] text-sub font-700">best {score}%</span></span>;
  }
  return <span className="text-muted" aria-label="not attempted">—</span>;
}

function status(s: SafetyReviewStudent) {
  if (s.certificate) return { text: "Issued", cls: "bg-mint text-green-d" };
  if (s.tier1_score === null) return { text: "Not started", cls: "bg-sky text-sub" };
  return { text: "Pending", cls: "bg-lemon text-ink" };
}

function Review() {
  const { classes, selected, select, isLoading } = useTeacherClass();
  const { data, error } = useSWR<SafetyReviewResponse>(
    selected ? `/teacher/classes/${selected.id}/safety` : null, fetcher, { refreshInterval: 5000 },
  );

  if (isLoading) return <div className="flex justify-center py-16 text-4xl animate-spin">⚗️</div>;
  if (classes.length === 0) return <NoClasses />;

  return (
    <>
      <div className="bg-white rounded-card shadow-card p-4 flex flex-wrap items-center gap-4">
        <ClassPicker classes={classes} selected={selected} onSelect={select} />
        {data && (
          <p className="text-sm font-800 text-sub">
            <span className="text-green-d">{data.summary.certified}</span> of {data.summary.total} students fully certified
          </p>
        )}
      </div>

      {error && !data && <p className="bg-coral rounded-2xl p-4 text-sm font-800 text-ink">Could not load this class.</p>}

      <div className="bg-white rounded-card shadow-card overflow-x-auto">
        <table className="w-full text-sm min-w-[640px]">
          <thead>
            <tr className="bg-sky text-left text-[11px] uppercase tracking-wide text-sub">
              <th className="px-4 py-3 font-800">Student</th>
              {TIERS.map(n => <th key={n} className="px-3 py-3 font-800 text-center">Tier {n}</th>)}
              <th className="px-4 py-3 font-800">Certificate</th>
            </tr>
          </thead>
          <tbody>
            {data?.students.map(s => {
              const st = status(s);
              return (
                <tr key={s.id} className="border-t border-black/5">
                  <td className="px-4 py-2.5">
                    <div className="flex items-center gap-2.5">
                      <Image src={avatarSrc(s.avatar_key)} alt="" width={30} height={30} className="rounded-full object-cover" />
                      <span className="font-800 text-ink">{s.display_name}</span>
                    </div>
                  </td>
                  {TIERS.map(n => <td key={n} className="px-3 py-2.5 text-center"><TierCell s={s} n={n} /></td>)}
                  <td className="px-4 py-2.5">
                    <span className={`px-2 py-0.5 rounded-full text-xs font-800 ${st.cls}`}>{st.text}</span>
                    {s.certificate && (
                      <p className="text-[10px] text-sub font-700 mt-1 break-all">
                        {s.certificate}{s.certificate_issued_on ? ` · ${s.certificate_issued_on}` : ""}
                      </p>
                    )}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
        {data && data.students.length === 0 && (
          <p className="text-center text-sm text-sub font-700 py-10">No students in this class yet.</p>
        )}
      </div>
    </>
  );
}

export default function SafetyReviewPage() {
  return (
    <TeacherShell>
      <div className="p-4 md:p-6 space-y-4">
        <h1 className="font-fredoka font-600 text-2xl text-ink">Safety Review 🛡️</h1>
        <Suspense fallback={<div className="flex justify-center py-16 text-4xl animate-spin">⚗️</div>}>
          <Review />
        </Suspense>
      </div>
    </TeacherShell>
  );
}
