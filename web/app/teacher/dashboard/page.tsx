"use client";

import Link from "next/link";
import Image from "next/image";
import useSWR from "swr";
import TeacherShell from "@/components/layout/TeacherShell";
import { NoClasses } from "@/components/teacher/ClassPicker";
import { useAuth } from "@/lib/auth";
import { avatarSrc } from "@/lib/avatars";
import { fetcher } from "@/lib/fetcher";
import type { TeacherDashboardResponse } from "@/lib/api";

/**
 * wf_S10. Totals across every class the teacher is assigned to, the students who
 * need attention and why, and quick actions. FR-09.3: refreshed every 5 seconds.
 */
const ACTIONS = [
  { href: "/teacher/tasks",         icon: "📝", label: "Assign Task" },
  { href: "/teacher/monitor",       icon: "📋", label: "Class Monitor" },
  { href: "/teacher/safety",        icon: "🛡️", label: "Safety Review" },
  { href: "/teacher/notifications", icon: "📣", label: "Broadcast" },
];

export default function TeacherDashboardPage() {
  const { user } = useAuth();
  const { data, isLoading, error } = useSWR<TeacherDashboardResponse>("/teacher/dashboard", fetcher, {
    refreshInterval: 5000,
  });

  const stats = data
    ? [
        { label: "Students",      value: data.stats.students,              icon: "👩‍🎓", bg: "#E8F4FD" },
        { label: "Avg XP",        value: data.stats.avg_xp.toLocaleString(), icon: "⭐",  bg: "#FFF9C4" },
        { label: "Certified",     value: data.stats.certified,             icon: "🛡️", bg: "#C8F7E4" },
        { label: "Ready for lab", value: data.stats.ready_for_lab,         icon: "🧪", bg: "#FFD6C8" },
      ]
    : [];

  return (
    <TeacherShell>
      <div className="p-4 md:p-6 space-y-5">
        <div>
          <h1 className="font-fredoka font-600 text-2xl text-ink">Welcome, {user?.display_name} 👋</h1>
          <p className="text-sub font-700 text-sm mt-1">
            {data && data.classes.length > 0
              ? `Your classes: ${data.classes.map(c => c.title).join(", ")}`
              : "Teacher dashboard"}
          </p>
        </div>

        {isLoading && <div className="flex justify-center py-16 text-4xl animate-spin">⚗️</div>}
        {error && !data && (
          <p className="bg-coral rounded-2xl p-4 text-sm font-800 text-ink">
            Could not load your classes. Check your connection and refresh.
          </p>
        )}

        {data && data.classes.length === 0 && <NoClasses />}

        {data && data.classes.length > 0 && (
          <>
            {/* Class stats hero */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
              {stats.map(s => (
                <div key={s.label} className="rounded-card shadow-card p-4 text-center" style={{ background: s.bg }}>
                  <p className="text-2xl" aria-hidden>{s.icon}</p>
                  <p className="font-fredoka font-600 text-2xl text-ink mt-1">{s.value}</p>
                  <p className="text-[11px] text-sub font-800 uppercase tracking-wide">{s.label}</p>
                </div>
              ))}
            </div>

            <div className="grid md:grid-cols-[1fr_260px] gap-5 items-start">
              {/* Students needing attention */}
              <section className="bg-white rounded-card shadow-card p-5">
                <h2 className="font-fredoka font-600 text-lg text-ink mb-3">Students needing attention</h2>
                {data.attention.length === 0 ? (
                  <p className="text-sm text-sub font-700 py-6 text-center">🎉 Everyone is on track.</p>
                ) : (
                  <ul className="flex flex-col gap-2">
                    {data.attention.map(s => (
                      <li key={s.id}>
                        <Link href={`/teacher/monitor?class=${s.class_id}`}
                              className="flex items-center gap-3 p-3 rounded-2xl bg-sky/60 hover:bg-sky transition-colors">
                          <Image src={avatarSrc(s.avatar_key)} alt="" width={36} height={36}
                                 className="rounded-full border-2 border-white object-cover flex-shrink-0" />
                          <div className="min-w-0 flex-1">
                            <p className="font-nunito font-800 text-sm text-ink truncate">
                              {s.display_name} <span className="text-muted font-700">· {s.class_title}</span>
                            </p>
                            <div className="flex flex-wrap gap-1 mt-1">
                              {s.reasons.map(r => (
                                <span key={r} className="text-[11px] font-800 px-2 py-0.5 rounded-full bg-white text-orange-d">
                                  {r}
                                </span>
                              ))}
                            </div>
                          </div>
                        </Link>
                      </li>
                    ))}
                  </ul>
                )}
              </section>

              {/* Quick actions */}
              <section className="bg-white rounded-card shadow-card p-5">
                <h2 className="font-fredoka font-600 text-lg text-ink mb-3">Quick actions</h2>
                <div className="flex flex-col gap-2">
                  {ACTIONS.map(a => (
                    <Link key={a.href} href={a.href}
                          className="flex items-center gap-2 px-4 py-3 rounded-2xl bg-sky/60 hover:bg-sky
                                     font-nunito font-800 text-sm text-ink transition-colors">
                      <span aria-hidden>{a.icon}</span>{a.label}
                    </Link>
                  ))}
                </div>
              </section>
            </div>
          </>
        )}
      </div>
    </TeacherShell>
  );
}
