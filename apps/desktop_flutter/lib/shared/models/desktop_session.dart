import 'package:desktop_flutter/shared/models/auth_user.dart';

class DesktopSession {
  const DesktopSession({
    required this.baseUrl,
    required this.token,
    required this.user,
  });

  final String baseUrl;
  final String token;
  final AuthUser user;

  factory DesktopSession.fromJson(Map<String, dynamic> json) {
    return DesktopSession(
      baseUrl: json['baseUrl']?.toString() ?? '',
      token: json['token']?.toString() ?? '',
      user: AuthUser.fromJson(json['user'] as Map<String, dynamic>? ?? {}),
    );
  }

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'baseUrl': baseUrl,
      'token': token,
      'user': user.toJson(),
    };
  }
}
