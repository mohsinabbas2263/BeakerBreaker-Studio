import 'dart:math' as math;

import 'package:flutter/material.dart';

/// FR-05.1: what AR mode puts on the student's desk, the app's copy of the web
/// portal's web/lib/arScenes.ts and components/ar/labRig.ts. The bench is a list of
/// plain shapes, so ARCore (Android), ARKit (iOS) and the simulation (FR-05.4) all
/// draw the same thing. Sizes are in metres; y is up, the tray top is at y = 0.012.
enum Shape { box, cylinder, sphere }

class Prim {
  const Prim(this.shape, this.size, this.pos, this.color, {this.horizontal = false, this.opacity = 1});
  final Shape shape;

  /// box: (w, h, d); cylinder: (radius, height, -); sphere: (radius, -, -)
  final (double, double, double) size;
  final (double, double, double) pos;
  final Color color;

  /// A cylinder lying along x instead of standing up.
  final bool horizontal;
  final double opacity;

  /// The same shape turned [angle] radians about the vertical axis through the bench.
  Prim turned(double angle) {
    final (x, y, z) = pos;
    final c = math.cos(angle), s = math.sin(angle);
    return Prim(shape, size, (x * c + z * s, y, -x * s + z * c), color, horizontal: horizontal, opacity: opacity);
  }
}

class Bench {
  const Bench(this.caption, this.prims);
  final String caption;
  final List<Prim> prims;
}

const _top = 0.012;
const _glass = Color(0xFFE3F2FD);

List<Prim> _tray() => const [
      Prim(Shape.box, (0.43, 0.004, 0.27), (0, 0.002, 0), Color(0xFF4FC3F7)),
      Prim(Shape.box, (0.42, 0.012, 0.26), (0, 0.006, 0), Color(0xFFECEFF1)),
    ];

List<Prim> _tube(double x, double z, Color fill, {double y = _top, double h = 0.13, double fillH = 0.04}) => [
      Prim(Shape.cylinder, (0.012, h, 0), (x, y + h / 2, z), _glass, opacity: .35),
      Prim(Shape.cylinder, (0.0105, fillH, 0), (x, y + fillH / 2, z), fill),
    ];

List<Prim> _beaker(double x, double z, Color fill, double fillH) => [
      Prim(Shape.cylinder, (0.035, 0.075, 0), (x, _top + 0.0375, z), _glass, opacity: .3),
      Prim(Shape.cylinder, (0.033, fillH, 0), (x, _top + fillH / 2, z), fill),
    ];

List<Prim> _burner(double x, double z) => [
      Prim(Shape.cylinder, (0.032, 0.01, 0), (x, _top + 0.005, z), const Color(0xFF455A64)),
      Prim(Shape.cylinder, (0.008, 0.07, 0), (x, _top + 0.045, z), const Color(0xFF90A4AE)),
      Prim(Shape.sphere, (0.009, 0, 0), (x, _top + 0.09, z), const Color(0xFF42A5F5), opacity: .8),
      Prim(Shape.sphere, (0.005, 0, 0), (x, _top + 0.087, z), const Color(0xFFFFB74D)),
    ];

List<Prim> _paper(double x, double z, List<Color> spots) => [
      Prim(Shape.box, (0.03, 0.1, 0.001), (x, _top + 0.05, z), Colors.white),
      for (var i = 0; i < spots.length; i++) Prim(Shape.sphere, (0.0035, 0, 0), (x, _top + 0.032 + i * 0.018, z + 0.001), spots[i]),
    ];

