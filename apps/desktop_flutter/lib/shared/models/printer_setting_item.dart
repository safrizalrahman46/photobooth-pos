class PrinterSettingItem {
  const PrinterSettingItem({
    required this.id,
    required this.branchId,
    required this.deviceName,
    required this.printerType,
    required this.paperWidthMm,
    required this.isDefault,
    required this.isActive,
    required this.connection,
  });

  final int id;
  final int branchId;
  final String deviceName;
  final String printerType;
  final int paperWidthMm;
  final bool isDefault;
  final bool isActive;
  final Map<String, dynamic> connection;

  factory PrinterSettingItem.fromJson(Map<String, dynamic> json) {
    final connectionRaw = json['connection'];

    return PrinterSettingItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      branchId: (json['branch_id'] as num?)?.toInt() ?? 0,
      deviceName: json['device_name']?.toString() ?? '-',
      printerType: json['printer_type']?.toString() ?? 'thermal',
      paperWidthMm: (json['paper_width_mm'] as num?)?.toInt() ?? 80,
      isDefault: json['is_default'] == true,
      isActive: json['is_active'] == true,
      connection: connectionRaw is Map<String, dynamic>
          ? connectionRaw
          : <String, dynamic>{},
    );
  }
}
