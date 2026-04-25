// lib/domain/entities/addon_insight.dart

class AddonInsight {
  final String mostPopularNama;
  final int revenueToday;
  final double revenueChangePercent;
  final String lowStockNama;
  final int lowStockSisa;
  final String storageWarehouseLabel;
  final int storageCapacityPercent;

  const AddonInsight({
    required this.mostPopularNama,
    required this.revenueToday,
    required this.revenueChangePercent,
    required this.lowStockNama,
    required this.lowStockSisa,
    required this.storageWarehouseLabel,
    required this.storageCapacityPercent,
  });
}
