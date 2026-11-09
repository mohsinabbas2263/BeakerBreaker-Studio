import 'package:flutter/material.dart';

import '../api/api.dart';
import '../ar/ar_screen.dart';
import '../theme.dart';
import '../widgets/common.dart';
import 'quiz_screen.dart';
import 'run_screen.dart';

/// wf_S03 detail: the equation and note (FR-03.6), why it is locked (FR-03.3),
/// and Start or Resume (FR-03.5), in Game Mode or AR Mode (FR-05.1).
class ExperimentDetailScreen extends StatelessWidget {
  const ExperimentDetailScreen({super.key, required this.id});
  final int id;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(),
      body: Loader(
        load: () => api.get('/experiments/$id'),
        builder: (context, d, reload) {
          final e = d['experiment'] as Json;
          final access = d['access'] as Json;
          final steps = (d['steps'] as List).cast<Json>();
          final resume = d['resume'] as Json?;
          final allowed = access['allowed'] == true;
          final done = (resume?['steps_completed'] as int?) ?? 0;

          Future<void> open(Widget screen) async {
            await Navigator.push(context, MaterialPageRoute(builder: (_) => screen));
            await reload();
          }

          return ListView(padding: const EdgeInsets.all(16), children: [
            Center(child: Text('${e['emoji']}', style: const TextStyle(fontSize: 64))),
            const SizedBox(height: 8),
            Text('${e['title']}', textAlign: TextAlign.center, style: fredoka(24)),
            const SizedBox(height: 6),
            Wrap(alignment: WrapAlignment.center, spacing: 8, runSpacing: 6, children: [
              Pill('${e['topic']}'),
              Pill('${e['difficulty']}'.toUpperCase(), bg: C.mint, fg: C.greenD),
              Pill('⏱ ${e['estimated_minutes']} min', bg: C.lemon, fg: C.orangeD),
              Pill('+${e['base_points']} XP', bg: C.coral, fg: C.orangeD),
              if (e['ar_available'] == true) const Pill('🥽 AR available'),
            ]),
            const SizedBox(height: 16),
            if (e['chemical_equation'] != null)
              BBCard(
                color: C.lemon,
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text('🔬 THE SCIENCE', style: fredoka(12, color: C.orangeD)),
                  const SizedBox(height: 4),
                  Text('${e['chemical_equation']}', style: fredoka(16, weight: FontWeight.w500)),
                  if (e['explanatory_note'] != null) ...[
                    const SizedBox(height: 8),
                    Text('${e['explanatory_note']}', style: nunito(13, color: C.sub)),
                  ],
                ]),
              ),
            const SizedBox(height: 14),
            if (!allowed)
              BBCard(
                color: C.coral,
                child: Text('🔒 ${access['reason'] ?? 'This experiment is locked.'}', style: nunito(14, weight: FontWeight.w800)),
              )
            else ...[
              BBCard(
                child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  Text('${steps.length} steps', style: fredoka(16)),
                  const SizedBox(height: 8),
                  for (final s in steps)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 4),
                      child: Row(children: [
                        CircleAvatar(
                          radius: 13,
                          backgroundColor: (s['step_order'] as int) <= done ? C.green : C.sky,
                          child: Text('${s['step_order']}', style: nunito(12, color: (s['step_order'] as int) <= done ? Colors.white : C.blueD, weight: FontWeight.w900)),
                        ),
                        const SizedBox(width: 10),
                        Expanded(child: Text('${s['title']}', style: nunito(14))),
                      ]),
                    ),
                ]),
              ),
              const SizedBox(height: 16),
              FilledButton(
                onPressed: () => open(RunScreen(experimentId: id)),
                child: Text(resume != null && done > 0 ? 'Resume at step ${done + 1} ▶' : 'Start Experiment ▶'),
              ),
              if (e['ar_available'] == true) ...[
                const SizedBox(height: 10),
                FilledButton(
                  style: FilledButton.styleFrom(backgroundColor: C.blueD),
                  onPressed: () => open(ArScreen(experimentId: id)),
                  child: const Text('🥽 Play in AR Mode'),
                ),
              ],
              // FR-04.1: the server opens the quiz only after every step of a run;
              // until then the quiz screen shows its reason.
              if (resume == null) ...[
                const SizedBox(height: 4),
                TextButton(
                  onPressed: () => open(QuizScreen(experimentId: id)),
                  child: Text('📝 Take the quiz', style: nunito(14, color: C.purple, weight: FontWeight.w900)),
                ),
              ],
            ],
          ]);
        },
      ),
    );
  }
}
