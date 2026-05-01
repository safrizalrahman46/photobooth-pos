class AddOnCatalogItem {
  const AddOnCatalogItem({
    required this.id,
    required this.packageId,
    required this.code,
    required this.name,
    required this.description,
    required this.price,
    required this.maxQty,
    required this.isPhysical,
    required this.isActive,
    required this.effectiveAvailableStock,
    required this.effectiveStockStatus,
    required this.effectiveStockLabel,
  });

  final int id;
  final int? packageId;
  final String code;
  final String name;
  final String description;
  final double price;
  final int maxQty;
  final bool isPhysical;
  final bool isActive;
  final int? effectiveAvailableStock;
  final String effectiveStockStatus;
  final String effectiveStockLabel;

  factory AddOnCatalogItem.fromJson(Map<String, dynamic> json) {
    return AddOnCatalogItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      packageId: (json['package_id'] as num?)?.toInt(),
      code: json['code']?.toString() ?? '',
      name: json['name']?.toString() ?? '-',
      description: json['description']?.toString() ?? '',
      price: (json['price'] as num?)?.toDouble() ?? 0,
      maxQty: (json['max_qty'] as num?)?.toInt() ?? 1,
      isPhysical: json['is_physical'] == true,
      isActive: json['is_active'] == true,
      effectiveAvailableStock:
          (json['effective_available_stock'] as num?)?.toInt(),
      effectiveStockStatus:
          json['effective_stock_status']?.toString() ?? 'untracked',
      effectiveStockLabel:
          json['effective_stock_label']?.toString() ?? 'Not mapped',
    );
  }
}
