import 'package:flutter/foundation.dart';

import '../api/api.dart';

/// The game runner's state and moves (docs/experiment-engine.md), the app's copy of
/// the web portal's useGameRunner. Game Mode and AR Mode both use it, so they offer
/// the same controls (FR-05.5) and every move goes to the same server judge (FR-02.5).
///
/// FR-03.4  steps come in order; the server refuses a step out of order.
/// FR-03.5  each move is saved as it happens, so leaving and coming back resumes
///          inside the step (NFR-05).
/// FR-03.7  ask() answers about the current step; with the AI service down the API
///          answers with the pre-authored line (NFR-06).
class GameRunner extends ChangeNotifier {
  GameRunner({required this.experimentId, required this.mode});

  final int experimentId;
  final String mode; // standard | ar

  Json? detail;
  int? sessionId;
  int done = 0;
  String? error;
  bool busy = false;
  bool finished = false;
  bool notAllowed = false;

  String? line;
  String mood = 'point';
  int moodKey = 0;
  Json progress = {};
  Json? reveal;
  String? lastMistake;
  bool hinted = false;

  Json? answer;
  bool asking = false;

  /// Badges the last move earned, for the screen to announce once (FR-06.3).
  List<dynamic> newBadges = [];

  List<Json> get steps => ((detail?['steps'] as List?) ?? []).cast<Json>();
  int get total => steps.length;
  Json? get current => steps.isEmpty ? null : steps[done.clamp(0, total - 1)];
  double get pct => total == 0 ? 0 : done / total;
  Json get experiment => (detail?['experiment'] as Json?) ?? {};

  bool _disposed = false;

  @override
  void dispose() {
    _disposed = true;
    super.dispose();
  }

  void _notify() {
    if (!_disposed) notifyListeners();
  }

  Future<void> load() async {
    try {
      final d = await api.get('/experiments/$experimentId');
      if (d['access']?['allowed'] != true) {
        notAllowed = true;
        error = d['access']?['reason'] as String? ?? 'This experiment is locked.';
        _notify();
        return;
      }
      final s = await api.post('/experiments/$experimentId/start', {'presentation_mode': mode});
      detail = d;
      sessionId = s['session']['id'] as int;
      done = ((s['session']['completed_step_ids'] as List?) ?? []).length;
      final resume = d['resume'] as Json?;
      if (resume?['step_state'] != null && resume!['session_id'] == sessionId) {
        progress = Json.from((resume['step_state']['progress'] as Map?) ?? {});
      }
      _openStep();
    } on ApiError catch (e) {
      error = e.message;
    }
    _notify();
  }

  void _openStep() {
    final c = current;
    if (c == null) return;
    line = (c['intro'] as String?) ?? c['instruction_text'] as String?;
    mood = 'point';
    reveal = null;
    lastMistake = null;
    hinted = false;
    answer = null;
  }

  Future<String?> act(Json action) async {
    final c = current;
    if (c == null || sessionId == null || busy) return null;
    busy = true;
    _notify();
    try {
      final res = await api.post('/sessions/$sessionId/steps/${c['id']}/actions', {'action': action, 'hinted': hinted});
      line = (res['message'] as String?) ?? (res['outcome'] == 'partial' ? 'Good! Keep going. 👍' : line);
      mood = res['mood'] as String? ?? 'point';
      moodKey++;
      reveal = res['reveal'] as Json?;
      lastMistake = res['mistake_code'] as String?;
      newBadges = (res['badges_awarded'] as List?) ?? [];

      if (res['step_complete'] == true) {
        progress = {};
        if (res['quiz_unlocked'] == true) {
          finished = true;
        } else {
          // Let the student enjoy the success line before the next step opens.
          final next = res['steps_completed'] as int;
          Future.delayed(const Duration(milliseconds: 1100), () {
            if (_disposed) return;
            done = next;
            _openStep();
            _notify();
          });
        }
      } else {
        progress = Json.from((res['progress'] as Map?) ?? {});
      }
      return null;
    } on ApiError catch (e) {
      if (e.code == 'out_of_order') {
        final s = await api.post('/experiments/$experimentId/start', {'presentation_mode': mode});
        done = ((s['session']['completed_step_ids'] as List?) ?? []).length;
        _openStep();
        return 'Your progress was updated on another device.';
      }
      return e.message;
    } finally {
      busy = false;
      _notify();
    }
  }

  Future<String?> ask(String question) async {
    final c = current;
    if (c == null || question.trim().isEmpty) return null;
    asking = true;
    hinted = true; // recorded with the next move, for the readiness report
    _notify();
    try {
      answer = await api.post('/steps/${c['id']}/guidance', {'question': question.trim()});
      mood = 'think';
      return null;
    } on ApiError {
      return 'Could not reach your guide just now. Try again.';
    } finally {
      asking = false;
      _notify();
    }
  }
}
