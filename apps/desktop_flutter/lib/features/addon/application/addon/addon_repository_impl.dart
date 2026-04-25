// lib/application/addon/addon_repository_impl.dart

import '../../domain/entities/add_on.dart';
import '../../domain/entities/addon_insight.dart';
import '../../domain/repositories/addon_repository.dart';

class AddOnRepositoryImpl implements AddOnRepository {
  static const List<AddOn> _addons = [
    AddOn(
      id: '1',
      nama: 'Cetak Foto 4R',
      deskripsi: 'Professional Glossy Finish',
      harga: 15000,
      iconType: 'print',
      statusType: AddOnStatusType.stockLevel,
      sisaStok: 45,
      isHighlightedLeft: true,
    ),
    AddOn(
      id: '2',
      nama: 'Frame Custom',
      deskripsi: 'Minimalist Wood / Acrylic',
      harga: 35000,
      iconType: 'frame',
      statusType: AddOnStatusType.stockLevel,
      sisaStok: 12,
      isHighlightedLeft: true,
    ),
    AddOn(
      id: '3',
      nama: 'Extra Person',
      deskripsi: 'Additional person per session',
      harga: 10000,
      iconType: 'person',
      statusType: AddOnStatusType.available,
      isHighlightedLeft: false,
    ),
    AddOn(
      id: '4',
      nama: 'Sewa Kostum',
      deskripsi: 'Premium themed outfits',
      harga: 50000,
      iconType: 'costume',
      statusType: AddOnStatusType.available,
      isHighlightedLeft: false,
    ),
  ];

  static const AddonInsight _insight = AddonInsight(
    mostPopularNama: 'Cetak Foto 4R',
    revenueToday: 1450000,
    revenueChangePercent: 12,
    lowStockNama: 'Frame Custom',
    lowStockSisa: 12,
    storageWarehouseLabel: 'Warehouse B',
    storageCapacityPercent: 85,
  );

  @override
  Future<List<AddOn>> getAddOnList() async {
    await Future.delayed(const Duration(milliseconds: 250));
    return _addons;
  }

  @override
  Future<AddonInsight> getInsight() async {
    await Future.delayed(const Duration(milliseconds: 250));
    return _insight;
  }
}
