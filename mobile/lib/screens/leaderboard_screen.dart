import 'package:flutter/material.dart';

import '../api/api.dart';
import '../theme.dart';
import '../widgets/common.dart';

/// wf_S07. FR-07.1: name, avatar, rank level and points only; nothing else about
/// any other student is shown (SR-08).
class LeaderboardScreen extends StatelessWidget {
  const LeaderboardScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Leaderboard 🏆')),
      body: Loader(
        load: () => api.get('/leaderboard'),
        builder: (context, d, reload) {
          final rows = (d['rows'] as List).cast<Json>();
          return RefreshIndicator(
            onRefresh: reload,
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: rows.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, i) {
                final r = rows[i];
                final me = r['is_me'] == true;
                final pos = r['position'] as int;
                final medal = pos == 1 ? '🥇' : pos == 2 ? '🥈' : pos == 3 ? '🥉' : '$pos';
                return BBCard(
                  color: me ? C.sky : Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                  child: Row(children: [
                    SizedBox(width: 34, child: Text(medal, textAlign: TextAlign.center, style: fredoka(pos <= 3 ? 22 : 16, color: C.sub))),
                    const SizedBox(width: 8),
                    Avatar(r['avatar_key'] as String?, size: 40),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text('${r['display_name']}${me ? ' (you)' : ''}', style: fredoka(15)),
                        Text('${r['rank_name']}', style: nunito(12, color: C.sub)),
                      ]),
                    ),
                    Text('${r['total_points']} XP', style: nunito(14, color: C.orangeD, weight: FontWeight.w900)),
                  ]),
                );
              },
            ),
          );
        },
      ),
    );
  }
}
