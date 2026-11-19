import 'dart:math' as math;

import 'package:flutter/material.dart';

import 'bench.dart';

/// FR-05.4: the simulation draws the same bench as live AR, in a simple 3D view
/// that turns with the same Rotate control (FR-05.5). Painted with Canvas, so it
/// needs no camera, no AR support and nothing beyond the phone's normal graphics.
class SimBench extends StatefulWidget {
  const SimBench({super.key, required this.bench, required this.quarterTurns});
  final Bench bench;
  final int quarterTurns;

  @override
  State<SimBench> createState() => _SimBenchState();
}

class _SimBenchState extends State<SimBench> with SingleTickerProviderStateMixin {
  late final AnimationController _sway = AnimationController(vsync: this, duration: const Duration(seconds: 8))..repeat();
  double _angle = 0;

  @override
  void dispose() {
    _sway.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final target = widget.quarterTurns * math.pi / 2;
    return TweenAnimationBuilder<double>(
      tween: Tween(begin: _angle, end: target),
      duration: const Duration(milliseconds: 600),
      curve: Curves.easeOutBack,
      onEnd: () => _angle = target,
      builder: (context, angle, _) => AnimatedBuilder(
        animation: _sway,
        builder: (context, _) {
          _angle = angle;
          final sway = math.sin(_sway.value * 2 * math.pi) * .08;
          return CustomPaint(painter: _BenchPainter(widget.bench, angle + sway), size: Size.infinite);
        },
      ),
    );
  }
}

class _BenchPainter extends CustomPainter {
  _BenchPainter(this.bench, this.angle);
  final Bench bench;
  final double angle;

  // A camera above and in front of the bench, looking down at about 30 degrees.
  static const _tilt = 0.52;

  Offset _project(double x, double y, double z, Size size, double scale) {
    final sy = y * math.cos(_tilt) - z * math.sin(_tilt);
    return Offset(size.width / 2 + x * scale, size.height * .7 - sy * scale);
  }

  double _depth(Prim p) {
    final (_, y, z) = p.pos;
    return z * math.cos(_tilt) + y * math.sin(_tilt);
  }

  @override
  void paint(Canvas canvas, Size size) {
    final scale = math.min(size.width / 0.55, size.height / 0.4);
    canvas.drawOval(
      Rect.fromCenter(center: _project(0, 0, 0, size, scale), width: 0.5 * scale, height: 0.5 * scale * math.sin(_tilt) * .7),
      Paint()..color = Colors.black.withValues(alpha: .22),
    );
    final prims = bench.prims.map((p) => p.turned(angle)).toList()..sort((a, b) => _depth(a).compareTo(_depth(b)));
    for (final p in prims) {
      final paint = Paint()..color = p.color.withValues(alpha: p.opacity);
      final (x, y, z) = p.pos;
      final (a, b, c) = p.size;
      switch (p.shape) {
        case Shape.sphere:
          canvas.drawCircle(_project(x, y, z, size, scale), a * scale, paint);
        case Shape.cylinder:
          if (p.horizontal) {
            final l = _project(x - b / 2 * math.cos(angle), y, z + b / 2 * math.sin(angle), size, scale);
            final r = _project(x + b / 2 * math.cos(angle), y, z - b / 2 * math.sin(angle), size, scale);
            canvas.drawLine(l, r, paint..strokeWidth = a * 2 * scale..strokeCap = StrokeCap.round);
          } else {
            final bottom = _project(x, y - b / 2, z, size, scale);
            final top = _project(x, y + b / 2, z, size, scale);
            final w = a * 2 * scale, e = w * math.sin(_tilt) * .6;
            final path = Path()
              ..addRect(Rect.fromLTRB(bottom.dx - w / 2, top.dy, bottom.dx + w / 2, bottom.dy))
              ..addOval(Rect.fromCenter(center: bottom, width: w, height: e));
            canvas.drawPath(path, paint);
            canvas.drawOval(Rect.fromCenter(center: top, width: w, height: e),
                Paint()..color = Color.lerp(p.color, Colors.white, .35)!.withValues(alpha: p.opacity));
          }
        case Shape.box:
          final ca = math.cos(angle), sa = math.sin(angle);
          Offset corner(double dx, double dy, double dz) {
            final rx = dx * ca + dz * sa, rz = -dx * sa + dz * ca;
            return _project(x + rx, y + dy, z + rz, size, scale);
          }
          final top = Path()
            ..addPolygon([corner(-a / 2, b / 2, -c / 2), corner(a / 2, b / 2, -c / 2), corner(a / 2, b / 2, c / 2), corner(-a / 2, b / 2, c / 2)], true);
          final front = Path()
            ..addPolygon([corner(-a / 2, -b / 2, c / 2), corner(a / 2, -b / 2, c / 2), corner(a / 2, b / 2, c / 2), corner(-a / 2, b / 2, c / 2)], true);
          final side = Path()
            ..addPolygon([corner(a / 2, -b / 2, c / 2), corner(a / 2, -b / 2, -c / 2), corner(a / 2, b / 2, -c / 2), corner(a / 2, b / 2, c / 2)], true);
          canvas.drawPath(front, Paint()..color = Color.lerp(p.color, Colors.black, .12)!.withValues(alpha: p.opacity));
          canvas.drawPath(side, Paint()..color = Color.lerp(p.color, Colors.black, .2)!.withValues(alpha: p.opacity));
          canvas.drawPath(top, paint);
      }
    }
  }

  @override
  bool shouldRepaint(covariant _BenchPainter old) => old.angle != angle || old.bench != bench;
}
