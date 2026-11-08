"use client";

import { Suspense, useState } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import AuthCard, { buttonClass, buttonStyle, inputClass, labelClass } from "@/components/layout/AuthCard";
import { api, ApiError } from "@/lib/api";

/**
 * FR-01.3: the page the emailed link opens. The token works once and for 60
 * minutes; the server also signs the account out on every device (SR-05).
 */
export default function ResetPasswordPage() {
  return (
    <Suspense fallback={null}>
      <ResetPasswordForm />
    </Suspense>
  );
}

function ResetPasswordForm() {
  const params = useSearchParams();
  const token  = params.get("token") ?? "";
  const email  = params.get("email") ?? "";

  const [password, setPassword] = useState("");
  const [confirm, setConfirm]   = useState("");
  const [done, setDone]         = useState(false);
  const [error, setError]       = useState<string | null>(null);
  const [loading, setLoading]   = useState(false);

  if (!token || !email) {
    return (
      <AuthCard title="Link not valid">
        <p className="text-sm text-sub font-700 text-center">
          This reset link is incomplete. Open the link from your email again, or ask for a new one.
        </p>
        <Link href="/forgot-password" className={`${buttonClass} block text-center mt-5`} style={buttonStyle}>
          Get a new link
        </Link>
      </AuthCard>
    );
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);

    // SR-07 is enforced by the server; these checks only save a round trip.
    if (password.length < 8) return setError("Use at least 8 characters.");
    if (password !== confirm) return setError("The two passwords do not match.");

    setLoading(true);
    try {
      await api.post("/reset-password", {
        token, email, password, password_confirmation: confirm,
      }, { skipAuth: true });
      setDone(true);
    } catch (err) {
      setError(err instanceof ApiError && err.status === 422
        ? "This link has expired or was already used. Ask for a new one."
        : "Could not change the password. Check your connection and try again.");
    } finally {
      setLoading(false);
    }
  }

  if (done) {
    return (
      <AuthCard title="Password changed ✅">
        <p className="text-sm text-sub font-700 text-center" role="status">
          You can now sign in with your new password. For safety, you have been signed out on every other device.
        </p>
        <Link href="/login" className={`${buttonClass} block text-center mt-5`} style={buttonStyle}>
          Sign in
        </Link>
      </AuthCard>
    );
  }

  return (
    <AuthCard title="Choose a new password">
      <p className="text-sm text-sub font-700 text-center mb-5">For <span className="text-ink">{email}</span></p>

      {error && (
        <div className="bg-coral border-2 border-red/30 rounded-2xl px-4 py-3 mb-4 text-sm font-700 text-red" role="alert">
          ⚠️ {error}
          {error.includes("expired") && (
            <Link href="/forgot-password" className="block mt-1 underline">Get a new link</Link>
          )}
        </div>
      )}

      <form onSubmit={handleSubmit} className="flex flex-col gap-4">
        <div>
          <label htmlFor="password" className={labelClass}>New password</label>
          <input id="password" type="password" required minLength={8} autoComplete="new-password"
                 value={password} onChange={e => setPassword(e.target.value)} className={inputClass} />
        </div>
        <div>
          <label htmlFor="confirm" className={labelClass}>Type it again</label>
          <input id="confirm" type="password" required minLength={8} autoComplete="new-password"
                 value={confirm} onChange={e => setConfirm(e.target.value)} className={inputClass} />
        </div>
        <button type="submit" disabled={loading} className={buttonClass} style={buttonStyle}>
          {loading ? "Saving…" : "Save new password"}
        </button>
      </form>
    </AuthCard>
  );
}
