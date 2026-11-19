import 'dart:math' as math;

import 'package:flutter/material.dart';

import '../api/api.dart';
import '../theme.dart';
import 'lab_items.dart';

/// docs/experiment-engine.md: the controls for one game step, the app's copy of the
/// web portal's GameStep. It only sends what the student did (onAct); the server
/// judges it (FR-02.5), so nothing here knows a right answer, except what the server
/// reveals after 3 mistakes. Every drag also works as a tap.
class GameStep extends StatelessWidget {
  const GameStep({
    super.key,
    required this.step,
    required this.progress,
    required this.reveal,
    required this.lastMistake,
    required this.busy,
    required this.onAct,
  });

  final Json step;
  final Json progress;
  final Json? reveal;
  final String? lastMistake;
  final bool busy;
  final void Function(Json action) onAct;

  Json get config => Json.from((step['config'] as Map?) ?? {});

  @override
  Widget build(BuildContext context) {
    switch (step['kind']) {
      case 'gear':
      case 'choose':
        return _GearOrChoose(this);
      case 'place':
        return _Place(this, key: ValueKey(step['id']));
      case 'measure':
        return _Measure(this, key: ValueKey(step['id']));
      case 'add_until':
        return _AddUntil(this, key: ValueKey(step['id']));
      case 'heat':
        return _Heat(this, key: ValueKey(step['id']));
      case 'read':
        return _Read(this, key: ValueKey(step['id']));
      case 'observe':
        return _Observe(this);
      default:
        return PrimaryButton(label: '👉 ${step['action_label'] ?? 'Done with this step'}', onPressed: busy ? null : () => onAct({}));
    }
  }
}

class PrimaryButton extends StatelessWidget {
  const PrimaryButton({super.key, required this.label, this.onPressed});
  final String label;
  final VoidCallback? onPressed;

  @override
  Widget build(BuildContext context) => FilledButton(onPressed: onPressed, child: Text(label));
}

class _Hint extends StatelessWidget {
  const _Hint(this.text);
  final String text;
  @override
  Widget build(BuildContext context) => Text(text, textAlign: TextAlign.center, style: nunito(12, color: C.greenD, weight: FontWeight.w800));
}

/// One lab item: a chip that can be tapped, or dragged onto a drop zone.
class ItemChip extends StatelessWidget {
  const ItemChip({super.key, required this.id, required this.onTap, this.picked = false, this.done = false, this.disabled = false, this.glow = false});
  final String id;
  final VoidCallback onTap;
  final bool picked, done, disabled, glow;

  @override
  Widget build(BuildContext context) {
    final it = item(id);
    final chip = AnimatedContainer(
      duration: const Duration(milliseconds: 300),
      curve: kBounce,
      width: 86,
      height: 86,
      decoration: BoxDecoration(
        color: done ? C.mint : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: picked || glow ? C.green : (done ? C.green : C.blue.withValues(alpha: .25)), width: picked || glow ? 3 : 2),
        boxShadow: [if (glow) BoxShadow(color: C.green.withValues(alpha: .5), blurRadius: 14)],
      ),
      padding: const EdgeInsets.all(4),
      child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
        Text(done ? '✅' : it.emoji, style: const TextStyle(fontSize: 28)),
        const SizedBox(height: 2),
        Text(it.label, textAlign: TextAlign.center, maxLines: 2, overflow: TextOverflow.ellipsis, style: nunito(10.5, weight: FontWeight.w800)),
      ]),
    );
    final interactive = !(disabled || done);
    return Semantics(
      button: true,
      selected: picked,
      label: it.label,
      child: Opacity(
        opacity: disabled && !done ? .5 : 1,
        child: interactive
            ? Draggable<String>(
                data: id,
                feedback: Material(color: Colors.transparent, child: Transform.scale(scale: 1.1, child: chip)),
                childWhenDragging: Opacity(opacity: .4, child: chip),
                child: GestureDetector(onTap: onTap, child: chip),
              )
            : chip,
      ),
    );
  }
}

