import 'dart:async';
import 'dart:convert';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

/// The same REST API the web portal uses (docs/api.md). The address comes from the
/// build: flutter run --dart-define=API_URL=https://api.example.com/api
/// Without it, the Android emulator's address for the development machine is used.
const kApiUrl = String.fromEnvironment('API_URL', defaultValue: 'http://10.0.2.2:8000/api');

class ApiError implements Exception {
  ApiError(this.status, this.code, this.message, [this.body = const {}]);
  final int status;
  final String? code;
  final String message;
  final Map<String, dynamic> body;

  @override
  String toString() => message;
}

typedef Json = Map<String, dynamic>;

/// Holds the access token (SR-03) in the platform's secure storage, never in plain
/// preferences, and sends it with every request.
class Api {
  Api._();
  static final Api instance = Api._();

  static const _storage = FlutterSecureStorage();
  static const _tokenKey = 'bb_token';

  String? _token;

  /// Called when the server says the token is no longer valid (401).
  void Function()? onUnauthenticated;

  Future<void> loadToken() async => _token = await _storage.read(key: _tokenKey);

  bool get hasToken => _token != null;

  Future<void> saveToken(String token) async {
    _token = token;
    await _storage.write(key: _tokenKey, value: token);
  }

  Future<void> clearToken() async {
    _token = null;
    await _storage.delete(key: _tokenKey);
  }

  Future<Json> get(String path) => _send('GET', path);
  Future<Json> post(String path, [Object? body]) => _send('POST', path, body);
  Future<Json> patch(String path, [Object? body]) => _send('PATCH', path, body);

  Future<Json> _send(String method, String path, [Object? body]) async {
    final request = http.Request(method, Uri.parse('$kApiUrl$path'))
      ..headers.addAll({
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        if (_token != null) 'Authorization': 'Bearer $_token',
      });
    if (body != null) request.body = jsonEncode(body);

    final http.Response res;
    try {
      res = await http.Response.fromStream(await request.send().timeout(const Duration(seconds: 20)));
    } on TimeoutException {
      throw ApiError(0, 'offline', 'The lab is taking too long to answer. Check your connection.');
    } catch (_) {
      throw ApiError(0, 'offline', 'Could not reach the lab. Check your connection.');
    }

    Json data = {};
    if (res.body.isNotEmpty) {
      try {
        final decoded = jsonDecode(res.body);
        data = decoded is Map<String, dynamic> ? decoded : {'data': decoded};
      } catch (_) {}
    }

    if (res.statusCode == 401) {
      await clearToken();
      onUnauthenticated?.call();
      throw ApiError(401, 'unauthenticated', 'Your session has ended. Please sign in again.');
    }
    if (res.statusCode >= 400) {
      var message = data['message'] as String? ?? 'Something went wrong.';
      final errors = data['errors'];
      if (errors is Map && errors.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) message = '${first.first}';
      }
      throw ApiError(res.statusCode, data['code'] as String?, message, data);
    }
    return data;
  }
}

final api = Api.instance;
