
class DateUtil {
  static DateTime parseUtcToLocal(String value) {
    if (value.trim().isEmpty) return DateTime.now();
    try {
      final trimmed = value.trim();
      // Check if it already has timezone indicators
      if (trimmed.endsWith('Z') || trimmed.contains('+') || RegExp(r'-\d{2}:\d{2}$').hasMatch(trimmed)) {
        return DateTime.parse(trimmed).toLocal();
      }
      
      // If no timezone is present, format it to ISO 8601 UTC and parse
      String formatted = trimmed;
      if (formatted.contains(' ')) {
        formatted = formatted.replaceAll(' ', 'T');
      }
      if (!formatted.endsWith('Z')) {
        formatted = '${formatted}Z';
      }
      return DateTime.parse(formatted).toLocal();
    } catch (_) {
      final parsed = DateTime.tryParse(value);
      if (parsed != null) {
        return parsed.toLocal();
      }
      return DateTime.now();
    }
  }

  static String formatBookingTime(String? value) {
    if (value == null || value.isEmpty) return '-';
    final local = parseUtcToLocal(value);
    return '${local.hour.toString().padLeft(2, '0')}:${local.minute.toString().padLeft(2, '0')}';
  }

  static String formatDateTime(String? value) {
    if (value == null || value.isEmpty) return '-';
    final local = parseUtcToLocal(value);
    final year = local.year.toString().padLeft(4, '0');
    final month = local.month.toString().padLeft(2, '0');
    final day = local.day.toString().padLeft(2, '0');
    final hour = local.hour.toString().padLeft(2, '0');
    final minute = local.minute.toString().padLeft(2, '0');
    return '$year-$month-$day $hour:$minute';
  }
}
