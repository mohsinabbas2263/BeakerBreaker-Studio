import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../api/api.dart';
import '../auth.dart';
import '../theme.dart';
import '../widgets/common.dart';
import '../widgets/mcq.dart';

/// wf_S06, safety training. FR-02.1 four tiers in order, FR-02.2 the next opens only
/// when the last is passed, FR-02.4 a randomised set each time, FR-02.5 marked on
/// the server, FR-02.7 the certificate when all four are passed.
class SafetyScreen extends StatelessWidget {
  const SafetyScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Safety Training 🛡️')),
      body: Loader(
        load: () => api.get('/safety/tiers'),
        builder: (context, d, reload) {
          final tiers = (d['tiers'] as List).cast<Json>();
          return RefreshIndicator(
            onRefresh: reload,
            child: ListView(padding: const EdgeInsets.all(16), children: [
              if (d['certificate'] != null)
                const Padding(
                  padding: EdgeInsets.only(bottom: 14),
                  child: BBCard(
                    color: C.yellow,
                    child: Column(children: [
                      Text('🏅', style: TextStyle(fontSize: 40)),
                      Text('Safety Certificate Earned!', style: TextStyle(fontWeight: FontWeight.w900, fontSize: 16)),
                    ]),
                  ),
                ),
              for (final t in tiers)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: Opacity(
                    opacity: t['available'] == true || t['passed'] == true ? 1 : .55,
                    child: BBCard(
                      onTap: t['available'] == true
                          ? () => Navigator.push(context, MaterialPageRoute(builder: (_) => SafetyQuizScreen(tier: t['tier_number'] as int)))
                              .then((_) => reload())
                          : null,
                      child: Row(children: [
                        CircleAvatar(
                          radius: 22,
                          backgroundColor: t['passed'] == true ? C.green : (t['available'] == true ? C.blue : C.muted),
                          child: Text(t['passed'] == true ? '✓' : '${t['tier_number']}', style: nunito(16, color: Colors.white, weight: FontWeight.w900)),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Text('Tier ${t['tier_number']}: ${t['title']}', style: fredoka(15)),
                            Text(
                              t['passed'] == true
                                  ? 'Passed · best ${t['best_score']}%'
                                  : t['available'] == true
                                      ? '${t['question_count']} questions · pass at ${t['pass_mark']}%'
                                      : '🔒 Pass the tier before it first',
                              style: nunito(12, color: C.sub),
                            ),
                          ]),
                        ),
                        if (t['available'] == true) const Icon(Icons.chevron_right, color: C.sub),
                      ]),
                    ),
                  ),
                ),
            ]),
          );
        },
      ),
    );
  }
}

class SafetyQuizScreen extends StatefulWidget {
  const SafetyQuizScreen({super.key, required this.tier});
  final int tier;

  @override
  State<SafetyQuizScreen> createState() => _SafetyQuizScreenState();
}

class _SafetyQuizScreenState extends State<SafetyQuizScreen> {
  Json? _quiz;
  Json? _result;
  String? _error;
  int _attempt = 0;

  @override
  void initState() {
    super.initState();
    _start();
  }

  Future<void> _start() async {
    setState(() { _quiz = null; _result = null; _error = null; _attempt++; });
    try {
      final q = await api.post('/safety/tiers/${widget.tier}/start');
      if (mounted) setState(() => _quiz = q);
    } on ApiError catch (e) {
      if (mounted) setState(() => _error = e.message);
    }
  }

  Future<void> _submit(Map<String, int> answers) async {
    try {
      final res = await api.post('/safety/tiers/${widget.tier}/submit', {'answers': answers, '_safety_session': _quiz!['_safety_session']});
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
    return Scaffold(
      appBar: AppBar(title: Text('Safety Tier ${widget.tier}')),
      body: _error != null
          ? ErrorView(message: _error!)
          : _quiz == null
              ? const Spinner()
              : r != null
                  ? Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24),
                        child: BBCard(
                          color: r['passed'] == true ? C.mint : C.lemon,
                          child: Column(mainAxisSize: MainAxisSize.min, children: [
                            Text(r['passed'] == true ? (r['certificate'] != null ? '🏅' : '🎉') : '💪', style: const TextStyle(fontSize: 48)),
                            Text('${r['score']}%', style: fredoka(36)),
                            Text(
                              r['passed'] == true
                                  ? (r['certificate'] != null ? 'All four tiers passed. Your safety certificate is ready!' : 'Tier passed! The next tier is open.')
                                  : 'You need ${r['pass_mark']}% to pass. Review and try again.',
                              textAlign: TextAlign.center,
                              style: nunito(14, weight: FontWeight.w800),
                            ),
                            const SizedBox(height: 14),
                            if (r['passed'] != true) FilledButton(onPressed: _start, child: const Text('Try again')),
                            if (r['passed'] == true) FilledButton(onPressed: () => Navigator.pop(context), child: const Text('Back to safety')),
                          ]),
                        ),
                      ),
                    )
                  : ListView(padding: const EdgeInsets.all(16), children: [
                      Text('${_quiz!['title']}', style: fredoka(19)),
                      const SizedBox(height: 12),
                      McqForm(key: ValueKey(_attempt), questions: (_quiz!['questions'] as List).cast<Json>(), onSubmit: _submit),
                    ]),
    );
  }
}
