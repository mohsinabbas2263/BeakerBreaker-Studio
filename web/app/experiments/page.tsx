"use client";

import { useState } from "react";
import useSWR from "swr";
import Link from "next/link";
import Shell from "@/components/layout/Shell";
import { fetcher } from "@/lib/fetcher";
import type { ExperimentsListResponse } from "@/lib/api";

const DIFF_COLOR: Record<string, string> = {
  easy:   "bg-mint text-green-d",
  medium: "bg-lemon text-[#7a5a00]",
  hard:   "bg-coral text-orange-d",
};

export default function ExperimentsPage() {
  const { data, isLoading } = useSWR<ExperimentsListResponse>("/experiments", fetcher);
  const [topic, setTopic]   = useState<string>("All");

  const topics   = ["All", ...(data?.topics ?? [])];
  const filtered = (data?.experiments ?? []).filter(e => topic === "All" || e.topic === topic);

  return (
    <Shell>
      <div className="p-4 md:p-6">
        <h1 className="font-fredoka font-600 text-2xl text-ink mb-1">Experiments 🔬</h1>
        <p className="text-sub font-700 text-sm mb-5">PTB Class 9 &amp; 10 Chemistry</p>

        {/* Topic filter */}
        <div className="flex gap-2 overflow-x-auto pb-2 mb-5 scrollbar-hide">
          {topics.map(t => (
            <button key={t} onClick={() => setTopic(t)}
                    className={`flex-shrink-0 px-4 py-2 rounded-full font-nunito font-800 text-sm
                                transition-all duration-200 whitespace-nowrap
                                ${topic === t
                                  ? "text-white shadow-blue"
                                  : "bg-white text-sub hover:bg-sky"}`}
                    style={topic === t ? { background: "linear-gradient(135deg,#4FC3F7,#29B6F6)" } : {}}>
              {t}
            </button>
          ))}
        </div>

        {isLoading && (
          <div className="text-center py-16 text-4xl animate-spin">⚗️</div>
        )}

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          {filtered.map(exp => (
            <Link key={exp.id} href={`/experiments/${exp.id}`}
                  className={`block rounded-card shadow-card overflow-hidden
                               transition-all duration-200 hover:scale-[1.02] hover:shadow-lg
                               ${exp.locked ? "opacity-70" : ""}`}>
              {/* Gradient header */}
              <div className="p-5 flex items-center gap-3"
                   style={{ background: exp.card_gradient || "linear-gradient(135deg,#E8F4FD,#BBDEFB)" }}>
                <span className="text-4xl">{exp.emoji}</span>
                <div className="flex-1 min-w-0">
                  <p className="font-fredoka font-600 text-base text-ink truncate">{exp.title}</p>
                  <p className="text-xs text-sub font-700">{exp.topic}</p>
                </div>
                {exp.locked && <span className="text-xl">🔒</span>}
                {exp.completed && <span className="text-green font-900 text-sm">✓</span>}
              </div>

              {/* Info row */}
              <div className="bg-white px-4 py-3 flex items-center gap-3">
                <span className={`px-2.5 py-1 rounded-full text-[11px] font-800 capitalize ${DIFF_COLOR[exp.difficulty]}`}>
                  {exp.difficulty}
                </span>
                <span className="text-xs text-sub font-700">⏱ {exp.estimated_minutes} min</span>
                <span className="ml-auto text-xs font-800 text-orange-d">+{exp.base_points} XP</span>
              </div>
            </Link>
          ))}
        </div>

        {!isLoading && filtered.length === 0 && (
          <div className="text-center py-16">
            <span className="text-5xl">🧫</span>
            <p className="text-sub font-700 mt-3">No experiments in this topic yet.</p>
          </div>
        )}
      </div>
    </Shell>
  );
}
