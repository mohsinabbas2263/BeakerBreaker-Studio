import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../api/api.dart';
import '../ar/ar_screen.dart';
import '../auth.dart';
import '../theme.dart';
import '../widgets/common.dart';
import 'experiment_detail_screen.dart';
import 'notifications_screen.dart';
import 'shell.dart';

/// wf_S02, the student home: greeting, next safety tier, today's missions
/// (FR-07.3) and a way back into the experiments.
class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  Future<Json> _load() async {
    final r = await Future.wait([api.get('/missions'), api.get('/safety/tiers'), api.get('/experiments'), api.get('/notifications')]);
    return {'missions': r[0], 'safety': r[1], 'experiments': r[2], 'notifications': r[3]};
  }

  @override
  Widget build(BuildContext context) {
    final user = context.watch<Auth>().user ?? {};
    return Scaffold(
      appBar: AppBar(
        title: Row(children: [
          Image.asset('assets/brand/mark.png', width: 28),
          const SizedBox(width: 8),
          const Text('Beaker Breakers'),
        ]),
        actions: [
          IconButton(
            tooltip: 'Notifications',
            icon: const Text('🔔', style: TextStyle(fontSize: 22)),
            onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const NotificationsScreen())),
          ),
          Padding(padding: const EdgeInsets.only(right: 12), child: Pill('⭐ ${user['total_points'] ?? 0}', bg: C.yellow, fg: C.ink)),
        ],
      ),
      body: Loader(
        load: _load,
        builder: (context, d, reload) {
          final tiers = (d['safety']['tiers'] as List).cast<Json>();
          final passed = tiers.where((t) => t['passed'] == true).length;
          final next = tiers.cast<Json?>().firstWhere((t) => t!['passed'] != true, orElse: () => null);
          final exps = (d['experiments']['experiments'] as List).cast<Json>();
          final recent = exps.where((e) => e['locked'] != true).take(3).toList();
          final unread = d['notifications']['unread_count'] ?? 0;

          return RefreshIndicator(
            onRefresh: () async { await reload(); if (context.mounted) await context.read<Auth>().refresh(); },
            child: ListView(padding: const EdgeInsets.all(16), children: [
              Row(children: [
                Avatar(user['avatar_key'] as String?, size: 56),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text('Assalam o Alaikum, ${(user['display_name'] ?? '').toString().split(' ').first}!', style: fredoka(20)),
                    Text('${user['rank_name'] ?? ''} · ${user['total_points'] ?? 0} XP · 🔥 ${user['streak_days'] ?? 0}', style: nunito(13, color: C.sub)),
                  ]),
                ),
              ]),
              const SizedBox(height: 16),
              if (unread > 0)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: BBCard(
                    color: C.lemon,
                    onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const NotificationsScreen())).then((_) => reload()),
                    child: Text('🔔 You have $unread new message${unread == 1 ? '' : 's'} from your teacher', style: nunito(14, weight: FontWeight.w800)),
                  ),
                ),
              if (next != null)
                _Banner(
                  emoji: '🛡️',
                  title: passed == 0 ? 'Start with Safety Tier 1' : 'Next: ${next['title']}',
                  subtitle: passed == 0 ? 'Pass it to unlock the experiments' : 'Tier ${next['tier_number']} of 4 — keep going!',
                  colors: const [C.mint, C.green],
                  onTap: () => Shell.goTo(context, 2),
                ),
              _Missions(d['missions'] as Json),
              const SizedBox(height: 4),
              Row(children: [
                Expanded(child: _Quick(emoji: '🔬', title: 'Experiments', sub: '${exps.length} available', onTap: () => Shell.goTo(context, 1))),
                const SizedBox(width: 12),
                Expanded(
                  child: _Quick(
                    emoji: '🥽',
                    title: 'AR Mode',
                    sub: 'Lab on your desk',
                    onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ArScreen())),
                  ),
                ),
              ]),
              if (recent.isNotEmpty) ...[
                const SizedBox(height: 20),
                Text('Jump Back In', style: fredoka(18)),
                const SizedBox(height: 10),
                for (final e in recent)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 10),
                    child: BBCard(
                      onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => ExperimentDetailScreen(id: e['id'] as int))),
                      child: Row(children: [
                        Text('${e['emoji']}', style: const TextStyle(fontSize: 30)),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                            Text('${e['title']}', style: fredoka(15)),
                            Text('${e['topic']} · ${e['base_points']} XP', style: nunito(12, color: C.sub)),
                          ]),
                        ),
                        if (e['completed'] == true) const Text('✓', style: TextStyle(color: C.green, fontWeight: FontWeight.w900)),
                      ]),
                    ),
                  ),
              ],
            ]),
          );
        },
      ),
    );
  }
}

class _Banner extends StatelessWidget {
  const _Banner({required this.emoji, required this.title, required this.subtitle, required this.colors, required this.onTap});
  final String emoji, title, subtitle;
  final List<Color> colors;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(18),
        child: Ink(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(gradient: LinearGradient(colors: colors), borderRadius: BorderRadius.circular(18)),
          child: Row(children: [
            Text(emoji, style: const TextStyle(fontSize: 30)),
            const SizedBox(width: 12),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(title, style: fredoka(16, color: Colors.white)),
                Text(subtitle, style: nunito(12, color: Colors.white)),
              ]),
            ),
            const Icon(Icons.arrow_forward, color: Colors.white),
          ]),
        ),
      ),
    );
  }
}

/// FR-07.3: today's three missions, counted on the server.
class _Missions extends StatelessWidget {
  const _Missions(this.data);
  final Json data;

  @override
  Widget build(BuildContext context) {
    final missions = (data['missions'] as List).cast<Json>();
    final done = missions.where((m) => m['completed'] == true).length;
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: BBCard(
        child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          Row(children: [
            Expanded(child: Text("🎯 Today's Missions", style: fredoka(17))),
            Text('$done/${missions.length} done', style: nunito(12, color: C.sub, weight: FontWeight.w900)),
          ]),
          const SizedBox(height: 10),
          for (final m in missions)
            Container(
              margin: const EdgeInsets.only(bottom: 8),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: m['completed'] == true ? C.mint : C.sky, borderRadius: BorderRadius.circular(16)),
              child: Row(children: [
                Text(m['completed'] == true ? '✅' : '${m['icon']}', style: const TextStyle(fontSize: 22)),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text('${m['title']}',
                        style: nunito(13, weight: FontWeight.w800).copyWith(
                            decoration: m['completed'] == true ? TextDecoration.lineThrough : null)),
                    if ((m['target'] as int) > 1) ...[
                      const SizedBox(height: 6),
                      ProgressBar((m['progress'] as int) / (m['target'] as int), height: 6, color: C.green),
                    ],
                  ]),
                ),
                const SizedBox(width: 8),
                Text('${m['progress']}/${m['target']}', style: nunito(12, color: C.sub, weight: FontWeight.w900)),
              ]),
            ),
          Text('New missions every day at midnight.', textAlign: TextAlign.center, style: nunito(11, color: C.muted)),
        ]),
      ),
    );
  }
}

class _Quick extends StatelessWidget {
  const _Quick({required this.emoji, required this.title, required this.sub, required this.onTap});
  final String emoji, title, sub;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) => BBCard(
        onTap: onTap,
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(emoji, style: const TextStyle(fontSize: 30)),
          const SizedBox(height: 6),
          Text(title, style: fredoka(15)),
          Text(sub, style: nunito(12, color: C.sub)),
        ]),
      );
}
