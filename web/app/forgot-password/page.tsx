"use client";

import { useState } from "react";
import AuthCard, { buttonClass, buttonStyle, inputClass, labelClass } from "@/components/layout/AuthCard";
import { api, ApiError } from "@/lib/api";

/**
 * FR-01.3: ask for a reset link. The server answers the same way whether or not
 * the address has an account, so this page always shows the same confirmation.
 */
export default function ForgotPasswordPage() {
  const [email, setEmail]     = useState("");
  const [sent, setSent]       = useState(false);
  const [error, setError]     = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setLoading(true);
    try {
      await api.post("/forgot-password", { email }, { skipAuth: true });
      setSent(true);
    } catch (err) {
      setError(err instanceof ApiError && err.status === 429
        ? "Too many requests. Please wait a minute and try again."
        : "Could not send the request. Check your connection and try again.");
    } finally {
      setLoading(false);
    }
  }

  if (sent) {
    return (
      <AuthCard title="Check your email 📬">
        <p className="text-sm text-sub font-700 text-center leading-relaxed" role="status">
          If an account uses <span className="text-ink">{email}</span>, a reset link is on its way.
          The link works once and expires in 60 minutes.
        </p>
        <p className="text-xs text-muted font-700 text-center mt-4">
          No email? Check your spam folder, or ask your teacher or school administrator to reset it.
        </p>
      </AuthCard>
    );
  }

  return (
    <AuthCard title="Forgot your password?">
      <p className="text-sm text-sub font-700 text-center mb-5">
        Enter your email and we&apos;ll send you a link to choose a new one.
      </p>

      {error && (
        <div className="bg-coral border-2 border-red/30 rounded-2xl px-4 py-3 mb-4 text-sm font-700 text-red" role="alert">
          ⚠️ {error}
        </div>
      )}

      <form onSubmit={handleSubmit} className="flex flex-col gap-4">
        <div>
          <label htmlFor="email" className={labelClass}>Email address</label>
          <input id="email" type="email" required autoComplete="email" value={email}
                 onChange={e => setEmail(e.target.value)} placeholder="student@school.edu.pk"
                 className={inputClass} />
        </div>
        <button type="submit" disabled={loading} className={buttonClass} style={buttonStyle}>
          {loading ? "Sending…" : "Send reset link"}
        </button>
      </form>
    </AuthCard>
  );
}
