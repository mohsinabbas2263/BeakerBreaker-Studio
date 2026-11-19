import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../api/api.dart';
import '../auth.dart';
import '../theme.dart';
import '../widgets/common.dart';
import 'notifications_screen.dart';
import 'readiness_screen.dart';

/// wf_S08. The student may change their display name and avatar and nothing else
/// (the server refuses anything more). FR-06.3: the badges earned, of all there are.
class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My Profile')),
      body: Loader(
        load: () => api.get('/profile'),
        builder: (context, p, reload) {
          final badges = (p['badges'] as List).cast<Json>();
          return RefreshIndicator(
            onRefresh: reload,
            child: ListView(padding: const EdgeInsets.all(16), children: [
              Center(child: Avatar(p['avatar_key'] as String?, size: 96)),
              const SizedBox(height: 10),
              Center(child: Text('${p['display_name']}', style: fredoka(22))),
              Center(child: Text('${p['class'] ?? ''}', style: nunito(13, color: C.sub))),
              const SizedBox(height: 6),
              Center(
                child: TextButton.icon(
                  onPressed: () => _edit(context, p, reload),
                  icon: const Icon(Icons.edit, size: 18),
                  label: const Text('Change name or avatar'),
                ),
              ),
              const SizedBox(height: 10),
              Row(children: [
                _stat('⭐', '${p['total_points']}', 'XP'),
                _stat('🎖️', '${p['rank_name']}', 'Rank'),
                _stat('🔥', '${p['streak_days']}', 'Day streak'),
              ]),
              const SizedBox(height: 8),
              Row(children: [
                _stat('🧪', '${p['experiments_completed']}', 'Experiments'),
                _stat('🛡️', '${p['safety_tier_passed']}/4', 'Safety tiers'),
                _stat('🏅', '${badges.length}/${p['badges_total'] ?? '?'}', 'Badges'),
              ]),
              const SizedBox(height: 14),
              if (p['certificate'] != null)
                const BBCard(color: C.yellow, child: Center(child: Text('🏅 Safety Certificate Earned!', style: TextStyle(fontWeight: FontWeight.w900)))),
              const SizedBox(height: 14),
              BBCard(
                onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ReadinessScreen())),
                child: Row(children: [
                  const Text('📊', style: TextStyle(fontSize: 26)),
                  const SizedBox(width: 10),
                  Expanded(child: Text('My lab readiness report', style: fredoka(15))),
                  const Icon(Icons.chevron_right, color: C.sub),
                ]),
              ),
              const SizedBox(height: 10),
              BBCard(
                onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const NotificationsScreen())),
                child: Row(children: [
                  const Text('🔔', style: TextStyle(fontSize: 26)),
                  const SizedBox(width: 10),
                  Expanded(child: Text('Messages from my teacher', style: fredoka(15))),
                  const Icon(Icons.chevron_right, color: C.sub),
                ]),
              ),
              const SizedBox(height: 18),
              Text('Badges', style: fredoka(18)),
              const SizedBox(height: 8),
              if (badges.isEmpty) Text('Pass a safety tier or an experiment to earn your first badge.', style: nunito(13, color: C.sub)),
              Wrap(spacing: 8, runSpacing: 8, children: [
                for (final b in badges)
                  Tooltip(
                    message: '${b['description'] ?? ''}',
                    child: Chip(
                      label: Text('${b['icon']} ${b['name']}', style: nunito(13, weight: FontWeight.w800)),
                      backgroundColor: Colors.white,
                      side: BorderSide(color: C.blue.withValues(alpha: .25), width: 2),
                      shape: const StadiumBorder(),
                    ),
                  ),
              ]),
              const SizedBox(height: 24),
              OutlinedButton(
                onPressed: () => context.read<Auth>().logout(),
                style: OutlinedButton.styleFrom(minimumSize: const Size.fromHeight(48), foregroundColor: C.orangeD),
                child: const Text('Sign out'),
              ),
            ]),
          );
        },
      ),
    );
  }

  Widget _stat(String emoji, String value, String label) => Expanded(
        child: Padding(
          padding: const EdgeInsets.all(4),
          child: BBCard(
            padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 6),
            child: Column(children: [
              Text(emoji, style: const TextStyle(fontSize: 20)),
              Text(value, textAlign: TextAlign.center, maxLines: 1, overflow: TextOverflow.ellipsis, style: fredoka(15)),
              Text(label, style: nunito(11, color: C.sub)),
            ]),
          ),
        ),
      );

  Future<void> _edit(BuildContext context, Json p, Future<void> Function() reload) async {
    final name = TextEditingController(text: '${p['display_name']}');
    String avatar = '${p['avatar_key']}';
    String? error;
    final auth = context.read<Auth>();

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(22))),
      builder: (sheet) => StatefulBuilder(
        builder: (sheet, setSheet) => Padding(
          padding: EdgeInsets.fromLTRB(20, 20, 20, MediaQuery.of(sheet).viewInsets.bottom + 20),
          child: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            Text('Your name and avatar', style: fredoka(19)),
            const SizedBox(height: 12),
            TextField(controller: name, maxLength: 40, decoration: InputDecoration(labelText: 'Display name', errorText: error)),
            const SizedBox(height: 8),
            Row(mainAxisAlignment: MainAxisAlignment.spaceEvenly, children: [
              for (final a in kAvatars)
                GestureDetector(
                  onTap: () => setSheet(() => avatar = a),
                  child: Container(
                    padding: const EdgeInsets.all(3),
                    decoration: BoxDecoration(shape: BoxShape.circle, border: Border.all(color: avatar == a ? C.blueD : Colors.transparent, width: 3)),
                    child: Avatar(a, size: 58, border: false),
                  ),
                ),
            ]),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: () async {
                try {
                  final res = await api.patch('/profile', {'display_name': name.text.trim(), 'avatar_key': avatar});
                  auth.patchUser({'display_name': res['display_name'] ?? name.text.trim(), 'avatar_key': res['avatar_key'] ?? avatar});
                  if (sheet.mounted) Navigator.pop(sheet);
                } on ApiError catch (e) {
                  setSheet(() => error = e.message);
                }
              },
              child: const Text('Save'),
            ),
          ]),
        ),
      ),
    );
    await reload();
  }
}
