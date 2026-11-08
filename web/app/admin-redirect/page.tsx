"use client";

import { useEffect } from "react";

/**
 * Administrators work in the Filament panel served by the backend (wf_S16 to S18),
 * not in this portal. Its address is the API address without the /api suffix.
 */
const API = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";
const ADMIN_URL = API.replace(/\/api\/?$/, "") + "/admin";

export default function AdminRedirectPage() {
  useEffect(() => {
    window.location.replace(ADMIN_URL);
  }, []);

  return (
    <div className="min-h-screen flex flex-col items-center justify-center gap-3 p-6 text-center">
      <span className="text-4xl animate-spin">⚗️</span>
      <p className="font-fredoka font-600 text-ink">Opening the administrator panel…</p>
      <a href={ADMIN_URL} className="text-blue-d font-800 text-sm">Not moving? Open it here.</a>
    </div>
  );
}
