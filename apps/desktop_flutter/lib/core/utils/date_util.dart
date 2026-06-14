
class DateUtil {
  static DateTime parseUtcToLocal(String value) {
    if (value.trim().isEmpty) return DateTime.now();
    try {
      final trimmed = value.trim();
      
      // Parse using DateTime.parse
      final parsed = DateTime.parse(trimmed);
      
      // Check if there is an explicit offset in the string (like +07:00, +08:00, -05:00)
      // or if it ends with 'Z' (which is UTC +00:00).
      // If there's an offset in the string, we want to extract that offset and apply it,
      // instead of using the OS local timezone via .toLocal().
      int offsetMinutes = 420; // Default to UTC+7 (Asia/Jakarta) if no offset is found
      
      if (trimmed.endsWith('Z')) {
        offsetMinutes = 420; // Default to UTC+7 since the server returns Z
      } else {
        final match = RegExp(r'([+-])(\d{2}):?(\d{2})$').firstMatch(trimmed);
        if (match != null) {
          final sign = match.group(1) == '+' ? 1 : -1;
          final hours = int.parse(match.group(2)!);
          final minutes = int.parse(match.group(3)!);
          offsetMinutes = sign * (hours * 60 + minutes);
        } else {
          // If no timezone offset is in the string, and it doesn't end with Z,
          // it might already be local time (e.g. 2026-06-14 19:09:00).
          // In this case, we just parse it as local time directly without adding any offset.
          if (!trimmed.contains('T') && !trimmed.contains('+') && !trimmed.contains('Z')) {
            return parsed; 
          }
        }
      }
      
      // Convert the parsed DateTime to UTC first
      final utcTime = parsed.toUtc();
      
      // Add the offset to get the correct time
      return utcTime.add(Duration(minutes: offsetMinutes));
    } catch (_) {
      final parsed = DateTime.tryParse(value);
      if (parsed != null) {
        return parsed;
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
