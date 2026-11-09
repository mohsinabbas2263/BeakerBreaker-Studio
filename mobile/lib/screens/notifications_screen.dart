import 'package:flutter/material.dart';

import '../api/api.dart';
import '../theme.dart';
import '../widgets/common.dart';

/// FR-09.6, FR-09.7: tasks, reminders, safety alerts and announcements from the
/// student's teacher. SR-08: the endpoint only ever returns the student's own.
class NotificationsScreen extends StatelessWidget {
  const NotificationsScreen({super.key});

  static const _icons = {'task': '📋', 'reminder': '⏰', 'safety_alert': '⚠️', 'announcement': '📣'};

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Messages 🔔')),
      body: Loader(
        load: () async {
          final d = await api.get('/notifications');
          if ((d['unread_count'] ?? 0) > 0) {
            try {
              await api.patch('/notifications/read-all');
            } catch (_) {}
          }
          return d;
        },
        builder: (context, d, reload) {
          final rows = (d['notifications'] as List).cast<Json>();
          if (rows.isEmpty) {
            return Center(child: Text('No messages yet.', style: nunito(15, color: C.sub)));
          }
          return RefreshIndicator(
            onRefresh: reload,
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: rows.length,
              separatorBuilder: (context, i) => const SizedBox(height: 10),
              itemBuilder: (context, i) {
                final n = rows[i];
                final alert = n['notification_type'] == 'safety_alert';
                return BBCard(
                  color: alert ? C.coral : (n['read_at'] == null ? C.sky : Colors.white),
                  child: Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text(_icons[n['notification_type']] ?? '🔔', style: const TextStyle(fontSize: 24)),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                        Text('${n['subject']}', style: fredoka(15)),
                        const SizedBox(height: 2),
                        Text('${n['body']}', style: nunito(13)),
                        const SizedBox(height: 4),
                        Text(_when(n['created_at'] as String?), style: nunito(11, color: C.muted)),
                      ]),
                    ),
                  ]),
                );
              },
            ),
          );
        },
      ),
    );
  }

  String _when(String? iso) {
    final t = iso == null ? null : DateTime.tryParse(iso)?.toLocal();
    if (t == null) return '';
    final m = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][t.month - 1];
    final h = t.hour % 12 == 0 ? 12 : t.hour % 12;
    return '${t.day} $m, $h:${t.minute.toString().padLeft(2, '0')} ${t.hour < 12 ? 'am' : 'pm'}';
  }
}
