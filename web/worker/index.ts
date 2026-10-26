/**
 * Custom service worker entry — merged with the workbox SW by next-pwa.
 *
 * Adds push event handling (FR-09.6) and notification click routing.
 * This file is compiled to swe-worker-{hash}.js in public/ on each build.
 *
 * The message handler below preserves the built-in next-pwa caching
 * behaviour (START_URL_CACHE, FRONTEND_NAV_CACHE).
 */

// ── Push: show an OS notification when a VAPID background push arrives ────────
self.addEventListener("push", (event: PushEvent) => {
  let data: { subject?: string; body?: string } = {};
  try { data = event.data?.json() ?? {}; } catch {}

  event.waitUntil(
    self.registration.showNotification(data.subject ?? "Beaker Breakers", {
      body:  data.body ?? "You have a new notification.",
      icon:  "/icons/icon-192.png",
      badge: "/icons/badge-72.png",
      tag:   "bb-notification",
      data:  { url: "/notifications" },
    })
  );
});

// ── Notification click: focus the PWA on /notifications ─────────────────────
self.addEventListener("notificationclick", (event: NotificationEvent) => {
  event.notification.close();
  const url: string = (event.notification.data as { url?: string })?.url ?? "/notifications";

  event.waitUntil(
    clients
      .matchAll({ type: "window", includeUncontrolled: true })
      .then((list: readonly WindowClient[]) => {
        for (const client of list) {
          if ("focus" in client) {
            client.navigate(url);
            return client.focus();
          }
        }
        return clients.openWindow(url);
      })
  );
});

// ── Cache message handler (preserves next-pwa built-in behaviour) ─────────────
self.onmessage = async (event: ExtendableMessageEvent) => {
  const type: string = (event.data as { type?: string })?.type ?? "";

  if (type === "__START_URL_CACHE__") {
    const url: string = (event.data as { url: string }).url;
    const res = await fetch(url);
    if (!res.redirected) {
      const cache = await caches.open("start-url");
      await cache.put(url, res);
    }
    return;
  }

  if (type === "__FRONTEND_NAV_CACHE__") {
    const url: string = (event.data as { url: string; shouldCacheAggressively?: boolean }).url;
    const cache = await caches.open("pages");
    if (await cache.match(url, { ignoreSearch: true })) return;
    const res = await fetch(url);
    if (!res.ok) return;
    await cache.put(url, res.clone());
    return;
  }
};
