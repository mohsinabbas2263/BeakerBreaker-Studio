import Image from "next/image";
import Link from "next/link";

/** The card layout shared by the sign-in, forgot-password and reset-password screens. */
export default function AuthCard({ title, children }: { title: string; children: React.ReactNode }) {
  return (
    <div className="min-h-screen flex items-center justify-center p-4"
         style={{ background: "linear-gradient(135deg,#E8F4FD 0%,#C8F7E4 50%,#FFF9C4 100%)" }}>
      <div className="w-full max-w-sm">
        <div className="text-center mb-8">
          <Image src="/brand/logo.png" alt="Beaker Breakers Studio" width={720} height={970} priority
                 className="w-40 h-auto mx-auto mb-2" style={{ filter: "drop-shadow(0 8px 16px rgba(79,195,247,.25))" }} />
          <p className="text-sub font-700 text-sm mt-1">Virtual Chemistry Lab</p>
        </div>

        <div className="bg-white rounded-card shadow-card p-6">
          <h1 className="font-fredoka font-600 text-xl text-ink mb-5 text-center">{title}</h1>
          {children}
        </div>

        <p className="text-center text-sm font-800 mt-6">
          <Link href="/login" className="text-blue-d hover:underline">← Back to sign in</Link>
        </p>
      </div>
    </div>
  );
}

export const inputClass =
  "w-full px-4 py-3 rounded-2xl border-2 border-blue/20 font-nunito font-700 text-sm text-ink bg-sky " +
  "focus:outline-none focus:border-blue transition-colors";

export const labelClass = "block text-xs font-800 text-sub uppercase tracking-wide mb-1.5";

export const buttonClass =
  "w-full py-3.5 rounded-2xl font-fredoka font-600 text-lg text-white transition-all duration-200 " +
  "hover:scale-[1.02] active:scale-[0.98] disabled:opacity-60 disabled:cursor-not-allowed";

export const buttonStyle = {
  background: "linear-gradient(135deg,#4FC3F7,#29B6F6)",
  boxShadow: "0 8px 24px rgba(79,195,247,.4)",
};
