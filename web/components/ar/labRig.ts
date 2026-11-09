import type * as THREE_NS from "three";
import type { ArScene, Piece } from "@/lib/arScenes";

/**
 * The 3D bench AR mode draws (FR-05.1), built from plain shapes so it stays light
 * enough for a 3 GB phone (NFR-11). The same rig is drawn on the desk in live AR
 * and in the labelled simulation (FR-05.4), and turns with the same Rotate control
 * (FR-05.5). Units are metres.
 */
type T = typeof THREE_NS;

export interface LabRig {
  root: THREE_NS.Group;
  /** Animate bubbles and flames; `t` in seconds. */
  tick(t: number): void;
  dispose(): void;
}

const TRAY_TOP = 0.012;

export function buildLab(THREE: T, scene: ArScene): LabRig {
  const root = new THREE.Group();
  const animated: ((t: number) => void)[] = [];

  const glass = new THREE.MeshStandardMaterial({
    color: 0xffffff, transparent: true, opacity: 0.28, roughness: 0.05, metalness: 0,
    side: THREE.DoubleSide, depthWrite: false,
  });
  const mat = (color: string | number, extra: Partial<THREE_NS.MeshStandardMaterialParameters> = {}) =>
    new THREE.MeshStandardMaterial({ color, roughness: 0.6, ...extra });

  // The tray, and a soft shadow so the bench sits on the desk rather than floating.
  const tray = new THREE.Mesh(new THREE.BoxGeometry(0.42, 0.012, 0.26), mat("#ECEFF1"));
  tray.position.y = 0.006;
  root.add(tray);
  const rim = new THREE.Mesh(new THREE.BoxGeometry(0.43, 0.004, 0.27), mat("#4FC3F7"));
  rim.position.y = 0.002;
  root.add(rim);
  const shadow = new THREE.Mesh(
    new THREE.CircleGeometry(0.28, 32),
    new THREE.MeshBasicMaterial({ color: 0x000000, transparent: true, opacity: 0.18, depthWrite: false }),
  );
  shadow.rotation.x = -Math.PI / 2;
  shadow.scale.set(1, 0.7, 1);
  shadow.position.y = 0.0005;
  root.add(shadow);

  function label(text: string, x: number, y: number, z: number) {
    const canvas = document.createElement("canvas");
    canvas.width = 256;
    canvas.height = 96;
    const ctx = canvas.getContext("2d")!;
    ctx.fillStyle = "rgba(45,55,72,.88)";
    ctx.beginPath();
    ctx.roundRect(8, 8, 240, 80, 40);
    ctx.fill();
    ctx.fillStyle = "#ffffff";
    ctx.font = "800 44px Nunito, system-ui, sans-serif";
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    ctx.fillText(text, 128, 50);
    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: texture, depthTest: false }));
    sprite.scale.set(0.06, 0.0225, 1);
    sprite.position.set(x, y, z);
    sprite.renderOrder = 10;
    root.add(sprite);
  }

  function openTube(radius: number, height: number) {
    const tube = new THREE.Mesh(new THREE.CylinderGeometry(radius, radius, height, 24, 1, true), glass);
    tube.position.y = height / 2;
    const bottom = new THREE.Mesh(new THREE.SphereGeometry(radius, 16, 8, 0, Math.PI * 2, Math.PI / 2, Math.PI / 2), glass);
    const g = new THREE.Group();
    g.add(tube, bottom);
    return g;
  }

  function liquid(radius: number, height: number, color: string) {
    const m = new THREE.Mesh(
      new THREE.CylinderGeometry(radius, radius, height, 24),
      mat(color, { transparent: true, opacity: 0.85, roughness: 0.2 }),
    );
    m.position.y = height / 2;
    return m;
  }

  const hasBurner = scene.pieces.some(p => p.kind === "burner");

  const build: { [K in Piece["kind"]]: (p: Extract<Piece, { kind: K }>) => void } = {
    burner(p) {
      const g = new THREE.Group();
      const base = new THREE.Mesh(new THREE.CylinderGeometry(0.03, 0.034, 0.01, 24), mat("#455A64", { metalness: 0.5 }));
      base.position.y = 0.005;
      const barrel = new THREE.Mesh(new THREE.CylinderGeometry(0.008, 0.008, 0.07, 16), mat("#90A4AE", { metalness: 0.7, roughness: 0.3 }));
      barrel.position.y = 0.045;
      const flame = new THREE.Mesh(
        new THREE.ConeGeometry(0.008, 0.035, 16),
        new THREE.MeshBasicMaterial({ color: "#42A5F5", transparent: true, opacity: 0.75 }),
      );
      flame.position.y = 0.098;
      const core = new THREE.Mesh(
        new THREE.ConeGeometry(0.004, 0.018, 12),
        new THREE.MeshBasicMaterial({ color: "#FFB74D", transparent: true, opacity: 0.9 }),
      );
      core.position.y = 0.09;
      g.add(base, barrel, flame, core);
      g.position.set(p.x, TRAY_TOP, p.z);
      root.add(g);
      animated.push(t => {
        const s = 1 + Math.sin(t * 18) * 0.06 + Math.sin(t * 7) * 0.04;
        flame.scale.set(1, s, 1);
        core.scale.set(1, 2 - s, 1);
      });
    },

    tube(p) {
      const g = openTube(0.012, 0.13);
      g.add(liquid(0.0105, p.fillHeight, p.fill));
      if (p.tilt !== undefined && hasBurner) {
        // Held in a clamp above the flame, mouth tilted slightly down as the book shows.
        g.rotation.z = -p.tilt;
        g.position.set(p.x - 0.01, TRAY_TOP + 0.12, p.z);
        const stand = new THREE.Group();
        const rod = new THREE.Mesh(new THREE.CylinderGeometry(0.004, 0.004, 0.24, 12), mat("#78909C", { metalness: 0.6 }));
        rod.position.set(-0.11, 0.12, 0);
        const foot = new THREE.Mesh(new THREE.BoxGeometry(0.08, 0.008, 0.06), mat("#546E7A"));
        foot.position.set(-0.11, 0.004, 0);
        const arm = new THREE.Mesh(new THREE.CylinderGeometry(0.003, 0.003, 0.1, 8), mat("#78909C", { metalness: 0.6 }));
        arm.rotation.z = Math.PI / 2;
        arm.position.set(-0.065, 0.17, 0);
        stand.add(rod, foot, arm);
        stand.position.set(p.x, TRAY_TOP, p.z);
        root.add(stand);
      } else {
        g.position.set(p.x, TRAY_TOP + 0.012, p.z);
      }
      root.add(g);
      if (p.label) label(p.label, p.x + (p.tilt !== undefined && hasBurner ? 0.05 : 0), TRAY_TOP + (p.tilt !== undefined && hasBurner ? 0.26 : 0.17), p.z);
    },

    rack(p) {
      const n = p.tubes.length;
      const gap = 0.05;
      const width = gap * n;
      const rack = new THREE.Mesh(new THREE.BoxGeometry(width + 0.02, 0.012, 0.05), mat("#A1887F"));
      rack.position.set(p.x, TRAY_TOP + 0.05, p.z);
      const base = new THREE.Mesh(new THREE.BoxGeometry(width + 0.02, 0.008, 0.05), mat("#8D6E63"));
      base.position.set(p.x, TRAY_TOP + 0.004, p.z);
      root.add(rack, base);
      for (const side of [-1, 1]) {
        const post = new THREE.Mesh(new THREE.BoxGeometry(0.008, 0.05, 0.05), mat("#8D6E63"));
        post.position.set(p.x + side * (width / 2 + 0.006), TRAY_TOP + 0.029, p.z);
        root.add(post);
      }
      p.tubes.forEach((t, i) => {
        const x = p.x - width / 2 + gap * (i + 0.5);
        const g = openTube(0.011, 0.12);
        g.add(liquid(0.0095, 0.05, t.fill));
        const metal = new THREE.Mesh(new THREE.BoxGeometry(0.008, 0.004, 0.008), mat("#9E9E9E", { metalness: 0.8, roughness: 0.3 }));
        metal.position.y = 0.002;
        g.add(metal);
        // Bubbles of hydrogen: more for the more reactive metal, none for copper.
        for (let b = 0; b < t.bubbles; b++) {
          const bubble = new THREE.Mesh(
            new THREE.SphereGeometry(0.0018, 8, 6),
            new THREE.MeshBasicMaterial({ color: 0xffffff, transparent: true, opacity: 0.85 }),
          );
          const phase = b / Math.max(t.bubbles, 1);
          const speed = 0.02 + t.bubbles * 0.004;
          const dx = (Math.sin(b * 12.9) * 0.5) * 0.008;
          g.add(bubble);
          animated.push(time => {
            const y = ((time * speed + phase * 0.05) % 0.05);
            bubble.position.set(dx, 0.004 + y, Math.cos(b * 7.1) * 0.003);
          });
        }
        g.position.set(x, TRAY_TOP + 0.008, p.z);
        root.add(g);
        label(t.label, x, TRAY_TOP + 0.16, p.z);
      });
    },

    longTube(p) {
      const g = new THREE.Group();
      const tube = new THREE.Mesh(new THREE.CylinderGeometry(0.012, 0.012, p.length, 24, 1, true), glass);
      tube.rotation.z = Math.PI / 2;
      g.add(tube);
      for (const side of [-1, 1]) {
        const plug = new THREE.Mesh(new THREE.SphereGeometry(0.012, 12, 8), mat("#FAFAFA", { roughness: 1 }));
        plug.position.x = side * (p.length / 2);
        g.add(plug);
        const support = new THREE.Mesh(new THREE.BoxGeometry(0.01, 0.05, 0.03), mat("#8D6E63"));
        support.position.set(side * (p.length / 2 - 0.04), -0.035, 0);
        g.add(support);
      }
      // The white ring of ammonium chloride, nearer the HCl end (ringAt from that end).
      const ring = new THREE.Mesh(new THREE.CylinderGeometry(0.0112, 0.0112, 0.012, 24), mat("#FFFFFF", { roughness: 1 }));
      ring.rotation.z = Math.PI / 2;
      ring.position.x = -p.length / 2 + p.ringAt * p.length;
      g.add(ring);
      g.position.set(p.x, TRAY_TOP + 0.06, p.z);
      root.add(g);
      label(p.ends[0], p.x - p.length / 2, TRAY_TOP + 0.11, p.z);
      label(p.ends[1], p.x + p.length / 2, TRAY_TOP + 0.11, p.z);
    },

    beaker(p) {
      const g = openTube(0.035, 0.075);
      g.children[1].scale.set(1, 0.05, 1);   // a flat-bottomed beaker
      g.add(liquid(0.033, p.fillHeight, p.fill));
      g.position.set(p.x, TRAY_TOP + 0.002, p.z);
      root.add(g);
      if (p.label) label(p.label, p.x, TRAY_TOP + 0.11, p.z);
    },

    paper(p) {
      const g = new THREE.Group();
      const sheet = new THREE.Mesh(new THREE.PlaneGeometry(0.03, 0.1), mat("#FFFFFF", { side: THREE.DoubleSide, roughness: 1 }));
      sheet.position.y = 0.05;
      g.add(sheet);
      const line = new THREE.Mesh(new THREE.PlaneGeometry(0.028, 0.0008), new THREE.MeshBasicMaterial({ color: "#9E9E9E", side: THREE.DoubleSide }));
      line.position.set(0, 0.022, 0.0006);
      g.add(line);
      // The dyes, already separated up the paper.
      p.spots.forEach((color, i) => {
        const spot = new THREE.Mesh(new THREE.CircleGeometry(0.0035, 16), new THREE.MeshBasicMaterial({ color, side: THREE.DoubleSide }));
        spot.position.set(0, 0.032 + i * 0.018, 0.0007);
        spot.scale.set(1, 0.8, 1);
        g.add(spot);
      });
      g.position.set(p.x, TRAY_TOP + 0.004, p.z);
      root.add(g);
      if (p.label) label(p.label, p.x, TRAY_TOP + 0.13, p.z);
    },
  };

  for (const piece of scene.pieces) {
    (build[piece.kind] as (p: Piece) => void)(piece);
  }

  return {
    root,
    tick(t) { for (const f of animated) f(t); },
    dispose() { disposeTree(root); },
  };
}

