import 'package:flutter/foundation.dart';

import 'api/api.dart';

/// Who is signed in. FR-01.6: there is no sign-up here; accounts are made by the
/// school's administrator, and this app only signs in with them.
class Auth extends ChangeNotifier {
  Json? user;
  bool loading = true;

  bool get signedIn => user != null;

  /// On start: a saved token is checked against /me before it is trusted.
  Future<void> restore() async {
    await api.loadToken();
    api.onUnauthenticated = () {
      user = null;
      notifyListeners();
    };
    if (api.hasToken) {
      try {
        user = (await api.get('/me'))['user'] as Json?;
      } catch (_) {
        user = null;
      }
    }
    loading = false;
    notifyListeners();
  }

  /// FR-01.2: the server decides the role. The mobile app is for students
  /// (Game Mode and AR Mode); teachers and administrators use the web portal.
  Future<void> login(String email, String password) async {
    final res = await api.post('/login', {'email': email, 'password': password, 'device_name': 'mobile'});
    final u = res['user'] as Json;
    if (u['role'] != 'student') {
      await api.saveToken(res['token'] as String);
      try {
        await api.post('/logout');
      } catch (_) {}
      await api.clearToken();
      throw ApiError(403, 'wrong_app', 'The mobile app is for students. Teachers and administrators sign in on the web portal.');
    }
    await api.saveToken(res['token'] as String);
    user = u;
    notifyListeners();
  }

  Future<void> refresh() async {
    try {
      user = (await api.get('/me'))['user'] as Json?;
      notifyListeners();
    } catch (_) {}
  }

  void patchUser(Json changes) {
    user = {...?user, ...changes};
    notifyListeners();
  }

  Future<void> logout() async {
    try {
      await api.post('/logout');
    } catch (_) {}
    await api.clearToken();
    user = null;
    notifyListeners();
  }
}
