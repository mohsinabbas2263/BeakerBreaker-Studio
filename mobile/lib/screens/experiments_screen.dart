import 'package:flutter/material.dart';

import '../api/api.dart';
import '../theme.dart';
import '../widgets/common.dart';
import 'experiment_detail_screen.dart';

/// wf_S03, the experiment library. FR-03.1 the student's own licensed experiments,
/// FR-03.2 filtered by topic and difficulty, FR-03.3 a locked one says why.
class ExperimentsScreen extends StatefulWidget {
  const ExperimentsScreen({super.key});

  @override
  State<ExperimentsScreen> createState() => _ExperimentsScreenState();
}

class _ExperimentsScreenState extends State<ExperimentsScreen> {
  String? _topic;
  String? _difficulty;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Experiments 🔬')),
      body: Loader(
        load: () => api.get('/experiments'),
        builder: (context, d, reload) {
          final topics = (d['topics'] as List).cast<String>();
          final all = (d['experiments'] as List).cast<Json>();
          final shown = all.where((e) => (_topic == null || e['topic'] == _topic) && (_difficulty == null || e['difficulty'] == _difficulty)).toList();

          return RefreshIndicator(
            onRefresh: reload,
            child: ListView(padding: const EdgeInsets.all(16), children: [
              SizedBox(
                height: 40,
                child: ListView(scrollDirection: Axis.horizontal, children: [
                  _chip('All', _topic == null, () => setState(() => _topic = null)),
                  for (final t in topics) _chip(t, _topic == t, () => setState(() => _topic = t)),
                ]),
              ),
              const SizedBox(height: 8),
              Row(children: [
                for (final level in ['easy', 'medium', 'hard'])
                  _chip(level[0].toUpperCase() + level.substring(1), _difficulty == level,
                      () => setState(() => _difficulty = _difficulty == level ? null : level)),
              ]),
              const SizedBox(height: 12),
              if (shown.isEmpty) Padding(padding: const EdgeInsets.all(24), child: Text('No experiments match.', textAlign: TextAlign.center, style: nunito(14, color: C.sub))),
              for (final e in shown) _ExperimentCard(e, onReturn: reload),
            ]),
          );
        },
      ),
    );
  }

  Widget _chip(String label, bool on, VoidCallback tap) => Padding(
        padding: const EdgeInsets.only(right: 8),
        child: ChoiceChip(
          label: Text(label, style: nunito(13, color: on ? Colors.white : C.ink, weight: FontWeight.w800)),
          selected: on,
          onSelected: (_) => tap(),
          selectedColor: C.blueD,
          backgroundColor: Colors.white,
          showCheckmark: false,
          shape: const StadiumBorder(),
        ),
      );
}

Color _parseGradientStart(String? css) {
  final m = RegExp(r'#([0-9A-Fa-f]{6})').firstMatch(css ?? '');
  return m == null ? C.blue : Color(int.parse('FF${m.group(1)}', radix: 16));
}

class _ExperimentCard extends StatelessWidget {
  const _ExperimentCard(this.e, {required this.onReturn});
  final Json e;
  final Future<void> Function() onReturn;

  @override
  Widget build(BuildContext context) {
    final locked = e['locked'] == true;
    final top = _parseGradientStart(e['card_gradient'] as String?);
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Opacity(
        opacity: locked ? .6 : 1,
        child: BBCard(
          padding: EdgeInsets.zero,
          onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => ExperimentDetailScreen(id: e['id'] as int))).then((_) => onReturn()),
          child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                gradient: LinearGradient(colors: [top, top.withValues(alpha: .7)]),
                borderRadius: const BorderRadius.vertical(top: Radius.circular(18)),
              ),
              child: Row(children: [
                Text('${e['emoji']}', style: const TextStyle(fontSize: 34)),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text('${e['title']}', style: fredoka(16, color: Colors.white)),
                    Text('${e['topic']}', style: nunito(12, color: Colors.white)),
                  ]),
                ),
                if (e['completed'] == true) const Text('✓', style: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.w900)),
                if (locked) const Text('🔒', style: TextStyle(fontSize: 20)),
              ]),
            ),
            Padding(
              padding: const EdgeInsets.all(12),
              child: locked
                  ? Text('${e['lock_reason'] ?? 'Locked'}', style: nunito(12, color: C.sub))
                  : Row(children: [
                      Pill('${e['difficulty']}'.toUpperCase(), bg: C.mint, fg: C.greenD),
                      const SizedBox(width: 8),
                      Text('⏱ ${e['estimated_minutes']} min', style: nunito(12, color: C.sub)),
                      if (e['ar_available'] == true) ...[const SizedBox(width: 8), const Pill('🥽 AR')],
                      const Spacer(),
                      Text('+${e['base_points']} XP', style: nunito(13, color: C.orangeD, weight: FontWeight.w900)),
                    ]),
            ),
          ]),
        ),
      ),
    );
  }
}
