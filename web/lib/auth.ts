"use client";

import { useEffect, useState, useCallback } from "react";
import { useRouter } from "next/navigation";
import { api, type User, clearToken, saveToken } from "./api";

/** Merges changes into the signed-in user kept in the browser, and tells every useAuth. */
export function updateStoredUser(changes: Partial<User>) {
  if (typeof window === "undefined") return;
  try {
    const current = JSON.parse(localStorage.getItem("bb_user") ?? "null");
    if (!current) return;
    localStorage.setItem("bb_user", JSON.stringify({ ...current, ...changes }));
    window.dispatchEvent(new Event("bb-user"));
  } catch {}
}

export function useAuth() {
  const router = useRouter();
  const [user, setUser]       = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const read = () => {
      const stored = typeof window !== "undefined" ? localStorage.getItem("bb_user") : null;
      if (stored) {
        try { setUser(JSON.parse(stored)); } catch {}
      }
    };
    read();
    setLoading(false);
    // updateStoredUser() fires this, so the sidebar avatar changes without a reload.
    window.addEventListener("bb-user", read);
    return () => window.removeEventListener("bb-user", read);
  }, []);

  const logout = useCallback(async () => {
    try { await api.post("/logout"); } catch {}
    clearToken();
    setUser(null);
    router.replace("/login");
  }, [router]);

  const login = useCallback((token: string, u: User) => {
    saveToken(token, u);
    setUser(u);
  }, []);

  return { user, loading, login, logout };
}
