"use client";

/**
 * M6 – FR-05.1 to FR-05.6: WebXR AR mode with three fallbacks.
 *
 * The page runs a phase machine:
 *   selector  → experiment chosen, "Launch AR" available
 *   launching → WebXR support check + camera permission request
 *   scanning  → hit-test surface detection (≤ 3 s, FR-05.3)
 *   live      → XR session active, camera feed, step overlay
 *   simulation→ fallback — all three reasons land here (FR-05.4)
 *
 * FR-05.5: StepOverlay and ControlBar are identical in live and simulation.
 * FR-05.6: exitAr() calls session.end() and stops all MediaStream tracks.
 */

import { Suspense, useState, useEffect, useRef, useCallback } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import Shell from "@/components/layout/Shell";
import {
  api,
  type ExperimentsListResponse,
  type ExperimentDetailResponse,
  type StartSessionResponse,
  type StepProgressResponse,
  type GuidanceResponse,
  type Step,
} from "@/lib/api";
import { useToast } from "@/components/ui/Toast";

// ── Minimal WebXR type shims ───────────────────────────────────────────────────
// @types/webxr is not installed; use targeted shims rather than `any` everywhere.

interface XRSystem {
  isSessionSupported(mode: string): Promise<boolean>;
  requestSession(mode: string, options?: object): Promise<XRSession>;
}

interface XRSession {
  requestAnimationFrame(cb: XRFrameCallback): number;
  requestReferenceSpace(type: string): Promise<XRReferenceSpace>;
  requestHitTestSource(options: { space: XRReferenceSpace }): Promise<XRHitTestSource>;
  updateRenderState(state: { baseLayer: XRWebGLLayer }): void;
  end(): Promise<void>;
  addEventListener(type: "end", cb: () => void): void;
}

interface XRReferenceSpace {}
interface XRHitTestSource {}
interface XRFrame {
  getHitTestResults(source: XRHitTestSource): ArrayLike<unknown>;
}
interface XRWebGLLayer {
  framebuffer: WebGLFramebuffer | null;
}
type XRFrameCallback = (time: number, frame: XRFrame) => void;

declare global {
  interface Navigator { xr?: XRSystem }
  const XRWebGLLayer: new (session: XRSession, gl: WebGL2RenderingContext) => XRWebGLLayer;
}

// ── Types ─────────────────────────────────────────────────────────────────────

type ArPhase = "selector" | "launching" | "scanning" | "live" | "simulation";
type FallbackReason = "unsupported" | "permission_denied" | "no_surface";

interface ArExperiment { id: number; title: string; emoji: string; topic: string }

interface RunningSession {
  sessionId: number;
  steps: Step[];
  stepIdx: number;
}

// ── Main component ────────────────────────────────────────────────────────────

// useSearchParams needs a Suspense boundary for the static build.
export default function ArPage() {
  return (
    <Suspense fallback={null}>
      <ArPageInner />
    </Suspense>
  );
}

