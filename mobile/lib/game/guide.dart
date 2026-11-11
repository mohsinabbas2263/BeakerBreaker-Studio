import 'dart:math' as math;

import 'package:flutter/material.dart';

import '../theme.dart';
import '../widgets/common.dart';
import 'game_runner.dart';

/// The student's own avatar as their lab guide, with a short movement for its mood
/// (point, happy, think, oops, worried, party), and the line it is saying.
class GuideBubble extends StatelessWidget {
  const GuideBubble({super.key, required this.runner, required this.avatarKey, this.size = 60});
  final GameRunner runner;
  final String? avatarKey;
  final double size;

  @override
  Widget build(BuildContext context) {
    final mood = runner.mood;
    final bg = mood == 'worried' ? C.coral : mood == 'oops' ? C.lemon : Colors.white;
    return Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
      TweenAnimationBuilder<double>(
        key: ValueKey(runner.moodKey),
        tween: Tween(begin: 0, end: 1),
        duration: const Duration(milliseconds: 700),
        builder: (context, t, child) {
          final wave = math.sin(t * math.pi * 4) * (1 - t);
          final dx = (mood == 'oops' || mood == 'worried') ? wave * 6 : 0.0;
          final dy = (mood == 'happy' || mood == 'party') ? -math.sin(t * math.pi) * 10 : 0.0;
          final turn = mood == 'think' ? math.sin(t * math.pi) * .12 : 0.0;
          return Transform.translate(offset: Offset(dx, dy), child: Transform.rotate(angle: turn, child: child));
        },
        child: Avatar(avatarKey, size: size),
      ),
      const SizedBox(width: 10),
      Expanded(
        child: Semantics(
          liveRegion: true,
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 250),
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: bg,
              borderRadius: const BorderRadius.only(
                topRight: Radius.circular(18), bottomLeft: Radius.circular(18), bottomRight: Radius.circular(18), topLeft: Radius.circular(4)),
              boxShadow: kCardShadow,
            ),
            child: Text(runner.line ?? '', style: nunito(15, weight: FontWeight.w800)),
          ),
        ),
      ),
    ]);
  }
}

/// FR-03.7: ask the guide about the current step. The answer says whether it came
/// from the AI or from the pre-authored lab notes (NFR-06).
class AskGuide extends StatefulWidget {
  const AskGuide({super.key, required this.runner});
  final GameRunner runner;

  @override
  State<AskGuide> createState() => _AskGuideState();
}

class _AskGuideState extends State<AskGuide> {
  final _ctl = TextEditingController();

  Future<void> _ask() async {
    final err = await widget.runner.ask(_ctl.text);
    if (!mounted) return;
    if (err != null) {
      toast(context, err, error: true);
    } else {
      _ctl.clear();
    }
  }

  @override
  Widget build(BuildContext context) {
    final a = widget.runner.answer;
    return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      Row(children: [
        Expanded(
          child: TextField(
            controller: _ctl,
            maxLength: 300,
            decoration: const InputDecoration(hintText: 'e.g. Why do we swirl the flask?', counterText: ''),
            onSubmitted: (_) => _ask(),
          ),
        ),
        const SizedBox(width: 8),
        FilledButton(
          style: FilledButton.styleFrom(minimumSize: const Size(64, 52), backgroundColor: C.blueD),
          onPressed: widget.runner.asking ? null : _ask,
          child: Text(widget.runner.asking ? '…' : 'Ask'),
        ),
      ]),
      if (a != null) ...[
        const SizedBox(height: 10),
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(color: C.sky, borderRadius: BorderRadius.circular(16)),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            a['source'] == 'ai' ? const Pill('AI', bg: Colors.white) : const Pill('Lab notes', bg: C.lemon, fg: C.orangeD),
            const SizedBox(height: 6),
            Text('${a['text']}', style: nunito(14)),
          ]),
        ),
      ],
    ]);
  }
}