class DropZone extends StatelessWidget {
  const DropZone({super.key, required this.label, required this.onDrop, this.filled, this.active = false, this.onTap, this.wide = false});
  final String label;
  final void Function(String id) onDrop;
  final String? filled;
  final bool active, wide;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return DragTarget<String>(
      onAcceptWithDetails: (d) => onDrop(d.data),
      builder: (context, candidates, _) {
        final over = candidates.isNotEmpty || active;
        return GestureDetector(
          onTap: onTap,
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 200),
            width: wide ? double.infinity : 150,
            constraints: const BoxConstraints(minHeight: 110),
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: over ? C.mint : (filled != null ? C.mint.withValues(alpha: .6) : C.sky),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: over || filled != null ? C.green : C.blue.withValues(alpha: .5), width: 2.5),
            ),
            child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
              if (filled != null) ...[
                Text(item(filled!).emoji, style: const TextStyle(fontSize: 28)),
                Text(item(filled!).label, style: nunito(12, weight: FontWeight.w800)),
              ] else
                const Opacity(opacity: .4, child: Text('⬇️', style: TextStyle(fontSize: 28))),
              const SizedBox(height: 4),
              Text(label.toUpperCase(), textAlign: TextAlign.center, style: nunito(10.5, color: C.sub, weight: FontWeight.w900)),
            ]),
          ),
        );
      },
    );
  }
}

class _GearOrChoose extends StatelessWidget {
  const _GearOrChoose(this.p);
  final GameStep p;

  @override
  Widget build(BuildContext context) {
    final items = ((p.config['items'] as List?) ?? []).cast<String>();
    final worn = ((p.progress['worn'] as List?) ?? []).cast<String>();
    final gear = p.step['kind'] == 'gear';
    final hint = <String>[
      ...((p.reveal?['required'] as List?) ?? []).cast<String>(),
      if (p.reveal?['correct'] != null) '${p.reveal!['correct']}',
    ];
    return Column(children: [
      DropZone(
        label: gear ? 'Drag onto yourself' : 'Into the ${place('${p.config['target'] ?? 'beaker'}')}',
        wide: true,
        onDrop: (id) => p.onAct({'item': id}),
      ),
      const SizedBox(height: 14),
      Wrap(spacing: 10, runSpacing: 10, alignment: WrapAlignment.center, children: [
        for (final id in items)
          ItemChip(id: id, disabled: p.busy, done: worn.contains(id), glow: hint.contains(id), onTap: () => p.onAct({'item': id})),
      ]),
      const SizedBox(height: 8),
      Text('Tap an item, or drag it.', style: nunito(11, color: C.muted)),
    ]);
  }
}

class _Place extends StatefulWidget {
  const _Place(this.p, {super.key});
  final GameStep p;
  @override
  State<_Place> createState() => _PlaceState();
}

class _PlaceState extends State<_Place> {
  String? _picked;

  void _drop(String zone, String? id) {
    if (id == null) return;
    setState(() => _picked = null);
    widget.p.onAct({'item': id, 'zone': zone});
  }

  @override
  Widget build(BuildContext context) {
    final p = widget.p;
    final placed = Map<String, dynamic>.from((p.progress['placed'] as Map?) ?? {});
    final answer = Map<String, dynamic>.from((p.reveal?['answer'] as Map?) ?? {});
    final zones = ((p.config['zones'] as List?) ?? []).cast<String>();
    final items = ((p.config['items'] as List?) ?? []).cast<String>();
    return Column(children: [
      Wrap(spacing: 10, runSpacing: 10, alignment: WrapAlignment.center, children: [
        for (final z in zones)
          DropZone(
            label: place(z),
            filled: placed[z] as String?,
            active: _picked != null && placed[z] == null,
            onDrop: (id) => _drop(z, id),
            onTap: () => _drop(z, _picked),
          ),
      ]),
      const SizedBox(height: 14),
      Wrap(spacing: 10, runSpacing: 10, alignment: WrapAlignment.center, children: [
        for (final id in items)
          ItemChip(
            id: id,
            disabled: p.busy,
            picked: _picked == id,
            glow: answer.values.contains(id),
            done: placed.values.contains(id) && p.config['simultaneous'] != true,
            onTap: () => setState(() => _picked = _picked == id ? null : id),
          ),
      ]),
      const SizedBox(height: 8),
      Text(_picked != null ? 'Now tap where the ${item(_picked!).label.toLowerCase()} goes.' : 'Tap an item, then tap its place. Or drag it.',
          textAlign: TextAlign.center, style: nunito(11, color: C.muted)),
    ]);
  }
}

int _decimals(num n) {
  final s = n.toString();
  return s.contains('.') ? s.split('.')[1].length : 0;
}

class _Measure extends StatefulWidget {
  const _Measure(this.p, {super.key});
  final GameStep p;
  @override
  State<_Measure> createState() => _MeasureState();
}

class _MeasureState extends State<_Measure> {
  late double _value = ((widget.p.config['min'] as num?) ?? 0).toDouble();

