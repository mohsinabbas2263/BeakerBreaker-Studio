"use client";

import useSWR from "swr";
import MissionsCard from "@/components/home/MissionsCard";
import Link from "next/link";
import Shell from "@/components/layout/Shell";
import { fetcher } from "@/lib/fetcher";
import { useAuth } from "@/lib/auth";
import type { ExperimentsListResponse, SafetyTiersResponse } from "@/lib/api";

const RANK_EMOJI: Record<number, string> = {
  1: "🧪", 2: "🔬", 3: "⚗️", 4: "🧫", 5: "🧬", 6: "🏅", 7: "🥇", 8: "🏆",
};

export default function HomePage() {
  const { user } = useAuth();
  const { data: exps }   = useSWR<ExperimentsListResponse>("/experiments", fetcher);
  const { data: safety } = useSWR<SafetyTiersResponse>("/safety/tiers", fetcher);

  const nextTier      = safety?.tiers.find(t => !t.passed && t.available);
  const recentExps    = exps?.experiments.filter(e => !e.locked).slice(0, 3) ?? [];
  const tiersPassed   = user?.safety_tier_passed ?? 0;
  const safetyDone    = tiersPassed >= 4;

  return (
    <Shell>
      <div className="p-4 md:p-6 max-w-2xl mx-auto">
        {/* Welcome */}
        <div className="mb-6 flex items-center gap-3">
          <span className="text-4xl">{RANK_EMOJI[user?.rank_level ?? 1]}</span>
          <div>
            <h1 className="font-fredoka font-600 text-2xl text-ink">
              Assalam o Alaikum, {user?.display_name?.split(" ")[0]}!
            </h1>
            <p className="text-sub font-700 text-sm">{user?.rank_name} · {user?.total_points.toLocaleString()} XP</p>
          </div>
        </div>

        {/* Streak banner */}
        {(user?.streak_days ?? 0) > 0 && (
          <div className="rounded-2xl p-4 mb-5 flex items-center gap-3"
               style={{ background: "linear-gradient(135deg,#FFF9C4,#FFD54F)" }}>
            <span className="text-3xl">🔥</span>
            <div>
              <p className="font-fredoka font-600 text-base text-ink">{user?.streak_days} day streak!</p>
              <p className="text-xs font-700 text-sub">Keep it up — visit every day to maintain your streak.</p>
            </div>
          </div>
        )}

        {/* Safety banner if tier 1 not passed */}
        {tiersPassed === 0 && (
          <Link href="/safety"
                className="block rounded-2xl p-4 mb-5 hover:scale-[1.01] transition-transform"
                style={{ background: "linear-gradient(135deg,#FFD6C8,#FF8A50)" }}>
            <div className="flex items-center gap-3">
              <span className="text-3xl">🛡️</span>
              <div>
                <p className="font-fredoka font-600 text-base text-white">Complete Safety Training First!</p>
                <p className="text-xs font-700 text-white/80">Pass Safety Tier 1 to unlock all experiments.</p>
              </div>
              <span className="ml-auto text-white text-xl">→</span>
            </div>
          </Link>
        )}

        {/* Next safety tier */}
        {!safetyDone && nextTier && tiersPassed > 0 && (
          <Link href="/safety"
                className="block rounded-2xl p-4 mb-5 hover:scale-[1.01] transition-transform"
                style={{ background: "linear-gradient(135deg,#C8F7E4,#4CAF82)" }}>
            <div className="flex items-center gap-3">
              <span className="text-3xl">🛡️</span>
              <div>
                <p className="font-fredoka font-600 text-base text-white">Next: {nextTier.title}</p>
                <p className="text-xs font-700 text-white/80">Tier {nextTier.tier_number} of 4 — keep going!</p>
              </div>
              <span className="ml-auto text-white text-xl">→</span>
            </div>
          </Link>
        )}

        <MissionsCard />

        {/* Quick actions */}
        <div className="grid grid-cols-2 gap-3 mb-6">
          <Link href="/experiments"
                className="rounded-card bg-white shadow-card p-4 flex flex-col gap-2
                           hover:scale-[1.03] transition-transform">
            <span className="text-3xl">🔬</span>
            <p className="font-fredoka font-600 text-base text-ink">Experiments</p>
            <p className="text-xs text-sub font-700">{exps?.experiments.length ?? 0} available</p>
          </Link>
          <Link href="/leaderboard"
                className="rounded-card bg-white shadow-card p-4 flex flex-col gap-2
                           hover:scale-[1.03] transition-transform">
            <span className="text-3xl">🏆</span>
            <p className="font-fredoka font-600 text-base text-ink">Leaderboard</p>
            <p className="text-xs text-sub font-700">See your rank</p>
          </Link>
        </div>

        {/* Recent experiments */}
        {recentExps.length > 0 && (
          <div>
            <h2 className="font-fredoka font-600 text-lg text-ink mb-3">Jump Back In</h2>
            <div className="flex flex-col gap-3">
              {recentExps.map(exp => (
                <Link key={exp.id} href={`/experiments/${exp.id}`}
                      className="bg-white rounded-card shadow-card p-4 flex items-center gap-3
                                 hover:scale-[1.01] transition-transform">
                  <span className="text-3xl">{exp.emoji}</span>
                  <div className="flex-1 min-w-0">
                    <p className="font-fredoka font-600 text-sm text-ink truncate">{exp.title}</p>
                    <p className="text-xs text-sub font-700">{exp.topic} · {exp.base_points} XP</p>
                  </div>
                  {exp.completed && <span className="text-green text-sm font-800">✓</span>}
                </Link>
              ))}
            </div>
          </div>
        )}
      </div>
    </Shell>
  );
}
