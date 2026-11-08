"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect } from "react";
import { useAuth } from "@/lib/auth";

/**
 * Layout for teacher screens (wf_S10 to wf_S15). AI Readiness (wf_S12) is linked
 * once M4 gives it an API.
 */
const NAV = [
  { href: "/teacher/dashboard",     icon: "🏫", label: "Dashboard" },
  { href: "/teacher/monitor",       icon: "📋", label: "Class Monitor" },
  { href: "/teacher/tasks",         icon: "📝", label: "Tasks" },
  { href: "/teacher/safety",        icon: "🛡️", label: "Safety Review" },
  { href: "/teacher/notifications", icon: "📣", label: "Broadcast" },
];

export default function TeacherShell({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const router = useRouter();
  const { user, loading, logout } = useAuth();

  useEffect(() => {
    if (loading) return;
    if (!user) router.replace("/login");
    // SR-04 is enforced by the API; this only keeps a student off teacher screens.
    else if (user.role !== "teacher") router.replace("/home");
  }, [loading, user, router]);

  if (loading || !user || user.role !== "teacher") {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <span className="text-4xl animate-spin">⚗️</span>
      </div>
    );
  }

  return (
    <div className="min-h-screen">
      <header className="sticky top-0 z-[900] bg-white/95 backdrop-blur-xl border-b-2 border-blue/20 shadow-sm">
        <div className="max-w-5xl mx-auto flex items-center gap-3 px-4 py-3 flex-wrap">
          <span className="text-3xl" aria-hidden>⚗️</span>
          <div className="mr-auto">
            <p className="font-fredoka font-600 text-base text-ink leading-tight">Beaker Breakers</p>
            <p className="text-[11px] text-sub font-800 uppercase tracking-wide">Teacher</p>
          </div>
          <nav className="flex gap-1.5 order-last w-full overflow-x-auto pb-0.5 -mx-1 px-1
                          lg:order-none lg:w-auto lg:overflow-visible">
            {NAV.map(n => {
              const active = pathname.startsWith(n.href);
              return (
                <Link key={n.href} href={n.href}
                      className={`flex items-center gap-1.5 px-3 py-2 rounded-xl font-nunito font-800 text-sm
                                  whitespace-nowrap flex-shrink-0
                                  ${active ? "text-white" : "text-sub hover:bg-sky hover:text-blue-d"}`}
                      style={active ? { background: "linear-gradient(135deg,#4FC3F7,#29B6F6)" } : {}}>
                  <span aria-hidden>{n.icon}</span>{n.label}
                </Link>
              );
            })}
          </nav>
          <button onClick={logout} className="text-sub text-xs font-800 hover:text-red px-2">Sign out</button>
        </div>
      </header>
      <main className="max-w-5xl mx-auto page-in">{children}</main>
    </div>
  );
}