  @override
  Widget build(BuildContext context) {
    final c = widget.p.config;
    final min = ((c['min'] as num?) ?? 0).toDouble();
    final max = ((c['max'] as num?) ?? 50).toDouble();
    final inc = ((c['step'] as num?) ?? 1).toDouble();
    final dp = _decimals(c['step'] as num? ?? 1);
    final unit = '${c['unit'] ?? ''}';
    void nudge(double d) => setState(() => _value = double.parse((_value + d).clamp(min, max).toStringAsFixed(6)));

    return Column(children: [
      Semantics(liveRegion: true, child: Text('${_value.toStringAsFixed(dp)} $unit', style: fredoka(36, color: C.blueD))),
      Slider(
        value: _value.clamp(min, max),
        min: min,
        max: max,
        divisions: ((max - min) / inc).round().clamp(1, 10000),
        activeColor: C.blueD,
        label: _value.toStringAsFixed(dp),
        onChanged: (v) => setState(() => _value = double.parse(v.toStringAsFixed(dp))),
      ),
      Row(mainAxisAlignment: MainAxisAlignment.center, children: [
        IconButton.filledTonal(tooltip: 'Less', onPressed: () => nudge(-inc), icon: const Icon(Icons.remove)),
        const SizedBox(width: 12),
        IconButton.filledTonal(tooltip: 'More', onPressed: () => nudge(inc), icon: const Icon(Icons.add)),
      ]),
      if (widget.p.reveal?['target'] != null) _Hint('Hint: ${widget.p.reveal!['target']} $unit'),
      const SizedBox(height: 10),
      PrimaryButton(
        label: '${widget.p.step['action_label'] ?? 'Transfer'} 👉',
        onPressed: widget.p.busy ? null : () => widget.p.onAct({'value': _value}),
      ),
    ]);
  }
}

class _AddUntil extends StatefulWidget {
  const _AddUntil(this.p, {super.key});
  final GameStep p;
  @override
  State<_AddUntil> createState() => _AddUntilState();
}

class _AddUntilState extends State<_AddUntil> {
  double _added = 0;

  @override
  void didUpdateWidget(covariant _AddUntil old) {
    super.didUpdateWidget(old);
    // After an overshoot the run is spoilt, as in a real titration: start a fresh one.
    if (widget.p.lastMistake == 'overshoot' && old.p.lastMistake != 'overshoot') _added = 0;
  }

  @override
  Widget build(BuildContext context) {
    final c = widget.p.config;
    final incs = ((c['increments'] as List?) ?? [1]).cast<num>();
    final dp = incs.map(_decimals).fold<int>(0, math.max);
    final unit = '${c['unit'] ?? ''}';
    final v = c['visual'] as Map?;
    final end = ((c['end'] as num?) ?? 0).toDouble();
    double fade = 0;
    if (v != null) {
      final from = ((v['fade_from'] as num?) ?? 0).toDouble();
      fade = ((_added - from) / math.max(0.0001, end - from)).clamp(0, 1).toDouble();
    }
    return Column(children: [
      if (v != null)
        SizedBox(
          width: 110,
          height: 120,
          child: CustomPaint(painter: _FlaskPainter(colour('${v['from']}'), colour('${v['to']}'), fade)),
        ),
      Semantics(liveRegion: true, child: Text('${_added.toStringAsFixed(dp)} $unit added', style: fredoka(28, color: C.blueD))),
      const SizedBox(height: 8),
      Wrap(spacing: 8, runSpacing: 8, alignment: WrapAlignment.center, children: [
        for (final i in incs)
          FilledButton.tonal(
            onPressed: widget.p.busy ? null : () => setState(() => _added = double.parse((_added + i).toStringAsFixed(6))),
            child: Text('+$i $unit', style: nunito(14, color: C.blueD, weight: FontWeight.w900)),
          ),
      ]),
      if (widget.p.reveal?['end'] != null) _Hint('Hint: stop at ${widget.p.reveal!['end']} $unit'),
      const SizedBox(height: 10),
      PrimaryButton(label: '✋ Stop here', onPressed: widget.p.busy || _added == 0 ? null : () => widget.p.onAct({'stop_at': _added})),
    ]);
  }
}

class _FlaskPainter extends CustomPainter {
  _FlaskPainter(this.from, this.to, this.fade);
  final Color from, to;
  final double fade;

