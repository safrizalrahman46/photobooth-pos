import 'package:desktop_flutter/core/config/app_config.dart';

const String defaultOperatorErrorMessage =
    'Permintaan belum berhasil diproses. Coba lagi beberapa saat.';

String resolveRequestErrorMessage(
  Object? error, {
  String fallback = defaultOperatorErrorMessage,
}) {
  if (error == null) {
    return fallback;
  }

  return sanitizeRequestMessage(error.toString(), fallback: fallback);
}

String sanitizeRequestMessage(
  String? message, {
  String fallback = defaultOperatorErrorMessage,
}) {
  final raw = (message ?? '').trim();

  if (raw.isEmpty) {
    return fallback;
  }

  final cleaned = raw
      .replaceFirst(RegExp(r'^Exception:\s*', caseSensitive: false), '')
      .replaceFirst(RegExp(r'^ApiException:\s*', caseSensitive: false), '')
      .trim();
  final lower = cleaned.toLowerCase();

  if (lower.contains('timeout') || lower.contains('timed out')) {
    return AppConfig.connectionTimeoutMessage;
  }

  if (_looksLikeConnectionError(lower)) {
    return AppConfig.connectionErrorMessage;
  }

  if (_looksLikeServerError(lower)) {
    return 'Sistem sedang bermasalah. Coba lagi beberapa saat.';
  }

  if (_looksTechnical(cleaned, lower)) {
    return fallback;
  }

  return cleaned;
}

bool _looksLikeConnectionError(String lower) {
  return lower.contains('socketexception') ||
      lower.contains('clientexception') ||
      lower.contains('failed host lookup') ||
      lower.contains('connection refused') ||
      lower.contains('connection reset') ||
      lower.contains('connection closed') ||
      lower.contains('networkerror') ||
      lower.contains('failed to fetch') ||
      lower.contains('xmlhttprequest error');
}

bool _looksLikeServerError(String lower) {
  return lower.contains('internal server error') ||
      lower.contains('server error') ||
      lower.contains('http 500') ||
      lower.contains('status code: 500') ||
      lower == '500';
}

bool _looksTechnical(String cleaned, String lower) {
  if (cleaned.length > 220) {
    return true;
  }

  return lower.contains('sqlstate') ||
      lower.contains('stack trace') ||
      lower.contains('trace:') ||
      lower.contains('formatexception') ||
      lower.contains('typeerror') ||
      lower.contains('null check operator') ||
      lower.contains('no such method') ||
      lower.startsWith('<!doctype') ||
      lower.startsWith('<html') ||
      lower.contains('\n#0');
}
