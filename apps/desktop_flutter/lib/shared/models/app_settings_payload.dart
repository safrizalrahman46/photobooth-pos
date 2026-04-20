class AppSettingsPayload {
  const AppSettingsPayload({
    required this.general,
    required this.booking,
    required this.payment,
  });

  final Map<String, dynamic> general;
  final Map<String, dynamic> booking;
  final Map<String, dynamic> payment;

  factory AppSettingsPayload.fromJson(Map<String, dynamic> json) {
    return AppSettingsPayload(
      general: _toMap(json['general']),
      booking: _toMap(json['booking']),
      payment: _toMap(json['payment']),
    );
  }

  static Map<String, dynamic> _toMap(dynamic value) {
    if (value is Map<String, dynamic>) {
      return value;
    }

    if (value is Map) {
      return value.map((key, item) => MapEntry(key.toString(), item));
    }

    return <String, dynamic>{};
  }
}