  @override
  void paint(Canvas canvas, Size s) {
    final body = Path()
      ..moveTo(s.width * .38, 0)
      ..lineTo(s.width * .62, 0)
      ..lineTo(s.width * .62, s.height * .3)
      ..lineTo(s.width * .95, s.height * .92)
      ..quadraticBezierTo(s.width, s.height, s.width * .88, s.height)
      ..lineTo(s.width * .12, s.height)
      ..quadraticBezierTo(0, s.height, s.width * .05, s.height * .92)
      ..lineTo(s.width * .38, s.height * .3)
      ..close();
    canvas.save();
    canvas.clipPath(body);
    final liquid = Rect.fromLTWH(0, s.height * .45, s.width, s.height * .55);
    canvas.drawRect(liquid, Paint()..color = Color.lerp(from, to, fade)!);
    canvas.restore();
    canvas.drawPath(body, Paint()..style = PaintingStyle.stroke..strokeWidth = 4..color = C.blue.withValues(alpha: .4));
  }

  @override
  bool shouldRepaint(covariant _FlaskPainter old) => old.fade != fade || old.from != from || old.to != to;
}

class _Heat extends StatefulWidget {
  const _Heat(this.p, {super.key});
  final GameStep p;
  @override
  State<_Heat> createState() => _HeatState();
}

class _HeatState extends State<_Heat> {
  late List<String> _levels = ((widget.p.config['levels'] as List?) ?? ['low', 'high']).cast<String>();
  late String _level = _levels.first;
  double _seconds = 10;

  String _name(String l) => l == 'low' ? 'Gentle flame' : l == 'high' ? 'Strong flame' : l;

  @override
  Widget build(BuildContext context) {
    _levels = ((widget.p.config['levels'] as List?) ?? ['low', 'high']).cast<String>();
    final stage = (widget.p.progress['stage'] as int?) ?? 0;
    final seq = (widget.p.reveal?['sequence'] as List?)?.cast<String>();
    return Column(children: [
      Text(_level == _levels.last ? '🔥' : '🕯️', style: const TextStyle(fontSize: 56)),
      const SizedBox(height: 8),
      Wrap(spacing: 8, children: [
        for (final l in _levels)
          ChoiceChip(
            label: Text(_name(l), style: nunito(13, color: _level == l ? Colors.white : C.sub, weight: FontWeight.w900)),
            selected: _level == l,
            selectedColor: C.orange,
            showCheckmark: false,
            onSelected: (_) => setState(() => _level = l),
          ),
      ]),
      const SizedBox(height: 8),
      Text('Heat for ${_seconds.round()} seconds', style: nunito(14, color: C.sub, weight: FontWeight.w800)),
      Slider(value: _seconds, min: 0, max: 60, divisions: 12, activeColor: C.orange, onChanged: (v) => setState(() => _seconds = v)),
      if (stage > 0) _Hint('Stage $stage done. Keep going.'),
      if (seq != null) _Hint('Hint: ${seq.join(', then ')}, at least ${widget.p.reveal?['min_seconds']} s each'),
      const SizedBox(height: 10),
      PrimaryButton(label: '🔥 Heat', onPressed: widget.p.busy ? null : () => widget.p.onAct({'level': _level, 'seconds': _seconds.round()})),
    ]);
  }
}

class _Read extends StatefulWidget {
  const _Read(this.p, {super.key});
  final GameStep p;
  @override
  State<_Read> createState() => _ReadState();
}

class _ReadState extends State<_Read> {
  final _ctl = TextEditingController();

  void _submit() {
    final v = double.tryParse(_ctl.text.trim());
    if (v != null) widget.p.onAct({'value': v});
  }

  @override
  Widget build(BuildContext context) {
    final c = widget.p.config;
    final unit = '${c['unit'] ?? ''}';
    return Column(children: [
      if (c['instrument'] != null && c['display'] != null)
        Instrument(kind: '${c['instrument']}', value: (c['display'] as num).toDouble(), unit: unit),
      const SizedBox(height: 10),
      Row(mainAxisAlignment: MainAxisAlignment.center, children: [
        SizedBox(
          width: 140,
          child: TextField(
            controller: _ctl,
            keyboardType: const TextInputType.numberWithOptions(decimal: true, signed: true),
            textAlign: TextAlign.center,
            style: fredoka(24),
            decoration: const InputDecoration(hintText: '?', labelText: 'Your reading'),
            onSubmitted: (_) => _submit(),
            onChanged: (_) => setState(() {}),
          ),
        ),
        const SizedBox(width: 8),
        Text(unit, style: fredoka(18, color: C.sub)),
      ]),
      if (widget.p.reveal?['value'] != null) _Hint('Hint: ${widget.p.reveal!['value']} $unit'),
      const SizedBox(height: 10),
      PrimaryButton(label: 'Check ✔️', onPressed: widget.p.busy || _ctl.text.trim().isEmpty ? null : _submit),
    ]);
  }
}

