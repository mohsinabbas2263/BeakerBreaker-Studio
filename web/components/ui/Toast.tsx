"use client";

import { useCallback, useEffect, useState } from "react";

export type ToastKind = "success" | "error" | "info";

interface ToastItem {
  id: number;
  message: string;
  kind: ToastKind;
}

const EVENT = "bb-toast";

/**
 * Fire a toast from anywhere. A <ToastHost /> mounted once in the root layout
 * renders it, so pages do not each need their own container.
 */
export function useToast() {
  return useCallback((message: string, kind: ToastKind = "info") => {
    if (typeof window !== "undefined") {
      window.dispatchEvent(new CustomEvent(EVENT, { detail: { message, kind } }));
    }
  }, []);
}

const STYLES: Record<ToastKind, { bg: string; icon: string }> = {
  success: { bg: "linear-gradient(135deg,#4CAF82,#2E7D57)", icon: "✅" },
  error:   { bg: "linear-gradient(135deg,#FF8A50,#F56565)", icon: "⚠️" },
  info:    { bg: "linear-gradient(135deg,#4FC3F7,#29B6F6)", icon: "💡" },
};

export function ToastHost() {
  const [items, setItems] = useState<ToastItem[]>([]);

  useEffect(() => {
    let next = 0;
    const handler = (e: Event) => {
      const { message, kind } = (e as CustomEvent<{ message: string; kind: ToastKind }>).detail;
      const id = ++next;
      setItems(list => [...list, { id, message, kind }]);
      setTimeout(() => setItems(list => list.filter(t => t.id !== id)), 3200);
    };
    window.addEventListener(EVENT, handler);
    return () => window.removeEventListener(EVENT, handler);
  }, []);

  return (
    <div className="fixed top-4 left-1/2 -translate-x-1/2 z-[3000] flex flex-col gap-2 w-[min(92vw,360px)]"
         role="status" aria-live="polite">
      {items.map(t => (
        <div key={t.id}
             className="page-in rounded-2xl px-4 py-3 text-white font-nunito font-800 text-sm shadow-card
                        flex items-center gap-2"
             style={{ background: STYLES[t.kind].bg }}>
          <span aria-hidden>{STYLES[t.kind].icon}</span>
          <span>{t.message}</span>
        </div>
      ))}
    </div>
  );
}
