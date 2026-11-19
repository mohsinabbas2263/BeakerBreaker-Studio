"use client";

import useSWR from "swr";
import { fetcher } from "@/lib/fetcher";
import type { MissionsResponse } from "@/lib/api";

/**
 * FR-07.3: today's three missions. Progress comes from the server, which counts
 * what the student actually did today (Pakistan time); a new set arrives at midnight.
 */
export default function MissionsCard() {
  const { data } = useSWR<MissionsResponse>("/missions", fetcher, { refreshInterval: 30_000 });
  if (!data) return null;

  const done = data.missions.filter(m => m.completed).length;

  return (
    <section aria-labelledby="missions-title" className="bg-white rounded-card shadow-card p-4 mb-5">
      <div className="flex items-center justify-between mb-3">
        <h2 id="missions-title" className="font-fredoka font-600 text-lg text-ink">🎯 Today&apos;s Missions</h2>
        <span className="text-xs font-900 text-sub">{done}/{data.missions.length} done</span>
      </div>
      <ul className="space-y-2.5">
        {data.missions.map(m => {
          const pct = Math.round((m.progress / m.target) * 100);
          return (
            <li key={m.code} className={`rounded-2xl p-3 flex items-center gap-3 ${m.completed ? "bg-mint" : "bg-sky/60"}`}>
              <span className="text-2xl" aria-hidden>{m.completed ? "✅" : m.icon}</span>
              <div className="flex-1 min-w-0">
                <p className={`text-sm font-800 text-ink ${m.completed ? "line-through decoration-green-d/50" : ""}`}>{m.title}</p>
                {m.target > 1 && (
                  <div className="mt-1.5 h-1.5 bg-white rounded-full overflow-hidden" role="progressbar"
                       aria-valuenow={m.progress} aria-valuemin={0} aria-valuemax={m.target} aria-label={m.title}>
                    <div className="h-full rounded-full bg-green transition-all duration-500" style={{ width: `${pct}%` }} />
                  </div>
                )}
              </div>
              <span className="text-xs font-900 text-sub flex-shrink-0">{m.progress}/{m.target}</span>
            </li>
          );
        })}
      </ul>
      <p className="text-[11px] text-muted font-700 mt-2.5 text-center">New missions every day at midnight.</p>
    </section>
  );
}
