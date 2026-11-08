"use client";

import Link from "next/link";
import TeacherShell from "@/components/layout/TeacherShell";
import { useAuth } from "@/lib/auth";

/**
 * wf_S10. The class monitor, task assignment and safety review (FR-09.2 to FR-09.5)
 * need teacher endpoints that arrive with M3. Until then this page says so plainly
 * rather than showing made-up numbers.
 */
export default function TeacherDashboardPage() {
  const { user } = useAuth();

  return (
    <TeacherShell>
      <div className="p-4 md:p-6 space-y-5">
        <div>
          <h1 className="font-fredoka font-600 text-2xl text-ink">Welcome, {user?.display_name} 👋</h1>
          <p className="text-sub font-700 text-sm mt-1">Teacher dashboard</p>
        </div>

        <div className="grid sm:grid-cols-2 gap-4">
          <Link href="/teacher/notifications"
                className="bg-white rounded-card shadow-card p-5 hover:scale-[1.01] transition-transform">
            <p className="text-3xl">📣</p>
            <p className="font-fredoka font-600 text-lg text-ink mt-2">Broadcast to a class</p>
            <p className="text-sm text-sub font-700 mt-1">Send a task, reminder, safety alert or announcement.</p>
          </Link>

          <div className="bg-sky rounded-card p-5">
            <p className="text-3xl">🏫</p>
            <p className="font-fredoka font-600 text-lg text-ink mt-2">Class monitor, tasks, safety review</p>
            <p className="text-sm text-sub font-700 mt-1">
              Coming in the next update. You will see each student&apos;s points, experiments,
              certification and last activity here.
            </p>
          </div>
        </div>
      </div>
    </TeacherShell>
  );
}
