import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../auth.dart';
import '../game/game_runner.dart';
import '../game/game_step.dart';
import '../game/guide.dart';
import '../theme.dart';
import '../widgets/common.dart';
import 'quiz_screen.dart';

/// wf_S04 Game Mode: the experiment played step by step through the game engine.
class RunScreen extends StatefulWidget {
  const RunScreen({super.key, required this.experimentId});
  final int experimentId;

  @override
  State<RunScreen> createState() => _RunScreenState();
}

class _RunScreenState extends State<RunScreen> {
  late final GameRunner _runner = GameRunner(experimentId: widget.experimentId, mode: 'standard')..load();

  @override
  void dispose() {
    _runner.dispose();
    super.dispose();
  }

  Future<void> _act(Map<String, dynamic> action) async {
    final err = await _runner.act(action);
    if (!mounted) return;
    if (err != null) toast(context, err, error: true);
    announceBadges(context, _runner.newBadges);
    _runner.newBadges = [];
  }

  @override
  Widget build(BuildContext context) {
    final avatar = context.watch<Auth>().user?['avatar_key'] as String?;
    return ListenableBuilder(
      listenable: _runner,
      builder: (context, _) {
        final r = _runner;
        final exp = r.experiment;
        return Scaffold(
          appBar: AppBar(title: Text(exp.isEmpty ? '' : '${exp['emoji']} ${exp['title']}', overflow: TextOverflow.ellipsis)),
          body: r.error != null
              ? ErrorView(message: r.error!)
              : r.current == null
                  ? const Spinner()
                  : r.finished
                      ? _Finished(runner: r, avatar: avatar)
                      : ListView(padding: const EdgeInsets.all(16), children: [
                          BBCard(
                            child: Column(children: [
                              Row(children: [
                                Text('Step ${(r.done + 1).clamp(1, r.total)} of ${r.total}', style: nunito(12, color: C.sub, weight: FontWeight.w900)),
                                const Spacer(),
                                Text('${(r.pct * 100).round()}%', style: nunito(12, color: C.sub, weight: FontWeight.w900)),
                              ]),
                              const SizedBox(height: 6),
                              ProgressBar(r.pct),
                            ]),
                          ),
                          const SizedBox(height: 14),
                          GuideBubble(runner: r, avatarKey: avatar),
                          const SizedBox(height: 14),
                          BBCard(
                            padding: const EdgeInsets.all(18),
                            child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                              Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                                CircleAvatar(radius: 17, backgroundColor: C.blue, child: Text('${r.current!['step_order']}', style: nunito(14, color: Colors.white, weight: FontWeight.w900))),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                                    Text('${r.current!['title']}', style: fredoka(16)),
                                    Text('${r.current!['instruction_text']}', style: nunito(13, color: C.sub)),
                                  ]),
                                ),
                              ]),
                              const SizedBox(height: 16),
                              GameStep(
                                key: ValueKey(r.current!['id']),
                                step: r.current!,
                                progress: r.progress,
                                reveal: r.reveal,
                                lastMistake: r.lastMistake,
                                busy: r.busy,
                                onAct: _act,
                              ),
                            ]),
                          ),
                          const SizedBox(height: 14),
                          BBCard(
                            child: Theme(
                              data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
                              child: ExpansionTile(
                                tilePadding: EdgeInsets.zero,
                                title: Text('💬 Ask AI about this step', style: fredoka(15)),
                                children: [AskGuide(runner: r)],
                              ),
                            ),
                          ),
                          if (exp['chemical_equation'] != null) ...[
                            const SizedBox(height: 14),
                            BBCard(
                              color: C.lemon,
                              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                                Text('🔬 THE SCIENCE', style: fredoka(12, color: C.orangeD)),
                                const SizedBox(height: 4),
                                Text('${exp['chemical_equation']}', style: fredoka(15, weight: FontWeight.w500)),
                              ]),
                            ),
                          ],
                          const SizedBox(height: 10),
                          Text('Every move saves as you go.', textAlign: TextAlign.center, style: nunito(11, color: C.muted)),
                        ]),
        );
      },
    );
  }
}

class _Finished extends StatelessWidget {
  const _Finished({required this.runner, required this.avatar});
  final GameRunner runner;
  final String? avatar;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(children: [
          const Text('🎉', style: TextStyle(fontSize: 56)),
          Avatar(avatar, size: 96),
          const SizedBox(height: 14),
          Text('Experiment complete!', style: fredoka(24)),
          const SizedBox(height: 6),
          Text('You finished all ${runner.total} steps. Pass the quiz at 80% to earn ${runner.experiment['base_points']} XP.',
              textAlign: TextAlign.center, style: nunito(14, color: C.sub)),
          const SizedBox(height: 20),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: C.purple),
            onPressed: () => Navigator.pushReplacement(context,
                MaterialPageRoute(builder: (_) => QuizScreen(experimentId: runner.experimentId, sessionId: runner.sessionId))),
            child: const Text('📝 Take the Quiz'),
          ),
        ]),
      ),
    );
  }
}