final Map<String, Bench> _benches = {
  'p9-hydrated-copper-sulphate': Bench('Blue copper sulphate crystals in a boiling tube, over a burner.', [
    ..._tray(),
    ..._burner(0, 0),
    Prim(Shape.cylinder, (0.004, 0.24, 0), (-0.11, _top + 0.12, 0), const Color(0xFF78909C)),
    Prim(Shape.box, (0.08, 0.008, 0.06), (-0.11, _top + 0.004, 0), const Color(0xFF546E7A)),
    ..._tube(0, 0, const Color(0xFF1E88E5), y: _top + 0.11, fillH: 0.03),
    ..._beaker(0.13, 0.03, const Color(0xFFE3F2FD), 0.04),
  ]),
  'p10-diffusion-hcl-nh3': Bench('A long glass tube, cotton soaked in HCl at one end and NH₃ at the other.', [
    ..._tray(),
    const Prim(Shape.cylinder, (0.012, 0.34, 0), (0, _top + 0.06, 0), _glass, horizontal: true, opacity: .35),
    const Prim(Shape.sphere, (0.012, 0, 0), (-0.17, _top + 0.06, 0), Color(0xFFFAFAFA)),
    const Prim(Shape.sphere, (0.012, 0, 0), (0.17, _top + 0.06, 0), Color(0xFFFAFAFA)),
    // The white ring of ammonium chloride, nearer the HCl end.
    const Prim(Shape.cylinder, (0.0115, 0.012, 0), (-0.044, _top + 0.06, 0), Colors.white, horizontal: true),
    const Prim(Shape.box, (0.01, 0.05, 0.03), (-0.13, _top + 0.025, 0), Color(0xFF8D6E63)),
    const Prim(Shape.box, (0.01, 0.05, 0.03), (0.13, _top + 0.025, 0), Color(0xFF8D6E63)),
  ]),
  'f10-metal-reactivity': Bench('Four test tubes of dilute HCl with magnesium, zinc, iron and copper.', [
    ..._tray(),
    const Prim(Shape.box, (0.22, 0.012, 0.05), (0, _top + 0.05, 0), Color(0xFFA1887F)),
    const Prim(Shape.box, (0.22, 0.008, 0.05), (0, _top + 0.004, 0), Color(0xFF8D6E63)),
    for (var i = 0; i < 4; i++) ...[
      ..._tube(-0.075 + i * 0.05, 0, const Color(0xFFECEFF1), y: _top + 0.008, h: 0.12, fillH: 0.05),
      // Bubbles of hydrogen: more for the more reactive metal, none for copper.
      for (var b = 0; b < [4, 3, 1, 0][i]; b++)
        Prim(Shape.sphere, (0.002, 0, 0), (-0.075 + i * 0.05 + (b.isEven ? 0.003 : -0.003), _top + 0.02 + b * 0.009, 0), Colors.white),
    ],
  ]),
  'p11-paper-chromatography': Bench('A chromatography paper standing in solvent, with an ink spot on its base line.', [
    ..._tray(),
    ..._beaker(0, 0, const Color(0xFFE1F5FE), 0.015),
    ..._paper(0, 0, const [Color(0xFF1565C0), Color(0xFFD81B60), Color(0xFFFBC02D)]),
  ]),
  'f12-forensic-ink': Bench("Two chromatography papers: ink from the note and ink from a suspect's pen.", [
    ..._tray(),
    ..._beaker(-0.06, 0, const Color(0xFFE1F5FE), 0.015),
    ..._paper(-0.06, 0, const [Color(0xFF283593), Color(0xFF6A1B9A)]),
    ..._beaker(0.06, 0, const Color(0xFFE1F5FE), 0.015),
    ..._paper(0.06, 0, const [Color(0xFF283593), Color(0xFF6A1B9A)]),
  ]),
};

/// A plain bench for an AR experiment this file does not know yet.
final Bench _default = Bench('A beaker and a test tube on your bench.', [
  ..._tray(),
  ..._beaker(-0.05, 0, const Color(0xFFB3E5FC), 0.04),
  ..._tube(0.07, 0, const Color(0xFFC8E6C9), y: _top + 0.012),
]);

Bench benchFor(String? slug) => _benches[slug] ?? _default;
