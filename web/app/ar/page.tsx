"use client";

/**
 * M6, UC-04: AR mode, FR-05.1 to FR-05.6.
 *
 *   selector   → the student picks an AR experiment; the page says why it needs the camera
 *   starting   → WebXR support check, then the browser asks for the camera (FR-05.2)
 *   scanning   → hit-test looks for the desk for up to 3 s (FR-05.3)
 *   live       → the bench is anchored to the desk, the controls float over the camera
 *   simulation → the clearly labelled fallback (FR-05.4) for all three reasons:
 *                unsupported device, camera refused, no surface found
 *
 * FR-05.5: live and simulation show the same bench and the same controls, and both
 *          play the experiment through the game engine (useGameRunner), so every
 *          move is judged on the server (FR-02.5) and AR is never the only way to
 *          finish (the standard runner plays the same session).
 * FR-05.6: leaving AR ends the XR session, which releases the camera and the motion
 *          sensors, cancels the hit-test source and frees the GPU.
 */

import { Suspense, useCallback, useEffect, useMemo, useRef, useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import type * as THREE_NS from "three";
import Shell from "@/components/layout/Shell";
import GameStep from "@/components/game/GameStep";
import Confetti from "@/components/ui/Confetti";
import { useToast } from "@/components/ui/Toast";
import { addLights, buildLab, buildReticle, type LabRig } from "@/components/ar/labRig";
import { api, type ExperimentsListResponse, type ExperimentSummary } from "@/lib/api";
import { arScene } from "@/lib/arScenes";
import { useAuth } from "@/lib/auth";
import { avatarSrc } from "@/lib/avatars";
import { useGameRunner } from "@/lib/useGameRunner";

type Phase = "selector" | "starting" | "scanning" | "live" | "simulation";
type FallbackReason = "unsupported" | "permission_denied" | "no_surface";

const SCAN_MS = 3000;   // FR-05.3, NFR-12
const QUARTER = Math.PI / 2;

/** three.js is large; load it only on this page, and start as soon as the page opens. */
let threePromise: Promise<typeof THREE_NS> | null = null;
const loadThree = () => (threePromise ??= import("three"));

interface LiveXr {
  session: XRSession;
  renderer: THREE_NS.WebGLRenderer;
  rig: LabRig;
  hitSource: XRHitTestSource | null;
  scanTimer: ReturnType<typeof setTimeout> | null;
  /** Why we are ending the session: a fallback reason, "exit", or null if the browser ended it. */
  ending: FallbackReason | "exit" | null;
}

export default function ArPage() {
  return (
    <Suspense fallback={null}>
      <ArPageInner />
    </Suspense>
  );
}

function ArPageInner() {
  const router = useRouter();
  const params = useSearchParams();
  const toast = useToast();
  const { user } = useAuth();

  const [list, setList] = useState<ExperimentSummary[] | null>(null);
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [phase, setPhase] = useState<Phase>("selector");
  const [fallback, setFallback] = useState<FallbackReason | null>(null);
  const [xrActive, setXrActive] = useState(false);
  const [surfaceMs, setSurfaceMs] = useState<number | null>(null);
  const [turns, setTurns] = useState(0);
  const [panelOpen, setPanelOpen] = useState(true);
  const [askOpen, setAskOpen] = useState(false);
  const [no3d, setNo3d] = useState(false);

  const overlayRef = useRef<HTMLDivElement>(null);
  const panelRef = useRef<HTMLDivElement>(null);
  const simHostRef = useRef<HTMLDivElement>(null);
  const liveRef = useRef<LiveXr | null>(null);
  const turnsRef = useRef(0);
  turnsRef.current = turns;

  const selected = list?.find(e => e.id === selectedId) ?? null;
  const scene = useMemo(() => arScene(selected?.slug), [selected?.slug]);
  const running = phase !== "selector";

  const runner = useGameRunner(selectedId ? String(selectedId) : null, "ar", running);
  const { current, total, done, pct, finished } = runner;

  // ── The AR experiments this student may open (FR-05.1) ──────────────────────
  useEffect(() => {
    void loadThree().catch(() => {});
    api.get<ExperimentsListResponse>("/experiments")
      .then(res => {
        const ar = res.experiments.filter(e => e.ar_available);
        setList(ar);
        const wanted = Number(params?.get("exp"));
        if (ar.some(e => e.id === wanted)) setSelectedId(wanted);
        else if (ar.length === 1) setSelectedId(ar[0].id);
      })
      .catch(() => setList([]));
  }, [params]);

  // ── FR-05.6: release everything the live session holds ──────────────────────
  const releaseLive = useCallback(() => {
    const live = liveRef.current;
    if (!live) return;
    liveRef.current = null;
    if (live.scanTimer) clearTimeout(live.scanTimer);
    live.renderer.setAnimationLoop(null);
    try { live.hitSource?.cancel(); } catch {}
    live.rig.dispose();
    live.renderer.dispose();
    setXrActive(false);
  }, []);

  /** Ends the XR session; its "end" event releases the rest and moves on. */
  const endLive = useCallback((why: FallbackReason | "exit") => {
    const live = liveRef.current;
    if (!live) return Promise.resolve();
    live.ending = why;
    return live.session.end().catch(() => {});
  }, []);

  // Leaving the page by any route still ends the session and frees the camera.
  useEffect(() => () => {
    const live = liveRef.current;
    if (live) {
      live.ending = "exit";
      void live.session.end().catch(() => {});
      releaseLive();
    }
  }, [releaseLive]);

  // FR-05.4: the simulation appears at once; the experiment loads behind it.
  const goToSimulation = useCallback((reason: FallbackReason) => {
    setFallback(reason);
    setPhase("simulation");
  }, []);

  // ── Launch (UC-04 steps 1 to 4) ─────────────────────────────────────────────
  async function launch() {
    if (!selected) return;
    setFallback(null);
    setSurfaceMs(null);
    setPhase("starting");

    const xr = navigator.xr;
    if (!xr || !window.isSecureContext) return goToSimulation("unsupported");

    let supported = false;
    try { supported = await xr.isSessionSupported("immersive-ar"); } catch {}
    if (!supported) return goToSimulation("unsupported");

    // A camera already blocked for this site will not be asked again: say so now.
    try {
      const state = await navigator.permissions?.query({ name: "camera" as PermissionName });
      if (state?.state === "denied") return goToSimulation("permission_denied");
    } catch {}

    // FR-05.2: requesting the session is what shows the browser's camera prompt.
    // dom-overlay is required: without it the controls could not be shown over the
    // camera, and FR-05.5 needs the same controls in both presentations.
    let session: XRSession;
    try {
      session = await xr.requestSession("immersive-ar", {
        requiredFeatures: ["hit-test", "dom-overlay"],
        domOverlay: { root: overlayRef.current! },
      });
    } catch (e) {
      return goToSimulation(e instanceof DOMException && e.name === "NotAllowedError" ? "permission_denied" : "unsupported");
    }

    let THREE: typeof THREE_NS;
    let renderer: THREE_NS.WebGLRenderer;
    try {
      THREE = await loadThree();
      renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    } catch {
      await session.end().catch(() => {});
      return goToSimulation("unsupported");
    }
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.xr.enabled = true;
    renderer.xr.setReferenceSpaceType("local");

    const world = new THREE.Scene();
    addLights(THREE, world);
    const camera = new THREE.PerspectiveCamera();
    const reticle = buildReticle(THREE);
    world.add(reticle);
    const anchor = new THREE.Group();
    anchor.matrixAutoUpdate = false;
    anchor.visible = false;
    world.add(anchor);
    const rig = buildLab(THREE, scene);
    anchor.add(rig.root);

    const live: LiveXr = { session, renderer, rig, hitSource: null, scanTimer: null, ending: null };
    liveRef.current = live;
    setXrActive(true);

    session.addEventListener("end", () => {
      const why = live.ending;
      releaseLive();
      if (why && why !== "exit") goToSimulation(why);
      else if (!why) {
        // The browser or the student's back gesture closed AR. Nothing is lost.
        setPhase("selector");
        toast("AR closed. Your progress is saved.", "info");
      }
    });

    // Tapping the camera view moves the bench to where the ring is.
    session.addEventListener("select", () => {
      if (reticle.visible && anchor.visible) anchor.matrix.copy(reticle.matrix);
    });

    try {
      await renderer.xr.setSession(session);
      const viewer = await session.requestReferenceSpace("viewer");
      live.hitSource = (await session.requestHitTestSource?.({ space: viewer })) ?? null;
      if (!live.hitSource) throw new Error("no hit-test");
    } catch {
      await endLive("unsupported");
      return;
    }

    // FR-05.3: find the desk within 3 seconds, or fall back (FR-05.4).
    setPhase("scanning");
    const scanStart = performance.now();
    let placed = false;
    live.scanTimer = setTimeout(() => { if (!placed) void endLive("no_surface"); }, SCAN_MS);

    renderer.setAnimationLoop((time, frame) => {
      const space = renderer.xr.getReferenceSpace();
      if (frame && live.hitSource && space) {
        const hit = frame.getHitTestResults(live.hitSource)[0];
        const pose = hit?.getPose(space);
        reticle.visible = !!pose;
        if (pose) {
          reticle.matrix.fromArray(pose.transform.matrix);
          if (!placed) {
            placed = true;
            if (live.scanTimer) clearTimeout(live.scanTimer);
            anchor.matrix.copy(reticle.matrix);
            anchor.visible = true;
            setSurfaceMs(Math.round(performance.now() - scanStart));
            setPhase("live");
          }
        }
      }
      turnTowards(rig.root, turnsRef.current);
      rig.tick(time / 1000);
      renderer.render(world, camera);
    });
  }

  // Taps on the controls must not also move the bench (WebXR DOM overlay).
  useEffect(() => {
    const panel = panelRef.current;
    if (!panel) return;
    const stop = (e: Event) => e.preventDefault();
    panel.addEventListener("beforexrselect", stop);
    return () => panel.removeEventListener("beforexrselect", stop);
  }, [phase]);

  // ── The simulation's 3D bench (FR-05.4), the same rig as in live AR ─────────
  useEffect(() => {
    if (phase !== "simulation") return;
    const host = simHostRef.current;
    if (!host) return;
    let disposed = false;
    let cleanup = () => {};

    loadThree().then(THREE => {
      if (disposed) return;
      let renderer: THREE_NS.WebGLRenderer;
      try {
        renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
      } catch {
        setNo3d(true);   // NFR-11 asks for a 3D-capable browser; still playable below
        return;
      }
      renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
      renderer.domElement.className = "absolute inset-0 w-full h-full";
      renderer.domElement.setAttribute("aria-hidden", "true");
      host.appendChild(renderer.domElement);

      const world = new THREE.Scene();
      addLights(THREE, world);
      const camera = new THREE.PerspectiveCamera(40, 1, 0.01, 10);
      const rig = buildLab(THREE, scene);
      world.add(rig.root);

      const fit = () => {
        const w = host.clientWidth || 1;
        const h = host.clientHeight || 1;
        renderer.setSize(w, h, false);
        camera.aspect = w / h;
        // Pull back on a tall phone screen so the whole bench fits across.
        const distance = camera.aspect < 1 ? 0.5 / camera.aspect ** 0.6 : 0.5;
        camera.position.set(0, distance * 0.55, distance);
        camera.lookAt(0, 0.1, 0);
        camera.updateProjectionMatrix();
      };
      fit();
      const resize = new ResizeObserver(fit);
      resize.observe(host);

      renderer.setAnimationLoop(time => {
        turnTowards(rig.root, turnsRef.current, Math.sin(time / 2400) * 0.08);
        rig.tick(time / 1000);
        renderer.render(world, camera);
      });

      cleanup = () => {
        resize.disconnect();
        renderer.setAnimationLoop(null);
        rig.dispose();
        renderer.dispose();
        renderer.domElement.remove();
      };
    }).catch(() => setNo3d(true));

    return () => { disposed = true; cleanup(); };
  }, [phase, scene]);

  // The quiz opens: the camera is not needed any more (FR-05.6).
  useEffect(() => {
    if (finished) void endLive("exit");
  }, [finished, endLive]);

  async function exit() {
    await endLive("exit");
    router.push(selectedId ? `/experiments/${selectedId}` : "/experiments");
  }

  // ── Selector (UC-04 step 1) ──────────────────────────────────────────────────
  const selector = (
    <Shell>
      <div className="p-4 md:p-6 max-w-2xl mx-auto space-y-4">
        <div className="flex items-center gap-3">
          <Link href="/experiments" className="text-blue-d font-800 text-sm">‹ Back</Link>
          <h1 className="font-fredoka font-600 text-xl text-ink">AR Mode 🥽</h1>
        </div>

        <div className="rounded-card p-5 text-white space-y-1"
             style={{ background: "linear-gradient(135deg,#00BCD4,#0097A7)", boxShadow: "0 8px 24px rgba(0,188,212,.35)" }}>
          <p className="font-fredoka font-600 text-lg">Put the lab on your desk</p>
          <p className="text-sm font-700 text-white/90 leading-relaxed">
            Point your phone at a flat table and the bench appears on it. Your browser will ask to use
            the camera; it is only used to find the table, and nothing is recorded or uploaded.
          </p>
          <p className="text-xs font-700 text-white/80">
            No AR on this device, or camera not allowed? You get the same experiment as a simulation.
          </p>
        </div>

        <div className="bg-white rounded-card shadow-card p-5 space-y-3">
          <h2 className="font-fredoka font-600 text-base text-ink">Choose an experiment</h2>
          {list === null ? (
            <p className="text-sub text-sm font-700">Loading…</p>
          ) : list.length === 0 ? (
            <p className="text-sub text-sm font-700">None of your experiments has AR yet.</p>
          ) : (
            <div className="grid gap-2.5 sm:grid-cols-2" role="radiogroup" aria-label="AR experiments">
              {list.map(e => {
                const on = e.id === selectedId;
                return (
                  <button key={e.id} type="button" role="radio" aria-checked={on} disabled={e.locked}
                          onClick={() => setSelectedId(e.id)}
                          className={`flex items-center gap-3 text-left p-3 rounded-2xl border-2 transition-colors
                                      disabled:opacity-50 ${on ? "border-blue bg-sky" : "border-blue/15 bg-white hover:bg-sky/50"}`}>
                    <span className="text-3xl" aria-hidden>{e.emoji}</span>
                    <span className="min-w-0">
                      <span className="block font-fredoka font-600 text-sm text-ink leading-snug">{e.title}</span>
                      <span className="block text-[11px] font-800 text-sub">
                        {e.locked ? `🔒 ${e.lock_reason ?? "Locked"}` : `${e.topic} · ${e.step_count} steps`}
                      </span>
                    </span>
                  </button>
                );
              })}
            </div>
          )}
          <button onClick={launch} disabled={!selected || selected.locked}
                  className="w-full py-3.5 rounded-card font-fredoka font-600 text-white text-lg disabled:opacity-50 hover:opacity-90 transition-opacity"
                  style={{ background: "linear-gradient(135deg,#4FC3F7,#29B6F6)", boxShadow: "0 8px 24px rgba(79,195,247,.4)" }}>
            🥽 Launch AR
          </button>
        </div>
      </div>
    </Shell>
  );

  const worried = runner.mood === "worried";
  const showPanel = (phase === "live" || phase === "simulation") && !!current && !finished;

  return (
    <>
      {phase === "selector" && selector}

      {/* The AR view. Always mounted: in live AR it is the WebXR DOM overlay root,
          drawn over the camera, so it must exist before the session is requested. */}
      <div ref={overlayRef} aria-hidden={phase === "selector"}
           className={`fixed inset-0 z-[60] flex-col text-ink ${phase === "selector" ? "hidden" : "flex"}`}
           style={{ background: xrActive ? "transparent" : "linear-gradient(160deg,#0d2137 0%,#12355a 55%,#0a1628 100%)" }}>

        {/* Top bar: exit, title, which presentation this is (FR-05.4: clearly labelled) */}
        <div className="flex items-center gap-2 px-3 pt-[max(env(safe-area-inset-top),12px)] pb-2">
          <button onClick={exit} aria-label="Exit AR"
                  className="w-10 h-10 rounded-full bg-black/40 text-white text-lg font-900 flex items-center justify-center">✕</button>
          <p className="flex-1 min-w-0 text-center font-fredoka font-500 text-white truncate drop-shadow">
            {selected ? `${selected.emoji} ${selected.title}` : "AR Mode"}
          </p>
          <span className={`text-[10px] font-900 px-3 py-1.5 rounded-full uppercase tracking-wide
                            ${phase === "simulation" ? "bg-lemon text-orange-d" : "bg-mint text-green-d"}`}>
            {phase === "simulation" ? "◈ Simulation" : phase === "live" ? "● Live AR" : "AR"}
          </span>
        </div>

        {phase === "simulation" && fallback && (
          <FallbackBanner reason={fallback} onRetry={fallback === "unsupported" ? undefined : launch} />
        )}

        {/* The bench: drawn on the desk in live AR, in this box in the simulation */}
        <div className="relative flex-1 min-h-[130px]">
          {phase === "simulation" && (
            <div ref={simHostRef} className="absolute inset-0">
              {no3d && (
                <div className="absolute inset-0 flex items-center justify-center text-8xl" aria-hidden>
                  {selected?.emoji ?? "⚗️"}
                </div>
              )}
            </div>
          )}

          {(phase === "starting" || phase === "scanning") && (
            <div className="absolute inset-0 flex flex-col items-center justify-center gap-3 text-center px-6">
              <ScanningRing />
              <p className="font-fredoka font-600 text-white drop-shadow">
                {phase === "starting" ? "Starting AR…" : "Point your camera at a flat table…"}
              </p>
            </div>
          )}

          {phase !== "selector" && (
            <p className="absolute top-1 inset-x-0 text-center text-[11px] font-800 text-white/80 drop-shadow px-4">
              {phase === "live"
                ? `Desk found${surfaceMs !== null ? ` in ${(surfaceMs / 1000).toFixed(1)} s` : ""}. Tap the camera view to move the bench.`
                : phase === "simulation" ? scene.caption : ""}
            </p>
          )}
        </div>

        {/* The controls: the same in live and simulation (FR-05.5) */}
        {showPanel && current && (
          <div ref={panelRef} className="bg-white rounded-t-[22px] shadow-card max-h-[56vh] min-h-0 flex flex-col">
            <div className="flex items-center gap-2 px-4 pt-3">
              <div className="flex-1">
                <div className="flex justify-between text-[11px] font-800 text-sub mb-1">
                  <span>Step {Math.min(done + 1, total)} of {total}</span><span>{pct}%</span>
                </div>
                <div className="h-2 bg-sky rounded-full overflow-hidden" role="progressbar" aria-valuenow={pct} aria-valuemin={0} aria-valuemax={100}>
                  <div className="h-full rounded-full transition-all duration-500" style={{ width: `${pct}%`, background: "linear-gradient(90deg,#4FC3F7,#4CAF82)" }} />
                </div>
              </div>
              <button onClick={() => setPanelOpen(o => !o)} aria-expanded={panelOpen}
                      className="text-xs font-900 text-blue-d px-2 py-1">{panelOpen ? "Hide ▾" : "Show ▴"}</button>
            </div>

            {panelOpen && (
              <div className="flex-1 min-h-0 overflow-y-auto px-4 pb-2 pt-3 space-y-3">
                <div className="flex items-start gap-2.5">
                  <Image key={runner.moodKey} src={avatarSrc(user?.avatar_key)} alt="Your guide" width={48} height={48}
                         className={`rounded-full border-[3px] border-white shadow-card object-cover w-12 h-12 flex-shrink-0 mood-${runner.mood}`} />
                  <div className={`flex-1 rounded-2xl rounded-tl-sm px-3 py-2.5 ${worried ? "bg-coral" : runner.mood === "oops" ? "bg-lemon" : "bg-sky"}`}
                       aria-live="polite">
                    <p className="font-nunito font-800 text-sm text-ink leading-relaxed">{runner.line}</p>
                  </div>
                </div>

                <div>
                  <p className="font-fredoka font-600 text-ink">{current.step_order}. {current.title}</p>
                  <p className="text-xs text-sub font-700 leading-relaxed">{current.instruction_text}</p>
                </div>

                <GameStep key={current.id} step={current} progress={runner.progress} reveal={runner.reveal}
                          lastMistake={runner.lastMistake} busy={runner.busy} onAct={runner.act} />

                {askOpen && (
                  <div>
                    <form onSubmit={runner.ask} className="flex gap-2">
                      <label htmlFor="ar-ask" className="sr-only">Your question</label>
                      <input id="ar-ask" value={runner.question} onChange={e => runner.setQuestion(e.target.value)} maxLength={300}
                             placeholder="Ask about this step"
                             className="flex-1 min-w-0 px-3 py-2 rounded-xl border-2 border-blue/20 bg-sky text-sm font-700 focus:outline-none focus:border-blue" />
                      <button type="submit" disabled={runner.asking || !runner.question.trim()}
                              className="px-3 py-2 rounded-xl bg-blue-d text-white text-sm font-800 disabled:opacity-50">
                        {runner.asking ? "…" : "Ask"}
                      </button>
                    </form>
                    {runner.answer && (
                      <div className="mt-2 bg-sky rounded-2xl p-3">
                        <span className={`text-[10px] font-900 px-2 py-0.5 rounded-full ${runner.answer.source === "ai" ? "bg-white text-blue-d" : "bg-lemon text-orange-d"}`}>
                          {runner.answer.source === "ai" ? "AI" : "Lab notes"}
                        </span>
                        <p className="text-sm text-ink font-700 mt-1.5 leading-relaxed">{runner.answer.text}</p>
                      </div>
                    )}
                  </div>
                )}
              </div>
            )}

            {/* UC-04 step 5: rotate, guidance, exit */}
            <div className="flex-shrink-0 grid grid-cols-3 gap-2 px-4 pt-2 pb-[max(env(safe-area-inset-bottom),12px)] border-t border-sky">
              <button onClick={() => setTurns(t => t + 1)}
                      className="py-2.5 rounded-2xl bg-sky text-blue-d font-fredoka font-600 text-sm">⟳ Rotate</button>
              <button onClick={() => { setAskOpen(o => !o); setPanelOpen(true); }} aria-expanded={askOpen}
                      className="py-2.5 rounded-2xl text-white font-fredoka font-600 text-sm"
                      style={{ background: "linear-gradient(135deg,#9575CD,#7E57C2)" }}>💬 Ask</button>
              <button onClick={exit}
                      className="py-2.5 rounded-2xl text-white font-fredoka font-600 text-sm"
                      style={{ background: "linear-gradient(135deg,#FF8A50,#FF6D00)" }}>Exit</button>
            </div>
          </div>
        )}

        {(phase === "live" || phase === "simulation") && !current && !runner.error && (
          <p className="text-center text-white font-800 text-sm pb-8">Opening the experiment…</p>
        )}
        {runner.error && (
          <div className="bg-white rounded-t-[22px] p-5 text-center">
            <p className="font-fredoka font-600 text-ink">{runner.error}</p>
          </div>
        )}

        {finished && (
          <div className="bg-white rounded-t-[22px] p-5 text-center space-y-3 pb-[max(env(safe-area-inset-bottom),20px)]">
            <Confetti fire />
            <p className="font-fredoka font-600 text-xl text-ink">Experiment complete! 🎉</p>
            <p className="text-sub font-700 text-sm">Pass the quiz at 80% to earn {runner.detail?.experiment.base_points} XP.</p>
            <button onClick={() => router.push(`/experiments/${selectedId}/quiz?session=${runner.sessionId}`)}
                    className="w-full py-3.5 rounded-card font-fredoka font-600 text-white text-lg"
                    style={{ background: "linear-gradient(135deg,#9575CD,#7E57C2)" }}>
              📝 Take the Quiz
            </button>
          </div>
        )}
      </div>
    </>
  );
}

/** Rotate control: ease the bench to the next quarter turn, with the bouncy feel of the app. */
function turnTowards(root: THREE_NS.Object3D, quarterTurns: number, sway = 0) {
  const target = quarterTurns * QUARTER + sway;
  root.rotation.y += (target - root.rotation.y) * 0.14;
}

function ScanningRing() {
  return (
    <div className="relative w-36 h-36" aria-hidden>
      <div className="absolute inset-0 rounded-full border-2 border-dashed border-white/70 animate-spin" style={{ animationDuration: "3s" }} />
      <div className="absolute inset-5 rounded-full border border-dotted border-white/50 animate-spin"
           style={{ animationDuration: "2s", animationDirection: "reverse" }} />
      <div className="absolute inset-0 flex items-center justify-center text-4xl">🔍</div>
    </div>
  );
}

/** FR-05.4: say plainly why this is a simulation. */
function FallbackBanner({ reason, onRetry }: { reason: FallbackReason; onRetry?: () => void }) {
  const messages: Record<FallbackReason, string> = {
    unsupported: "This device or browser cannot run AR, so here is the simulation. Same experiment, same controls.",
    permission_denied: "The camera was not allowed, so here is the simulation. You can allow it in your browser settings and try again.",
    no_surface: "No flat table was found in 3 seconds, so here is the simulation. Try again in better light.",
  };
  return (
    <div role="status" data-testid="fallback-banner" className="mx-3 mb-1 bg-lemon rounded-2xl px-4 py-2.5 flex items-start gap-2">
      <span className="text-base" aria-hidden>◈</span>
      <p className="flex-1 text-xs font-800 text-ink leading-relaxed">{messages[reason]}</p>
      {onRetry && (
        <button onClick={onRetry} className="text-[11px] font-900 text-blue-d bg-white rounded-full px-3 py-1 flex-shrink-0">
          Try AR again
        </button>
      )}
    </div>
  );
}
