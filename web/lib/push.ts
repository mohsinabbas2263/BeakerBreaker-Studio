/**
 * M7 – FR-09.6: browser notification permission + active-tab notification helper.
 *
 * Background push (when the PWA is closed) works through the service worker
 * push event in worker/index.ts and requires VAPID keys to be configured on
 * the backend. Active-tab notifications fire immediately via new Notification().
 */

import { api } from "@/lib/api";

/** Ask for permission once; returns true if granted. */
export async function requestNotificationPermission(): Promise<boolean> {
  if (typeof window === "undefined" || !("Notification" in window)) return false;
  if (Notification.permission === "granted") return true;
  if (Notification.permission === "denied")  return false;
  const result = await Notification.requestPermission();
  return result === "granted";
}

/**
 * Show a browser notification in the active tab.
 * Silently does nothing if permission is not granted.
 */
export function showBrowserNotification(subject: string, body: string): void {
  if (typeof window === "undefined") return;
  if (Notification.permission !== "granted") return;
  try {
    new Notification(subject, {
      body,
      icon:  "/icons/icon-192.png",
      badge: "/icons/badge-72.png",
      tag:   "bb-notification",
    });
  } catch {}
}

/**
 * Register a web push subscription with the backend (FR-09.6).
 * Requires NEXT_PUBLIC_VAPID_PUBLIC_KEY to be set for VAPID subscriptions.
 * Falls back gracefully — a missing key means background push simply won't fire.
 */
export async function registerPushSubscription(): Promise<void> {
  if (typeof window === "undefined" || !("serviceWorker" in navigator)) return;

  const reg = await navigator.serviceWorker.ready.catch(() => null);
  if (!reg) return;

  try {
    // If a VAPID public key is available, subscribe via PushManager.
    const vapidKey = process.env.NEXT_PUBLIC_VAPID_PUBLIC_KEY;
    if (vapidKey) {
      const sub = await reg.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(vapidKey) as unknown as ArrayBuffer,
      });
      // Serialise and store on the backend as a web FCM token.
      await api.post("/push/register", {
        device_token: JSON.stringify(sub),
        platform:     "web",
      });
    }
  } catch {}
}

function urlBase64ToUint8Array(base64String: string): Uint8Array {
  const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
  const base64  = (base64String + padding).replace(/-/g, "+").replace(/_/g, "/");
  const raw     = atob(base64);
  const output  = new Uint8Array(raw.length);
  for (let i = 0; i < raw.length; i++) output[i] = raw.charCodeAt(i);
  return output;
}
