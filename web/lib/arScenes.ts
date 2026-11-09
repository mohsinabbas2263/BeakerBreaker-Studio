/**
 * FR-05.1: what AR mode puts on the student's desk for each AR experiment. The
 * backend says which experiments have AR (`ar_available`, set by `'ar' => true` in
 * backend/database/seeders/game/*.php); this file says what their bench looks like.
 * Sizes are in metres, because in live AR they are drawn at real size on the desk.
 *
 * Presentation only: nothing here knows a right answer (FR-02.5).
 */
export type Piece =
  | { kind: "burner"; x: number; z: number }
  | { kind: "tube"; x: number; z: number; fill: string; fillHeight: number; tilt?: number; label?: string }
  | { kind: "rack"; x: number; z: number; tubes: { fill: string; label: string; bubbles: number }[] }
  | { kind: "longTube"; x: number; z: number; length: number; ringAt: number; ends: [string, string] }
  | { kind: "beaker"; x: number; z: number; fill: string; fillHeight: number; label?: string }
  | { kind: "paper"; x: number; z: number; spots: string[]; label?: string };

export interface ArScene {
  /** One line under the title, saying what is on the bench. */
  caption: string;
  pieces: Piece[];
}

const SCENES: Record<string, ArScene> = {
  "p9-hydrated-copper-sulphate": {
    caption: "Blue copper sulphate crystals in a boiling tube, over a burner.",
    pieces: [
      { kind: "burner", x: 0, z: 0 },
      { kind: "tube", x: 0, z: 0, fill: "#1E88E5", fillHeight: 0.03, tilt: 0.35, label: "CuSO₄·5H₂O" },
      { kind: "beaker", x: 0.13, z: 0.03, fill: "#E3F2FD", fillHeight: 0.04, label: "water" },
    ],
  },
  "p10-diffusion-hcl-nh3": {
    caption: "A long glass tube, cotton soaked in HCl at one end and NH₃ at the other.",
    pieces: [
      { kind: "longTube", x: 0, z: 0, length: 0.34, ringAt: 0.37, ends: ["HCl", "NH₃"] },
    ],
  },
  "f10-metal-reactivity": {
    caption: "Four test tubes of dilute HCl with magnesium, zinc, iron and copper.",
    pieces: [
      {
        kind: "rack", x: 0, z: 0, tubes: [
          { fill: "#ECEFF1", label: "Mg", bubbles: 9 },
          { fill: "#ECEFF1", label: "Zn", bubbles: 5 },
          { fill: "#ECEFF1", label: "Fe", bubbles: 2 },
          { fill: "#ECEFF1", label: "Cu", bubbles: 0 },
        ],
      },
    ],
  },
  "p11-paper-chromatography": {
    caption: "A chromatography paper standing in solvent, with an ink spot on its base line.",
    pieces: [
      { kind: "beaker", x: 0, z: 0, fill: "#E1F5FE", fillHeight: 0.015 },
      { kind: "paper", x: 0, z: 0, spots: ["#1565C0", "#D81B60", "#FBC02D"], label: "Rf" },
    ],
  },
  "f12-forensic-ink": {
    caption: "Two chromatography papers: ink from the note and ink from a suspect's pen.",
    pieces: [
      { kind: "beaker", x: -0.06, z: 0, fill: "#E1F5FE", fillHeight: 0.015 },
      { kind: "paper", x: -0.06, z: 0, spots: ["#283593", "#6A1B9A"], label: "note" },
      { kind: "beaker", x: 0.06, z: 0, fill: "#E1F5FE", fillHeight: 0.015 },
      { kind: "paper", x: 0.06, z: 0, spots: ["#283593", "#6A1B9A"], label: "pen" },
    ],
  },
};

/** A plain bench for an AR experiment this file does not know yet. */
const DEFAULT_SCENE: ArScene = {
  caption: "A beaker and a test tube on your bench.",
  pieces: [
    { kind: "beaker", x: -0.05, z: 0, fill: "#B3E5FC", fillHeight: 0.04 },
    { kind: "tube", x: 0.07, z: 0, fill: "#C8E6C9", fillHeight: 0.04 },
  ],
};

export function arScene(slug: string | undefined): ArScene {
  return (slug && SCENES[slug]) || DEFAULT_SCENE;
}
