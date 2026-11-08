"use client";

import { useState } from "react";
import Image from "next/image";
import useSWR from "swr";
import Shell from "@/components/layout/Shell";
import { useToast } from "@/components/ui/Toast";
import { fetcher } from "@/lib/fetcher";
import { api, type Profile } from "@/lib/api";
import { updateStoredUser } from "@/lib/auth";
import { AVATARS, avatarSrc } from "@/lib/avatars";

const RANK_EMOJI: Record<number, string> = {
  1: "🧪", 2: "🔬", 3: "⚗️", 4: "🧫", 5: "🧬", 6: "🏅", 7: "🥇", 8: "🏆",
};


export default function ProfilePage() {
  const { data, isLoading, mutate } = useSWR<Profile>("/profile", fetcher);
  const toast = useToast();
  const [picking, setPicking] = useState(false);
  const [saving, setSaving]   = useState<string | null>(null);

  async function chooseAvatar(key: string) {
    if (!data || key === data.avatar_key) { setPicking(false); return; }
    setSaving(key);
    try {
      await api.patch("/profile", { avatar_key: key });
      updateStoredUser({ avatar_key: key });
      await mutate({ ...data, avatar_key: key }, { revalidate: false });
      setPicking(false);
      toast("Avatar updated!", "success");
    } catch (err) {
      toast(err instanceof Error ? err.message : "Could not change the avatar.", "error");
    } finally {
      setSaving(null);
    }
  }

  if (isLoading) return <Shell><div className="flex justify-center py-24 text-4xl animate-spin">⚗️</div></Shell>;
  if (!data)     return <Shell><div className="p-6 text-center text-sub font-700">Could not load profile.</div></Shell>;

  return (
    <Shell>
      <div className="p-4 md:p-6 max-w-lg mx-auto">
        {/* Hero */}
        <div className="rounded-card shadow-card p-6 mb-5 text-center"
             style={{ background: "linear-gradient(135deg,#E8F4FD,#BBDEFB)" }}>
          <button type="button" onClick={() => setPicking(p => !p)}
                  className="relative mx-auto mb-3 block rounded-full focus:outline-none focus-visible:ring-4 focus-visible:ring-blue/50"
                  aria-label="Change avatar" aria-expanded={picking}>
            <Image src={avatarSrc(data.avatar_key)} alt="" width={96} height={96}
                   className="rounded-full border-4 border-white shadow-card object-cover" />
            <span className="absolute -bottom-1 -right-1 w-9 h-9 rounded-full bg-white shadow-md
                             flex items-center justify-center text-lg">
              {RANK_EMOJI[data.rank_level] ?? "✏️"}
            </span>
          </button>
          <h1 className="font-fredoka font-600 text-2xl text-ink">{data.display_name}</h1>
          <p className="text-sub font-700 text-sm mt-1">{data.rank_name}</p>
          {data.class && <p className="text-xs text-muted font-700 mt-1">{data.class}</p>}
          {!picking && (
            <button type="button" onClick={() => setPicking(true)}
                    className="mt-3 text-xs font-800 text-blue-d underline underline-offset-2">
              Change avatar
            </button>
          )}

          {picking && (
            <div className="mt-4">
              <p className="text-xs font-800 text-sub mb-2">Pick your lab partner</p>
              <div className="flex justify-center gap-3 flex-wrap">
                {AVATARS.map(a => {
                  const active = a.key === data.avatar_key;
                  return (
                    <button key={a.key} type="button" disabled={saving !== null}
                            onClick={() => chooseAvatar(a.key)}
                            aria-label={a.label} aria-pressed={active}
                            className={`rounded-full p-0.5 transition-transform duration-300 hover:scale-110
                                        ${active ? "ring-4 ring-green" : "ring-2 ring-white"}
                                        ${saving === a.key ? "animate-pulse" : ""} disabled:opacity-60`}
                            style={{ transitionTimingFunction: "cubic-bezier(.68,-0.55,.27,1.55)" }}>
                      <Image src={a.src} alt="" width={56} height={56} className="rounded-full object-cover" />
                    </button>
                  );
                })}
              </div>
            </div>
          )}
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
                <span key={b.code}
                      className="px-3 py-1.5 bg-white rounded-full shadow-sm
                                 font-nunito font-800 text-sm text-ink border-2 border-blue/20">
                  {b.icon} {b.name}
                </span>
              ))}
            </div>
          </div>
        )}
      </div>
    </Shell>
  );
}
