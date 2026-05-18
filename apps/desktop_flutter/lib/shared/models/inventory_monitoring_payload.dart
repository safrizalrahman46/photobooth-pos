class InventoryMonitoringPayload {
  const InventoryMonitoringPayload({
    required this.items,
    required this.movements,
  });

  final List<InventoryStockItem> items;
  final List<InventoryMovementItem> movements;

  factory InventoryMonitoringPayload.fromJson(Map<String, dynamic> json) {
    final rawItems = json['inventory_items'];
    final rawMovements = json['inventory_movements'];

    return InventoryMonitoringPayload(
      items: rawItems is List
          ? rawItems
                .whereType<Map<String, dynamic>>()
                .map(InventoryStockItem.fromJson)
                .toList()
          : <InventoryStockItem>[],
      movements: rawMovements is List
          ? rawMovements
                .whereType<Map<String, dynamic>>()
                .map(InventoryMovementItem.fromJson)
                .toList()
          : <InventoryMovementItem>[],
    );
  }
}

class InventoryStockItem {
  const InventoryStockItem({
    required this.id,
    required this.code,
    required this.name,
    required this.unit,
    required this.availableStock,
    required this.lowStockThreshold,
    required this.isActive,
  });

  final int id;
  final String code;
  final String name;
  final String unit;
  final int availableStock;
  final int lowStockThreshold;
  final bool isActive;

  bool get isOut => availableStock <= 0;

  bool get isLow => availableStock > 0 && availableStock <= lowStockThreshold;

  String get stockStatusLabel {
    if (isOut) {
      return 'Out of stock';
    }

    if (isLow) {
      return 'Low stock';
    }

    return 'Ready';
  }

  factory InventoryStockItem.fromJson(Map<String, dynamic> json) {
    return InventoryStockItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      code: json['code']?.toString() ?? '',
      name: json['name']?.toString() ?? '-',
      unit: json['unit']?.toString() ?? 'pcs',
      availableStock: (json['available_stock'] as num?)?.toInt() ?? 0,
      lowStockThreshold: (json['low_stock_threshold'] as num?)?.toInt() ?? 0,
      isActive: json['is_active'] == true,
    );
  }
}

class InventoryMovementItem {
  const InventoryMovementItem({
    required this.id,
    required this.inventoryItemName,
    required this.inventoryItemCode,
    required this.unit,
    required this.movementType,
    required this.qty,
    required this.stockBefore,
    required this.stockAfter,
    required this.sourceType,
    required this.sourceRef,
    required this.notes,
    required this.actorName,
    required this.createdAtText,
  });

  final int id;
  final String inventoryItemName;
  final String inventoryItemCode;
  final String unit;
  final String movementType;
  final int qty;
  final int stockBefore;
  final int stockAfter;
  final String sourceType;
  final String sourceRef;
  final String notes;
  final String actorName;
  final String createdAtText;

  bool get isIncoming => movementType.toLowerCase() == 'in';

  String get typeLabel => isIncoming ? 'Masuk' : 'Keluar';

  String get sourceLabel {
    if (sourceRef.isNotEmpty) {
      return sourceRef;
    }

    if (sourceType.isNotEmpty) {
      return sourceType.replaceAll('_', ' ');
    }

    return 'manual';
  }

  factory InventoryMovementItem.fromJson(Map<String, dynamic> json) {
    return InventoryMovementItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      inventoryItemName: json['inventory_item_name']?.toString() ?? '-',
      inventoryItemCode: json['inventory_item_code']?.toString() ?? '',
      unit: json['unit']?.toString() ?? 'pcs',
      movementType: json['movement_type']?.toString() ?? '',
      qty: (json['qty'] as num?)?.toInt() ?? 0,
      stockBefore: (json['stock_before'] as num?)?.toInt() ?? 0,
      stockAfter: (json['stock_after'] as num?)?.toInt() ?? 0,
      sourceType: json['source_type']?.toString() ?? '',
      sourceRef: json['source_ref']?.toString() ?? '',
      notes: json['notes']?.toString() ?? '',
      actorName: json['actor_name']?.toString() ?? 'System',
      createdAtText: json['created_at_text']?.toString() ?? '-',
    );
  }
}
