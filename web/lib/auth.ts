"use client";

import { useEffect, useState, useCallback } from "react";
import { useRouter } from "next/navigation";
import { api, type User, clearToken, saveToken } from "./api";

export function useAuth() {
  const router = useRouter();
  const [user, setUser]       = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const stored = typeof window !== "undefined" ? localStorage.getItem("bb_user") : null;
    if (stored) {
      try { setUser(JSON.parse(stored)); } catch {}
    }
    setLoading(false);
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
