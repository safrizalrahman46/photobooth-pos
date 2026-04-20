import 'dart:convert';

import 'package:desktop_flutter/shared/models/desktop_session.dart';
import 'package:shared_preferences/shared_preferences.dart';

class SessionStore {
  static const String _sessionKey = 'desktop_session';

  Future<void> save(DesktopSession session) async {
    final preferences = await SharedPreferences.getInstance();
    await preferences.setString(_sessionKey, jsonEncode(session.toJson()));
  }

  Future<DesktopSession?> load() async {
    final preferences = await SharedPreferences.getInstance();
    final raw = preferences.getString(_sessionKey);

    if (raw == null || raw.isEmpty) {
      return null;
    }

    final decoded = jsonDecode(raw);

    if (decoded is! Map<String, dynamic>) {
      return null;
    }

    return DesktopSession.fromJson(decoded);
  }

  Future<void> clear() async {
    final preferences = await SharedPreferences.getInstance();
    await preferences.remove(_sessionKey);
  }
}
