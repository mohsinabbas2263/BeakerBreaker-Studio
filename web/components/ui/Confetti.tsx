"use client";

import { useEffect, useState } from "react";

const COLOURS = ["#4FC3F7", "#4CAF82", "#FF8A50", "#9575CD", "#FFD54F"];

/** The prototype's celebration burst. Respects reduced motion (NFR-08). */
export default function Confetti({ fire }: { fire: boolean }) {
  const [pieces, setPieces] = useState<{ left: number; delay: number; colour: string; rot: number }[]>([]);

  useEffect(() => {
    if (!fire) return;
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;

    setPieces(Array.from({ length: 70 }, (_, i) => ({
      left: Math.random() * 100,
      delay: Math.random() * 0.6,
      colour: COLOURS[i % COLOURS.length],
      rot: Math.random() * 360,
    })));
    const t = setTimeout(() => setPieces([]), 3200);
    return () => clearTimeout(t);
  }, [fire]);

  if (pieces.length === 0) return null;

  return (
    <div className="fixed inset-0 pointer-events-none z-[2500] overflow-hidden" aria-hidden>
      {pieces.map((p, i) => (
        <span key={i} className="confetti-piece"
              style={{ left: `${p.left}%`, animationDelay: `${p.delay}s`,
                       background: p.colour, transform: `rotate(${p.rot}deg)` }} />
      ))}
    </div>
  );
}