/** The ring that shows where the bench will land while AR looks for the desk. */
export function buildReticle(THREE: T): THREE_NS.Mesh {
  const reticle = new THREE.Mesh(
    new THREE.RingGeometry(0.06, 0.075, 40).rotateX(-Math.PI / 2),
    new THREE.MeshBasicMaterial({ color: "#4FC3F7", transparent: true, opacity: 0.9 }),
  );
  reticle.matrixAutoUpdate = false;
  reticle.visible = false;
  return reticle;
}

export function addLights(THREE: T, scene: THREE_NS.Scene) {
  scene.add(new THREE.HemisphereLight(0xffffff, 0xb0bec5, 2.2));
  const sun = new THREE.DirectionalLight(0xffffff, 1.6);
  sun.position.set(0.5, 1, 0.4);
  scene.add(sun);
}

/** FR-05.6 and NFR-11: give every buffer and texture back to the GPU. */
export function disposeTree(object: THREE_NS.Object3D) {
  object.traverse(o => {
    const mesh = o as THREE_NS.Mesh;
    mesh.geometry?.dispose();
    const materials = Array.isArray(mesh.material) ? mesh.material : mesh.material ? [mesh.material] : [];
    for (const m of materials) {
      (m as THREE_NS.MeshBasicMaterial).map?.dispose();
      m.dispose();
    }
  });
}