/// A ruler, syringe or thermometer scale with a marker at the reading. The number
/// is not printed, because reading the scale is the task.
class Instrument extends StatelessWidget {
  const Instrument({super.key, required this.kind, required this.value, required this.unit});
  final String kind, unit;
  final double value;

  @override
  Widget build(BuildContext context) {
    final vertical = kind == 'thermometer';
    return Semantics(
      label: '$kind scale in $unit',
      child: SizedBox(
        width: vertical ? 110 : double.infinity,
        height: vertical ? 300 : 90,
        child: CustomPaint(painter: _ScalePainter(kind, value)),
      ),
    );
  }
}

class _ScalePainter extends CustomPainter {
  _ScalePainter(this.kind, this.value);
  final String kind;
  final double value;

  @override
  void paint(Canvas canvas, Size size) {
    final vertical = kind == 'thermometer';
    final max = kind == 'thermometer' ? 110.0 : kind == 'syringe' ? 60.0 : math.max(10.0, (value / 10).ceil() * 10.0 + 10);
    final major = kind == 'ruler' && max <= 20 ? 1.0 : 10.0;
    final minor = major / (kind == 'ruler' && max <= 20 ? 10 : 5);
    final length = (vertical ? size.height : size.width) - 40;
    double pos(double v) => v / max * length;

    canvas.save();
    if (vertical) {
      canvas.translate(size.width / 2 - 13, size.height - 20);
      canvas.rotate(-math.pi / 2);
    } else {
      canvas.translate(20, 20);
    }
    final tube = RRect.fromRectAndRadius(Rect.fromLTWH(-6, 0, length + 12, 26), const Radius.circular(13));
    canvas.drawRRect(tube, Paint()..color = C.sky);
    canvas.drawRRect(tube, Paint()..style = PaintingStyle.stroke..strokeWidth = 2..color = C.blueD);
    final fill = kind == 'thermometer' ? C.orangeD : kind == 'syringe' ? C.purple : C.green;
    canvas.drawRRect(RRect.fromRectAndRadius(Rect.fromLTWH(0, 6, pos(value), 14), const Radius.circular(7)), Paint()..color = fill);

    final count = (max / minor).round();
    for (var i = 0; i <= count; i++) {
      final t = i * minor;
      final isMajor = ((t / major) - (t / major).round()).abs() < 1e-6;
      canvas.drawLine(Offset(pos(t), 26), Offset(pos(t), isMajor ? 40 : 33),
          Paint()..color = C.ink..strokeWidth = isMajor ? 1.5 : .7);
      if (isMajor) {
        final tp = TextPainter(
          text: TextSpan(text: t % 1 == 0 ? t.toInt().toString() : t.toString(), style: const TextStyle(fontSize: 11, color: C.sub)),
          textDirection: TextDirection.ltr,
        )..layout();
        canvas.save();
        canvas.translate(pos(t), 50);
        if (vertical) canvas.rotate(math.pi / 2);
        tp.paint(canvas, Offset(-tp.width / 2, vertical ? -tp.height / 2 : 0));
        canvas.restore();
      }
    }
    canvas.restore();
  }

  @override
  bool shouldRepaint(covariant _ScalePainter old) => old.value != value || old.kind != kind;
}

class _Observe extends StatelessWidget {
  const _Observe(this.p);
  final GameStep p;

  @override
  Widget build(BuildContext context) {
    final options = Map<String, dynamic>.from((p.config['options'] as Map?) ?? {});
    return Column(children: [
      for (final e in options.entries)
        Padding(
          padding: const EdgeInsets.only(bottom: 10),
          child: InkWell(
            borderRadius: BorderRadius.circular(16),
            onTap: p.busy ? null : () => p.onAct({'option': e.key}),
            child: Ink(
              width: double.infinity,
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: p.reveal?['correct'] == e.key ? C.mint : Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: p.reveal?['correct'] == e.key ? C.green : C.blue.withValues(alpha: .25), width: 2),
              ),
              child: Text.rich(TextSpan(children: [
                TextSpan(text: '${e.key.toUpperCase()}. ', style: nunito(14, color: C.blueD, weight: FontWeight.w900)),
                TextSpan(text: '${e.value}', style: nunito(14, weight: FontWeight.w800)),
              ])),
            ),
          ),
        ),
    ]);
  }
}
