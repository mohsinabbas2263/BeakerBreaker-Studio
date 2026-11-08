"use client";

import { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { api, type LoginResponse } from "@/lib/api";
import { useAuth } from "@/lib/auth";
import { beakySay } from "@/components/ui/Beaky";
import Beaky from "@/components/ui/Beaky";

export default function LoginPage() {
  const router = useRouter();
  const { login } = useAuth();
  const [email, setEmail]       = useState("");
  const [password, setPassword] = useState("");
  const [error, setError]       = useState<string | null>(null);
  const [loading, setLoading]   = useState(false);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    setLoading(true);
    try {
      const res = await api.post<LoginResponse>("/login", { email, password }, { skipAuth: true });
      login(res.token, res.user);
      beakySay("Welcome back! Let's do some chemistry! 🔬");
      // Role-based redirect
      if (res.user.role === "administrator") router.replace("/admin-redirect");
      else if (res.user.role === "teacher")  router.replace("/teacher/dashboard");
      else                                    router.replace("/home");
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : "Login failed";
      if (msg.toLowerCase().includes("429") || msg.toLowerCase().includes("too many")) {
        setError("Too many failed attempts. Please wait 15 minutes and try again.");
      } else {
        setError("Incorrect email or password. Please try again.");
      }
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center p-4"
         style={{ background: "linear-gradient(135deg,#E8F4FD 0%,#C8F7E4 50%,#FFF9C4 100%)" }}>
      <div className="w-full max-w-sm">
        {/* Brand */}
        <div className="text-center mb-8">
          <div className="text-7xl mb-3" style={{ filter: "drop-shadow(0 8px 16px rgba(79,195,247,.4))" }}>⚗️</div>
          <h1 className="font-fredoka font-600 text-3xl text-ink">Beaker Breakers</h1>
          <p className="text-sub font-700 text-sm mt-1">Virtual Chemistry Lab</p>
        </div>

        {/* Card */}
        <div className="bg-white rounded-card shadow-card p-6">
          <h2 className="font-fredoka font-600 text-xl text-ink mb-5 text-center">Sign In to Your Lab</h2>

          {error && (
            <div className="bg-coral border-2 border-red/30 rounded-2xl px-4 py-3 mb-4 text-sm font-700 text-red">
              ⚠️ {error}
            </div>
          )}

          <form onSubmit={handleSubmit} className="flex flex-col gap-4">
            <div>
              <label className="block text-xs font-800 text-sub uppercase tracking-wide mb-1.5">
                Email Address
              </label>
              <input
                type="email"
                value={email}
                onChange={e => setEmail(e.target.value)}
                placeholder="student@school.edu.pk"
                required
                className="w-full px-4 py-3 rounded-2xl border-2 border-blue/20
                           font-nunito font-700 text-sm text-ink bg-sky
                           focus:outline-none focus:border-blue transition-colors"
              />
            </div>

            <div>
              <label className="block text-xs font-800 text-sub uppercase tracking-wide mb-1.5">
                Password
              </label>
              <input
                type="password"
                value={password}
                onChange={e => setPassword(e.target.value)}
                placeholder="••••••••"
                required
                className="w-full px-4 py-3 rounded-2xl border-2 border-blue/20
                           font-nunito font-700 text-sm text-ink bg-sky
                           focus:outline-none focus:border-blue transition-colors"
              />
              {/* FR-01.3 */}
              <Link href="/forgot-password"
                    className="block text-right text-xs font-800 text-blue-d hover:underline mt-2">
                Forgot password?
              </Link>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-3.5 rounded-2xl font-fredoka font-600 text-lg text-white
                         transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]
                         disabled:opacity-60 disabled:cursor-not-allowed"
              style={{ background: "linear-gradient(135deg,#4FC3F7,#29B6F6)",
                       boxShadow: "0 8px 24px rgba(79,195,247,.4)" }}>
              {loading ? "Signing in…" : "Enter the Lab 🔬"}
            </button>
          </form>

          <p className="text-center text-xs text-muted font-700 mt-5">
            Accounts are created by your school administrator.
          </p>
        </div>

        <p className="text-center text-xs text-sub font-700 mt-6">
          Beaky the lab assistant is ready to help! 🧪
        </p>
      </div>
      <Beaky />
    </div>
  );
}
