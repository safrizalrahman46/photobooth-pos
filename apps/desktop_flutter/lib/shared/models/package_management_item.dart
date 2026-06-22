class PackageManagementItem {
  const PackageManagementItem({
    required this.id,
    required this.branchId,
    required this.code,
    required this.name,
    required this.description,
    required this.durationMinutes,
    required this.basePrice,
    required this.sortOrder,
    required this.isActive,
    this.samplePhotos,
  });

  final int id;
  final int? branchId;
  final String code;
  final String name;
  final String description;
  final int durationMinutes;
  final double basePrice;
  final int sortOrder;
  final bool isActive;
  final List<String>? samplePhotos;

  factory PackageManagementItem.fromJson(Map<String, dynamic> json) {
    final rawPhotos = json['sample_photos'];
    final photos = rawPhotos is List
        ? rawPhotos.map((e) => e?.toString() ?? '').where((s) => s.isNotEmpty).toList()
        : null;

    return PackageManagementItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      branchId: (json['branch_id'] as num?)?.toInt(),
      code: json['code']?.toString() ?? '-',
      name: json['name']?.toString() ?? '-',
      description: json['description']?.toString() ?? '',
      durationMinutes: (json['duration_minutes'] as num?)?.toInt() ?? 0,
      basePrice: (json['base_price'] as num?)?.toDouble() ?? 0,
      sortOrder: (json['sort_order'] as num?)?.toInt() ?? 0,
      isActive: json['is_active'] == true,
      samplePhotos: photos,
    );
  }
}
