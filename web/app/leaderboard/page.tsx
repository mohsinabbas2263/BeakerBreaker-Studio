"use client";

import Image from "next/image";
import useSWR from "swr";
import Shell from "@/components/layout/Shell";
import { fetcher } from "@/lib/fetcher";
import type { LeaderboardResponse } from "@/lib/api";
import { avatarSrc } from "@/lib/avatars";

const RANK_EMOJI: Record<number, string> = {
  1: "🧪", 2: "🔬", 3: "⚗️", 4: "🧫", 5: "🧬", 6: "🏅", 7: "🥇", 8: "🏆",
};

const MEDAL: Record<number, string> = { 1: "🥇", 2: "🥈", 3: "🥉" };

export default function LeaderboardPage() {
  const { data, isLoading } = useSWR<LeaderboardResponse>("/leaderboard", fetcher);

  return (
    <Shell>
      <div className="p-4 md:p-6 max-w-lg mx-auto">
        <h1 className="font-fredoka font-600 text-2xl text-ink mb-1">Leaderboard 🏆</h1>
        <p className="text-sub font-700 text-sm mb-6">Your class rankings</p>

        {isLoading && <div className="text-center py-16 text-4xl animate-spin">⚗️</div>}

        {/* My position */}
        {data?.me && (
          <div className="rounded-2xl p-4 mb-5 flex items-center gap-3"
               style={{ background: "linear-gradient(135deg,#E8F4FD,#BBDEFB)" }}>
            <span className="text-2xl font-900 text-blue-d w-8 text-center">#{data.me.position}</span>
            <Image src={avatarSrc(data.me.avatar_key)} alt="" width={44} height={44}
                   className="rounded-full border-2 border-white shadow-md object-cover flex-shrink-0" />
            <div className="flex-1">
              <p className="font-fredoka font-600 text-base text-ink">You</p>
              <p className="text-xs text-sub font-700">{RANK_EMOJI[data.me.rank_level]} {data.me.rank_name}</p>
            </div>
            <span className="font-fredoka font-600 text-base text-orange-d">
              {data.me.total_points.toLocaleString()} XP
            </span>
          </div>
        )}

        {/* Rows */}
        <div className="flex flex-col gap-2">
          {data?.rows.map(row => (
            <div key={row.position}
                 className={`flex items-center gap-3 px-4 py-3 rounded-2xl
                              ${row.is_me ? "bg-sky border-2 border-blue/40" : "bg-white shadow-sm"}`}>
              <span className="w-8 text-center text-sm font-900 text-sub">
                {MEDAL[row.position] ?? `#${row.position}`}
              </span>
              <Image src={avatarSrc(row.avatar_key)} alt="" width={36} height={36}
                     className="rounded-full border-2 border-white shadow-sm object-cover flex-shrink-0" />
              <div className="flex-1 min-w-0">
                <p className={`font-nunito font-800 text-sm truncate ${row.is_me ? "text-blue-d" : "text-ink"}`}>
                  {row.display_name} {row.is_me && "(You)"}
                </p>
                <p className="text-[11px] text-sub font-700">{RANK_EMOJI[row.rank_level]} {row.rank_name}</p>
              </div>
              <span className="font-fredoka font-600 text-sm text-orange-d">
                {row.total_points.toLocaleString()}
              </span>
            </div>
          ))}
        </div>

        {data?.rows.length === 0 && !isLoading && (
          <div className="text-center py-16">
            <span className="text-5xl">🏅</span>
            <p className="text-sub font-700 mt-3">No classmates yet — be the first!</p>
          </div>
        )}
      </div>
    </Shell>
  );
}