function ArPageInner() {
  const router      = useRouter();
  const params      = useSearchParams();
  const toast       = useToast();

  // ── Experiment list (AR-enabled only, FR-05.1: ≥4) ─────────────────────────
  const [arExperiments, setArExperiments] = useState<ArExperiment[]>([]);
  const [selectedId, setSelectedId]       = useState<number | null>(null);

  // ── Phase machine ───────────────────────────────────────────────────────────
  const [phase,    setPhase]    = useState<ArPhase>("selector");
  const [fallback, setFallback] = useState<FallbackReason | null>(null);
  const [scanSecs, setScanSecs] = useState(3);

  // ── Running step session ────────────────────────────────────────────────────
  const [session,  setSession]  = useState<RunningSession | null>(null);
  const [guidance, setGuidance] = useState<string>("");
  const [rotation, setRotation] = useState(0);
  const [beakyOpen, setBeakyOpen] = useState(false);
  const [completing, setCompleting] = useState(false);

  // ── WebXR refs (kept outside React state so they don't trigger re-renders) ──
  const xrSessionRef       = useRef<XRSession | null>(null);
  const xrCanvasRef        = useRef<HTMLCanvasElement>(null);
  const mediaStreamRef     = useRef<MediaStream | null>(null);
  const hitTestSourceRef   = useRef<XRHitTestSource | null>(null);
  const scanTimerRef       = useRef<ReturnType<typeof setTimeout> | null>(null);
  const surfaceFoundRef    = useRef(false);

  // ── Load AR experiments on mount ────────────────────────────────────────────
  useEffect(() => {
    api.get<ExperimentsListResponse>("/experiments").then(res => {
      const ar = res.experiments
        .filter(e => e.ar_available)
        .map(e => ({ id: e.id, title: e.title, emoji: e.emoji, topic: e.topic }));
      setArExperiments(ar);

      // Pre-select from query param if provided.
      const expParam = params?.get("exp");
      if (expParam) {
        const id = Number(expParam);
        if (ar.some(e => e.id === id)) setSelectedId(id);
      }
    }).catch(() => {});
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  // ── Cleanup on unmount ──────────────────────────────────────────────────────
  useEffect(() => {
    return () => { void cleanupResources(); };
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  // ── Resource cleanup (FR-05.6) ──────────────────────────────────────────────
  const cleanupResources = useCallback(async () => {
    if (scanTimerRef.current) clearTimeout(scanTimerRef.current);

    if (hitTestSourceRef.current) {
      hitTestSourceRef.current = null;
    }

    if (xrSessionRef.current) {
      try { await xrSessionRef.current.end(); } catch {}
      xrSessionRef.current = null;
    }

    // Stop all camera/sensor tracks (FR-05.6).
    if (mediaStreamRef.current) {
      mediaStreamRef.current.getTracks().forEach(t => t.stop());
      mediaStreamRef.current = null;
    }
  }, []);

  // ── Start an experiment session ─────────────────────────────────────────────
  const startExperimentSession = useCallback(async (expId: number) => {
    try {
      const [detail, startRes] = await Promise.all([
        api.get<ExperimentDetailResponse>(`/experiments/${expId}`),
        api.post<StartSessionResponse>(`/experiments/${expId}/start`, { presentation_mode: "ar" }),
      ]);

      const completedIds = new Set(startRes.session.completed_step_ids);
      const firstIncomplete = detail.steps.findIndex(s => !completedIds.has(s.id));
      const stepIdx = firstIncomplete >= 0 ? firstIncomplete : 0;

      setSession({ sessionId: startRes.session.id, steps: detail.steps, stepIdx });
      fetchGuidance(detail.steps[stepIdx].id);
    } catch (err) {
      toast(err instanceof Error ? err.message : "Failed to start experiment", "error");
    }
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  // ── Guidance ────────────────────────────────────────────────────────────────
  async function fetchGuidance(stepId: number, question?: string) {
    setGuidance("");
    try {
      const res = await api.post<GuidanceResponse>(`/steps/${stepId}/guidance`, question ? { question } : {});
      setGuidance(res.text);
    } catch {
      setGuidance("Follow the step instructions carefully.");
    }
  }

  // ── Enter simulation fallback ───────────────────────────────────────────────
  const goToSimulation = useCallback(async (reason: FallbackReason, expId: number) => {
    setFallback(reason);
    setPhase("simulation");
    await startExperimentSession(expId);
  }, [startExperimentSession]);

  // ── Launch AR pipeline ──────────────────────────────────────────────────────
  const launchAr = useCallback(async () => {
    if (!selectedId) return;
    setPhase("launching");

    // FR-05.2 / FR-05.4 check 1: WebXR supported?
    const xr = navigator.xr;
    if (!xr) {
      await goToSimulation("unsupported", selectedId);
      return;
    }

    let supported = false;
    try {
      supported = await xr.isSessionSupported("immersive-ar");
    } catch {}

    if (!supported) {
      await goToSimulation("unsupported", selectedId);
      return;
    }

    // FR-05.2 / FR-05.4 check 2: camera permission (requestSession triggers the dialog).
    let xrSession: XRSession;
    try {
      xrSession = await xr.requestSession("immersive-ar", {
        requiredFeatures: ["hit-test"],
        optionalFeatures: ["dom-overlay"],
        domOverlay: xrCanvasRef.current
          ? { root: xrCanvasRef.current.parentElement ?? document.body }
          : undefined,
      });
    } catch (e: unknown) {
      const reason =
        e instanceof Error && e.name === "NotAllowedError"
          ? "permission_denied"
          : "unsupported";
      await goToSimulation(reason, selectedId);
      return;
    }

    xrSessionRef.current = xrSession;
    xrSession.addEventListener("end", () => {
      xrSessionRef.current = null;
    });

    // Set up WebGL rendering layer.
    // xrCanvasRef is always mounted (hidden off-screen) so it is available here.
    let layer: XRWebGLLayer;
    let gl: WebGL2RenderingContext;
    try {
      const canvas = xrCanvasRef.current!;
      gl = canvas.getContext("webgl2", { xrCompatible: true }) as WebGL2RenderingContext;
      await (gl as unknown as { makeXRCompatible(): Promise<void> }).makeXRCompatible?.();
      layer = new (window as unknown as { XRWebGLLayer: typeof XRWebGLLayer }).XRWebGLLayer(xrSession, gl);
      xrSession.updateRenderState({ baseLayer: layer });
    } catch {
      // Device does not support WebXR WebGL layer — fall to simulation.
      await goToSimulation("unsupported", selectedId);
      return;
    }

    // FR-05.3: request hit-test source for surface detection.
    let hitSource: XRHitTestSource;
    try {
      const refSpace = await xrSession.requestReferenceSpace("viewer");
      hitSource = await xrSession.requestHitTestSource({ space: refSpace });
    } catch {
      await goToSimulation("unsupported", selectedId);
      return;
    }
    hitTestSourceRef.current = hitSource;

    // Start the 3-second surface detection timer.
    surfaceFoundRef.current = false;
    setPhase("scanning");
    setScanSecs(3);

    let secsLeft = 3;
    const tick = setInterval(() => {
      secsLeft -= 1;
      setScanSecs(secsLeft);
      if (secsLeft <= 0) clearInterval(tick);
    }, 1000);

    scanTimerRef.current = setTimeout(async () => {
      clearInterval(tick);
      if (!surfaceFoundRef.current) {
        await goToSimulation("no_surface", selectedId);
      }
    }, 3000);

    // FR-05.3 render loop: detect surface, clear canvas.
    const renderLoop: XRFrameCallback = (_, frame) => {
      if (!frame) return;
      xrSession.requestAnimationFrame(renderLoop);

      const hits = hitTestSourceRef.current
        ? frame.getHitTestResults(hitTestSourceRef.current)
        : [];

      if (hits.length > 0 && !surfaceFoundRef.current) {
        surfaceFoundRef.current = true;
        clearTimeout(scanTimerRef.current!);
        clearInterval(tick);
        setPhase("live");
        void startExperimentSession(selectedId);
      }

      // Clear the canvas; WebXR composites the camera over it.
      gl.bindFramebuffer(gl.FRAMEBUFFER, layer.framebuffer);
      gl.clearColor(0, 0, 0, 0);
      gl.clear(gl.COLOR_BUFFER_BIT | gl.DEPTH_BUFFER_BIT);
    };

    xrSession.requestAnimationFrame(renderLoop);
  }, [selectedId, goToSimulation, startExperimentSession]);

  // ── Complete a step ─────────────────────────────────────────────────────────
  async function handleStepComplete() {
    if (!session || completing) return;
    setCompleting(true);

    const { sessionId, steps, stepIdx } = session;
    const step = steps[stepIdx];

    try {
      const res = await api.post<StepProgressResponse>(`/sessions/${sessionId}/steps`, { step_id: step.id });

      if (res.quiz_unlocked) {
        toast("All steps done! 🎉 Head to the quiz!", "success");
        await cleanupResources();
        const expId = selectedId!;
        router.push(`/experiments/${expId}/quiz`);
        return;
      }

      const nextIdx = stepIdx + 1;
      setSession(s => s ? { ...s, stepIdx: nextIdx } : null);
      setGuidance("");
      fetchGuidance(steps[nextIdx].id);
      toast(`Step ${stepIdx + 1} done! ✅`, "success");
    } catch (err) {
      toast(err instanceof Error ? err.message : "Could not record step", "error");
    } finally {
      setCompleting(false);
    }
  }

  // ── Exit ────────────────────────────────────────────────────────────────────
  async function handleExit() {
    await cleanupResources();
    router.back();
  }

  // ── Render ───────────────────────────────────────────────────────────────────

  const isRunning = phase === "live" || phase === "simulation";

  return (
    <Shell>
      <div className="p-4 md:p-6 max-w-2xl mx-auto space-y-4">

        {/* Header */}
        <div className="flex items-center gap-3">
          <button onClick={handleExit}
                  className="text-blue-d font-800 text-sm hover:underline">
            ← Back
          </button>
          <h1 className="font-fredoka font-600 text-xl text-ink flex-1">AR Mode 🥽</h1>
          {isRunning && (
            <span className={`text-[10px] font-900 px-3 py-1 rounded-full uppercase tracking-wide
                              ${phase === "live"
                                ? "bg-mint text-green-d"
                                : "bg-sky text-blue-d"}`}>
              {phase === "live" ? "● LIVE" : "◈ SIMULATION"}
            </span>
          )}
        </div>

        {/* Selector phase */}
        {(phase === "selector" || phase === "launching") && (
          <div className="bg-white rounded-card shadow-card p-5 space-y-4">
            <h2 className="font-fredoka font-600 text-base text-ink">Select Experiment</h2>

            {arExperiments.length === 0 ? (
              <p className="text-sub text-sm font-700">Loading AR experiments…</p>
            ) : (
              <select
                value={selectedId ?? ""}
                onChange={e => setSelectedId(Number(e.target.value))}
                className="w-full px-4 py-3 rounded-2xl border-2 border-sky bg-sky/40
                           font-nunito text-sm text-ink focus:outline-none focus:border-blue"
              >
                <option value="">Choose an AR experiment…</option>
                {arExperiments.map(e => (
                  <option key={e.id} value={e.id}>{e.emoji} {e.title}</option>
                ))}
              </select>
            )}

            <button
              onClick={launchAr}
              disabled={!selectedId || phase === "launching"}
              className="w-full py-3.5 rounded-card font-fredoka font-600 text-white text-lg
                         disabled:opacity-50 hover:opacity-90 transition-opacity"
              style={{ background: "linear-gradient(135deg,#00BCD4,#0097A7)",
                       boxShadow: "0 8px 24px rgba(0,188,212,.4)" }}
            >
              {phase === "launching" ? "Checking support…" : "🥽 Launch AR"}
            </button>

            <p className="text-[11px] text-sub font-700 text-center leading-relaxed">
              Requires a WebXR-compatible device and camera permission.<br />
              If AR is unavailable, the experiment runs in simulated mode — same controls (FR-05.4).
            </p>
          </div>
        )}

        {/* Scanning phase */}
        {phase === "scanning" && (
          <ViewportArea mode="scanning" scanSecs={scanSecs} rotation={rotation}>
            <></>
          </ViewportArea>
        )}

        {/* Live / Simulation viewport */}
        {isRunning && (
          <>
            {/* Fallback reason banner (FR-05.4) */}
            {phase === "simulation" && fallback && (
              <FallbackBanner reason={fallback} />
            )}

            {/* Viewport with canvas (live) or gradient (simulation) */}
            <ViewportArea
              mode={phase === "live" ? "live" : "simulation"}
              scanSecs={0}
              rotation={rotation}
              xrCanvasRef={xrCanvasRef}
            >
              {/* Step overlay — FR-05.5: identical in both modes */}
              {session && (
                <StepOverlay session={session} guidance={guidance} beakyOpen={beakyOpen} />
              )}
            </ViewportArea>

            {/* Control bar — FR-05.5: identical in both modes */}
            <ControlBar
              rotation={rotation}
              onRotate={() => setRotation(r => r + 90)}
              onBeaky={() => setBeakyOpen(o => !o)}
              onExit={handleExit}
              onComplete={handleStepComplete}
              completing={completing}
              hasSteps={!!session}
            />
          </>
        )}
      </div>

      {/* XR canvas: always mounted (position: absolute, off-screen) so xrCanvasRef
          is populated before launchAr() tries to create the WebGL layer. */}
      <canvas
        ref={xrCanvasRef}
        aria-hidden="true"
        style={{ position: "absolute", top: -9999, left: -9999, width: 1, height: 1 }}
      />
    </Shell>
  );
}

// ── Sub-components (FR-05.5: shared between live and simulation) ──────────────

function ViewportArea({
  mode,
  scanSecs,
  rotation,
  xrCanvasRef,
  children,
}: {
  mode: "live" | "simulation" | "scanning";
  scanSecs: number;
  rotation: number;
  xrCanvasRef?: React.RefObject<HTMLCanvasElement>;
  children: React.ReactNode;
}) {
  return (
    <div className="relative w-full rounded-card overflow-hidden"
         style={{ aspectRatio: "4/3", minHeight: 240 }}>

      {/* Background: camera feed (live) or dark gradient (simulation/scanning) */}
      {mode === "live" ? (
        <canvas
          ref={xrCanvasRef}
          className="absolute inset-0 w-full h-full"
          style={{ background: "#000" }}
        />
      ) : (
        <SimulatedBackground rotation={rotation} />
      )}

      {/* Scanning ring overlay (FR-05.3 detection feedback) */}
      {mode === "scanning" && (
        <div className="absolute inset-0 flex flex-col items-center justify-center gap-3">
          <ScanningRing />
          <p className="font-fredoka font-600 text-white text-sm drop-shadow">
            Scanning for flat surface… {scanSecs}s
          </p>
        </div>
      )}

      {/* Experiment overlay */}
      {mode !== "scanning" && children}
    </div>
  );
}

function SimulatedBackground({ rotation }: { rotation: number }) {
  return (
    <div
      className="absolute inset-0 flex items-center justify-center"
      style={{ background: "linear-gradient(135deg,#0a1628 0%,#0d2137 50%,#071020 100%)" }}
    >
      {/* Animated molecule placeholder */}
      <div
        className="text-8xl select-none"
        style={{
          transform: `rotate(${rotation}deg)`,
          transition: "transform 0.4s cubic-bezier(.68,-0.55,.27,1.55)",
          filter: "drop-shadow(0 0 24px rgba(79,195,247,.6))",
        }}
      >
        ⚗️
      </div>
    </div>
  );
}

function ScanningRing() {
  return (
    <div className="relative w-40 h-40">
      {/* Outer dashed ring */}
      <div className="absolute inset-0 rounded-full border-2 border-dashed border-white/60
                      animate-spin" style={{ animationDuration: "3s" }} />
      {/* Inner dotted ring */}
      <div className="absolute inset-4 rounded-full border border-dotted border-white/40
                      animate-spin" style={{ animationDuration: "2s", animationDirection: "reverse" }} />
      <div className="absolute inset-0 flex items-center justify-center">
        <span className="text-white/70 text-xs font-800 text-center leading-tight">
          surface<br />scanning
        </span>
      </div>
    </div>
  );
}

function StepOverlay({
  session,
  guidance,
  beakyOpen,
}: {
  session: RunningSession;
  guidance: string;
  beakyOpen: boolean;
}) {
  const { steps, stepIdx } = session;
  const step = steps[stepIdx];

  return (
    <div className="absolute bottom-0 left-0 right-0 bg-black/70 backdrop-blur-sm p-4 space-y-2">
      {/* Step info */}
      <div className="flex items-start gap-2">
        <span className="text-2xl flex-shrink-0">{step.item_emoji || "🔬"}</span>
        <div className="min-w-0">
          <p className="font-fredoka font-600 text-white text-sm leading-snug">{step.title}</p>
          <p className="text-white/70 text-xs font-700 leading-snug mt-0.5 line-clamp-2">
            {step.instruction_text}
          </p>
        </div>
        <span className="ml-auto text-white/50 text-xs font-800 flex-shrink-0">
          {stepIdx + 1}/{steps.length}
        </span>
      </div>

      {/* Guidance (shown when Beaky is open) */}
      {beakyOpen && guidance && (
        <div className="bg-blue/30 rounded-xl p-2.5">
          <p className="text-white text-xs font-700 leading-relaxed">{guidance}</p>
        </div>
      )}
    </div>
  );
}

/** FR-05.5: identical in LIVE and SIMULATION modes. */
function ControlBar({
  rotation,
  onRotate,
  onBeaky,
  onExit,
  onComplete,
  completing,
  hasSteps,
}: {
  rotation: number;
  onRotate: () => void;
  onBeaky: () => void;
  onExit: () => void;
  onComplete: () => void;
  completing: boolean;
  hasSteps: boolean;
}) {
  return (
    <div className="grid grid-cols-3 gap-3">
      <button
        onClick={onRotate}
        className="py-3.5 rounded-card font-fredoka font-600 text-sm text-ink
                   bg-white shadow-card hover:bg-sky transition-colors"
      >
        Rotate 90°
      </button>
      <button
        onClick={onBeaky}
        className="py-3.5 rounded-card font-fredoka font-600 text-sm text-white
                   transition-colors"
        style={{ background: "linear-gradient(135deg,#9575CD,#7E57C2)" }}
      >
        🧪 Beaky
      </button>
      {hasSteps ? (
        <button
          onClick={onComplete}
          disabled={completing}
          className="py-3.5 rounded-card font-fredoka font-600 text-sm text-white
                     disabled:opacity-50 hover:opacity-90 transition-opacity"
          style={{ background: "linear-gradient(135deg,#4CAF82,#2E7D57)" }}
        >
          {completing ? "…" : "✅ Done"}
        </button>
      ) : (
        <button
          onClick={onExit}
          className="py-3.5 rounded-card font-fredoka font-600 text-sm text-white
                     hover:opacity-90 transition-opacity"
          style={{ background: "linear-gradient(135deg,#FF8A50,#FF6D00)" }}
        >
          Exit
        </button>
      )}
    </div>
  );
}

function FallbackBanner({ reason }: { reason: FallbackReason }) {
  const messages: Record<FallbackReason, string> = {
    unsupported:        "Your device or browser does not support WebXR AR. Running in simulated mode.",
    permission_denied:  "Camera permission was not granted. Running in simulated mode.",
    no_surface:         "No flat surface was detected within 3 seconds. Running in simulated mode.",
  };

  return (
    <div data-testid="fallback-banner"
         className="bg-lemon rounded-2xl px-4 py-3 flex items-start gap-2">
      <span className="text-lg flex-shrink-0">◈</span>
      <p className="text-xs font-800 text-ink leading-relaxed">{messages[reason]}</p>
    </div>
  );
}
