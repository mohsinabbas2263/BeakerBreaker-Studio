"use client";

import Link from "next/link";
import Image from "next/image";
import { usePathname, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { useAuth } from "@/lib/auth";
import { avatarSrc } from "@/lib/avatars";
import Beaky from "@/components/ui/Beaky";
import NotificationBell from "@/components/ui/NotificationBell";
import { registerPushSubscription } from "@/lib/push";

const NAV = [
  { href: "/home",        icon: "🏠", label: "Home"        },
  { href: "/experiments", icon: "🔬", label: "Experiments" },
  { href: "/safety",      icon: "🛡️", label: "Safety"      },
  { href: "/leaderboard", icon: "🏆", label: "Leaderboard" },
  { href: "/readiness",   icon: "🧠", label: "Readiness"   },
  { href: "/profile",     icon: "👤", label: "Profile"     },
];

const TASKBAR = [
  { href: "/home",        icon: "🏠", label: "Home"   },
  { href: "/experiments", icon: "🔬", label: "Labs"   },
  { href: "/safety",      icon: "🛡️", label: "Safety" },
  { href: "/leaderboard", icon: "🏆", label: "Ranks"  },
  { href: "/profile",     icon: "👤", label: "Me"     },
];

export default function Shell({ children }: { children: React.ReactNode }) {
  const pathname  = usePathname();
  const router    = useRouter();
  const { user, loading, logout } = useAuth();
  const [sideOpen, setSideOpen] = useState(false);

  // Redirect unauthenticated users
  useEffect(() => {
    if (!loading && !user) router.replace("/login");
  }, [loading, user, router]);

  // Register push subscription once user is confirmed (FR-09.6).
  useEffect(() => {
    if (user) registerPushSubscription().catch(() => {});
  }, [user]);

  if (loading || !user) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <Image src="/brand/mark.png" alt="" width={256} height={317} className="w-12 h-auto animate-pulse" />
      </div>
    );
  }

  const avatar = avatarSrc(user.avatar_key);

  return (
    <div className="min-h-screen">
      {/* ── Mobile overlay ── */}
      {sideOpen && (
        <div className="fixed inset-0 bg-black/50 z-[1050] md:hidden"
             onClick={() => setSideOpen(false)} />
      )}

      {/* ── Sidebar ── */}
      <aside className={`fixed top-0 left-0 bottom-0 w-[260px] z-[1100]
                         bg-white/96 backdrop-blur-xl
                         border-r-2 border-blue/20
                         flex flex-col gap-4 p-4 overflow-y-auto
                         shadow-[8px_0_32px_rgba(0,0,0,.05)]
                         transition-transform duration-300
                         ${sideOpen ? "translate-x-0" : "-translate-x-full md:translate-x-0"}`}>
        {/* Brand */}
        <div className="flex items-center gap-2 pb-3 border-b-2 border-black/5">
          <Image src="/brand/mark.png" alt="" width={256} height={317} className="w-10 h-auto flex-shrink-0" />
          <div>
            <p className="font-fredoka font-600 text-base text-ink leading-tight">Beaker Breakers</p>
            <p className="text-[11px] text-sub font-800 uppercase tracking-wide">Studio</p>
          </div>
        </div>

        {/* User chip */}
        <div className="rounded-[18px] p-3 flex items-center gap-3"
             style={{ background: "linear-gradient(135deg,#E8F4FD,#BBDEFB)" }}>
          <Image src={avatar} alt={user.display_name} width={48} height={48}
                 className="rounded-full border-3 border-white shadow-md object-cover flex-shrink-0" />
          <div className="min-w-0">
            <p className="font-fredoka font-600 text-[13px] text-ink truncate">{user.display_name}</p>
            <p className="text-[11px] font-800 text-orange-d mt-0.5">{user.rank_name}</p>
          </div>
        </div>

        {/* Nav links */}
        <nav className="flex flex-col gap-1.5 flex-1">
          {NAV.map(n => {
            const active = pathname.startsWith(n.href);
            return (
              <Link key={n.href} href={n.href}
                    onClick={() => setSideOpen(false)}
                    className={`flex items-center gap-3 px-3.5 py-2.5 rounded-2xl
                                font-nunito font-800 text-[14px] transition-all duration-200
                                whitespace-nowrap
                                ${active
                                  ? "text-white shadow-blue"
                                  : "text-sub hover:bg-sky hover:text-blue-d hover:translate-x-0.5"}`}
                    style={active ? { background: "linear-gradient(135deg,#4FC3F7,#29B6F6)" } : {}}>
                <span className="text-xl leading-none">{n.icon}</span>
                {n.label}
              </Link>
            );
          })}

          {/* AR mode */}
          <Link href="/ar" onClick={() => setSideOpen(false)}
                className="flex items-center gap-2.5 px-3.5 py-3 mt-2 rounded-2xl
                           font-fredoka font-600 text-[15px] text-white whitespace-nowrap
                           transition-transform duration-200 hover:scale-[1.03]"
                style={{ background: "linear-gradient(135deg,#00BCD4,#0097A7)",
                         boxShadow: "0 8px 22px rgba(0,188,212,.4)" }}>
            <span className="text-xl">🥽</span>
            AR Mode
            <span className="ml-auto bg-yellow text-[#7a5a00] text-[9px] px-1.5 py-0.5
                             rounded-full font-900 tracking-wider">NEW</span>
          </Link>
        </nav>

        {/* Notifications */}
        <Link href="/notifications" onClick={() => setSideOpen(false)}
              className="flex items-center gap-3 px-3.5 py-2.5 rounded-2xl
                         font-nunito font-800 text-[14px] text-sub
                         hover:bg-sky hover:text-blue-d transition-colors whitespace-nowrap">
          <NotificationBell inline />
          <span>Notifications</span>
        </Link>

        {/* Points summary */}
        <div className="rounded-2xl bg-lemon p-3 text-center">
          <p className="font-fredoka font-600 text-orange-d text-lg leading-none">
            {user.total_points.toLocaleString()} XP
          </p>
          <p className="text-[11px] text-sub font-700 mt-0.5">🔥 {user.streak_days} day streak</p>
        </div>

        {/* Logout */}
        <button onClick={logout}
                className="text-sub text-xs font-700 hover:text-red transition-colors py-1">
          Sign out
        </button>
      </aside>

      {/* ── Mobile topbar ── */}
      <header className="md:hidden sticky top-0 z-[900] bg-white/96 backdrop-blur-xl
                          border-b-2 border-blue/18 flex items-center gap-3 px-3.5 py-2.5
                          shadow-sm">
        <button onClick={() => setSideOpen(true)}
                className="w-10 h-10 rounded-xl bg-sky text-blue-d text-xl font-900 flex items-center justify-center">
          ☰
        </button>
        <span className="flex-1 flex items-center gap-2 font-fredoka font-600 text-[17px] text-ink">
          <Image src="/brand/mark.png" alt="" width={256} height={317} className="w-7 h-auto" />
          Beaker Breakers
        </span>
        <NotificationBell />
        <div className="flex items-center gap-1.5 bg-gradient-to-r from-orange to-yellow
                        text-white px-3 py-1.5 rounded-full font-fredoka font-600 text-sm">
          <span>⭐</span>
          <span>{user.total_points.toLocaleString()}</span>
        </div>
      </header>

      {/* ── Main content ── */}
      <main className="md:ml-[260px] min-h-screen pb-24 md:pb-6">
        <div className="page-in">
          {children}
        </div>
      </main>

      {/* ── Mobile taskbar ── */}
      <nav className="md:hidden fixed bottom-0 left-0 right-0 z-[900]
                      bg-white/96 backdrop-blur-xl border-t-2 border-blue/18
                      flex justify-around items-center px-2 py-2
                      shadow-[0_-4px_24px_rgba(0,0,0,.08)]">
        {TASKBAR.map(t => {
          const active = pathname.startsWith(t.href);
          return (
            <Link key={t.href} href={t.href}
                  className={`flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-xl
                              transition-all duration-200
                              ${active ? "text-blue-d" : "text-sub"}`}>
              <span className={`text-2xl ${active ? "scale-110" : ""} transition-transform`}>
                {t.icon}
              </span>
              <span className={`text-[10px] font-800 ${active ? "text-blue-d" : "text-muted"}`}>
                {t.label}
              </span>
            </Link>
          );
        })}
      </nav>

      <Beaky />
    </div>
  );
}
