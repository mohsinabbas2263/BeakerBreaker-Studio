"use client";

import { useEffect, useState } from "react";
import type { Step, StepActionResponse } from "@/lib/api";
import { colour, item, place } from "@/lib/labItems";

/**
 * docs/experiment-engine.md: the controls for one game step. The component only
 * sends what the student did (onAct); the server judges it (FR-02.5), so nothing
 * here knows a right answer, except what the server reveals after 3 mistakes.
 *
 * Every drag also works as a tap, because HTML drag and drop does not work on
 * most phones, and taps are what keyboard and screen reader users can do too.
 */
type Action = Record<string, string | number>;

interface Props {
  step: Step;
  progress: StepActionResponse["progress"];
  reveal: Record<string, unknown> | null;
  /** The last mistake code, so add_until can start a fresh run after an overshoot. */
  lastMistake: string | null;
  busy: boolean;
  onAct: (action: Action) => void;
}

const chip = "flex flex-col items-center justify-center gap-1 w-[84px] h-[84px] rounded-2xl bg-white border-2 shadow-sm " +
  "font-nunito font-800 text-[11px] text-ink text-center leading-tight px-1 transition-transform duration-300 " +
  "hover:scale-105 focus:outline-none focus-visible:ring-4 focus-visible:ring-blue/40 disabled:opacity-50";
const bounce = { transitionTimingFunction: "cubic-bezier(.68,-0.55,.27,1.55)" };

function Item({ id, onPick, picked, done, disabled, glow }: {
  id: string; onPick: () => void; picked?: boolean; done?: boolean; disabled?: boolean; glow?: boolean;
}) {
  const it = item(id);
  return (
    <button type="button" draggable={!disabled && !done} disabled={disabled || done} onClick={onPick}
            onDragStart={e => e.dataTransfer.setData("text/plain", id)}
            aria-pressed={picked} aria-label={it.label}
            className={`${chip} ${picked ? "border-blue ring-4 ring-blue/30" : done ? "border-green bg-mint" : "border-blue/20"}
                        ${glow ? "bench-active" : ""} ${disabled || done ? "" : "cursor-grab"}`}
            style={bounce}>
      <span className="text-3xl" aria-hidden>{done ? "✅" : it.emoji}</span>
      <span>{it.label}</span>
    </button>
  );
}

function DropZone({ label, onDrop, filled, active, onTap, wide }: {
  label: string; onDrop: (id: string) => void; filled?: string | null; active?: boolean; onTap?: () => void; wide?: boolean;
}) {
  const [over, setOver] = useState(false);
  return (
    <button type="button" onClick={onTap}
            onDragOver={e => { e.preventDefault(); setOver(true); }} onDragLeave={() => setOver(false)}
            onDrop={e => { e.preventDefault(); setOver(false); onDrop(e.dataTransfer.getData("text/plain")); }}
            className={`rounded-2xl border-3 border-dashed flex flex-col items-center justify-center text-center p-3
                        min-h-[110px] ${wide ? "w-full" : "w-[150px]"} transition-colors
                        ${over || active ? "border-green bg-mint" : filled ? "border-green/60 bg-mint/60" : "border-blue/40 bg-sky"}`}>
      {filled
        ? <><span className="text-3xl" aria-hidden>{item(filled).emoji}</span><span className="text-xs font-800 text-ink mt-1">{item(filled).label}</span></>
        : <span className="text-3xl opacity-40" aria-hidden>⬇️</span>}
      <span className="text-[11px] font-900 text-sub uppercase tracking-wide mt-1">{label}</span>
    </button>
  );
}

// ---- the kinds ------------------------------------------------------------------

function GearOrChoose({ step, progress, reveal, busy, onAct }: Props) {
  const items = step.config.items ?? [];
  const worn = progress.worn ?? [];
  const target = step.kind === "gear" ? "You" : place(String(step.config.target ?? "beaker"));
  const hint = (reveal?.required as string[] | undefined) ?? (reveal?.correct ? [String(reveal.correct)] : []);

  return (
    <div className="flex flex-col items-center gap-4">
      <DropZone label={step.kind === "gear" ? "Drag onto yourself" : `Into the ${target}`} wide
                onDrop={id => id && onAct({ item: id })} />
      <div className="flex flex-wrap gap-2.5 justify-center">
        {items.map(id => (
          <Item key={id} id={id} disabled={busy} done={worn.includes(id)} glow={hint.includes(id)}
                onPick={() => onAct({ item: id })} />
        ))}
      </div>
      <p className="text-[11px] text-muted font-700">Tap an item, or drag it.</p>
    </div>
  );
}

