"use client";

import { Suspense, useMemo, useState } from "react";
import Image from "next/image";
import useSWR from "swr";
import TeacherShell from "@/components/layout/TeacherShell";
import ClassPicker, { NoClasses, useTeacherClass } from "@/components/teacher/ClassPicker";
import { avatarSrc } from "@/lib/avatars";
import { fetcher } from "@/lib/fetcher";
import type { ClassMonitorResponse, ClassStudent, ReadinessClassification } from "@/lib/api";

/**
 * wf_S11, FR-09.2: per student, points, experiments completed, safety certification
 * and last activity. FR-09.3 asks for updates within 5 seconds; until the live
 * channel (Pusher) is added, the table refreshes itself every 5 seconds.
 */
type Filter = "all" | "at_risk" | "uncertified";

const READINESS: Record<ReadinessClassification, { label: string; cls: string }> = {
  ready:      { label: "Ready",       cls: "bg-mint text-green-d" },
  developing: { label: "Approaching", cls: "bg-lemon text-ink" },
  not_ready:  { label: "Not ready",   cls: "bg-coral text-ink" },
};

function daysSince(date: string | null): number | null {
  if (!date) return null;
  const then = new Date(`${date}T00:00:00`);
  const today = new Date(); today.setHours(0, 0, 0, 0);
  return Math.round((today.getTime() - then.getTime()) / 86_400_000);
}

function lastActive(date: string | null): string {
  const d = daysSince(date);
  if (d === null) return "Never";
  if (d <= 0) return "Today";
  if (d === 1) return "Yesterday";
  return `${d} days ago`;
}

/** Same rule as the dashboard: inactive a week, never started, or a quiz under 80. */
function atRisk(s: ClassStudent): boolean {
  const d = daysSince(s.last_active_on);
  return d === null || d >= 7 || (s.last_quiz_score !== null && s.last_quiz_score < 80);
}

function Monitor() {
  const { classes, selected, select, isLoading: loadingClasses } = useTeacherClass();
  const { data, error } = useSWR<ClassMonitorResponse>(
    selected ? `/teacher/classes/${selected.id}/students` : null, fetcher, { refreshInterval: 5000 },
  );
  const [query, setQuery] = useState("");
  const [filter, setFilter] = useState<Filter>("all");

  const rows = useMemo(() => {
    const q = query.trim().toLowerCase();
    return (data?.students ?? [])
      .filter(s => !q || s.display_name.toLowerCase().includes(q))
      .filter(s => filter === "all" || (filter === "uncertified" ? !s.has_certificate : atRisk(s)));
  }, [data, query, filter]);

  if (loadingClasses) return <div className="flex justify-center py-16 text-4xl animate-spin">⚗️</div>;
  if (classes.length === 0) return <NoClasses />;

  return (
    <>
      <div className="bg-white rounded-card shadow-card p-4 flex flex-wrap items-center gap-3">
        <ClassPicker classes={classes} selected={selected} onSelect={select} />
        <input type="search" value={query} onChange={e => setQuery(e.target.value)} placeholder="Search a student"
               aria-label="Search a student"
               className="flex-1 min-w-[160px] px-3 py-2 rounded-xl bg-sky/50 border-2 border-transparent
                          focus:border-blue outline-none font-nunito font-700 text-sm" />
        <div className="flex gap-1 bg-sky/50 rounded-xl p-1" role="group" aria-label="Filter">
          {(["all", "at_risk", "uncertified"] as Filter[]).map(f => (
            <button key={f} type="button" onClick={() => setFilter(f)} aria-pressed={filter === f}
                    className={`px-3 py-1.5 rounded-lg text-xs font-800 transition-colors
                                ${filter === f ? "bg-white text-blue-d shadow-sm" : "text-sub hover:text-ink"}`}>
              {f === "all" ? "All" : f === "at_risk" ? "At risk" : "Uncertified"}
            </button>
          ))}
        </div>
      </div>

      {error && !data && (
        <p className="bg-coral rounded-2xl p-4 text-sm font-800 text-ink">Could not load this class.</p>
      )}

      <div className="bg-white rounded-card shadow-card overflow-x-auto">
        <table className="w-full text-sm min-w-[680px]">
          <thead>
            <tr className="bg-sky text-left text-[11px] uppercase tracking-wide text-sub">
              <th className="px-4 py-3 font-800">Student</th>
              <th className="px-3 py-3 font-800 text-right">XP</th>
              <th className="px-3 py-3 font-800 text-center">Experiments</th>
              <th className="px-3 py-3 font-800">Safety cert</th>
              <th className="px-3 py-3 font-800">AI readiness</th>
              <th className="px-4 py-3 font-800">Last active</th>
            </tr>
          </thead>
          <tbody>
            {rows.map(s => {
              const d = daysSince(s.last_active_on);
              return (
                <tr key={s.id} className="border-t border-black/5">
                  <td className="px-4 py-2.5">
                    <div className="flex items-center gap-2.5">
                      <Image src={avatarSrc(s.avatar_key)} alt="" width={32} height={32}
                             className="rounded-full object-cover flex-shrink-0" />
                      <div className="min-w-0">
                        <p className="font-800 text-ink truncate">{s.display_name}</p>
                        <p className="text-[11px] text-sub font-700">{s.rank_name}</p>
                      </div>
                    </div>
                  </td>
                  <td className="px-3 py-2.5 text-right font-fredoka font-600 text-orange-d">
                    {s.total_points.toLocaleString()}
                  </td>
                  <td className="px-3 py-2.5 text-center font-800 text-ink">{s.experiments_completed}</td>
                  <td className="px-3 py-2.5">
                    {s.has_certificate
                      ? <span className="px-2 py-0.5 rounded-full bg-mint text-green-d text-xs font-800">✓ Certified</span>
                      : <span className="text-xs font-800 text-sub">Tier {s.safety_tier_passed} of 4</span>}
                  </td>
                  <td className="px-3 py-2.5">
                    {s.readiness
                      ? <span className={`px-2 py-0.5 rounded-full text-xs font-800 ${READINESS[s.readiness].cls}`}>
                          {READINESS[s.readiness].label}
                        </span>
                      : <span className="text-xs text-muted font-700">No report yet</span>}
                  </td>
                  <td className={`px-4 py-2.5 text-xs font-800 ${d === null || d >= 7 ? "text-orange-d" : "text-sub"}`}>
                    {lastActive(s.last_active_on)}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
        {data && rows.length === 0 && (
          <p className="text-center text-sm text-sub font-700 py-10">
            {data.students.length === 0 ? "No students in this class yet." : "No student matches."}
          </p>
        )}
      </div>
      <p className="text-[11px] text-muted font-700">Updates every 5 seconds.</p>
    </>
  );
}

export default function ClassMonitorPage() {
  return (
    <TeacherShell>
      <div className="p-4 md:p-6 space-y-4">
        <h1 className="font-fredoka font-600 text-2xl text-ink">Class Monitor 📋</h1>
        <Suspense fallback={<div className="flex justify-center py-16 text-4xl animate-spin">⚗️</div>}>
          <Monitor />
        </Suspense>
      </div>
    </TeacherShell>
  );
}
