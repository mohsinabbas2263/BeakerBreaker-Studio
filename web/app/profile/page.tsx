"use client";

import useSWR from "swr";
import Shell from "@/components/layout/Shell";
import { fetcher } from "@/lib/fetcher";
import type { Profile } from "@/lib/api";

const RANK_EMOJI: Record<number, string> = {
  1: "🧪", 2: "🔬", 3: "⚗️", 4: "🧫", 5: "🧬", 6: "🏅", 7: "🥇", 8: "🏆",
};

const BADGE_LABELS: Record<string, string> = {
  safety_tier_1: "🛡️ Safety Tier 1",
  safety_tier_2: "🛡️ Safety Tier 2",
  safety_tier_3: "🛡️ Safety Tier 3",
  safety_tier_4: "🛡️ Safety Tier 4",
  first_experiment: "🔬 First Experiment",
  five_experiments: "⚗️ Five Experiments",
  streak_7: "🔥 7-Day Streak",
};

export default function ProfilePage() {
  const { data, isLoading } = useSWR<Profile>("/profile", fetcher);

  if (isLoading) return <Shell><div className="flex justify-center py-24 text-4xl animate-spin">⚗️</div></Shell>;
  if (!data)     return <Shell><div className="p-6 text-center text-sub font-700">Could not load profile.</div></Shell>;

  return (
    <Shell>
      <div className="p-4 md:p-6 max-w-lg mx-auto">
        {/* Hero */}
        <div className="rounded-card shadow-card p-6 mb-5 text-center"
             style={{ background: "linear-gradient(135deg,#E8F4FD,#BBDEFB)" }}>
          <div className="text-6xl mb-3">{RANK_EMOJI[data.rank_level]}</div>
          <h1 className="font-fredoka font-600 text-2xl text-ink">{data.display_name}</h1>
          <p className="text-sub font-700 text-sm mt-1">{data.rank_name}</p>
          {data.class && <p className="text-xs text-muted font-700 mt-1">{data.class.name}</p>}
        </div>

        {/* Stats */}
        <div className="grid grid-cols-2 gap-3 mb-5">
          {[
            { label: "Total XP",           value: data.total_points.toLocaleString(), icon: "⭐" },
            { label: "Rank Level",          value: `Level ${data.rank_level}`,          icon: "🏅" },
            { label: "Experiments Done",    value: data.experiments_completed,          icon: "🔬" },
            { label: "Streak",              value: `${data.streak_days} days`,          icon: "🔥" },
            { label: "Safety Tier Passed",  value: `${data.safety_tier_passed} / 4`,   icon: "🛡️" },
          ].map(s => (
            <div key={s.label} className="bg-white rounded-card shadow-card p-4 text-center">
              <div className="text-2xl mb-1">{s.icon}</div>
              <p className="font-fredoka font-600 text-lg text-ink">{s.value}</p>
              <p className="text-[11px] text-sub font-700">{s.label}</p>
            </div>
          ))}
        </div>

        {/* Certificate */}
        {data.certificate && (
          <div className="rounded-2xl p-4 mb-5 text-center"
               style={{ background: "linear-gradient(135deg,#FFF9C4,#FFD54F)" }}>
            <span className="text-4xl">🏅</span>
            <p className="font-fredoka font-600 text-base text-ink mt-2">Safety Certificate Earned!</p>
          </div>
        )}

        {/* Badges */}
        {data.badges.length > 0 && (
          <div>
            <h2 className="font-fredoka font-600 text-lg text-ink mb-3">Badges</h2>
            <div className="flex flex-wrap gap-2">
              {data.badges.map(b => (
                <span key={b}
                      className="px-3 py-1.5 bg-white rounded-full shadow-sm
                                 font-nunito font-800 text-sm text-ink border-2 border-blue/20">
                  {BADGE_LABELS[b] ?? b}
                </span>
              ))}
            </div>
          </div>
        )}
      </div>
    </Shell>
  );
}