function Place({ step, progress, reveal, busy, onAct }: Props) {
  const [picked, setPicked] = useState<string | null>(null);
  const placed = progress.placed ?? {};
  const answer = (reveal?.answer as Record<string, string> | undefined) ?? {};

  const drop = (zone: string, id: string | null) => {
    if (!id) return;
    setPicked(null);
    onAct({ item: id, zone });
  };

  return (
    <div className="flex flex-col items-center gap-4">
      <div className="flex flex-wrap gap-3 justify-center">
        {(step.config.zones ?? []).map(z => (
          <DropZone key={z} label={place(z)} filled={placed[z] ?? null} active={!!picked && !placed[z]}
                    onDrop={id => drop(z, id)} onTap={() => drop(z, picked)} />
        ))}
      </div>
      <div className="flex flex-wrap gap-2.5 justify-center">
        {(step.config.items ?? []).map(id => (
          <Item key={id} id={id} disabled={busy} picked={picked === id} glow={Object.values(answer).includes(id)}
                done={Object.values(placed).includes(id) && !step.config.simultaneous}
                onPick={() => setPicked(p => (p === id ? null : id))} />
        ))}
      </div>
      <p className="text-[11px] text-muted font-700">
        {picked ? `Now tap where the ${item(picked).label.toLowerCase()} goes.` : "Tap an item, then tap its place. Or drag it."}
      </p>
    </div>
  );
}

function Measure({ step, reveal, busy, onAct }: Props) {
  const c = step.config;
  const min = c.min ?? 0, max = c.max ?? 50, inc = c.step ?? 1;
  const [value, setValue] = useState(min);
  const decimals = String(inc).split(".")[1]?.length ?? 0;
  useEffect(() => setValue(min), [step.id, min]);

  return (
    <div className="flex flex-col items-center gap-4 w-full max-w-sm mx-auto">
      <p className="font-fredoka font-600 text-4xl text-blue-d" aria-live="polite">
        {value.toFixed(decimals)}<span className="text-lg text-sub ml-1">{c.unit}</span>
      </p>
      <input type="range" min={min} max={max} step={inc} value={value} onChange={e => setValue(Number(e.target.value))}
             aria-label={`Amount in ${c.unit || "units"}`} className="w-full accent-[#29B6F6]" />
      <div className="flex gap-2">
        {[-inc, inc].map(d => (
          <button key={d} type="button" onClick={() => setValue(v => Math.min(max, Math.max(min, +(v + d).toFixed(6))))}
                  className="w-12 h-10 rounded-xl bg-sky text-blue-d font-900">{d > 0 ? "+" : "−"}</button>
        ))}
      </div>
      {reveal?.target !== undefined && <p className="text-xs font-800 text-green-d">Hint: {String(reveal.target)} {c.unit}</p>}
      <button type="button" disabled={busy} onClick={() => onAct({ value })} className={primary}>
        {step.action_label ?? "Transfer"} 👉
      </button>
    </div>
  );
}

