import 'package:flutter/material.dart';

import '../api/api.dart';
import '../theme.dart';
import '../widgets/common.dart';

/// FR-08.6: a student sees their own readiness report, and nobody else's (SR-08).
class ReadinessScreen extends StatelessWidget {
  const ReadinessScreen({super.key});

  static const _names = {
    'procedural_accuracy': 'Procedure',
    'safety_compliance': 'Safety',
    'reagent_management': 'Reagents',
    'time_efficiency': 'Pacing',
    'conceptual_understanding': 'Concepts',
    'error_recovery': 'Fixing mistakes',
  };

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Lab Readiness 📊')),
      body: Loader(
        load: () async {
          try {
            return await api.get('/readiness');
          } on ApiError catch (e) {
            if (e.code == 'no_report' || e.status == 404) return {'none': e.message};
            rethrow;
          }
        },
        builder: (context, d, reload) {
          if (d['none'] != null) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(mainAxisSize: MainAxisSize.min, children: [
                  const Text('📊', style: TextStyle(fontSize: 48)),
                  const SizedBox(height: 8),
                  Text('${d['none']}', textAlign: TextAlign.center, style: fredoka(17)),
                ]),
              ),
            );
          }
          final r = d['report'] as Json;
          final dims = Map<String, dynamic>.from(r['dimensions'] as Map);
          final estimated = ((r['estimated'] as List?) ?? []).cast<String>();
          final cls = r['classification'];
          final (label, colour) = switch (cls) {
            'ready' => ('Ready for the real lab', C.green),
            'developing' => ('Getting there', C.orange),
            _ => ('Needs more practice', C.orangeD),
          };
          return ListView(padding: const EdgeInsets.all(16), children: [
            BBCard(
              color: colour.withValues(alpha: .15),
              child: Column(children: [
                Text(label, style: fredoka(21, color: colour)),
                const SizedBox(height: 6),
                Text('${r['narrative']}', textAlign: TextAlign.center, style: nunito(14)),
              ]),
            ),
            const SizedBox(height: 14),
            BBCard(
              child: Column(children: [
                for (final e in dims.entries)
                  Padding(
                    padding: const EdgeInsets.symmetric(vertical: 6),
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Row(children: [
                        Text(_names[e.key] ?? e.key, style: nunito(14, weight: FontWeight.w800)),
                        if (estimated.contains(e.key)) ...[const SizedBox(width: 6), const Pill('estimated', bg: C.lemon, fg: C.orangeD)],
                        const Spacer(),
                        Text('${e.value}', style: nunito(14, weight: FontWeight.w900)),
                      ]),
                      const SizedBox(height: 4),
                      ProgressBar((e.value as num) / 100, height: 8),
                    ]),
                  ),
              ]),
            ),
          ]);
        },
      ),
    );
  }
}
