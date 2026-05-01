import 'package:desktop_flutter/core/network/api_client.dart';
import 'package:desktop_flutter/shared/models/desktop_session.dart';

class ApiSession {
  static DesktopSession? _session;

  static DesktopSession? get current => _session;

  static ApiClient? get client {
    final session = _session;

    if (session == null) {
      return null;
    }

    return ApiClient(baseUrl: session.baseUrl, token: session.token);
  }

  static void set(DesktopSession? session) {
    _session = session;
  }

  static void clear() {
    _session = null;
  }
}
