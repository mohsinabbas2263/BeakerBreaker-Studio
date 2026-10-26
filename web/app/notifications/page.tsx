"use client";

import { useState } from "react";
import useSWR, { mutate } from "swr";
import Shell from "@/components/layout/Shell";
import { api, type NotificationsResponse, type StudentNotification } from "@/lib/api";
import { fetcher } from "@/lib/fetcher";
import { useToast } from "@/components/ui/Toast";

const TYPE_CONFIG: Record<string, { label: string; bg: string; text: string; icon: string }> = {
  task:         { label: "Task",        bg: "bg-sky",    text: "text-blue-d",  icon: "📋" },
  reminder:     { label: "Reminder",    bg: "bg-lemon",  text: "text-ink",     icon: "⏰" },
  safety_alert: { label: "Safety",      bg: "bg-coral",  text: "text-red-600", icon: "⚠️" },
  announcement: { label: "Announcement", bg: "bg-mint",  text: "text-green-d", icon: "📢" },
};

export default function NotificationsPage() {
  const toast = useToast();
  const [clearing, setClearing] = useState(false);

  const { data, isLoading } = useSWR<NotificationsResponse>(
    "/notifications",
    fetcher<NotificationsResponse>,
    { refreshInterval: 30_000 }
  );

  const notifications = data?.notifications ?? [];
  const unread        = data?.unread_count   ?? 0;

  async function markAllRead() {
    if (clearing || unread === 0) return;
    setClearing(true);
    try {
      await api.patch("/notifications/read-all");
      await mutate("/notifications");
      toast("All notifications marked as read ✅", "success");
    } catch {
      toast("Could not mark as read", "error");
    } finally {
      setClearing(false);
    }
  }

  return (
    <Shell>
      <div className="p-4 md:p-6 max-w-2xl mx-auto space-y-4">

        {/* Header */}
        <div className="flex items-center justify-between gap-3">
          <h1 className="font-fredoka font-600 text-2xl text-ink">
            Notifications 🔔
            {unread > 0 && (
              <span className="ml-2 text-base font-700 text-sub">
                {unread} unread
              </span>
            )}
          </h1>
          {unread > 0 && (
            <button
              onClick={markAllRead}
              disabled={clearing}
              className="text-xs font-800 text-blue-d hover:underline disabled:opacity-50"
            >
              {clearing ? "Clearing…" : "Mark all read"}
            </button>
          )}
        </div>

        {/* Loading */}
        {isLoading && (
          <div className="flex items-center justify-center py-12">
            <span className="text-4xl animate-spin">⚗️</span>
          </div>
        )}

        {/* Empty */}
        {!isLoading && notifications.length === 0 && (
          <div className="bg-white rounded-card shadow-card p-8 text-center">
            <p className="text-4xl mb-3">🔔</p>
            <p className="font-fredoka font-600 text-lg text-ink">No notifications yet</p>
            <p className="text-sm text-sub font-700 mt-1">
              You'll see task assignments and teacher messages here.
            </p>
          </div>
        )}

        {/* List */}
        {!isLoading && notifications.length > 0 && (
          <div className="space-y-3">
            {notifications.map(n => (
              <NotificationCard key={n.id} notification={n} />
            ))}
          </div>
        )}
      </div>
    </Shell>
  );
}

function NotificationCard({ notification: n }: { notification: StudentNotification }) {
  const cfg = TYPE_CONFIG[n.notification_type] ?? TYPE_CONFIG["announcement"];
  const unread = n.read_at === null;

  return (
    <div className={`bg-white rounded-card shadow-card p-4 flex gap-3 transition-opacity
                     ${unread ? "border-l-4 border-blue" : "opacity-80"}`}>
      <span className="text-2xl flex-shrink-0 mt-0.5">{cfg.icon}</span>

      <div className="flex-1 min-w-0">
        <div className="flex items-center gap-2 flex-wrap mb-1">
          <span className={`text-[10px] font-900 px-2 py-0.5 rounded-full uppercase
                            tracking-wide ${cfg.bg} ${cfg.text}`}>
            {cfg.label}
          </span>
          {unread && (
            <span className="w-2 h-2 rounded-full bg-blue flex-shrink-0" />
          )}
          <span className="text-[11px] text-muted font-700 ml-auto">
            {formatDate(n.created_at)}
          </span>
        </div>

        <p className="font-fredoka font-600 text-sm text-ink leading-snug">{n.subject}</p>
        <p className="text-xs text-sub font-700 mt-0.5 leading-relaxed">{n.body}</p>
      </div>
    </div>
  );
}

function formatDate(iso: string): string {
  const d = new Date(iso);
  const now = new Date();
  const diffMs = now.getTime() - d.getTime();
  const diffMins = Math.floor(diffMs / 60_000);

  if (diffMins < 1)  return "just now";
  if (diffMins < 60) return `${diffMins}m ago`;

  const diffHrs = Math.floor(diffMins / 60);
  if (diffHrs < 24)  return `${diffHrs}h ago`;

  return d.toLocaleDateString("en-GB", { day: "numeric", month: "short" });
}
