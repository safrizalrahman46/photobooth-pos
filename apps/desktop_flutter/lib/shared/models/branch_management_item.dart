class BranchManagementItem {
  const BranchManagementItem({
    required this.id,
    required this.code,
    required this.name,
    required this.timezone,
    required this.phone,
    required this.address,
    required this.isActive,
  });

  final int id;
  final String code;
  final String name;
  final String timezone;
  final String phone;
  final String address;
  final bool isActive;

  factory BranchManagementItem.fromJson(Map<String, dynamic> json) {
    return BranchManagementItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      code: json['code']?.toString() ?? '-',
      name: json['name']?.toString() ?? '-',
      timezone: json['timezone']?.toString() ?? 'Asia/Jakarta',
      phone: json['phone']?.toString() ?? '',
      address: json['address']?.toString() ?? '',
      isActive: json['is_active'] == true,
    );
  }
}
