"use client";

import { useState } from "react";
import useSWR, { mutate } from "swr";
import TeacherShell from "@/components/layout/TeacherShell";
import { api, type TeacherClassesResponse, type SentNotificationsResponse } from "@/lib/api";
import { fetcher } from "@/lib/fetcher";
import { useToast } from "@/components/ui/Toast";

const TYPES = [
  { value: "task",         label: "Task" },
  { value: "reminder",     label: "Reminder" },
  { value: "safety_alert", label: "Safety Alert" },
  { value: "announcement", label: "Announcement" },
];

const TYPE_COLORS: Record<string, string> = {
  task:         "bg-sky text-blue-d",
  reminder:     "bg-lemon text-ink",
  safety_alert: "bg-coral text-red",
  announcement: "bg-lavender text-ink",
};

export default function NotificationsPage() {
  const toast = useToast();

  const { data: classesData } = useSWR<TeacherClassesResponse>(
    "/teacher/classes", fetcher<TeacherClassesResponse>
  );
  const { data: histData } = useSWR<SentNotificationsResponse>(
    "/teacher/notifications", fetcher<SentNotificationsResponse>
  );

  const classes = classesData?.classes ?? [];
  const history = histData?.notifications ?? [];

  const [classId,  setClassId]  = useState("");
  const [type,     setType]     = useState("announcement");
  const [subject,  setSubject]  = useState("");
  const [body,     setBody]     = useState("");
  const [busy,     setBusy]     = useState(false);

  const remaining = 500 - body.length;

  async function send(e: React.FormEvent) {
    e.preventDefault();
    if (!classId || !subject.trim() || !body.trim()) return;
    setBusy(true);
    try {
      const res = await api.post<{ sent: number }>("/teacher/broadcast", {
        class_id: Number(classId),
        notification_type: type,
        subject: subject.trim(),
        body: body.trim(),
      });
      toast(`Sent to ${res.sent} student${res.sent !== 1 ? "s" : ""}! 🔔`, "success");
      await mutate("/teacher/notifications");
      setSubject(""); setBody("");
    } catch (err) {
      toast(err instanceof Error ? err.message : "Broadcast failed", "error");
    } finally {
      setBusy(false);
    }
  }

  return (
    <TeacherShell>
      <div className="p-4 md:p-6 space-y-5 max-w-5xl mx-auto">

        <h1 className="font-fredoka font-600 text-2xl text-ink">Notifications 🔔</h1>

        <div className="grid md:grid-cols-2 gap-5 items-start">

          {/* Compose */}
          <form onSubmit={send} className="bg-white rounded-card shadow-card p-5 space-y-4">
            <h2 className="font-fredoka font-600 text-lg text-ink">Broadcast Message</h2>

            <div>
              <label className="block text-sm font-800 text-ink mb-1.5">Type</label>
              <select value={type} onChange={e => setType(e.target.value)}
                      className="w-full px-4 py-3 rounded-2xl border-2 border-sky bg-sky/40
                                 font-nunito text-sm text-ink focus:outline-none focus:border-blue">
                {TYPES.map(t => <option key={t.value} value={t.value}>{t.label}</option>)}
              </select>
            </div>

            <div>
              <label className="block text-sm font-800 text-ink mb-1.5">Recipients</label>
              <select value={classId} onChange={e => setClassId(e.target.value)} required
                      className="w-full px-4 py-3 rounded-2xl border-2 border-sky bg-sky/40
                                 font-nunito text-sm text-ink focus:outline-none focus:border-blue">
                <option value="">Select class…</option>
                {classes.map(c => <option key={c.id} value={c.id}>{c.title}</option>)}
              </select>
            </div>

            <div>
              <label className="block text-sm font-800 text-ink mb-1.5">Subject</label>
              <input
                value={subject} onChange={e => setSubject(e.target.value)}
                required maxLength={120}
                placeholder="Brief subject line…"
                className="w-full px-4 py-3 rounded-2xl border-2 border-sky bg-sky/40
                           font-nunito text-sm text-ink focus:outline-none focus:border-blue"
              />
            </div>

            <div>
              <label className="block text-sm font-800 text-ink mb-1.5">
                Message
                <span className={`ml-2 font-700 ${remaining < 50 ? "text-red" : "text-sub"}`}>
                  ({remaining} chars left)
                </span>
              </label>
              <textarea
                value={body} onChange={e => setBody(e.target.value.slice(0, 500))}
                required rows={4}
                placeholder="Message to send to all students in the class… (≤ 500 characters, HTML stripped)"
                className="w-full px-4 py-3 rounded-2xl border-2 border-sky bg-sky/40
                           font-nunito text-sm text-ink focus:outline-none focus:border-blue resize-none"
              />
            </div>

            <button type="submit" disabled={busy || !classId}
                    className="w-full py-3 rounded-card font-fredoka font-600 text-white
                               disabled:opacity-60 hover:opacity-90 transition-opacity"
                    style={{ background: "linear-gradient(135deg,#9575CD,#7E57C2)" }}>
              {busy ? "Sending…" : "Send to Class 🔔"}
            </button>

            <p className="text-[10px] text-muted font-700 text-center">
              Delivered in-app and via browser notification (FR-09.7).
            </p>
          </form>

          {/* History */}
          <div className="bg-white rounded-card shadow-card p-5">
            <h2 className="font-fredoka font-600 text-lg text-ink mb-4">
              Sent History <span className="text-xs font-700 text-sub">(30-day archive)</span>
            </h2>

            {history.length === 0 && (
              <p className="text-sub text-sm font-700 text-center py-4">No messages sent yet.</p>
            )}

            <div className="space-y-3">
              {history.map((n, i) => (
                <div key={i} className="rounded-2xl bg-sky/40 p-3 space-y-1">
                  <div className="flex items-center gap-2">
                    <span className={`text-[10px] font-900 px-2 py-0.5 rounded-full
                                     ${TYPE_COLORS[n.notification_type] ?? "bg-sky text-sub"}`}>
                      {n.notification_type.replace("_", " ").toUpperCase()}
                    </span>
                    <span className="text-[10px] text-muted font-700 ml-auto">
                      {new Date(n.created_at).toLocaleDateString("en-GB", {
                        day: "numeric", month: "short", hour: "2-digit", minute: "2-digit"
                      })}
                    </span>
                  </div>
                  <p className="text-sm font-800 text-ink">{n.subject}</p>
                  <p className="text-xs text-sub font-700 line-clamp-2">{n.body}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </TeacherShell>
  );
}
