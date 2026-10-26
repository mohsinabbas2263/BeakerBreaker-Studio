"use client";

/**
 * M7 – FR-09.6: notification bell with unread badge.
 *
 * Polls GET /notifications every 30 seconds. When a new unread notification
 * arrives (unread_count increases), fires a browser Notification if permission
 * is granted — delivering the alert within 30 s of the backend write.
 */

import { useEffect, useRef } from "react";
import Link from "next/link";
import useSWR from "swr";
import { type NotificationsResponse } from "@/lib/api";
import { fetcher } from "@/lib/fetcher";
import { requestNotificationPermission, showBrowserNotification } from "@/lib/push";

export default function NotificationBell() {
  const { data } = useSWR<NotificationsResponse>(
    "/notifications",
    fetcher<NotificationsResponse>,
    { refreshInterval: 30_000 }
  );

  const unread   = data?.unread_count ?? 0;
  const prevRef  = useRef<number>(unread);

  // Kick off permission request on first load.
  useEffect(() => {
    requestNotificationPermission().catch(() => {});
  }, []);

  // Fire a browser notification whenever a new unread item appears.
  useEffect(() => {
    if (unread > prevRef.current && data?.notifications?.length) {
      const newest = data.notifications.find(n => n.read_at === null);
      if (newest) {
        showBrowserNotification(newest.subject, newest.body);
      }
    }
    prevRef.current = unread;
  }, [unread, data]);

  return (
    <Link href="/notifications" className="relative inline-flex items-center justify-center
                                           w-9 h-9 rounded-xl hover:bg-sky transition-colors">
      <span className="text-xl leading-none">🔔</span>
      {unread > 0 && (
        <span className="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px]
                         bg-red text-white text-[10px] font-900 rounded-full
                         flex items-center justify-center px-1 leading-none">
          {unread > 9 ? "9+" : unread}
        </span>
      )}
    </Link>
  );
}