function AddUntil({ step, reveal, busy, onAct, lastMistake }: Props) {
  const c = step.config;
  const incs = c.increments ?? [1];
  const [added, setAdded] = useState(0);
  const decimals = Math.max(...incs.map(i => String(i).split(".")[1]?.length ?? 0));
  useEffect(() => setAdded(0), [step.id]);
  // After an overshoot the run is spoilt, as in a real titration: start a fresh one.
  useEffect(() => { if (lastMistake === "overshoot") setAdded(0); }, [lastMistake]);

  const v = c.visual;
  const end = c.end ?? 0;
  const fade = v ? Math.min(1, Math.max(0, (added - v.fade_from) / Math.max(0.0001, end - v.fade_from))) : 0;

  return (
    <div className="flex flex-col items-center gap-4">
      {v && (
        <div className="relative w-28 h-32" aria-hidden>
          <div className="absolute inset-x-3 bottom-0 h-24 rounded-b-[40px] rounded-t-lg border-4 border-blue/30 overflow-hidden bg-white">
            <div className="absolute inset-x-0 bottom-0 h-3/4 transition-colors duration-500"
                 style={{ background: colour(v.from) }} />
            <div className="absolute inset-x-0 bottom-0 h-3/4 transition-opacity duration-500"
                 style={{ background: colour(v.to), opacity: fade }} />
          </div>
        </div>
      )}
      <p className="font-fredoka font-600 text-3xl text-blue-d" aria-live="polite">
        {added.toFixed(decimals)}<span className="text-base text-sub ml-1">{c.unit} added</span>
      </p>
      <div className="flex gap-2 flex-wrap justify-center">
        {incs.map(i => (
          <button key={i} type="button" disabled={busy} onClick={() => setAdded(a => +(a + i).toFixed(6))}
                  className="px-4 py-2.5 rounded-xl bg-sky text-blue-d font-900 hover:bg-blue/20">+{i} {c.unit}</button>
        ))}
      </div>
      {reveal?.end !== undefined && <p className="text-xs font-800 text-green-d">Hint: stop at {String(reveal.end)} {c.unit}</p>}
      <button type="button" disabled={busy || added === 0} onClick={() => onAct({ stop_at: added })} className={primary}>
        ✋ Stop here
      </button>
    </div>
  );
}

function Heat({ step, progress, reveal, busy, onAct }: Props) {
  const levels = step.config.levels ?? ["low", "high"];
  const [level, setLevel] = useState(levels[0]);
  const [seconds, setSeconds] = useState(10);
  const stage = progress.stage ?? 0;
  const seq = reveal?.sequence as string[] | undefined;

  return (
    <div className="flex flex-col items-center gap-4 w-full max-w-sm mx-auto">
      <div className="text-6xl" aria-hidden>{level === levels[levels.length - 1] ? "🔥" : "🕯️"}</div>
      <div className="flex gap-2" role="group" aria-label="Flame">
        {levels.map(l => (
          <button key={l} type="button" onClick={() => setLevel(l)} aria-pressed={level === l}
                  className={`px-4 py-2.5 rounded-xl font-900 text-sm ${level === l ? "bg-orange text-white" : "bg-sky text-sub"}`}>
            {l === "low" ? "Gentle flame" : l === "high" ? "Strong flame" : l}
          </button>
        ))}
      </div>
      <label className="w-full text-center">
        <span className="text-sm font-800 text-sub">Heat for {seconds} seconds</span>
        <input type="range" min={0} max={60} step={5} value={seconds} onChange={e => setSeconds(Number(e.target.value))}
               className="w-full accent-[#FF8A50]" />
      </label>
      {stage > 0 && <p className="text-xs font-800 text-green-d">Stage {stage} done. Keep going.</p>}
      {seq && <p className="text-xs font-800 text-green-d">Hint: {seq.join(", then ")}, at least {String(reveal?.min_seconds)} s each</p>}
      <button type="button" disabled={busy} onClick={() => onAct({ level, seconds })} className={primary}>🔥 Heat</button>
    </div>
  );
}

