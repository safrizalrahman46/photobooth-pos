class TimeSlotManagementItem {
  const TimeSlotManagementItem({
    required this.id,
    required this.branchId,
    required this.branchName,
    required this.slotDate,
    required this.startTime,
    required this.endTime,
    required this.capacity,
    required this.isBookable,
  });

  final int id;
  final int branchId;
  final String branchName;
  final String slotDate;
  final String startTime;
  final String endTime;
  final int capacity;
  final bool isBookable;

  factory TimeSlotManagementItem.fromJson(Map<String, dynamic> json) {
    final branch = json['branch'];

    return TimeSlotManagementItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      branchId: (json['branch_id'] as num?)?.toInt() ?? 0,
      branchName: branch is Map<String, dynamic>
          ? branch['name']?.toString() ?? '-'
          : '-',
      slotDate: json['slot_date']?.toString() ?? '-',
      startTime: _shortTime(json['start_time']?.toString() ?? '-'),
      endTime: _shortTime(json['end_time']?.toString() ?? '-'),
      capacity: (json['capacity'] as num?)?.toInt() ?? 1,
      isBookable: json['is_bookable'] == true,
    );
  }

  static String _shortTime(String value) {
    if (value.length >= 5) {
      return value.substring(0, 5);
    }

    return value;
  }
}
