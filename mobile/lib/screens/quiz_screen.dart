import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../api/api.dart';
import '../auth.dart';
import '../theme.dart';
import '../widgets/common.dart';
import '../widgets/mcq.dart';

/// wf_S05, the knowledge quiz.
/// FR-04.1 opens only after every step (the server answers 403 until then).
/// FR-04.2 each wrong answer comes back with its explanation.
/// FR-04.3 points once, on the first pass at 80 or above. FR-04.5 a retry is allowed.
class QuizScreen extends StatefulWidget {
  const QuizScreen({super.key, required this.experimentId, this.sessionId});
  final int experimentId;
  final int? sessionId;

  @override
  State<QuizScreen> createState() => _QuizScreenState();
}

class _QuizScreenState extends State<QuizScreen> {
  Json? _quiz;
  Json? _result;
  String? _blocked;
  int _attempt = 0;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _quiz = null; _result = null; _blocked = null; _attempt++; });
    try {
      final q = await api.get('/experiments/${widget.experimentId}/quiz');
      if (mounted) setState(() => _quiz = q);
    } on ApiError catch (e) {
      if (mounted) setState(() => _blocked = e.message);
    }
  }

  Future<void> _submit(Map<String, int> answers) async {
    try {
      final res = await api.post('/experiments/${widget.experimentId}/quiz', {
        'answers': answers,
        '_quiz_session': _quiz!['_quiz_session'],
        if (widget.sessionId != null) 'session_id': widget.sessionId,
      });
      if (!mounted) return;
      setState(() => _result = res);
      announceBadges(context, res['badges_awarded']);
      context.read<Auth>().refresh();
    } on ApiError catch (e) {
      if (mounted) toast(context, e.message, error: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    final r = _result;
    Map<int, Json>? feedback;
    if (r != null) {
      feedback = {for (final f in (r['feedback'] as List).cast<Json>()) f['question_id'] as int: f};
    }
    return Scaffold(
      appBar: AppBar(title: const Text('Knowledge Quiz 📝')),
      body: _blocked != null
          ? ErrorView(message: _blocked!)
          : _quiz == null
              ? const Spinner()
              : ListView(padding: const EdgeInsets.all(16), children: [
                  if (r != null)
                    BBCard(
                      color: r['passed'] == true ? C.mint : C.lemon,
                      child: Column(children: [
                        Text(r['passed'] == true ? '🎉' : '💪', style: const TextStyle(fontSize: 44)),
                        Text('${r['score']}%', style: fredoka(36)),
                        Text(
                          r['passed'] == true
                              ? ((r['points_awarded'] as int? ?? 0) > 0 ? 'Passed! +${r['points_awarded']} XP' : 'Passed!')
                              : 'You need ${r['pass_mark']}% to pass. Read the explanations and try again.',
                          textAlign: TextAlign.center,
                          style: nunito(14, weight: FontWeight.w800),
                        ),
                        if (r['may_reattempt'] == true) ...[
                          const SizedBox(height: 10),
                          FilledButton(onPressed: _load, child: const Text('Try again')),
                        ] else ...[
                          const SizedBox(height: 10),
                          FilledButton(onPressed: () => Navigator.pop(context), child: const Text('Back to the experiment')),
                        ],
                      ]),
                    ),
                  if (r != null) const SizedBox(height: 14),
                  Text('${_quiz!['experiment']?['title'] ?? ''}', style: fredoka(18)),
                  const SizedBox(height: 10),
                  McqForm(
                    key: ValueKey(_attempt),
                    questions: (_quiz!['questions'] as List).cast<Json>(),
                    onSubmit: _submit,
                    feedback: feedback,
                  ),
                ]),
    );
  }
}