function Instrument({ kind, value, unit }: { kind: string; value: number; unit: string }) {
  // A scale with ticks and a marker at the reading; the number itself is not printed,
  // because reading the scale is the task.
  const vertical = kind === "thermometer";
  const max = kind === "thermometer" ? 110 : kind === "syringe" ? 60 : Math.max(10, Math.ceil(value / 10) * 10 + 10);
  const major = kind === "thermometer" ? 10 : kind === "syringe" ? 10 : max > 20 ? 10 : 1;
  const minor = major / (kind === "ruler" && max <= 20 ? 10 : 5);
  const ticks = Array.from({ length: Math.round(max / minor) + 1 }, (_, i) => +(i * minor).toFixed(4));
  const L = 280, pos = (v: number) => (v / max) * L;

  return (
    <svg viewBox={vertical ? "0 0 90 320" : "0 0 320 90"} className={vertical ? "h-64" : "w-full max-w-md"}
         role="img" aria-label={`${kind} scale in ${unit}`}>
      <g transform={vertical ? "translate(45,300) rotate(-90)" : "translate(20,20)"}>
        <rect x={-6} y={0} width={L + 12} height={26} rx={13} fill="#E8F4FD" stroke="#29B6F6" strokeWidth={2} />
        <rect x={0} y={6} width={pos(value)} height={14} rx={7}
              fill={kind === "thermometer" ? "#FF6D00" : kind === "syringe" ? "#9575CD" : "#4CAF82"} />
        {ticks.map(t => {
          const isMajor = Math.abs(t / major - Math.round(t / major)) < 1e-6;
          return (
            <g key={t}>
              <line x1={pos(t)} x2={pos(t)} y1={26} y2={isMajor ? 40 : 33} stroke="#2D3748" strokeWidth={isMajor ? 1.5 : 0.7} />
              {isMajor && (
                <text x={pos(t)} y={54} fontSize={11} textAnchor="middle" fill="#718096"
                      transform={vertical ? `rotate(90 ${pos(t)} 54)` : undefined}>{t}</text>
              )}
            </g>
          );
        })}
      </g>
    </svg>
  );
}

function ReadOrNumber({ step, reveal, busy, onAct }: Props) {
  const [value, setValue] = useState("");
  useEffect(() => setValue(""), [step.id]);
  const c = step.config as Step["config"] & { instrument?: string; display?: number };

  return (
    <form onSubmit={e => { e.preventDefault(); if (value.trim() !== "") onAct({ value: Number(value) }); }}
          className="flex flex-col items-center gap-4 w-full">
      {c.instrument && c.display !== undefined && <Instrument kind={c.instrument} value={c.display} unit={c.unit ?? ""} />}
      <label className="flex items-center gap-2">
        <span className="sr-only">Your reading</span>
        <input type="number" inputMode="decimal" step="any" value={value} onChange={e => setValue(e.target.value)}
               className="w-36 px-4 py-3 rounded-2xl bg-sky border-2 border-blue/20 focus:border-blue outline-none
                          font-fredoka font-600 text-2xl text-ink text-center" placeholder="?" />
        <span className="font-fredoka font-600 text-lg text-sub">{c.unit}</span>
      </label>
      {reveal?.value !== undefined && <p className="text-xs font-800 text-green-d">Hint: {String(reveal.value)} {c.unit}</p>}
      <button type="submit" disabled={busy || value.trim() === ""} className={primary}>Check ✔️</button>
    </form>
  );
}

function Observe({ step, reveal, busy, onAct }: Props) {
  const options = step.config.options ?? {};
  return (
    <div className="grid sm:grid-cols-2 gap-2.5 w-full">
      {Object.entries(options).map(([key, text]) => (
        <button key={key} type="button" disabled={busy} onClick={() => onAct({ option: key })}
                className={`text-left px-4 py-3 rounded-2xl border-2 font-nunito font-800 text-sm text-ink transition-transform
                            hover:scale-[1.02] disabled:opacity-60
                            ${reveal?.correct === key ? "border-green bg-mint bench-active" : "border-blue/20 bg-white"}`}
                style={bounce}>
          <span className="text-blue-d font-900 mr-1.5">{key.toUpperCase()}.</span>{text}
        </button>
      ))}
    </div>
  );
}

const primary = "w-full max-w-sm py-3.5 rounded-2xl font-fredoka font-600 text-white text-base bg-green " +
  "hover:bg-green-d shadow-md transition-transform hover:scale-[1.02] disabled:opacity-60";

export default function GameStep(props: Props) {
  switch (props.step.kind) {
    case "gear":
    case "choose": return <GearOrChoose {...props} />;
    case "place": return <Place {...props} />;
    case "measure": return <Measure {...props} />;
    case "add_until": return <AddUntil {...props} />;
    case "heat": return <Heat {...props} />;
    case "read": return <ReadOrNumber {...props} />;
    case "observe": return <Observe {...props} />;
    default:
      return (
        <button type="button" disabled={props.busy} onClick={() => props.onAct({})} className={primary}>
          👉 {props.step.action_label || "Done with this step"}
        </button>
      );
  }
}
