"use client";

import { useState, useEffect } from "react";

export default function Beaky() {
  const [msg, setMsg]         = useState<string | null>(null);
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const handler = (e: CustomEvent) => {
      setMsg(e.detail as string);
      setVisible(true);
      setTimeout(() => setVisible(false), 3500);
    };
    window.addEventListener("beaky", handler as EventListener);
    return () => window.removeEventListener("beaky", handler as EventListener);
  }, []);

  return (
    <div className={`fixed bottom-24 md:bottom-6 right-4 z-[2000]
                     flex flex-col items-end gap-2 transition-all duration-300
                     ${visible ? "opacity-100 translate-y-0" : "opacity-0 translate-y-4 pointer-events-none"}`}>
      {msg && (
        <div className="bg-white rounded-2xl rounded-br-sm px-4 py-2.5 shadow-card
                        font-nunito font-700 text-sm text-ink max-w-[220px]
                        border-2 border-blue/30">
          {msg}
        </div>
      )}
      <span className="text-4xl select-none" style={{ filter: "drop-shadow(0 4px 12px rgba(79,195,247,.5))" }}>
        🧪
      </span>
    </div>
  );
}

export function beakySay(msg: string) {
  if (typeof window !== "undefined") {
    window.dispatchEvent(new CustomEvent("beaky", { detail: msg }));
  }
}
