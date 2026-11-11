import 'package:flutter/material.dart';

import '../api/api.dart';
import '../theme.dart';
import 'common.dart';

/// Multiple-choice questions, shared by the knowledge quiz (FR-04) and the safety
/// tiers (FR-02). The options come shuffled from the server; only the chosen
/// positions are sent back, and the score is the server's (FR-02.5).
class McqForm extends StatefulWidget {
  const McqForm({super.key, required this.questions, required this.onSubmit, this.feedback});
  final List<Json> questions;
  final Future<void> Function(Map<String, int> answers) onSubmit;

  /// After submitting: per question id, {correct, correct_option, explanation}.
  final Map<int, Json>? feedback;

  @override
  State<McqForm> createState() => _McqFormState();
}

class _McqFormState extends State<McqForm> {
  final Map<int, int> _answers = {};
  bool _busy = false;

  @override
  Widget build(BuildContext context) {
    final fb = widget.feedback;
    final n = widget.questions.length;
    return Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
      if (fb == null) ...[
        Row(children: [
          Text('${_answers.length} of $n answered', style: nunito(12, color: C.sub, weight: FontWeight.w900)),
        ]),
        const SizedBox(height: 6),
        ProgressBar(n == 0 ? 0 : _answers.length / n, height: 8),
        const SizedBox(height: 14),
      ],
      for (var qi = 0; qi < n; qi++) _question(qi, widget.questions[qi], fb?[widget.questions[qi]['id']]),
      if (fb == null)
        FilledButton(
          style: FilledButton.styleFrom(backgroundColor: C.purple),
          onPressed: _busy || _answers.length < n
              ? null
              : () async {
                  setState(() => _busy = true);
                  try {
                    await widget.onSubmit(_answers.map((k, v) => MapEntry('$k', v)));
                  } finally {
                    if (mounted) setState(() => _busy = false);
                  }
                },
          child: Text(_busy ? 'Marking…' : _answers.length < n ? 'Answer every question' : 'Submit answers'),
        ),
    ]);
  }

  Widget _question(int index, Json q, Json? f) {
    final id = q['id'] as int;
    final options = (q['options'] as List).cast<String>();
    final chosen = _answers[id];
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: BBCard(
        child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          Text('${index + 1}. ${q['prompt']}', style: fredoka(16, weight: FontWeight.w500)),
          const SizedBox(height: 10),
          for (var i = 0; i < options.length; i++)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: InkWell(
                borderRadius: BorderRadius.circular(14),
                onTap: f != null ? null : () => setState(() => _answers[id] = i),
                child: Ink(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: _optionColour(f, chosen, i),
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: chosen == i ? C.blueD : C.blue.withValues(alpha: .2), width: 2),
                  ),
                  child: Row(children: [
                    Text('${String.fromCharCode(65 + i)}.', style: nunito(14, color: C.blueD, weight: FontWeight.w900)),
                    const SizedBox(width: 8),
                    Expanded(child: Text(options[i], style: nunito(14, weight: FontWeight.w700))),
                  ]),
                ),
              ),
            ),
          if (f != null && f['correct'] != true && f['explanation'] != null)
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(color: C.lemon, borderRadius: BorderRadius.circular(12)),
              child: Text('💡 ${f['explanation']}', style: nunito(13)),
            ),
        ]),
      ),
    );
  }

  Color _optionColour(Json? f, int? chosen, int i) {
    if (f == null) return chosen == i ? C.sky : Colors.white;
    if (f['correct'] == true && chosen == i) return C.mint;
    if (f['correct_option'] == i) return C.mint;
    if (chosen == i) return C.coral;
    return Colors.white;
  }
}
