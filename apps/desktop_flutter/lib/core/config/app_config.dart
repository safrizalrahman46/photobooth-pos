class AppConfig {
  static const String appName = 'Ready To Pict Desktop';
  static const String productionApiBaseUrl = 'https://readytopict.com/api/v1';
  static const String defaultApiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: productionApiBaseUrl,
  );
  static const int apiTimeoutSeconds = int.fromEnvironment(
    'API_TIMEOUT_SECONDS',
    defaultValue: 30,
  );
  static const Duration apiRequestTimeout = Duration(
    seconds: apiTimeoutSeconds,
  );
  static const String deviceName = 'windows-desktop';

  static String get apiBaseUrl => normalizeApiBaseUrl(defaultApiBaseUrl);

  static String normalizeApiBaseUrl(String value) {
    return value.trim().replaceFirst(RegExp(r'/+$'), '');
  }

  static bool matchesConfiguredApiBaseUrl(String value) {
    return normalizeApiBaseUrl(value) == apiBaseUrl;
  }

  static const String connectionErrorMessage =
      'Tidak dapat terhubung ke server Ready To Pict. Periksa koneksi internet atau coba lagi.';
  static const String connectionTimeoutMessage =
      'Koneksi ke server Ready To Pict melewati batas waktu. Coba lagi.';
}
