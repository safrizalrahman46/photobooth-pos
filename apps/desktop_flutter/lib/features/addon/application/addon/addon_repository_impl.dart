import 'package:desktop_flutter/core/session/api_session.dart';
import '../../domain/entities/add_on.dart';
import '../../domain/entities/addon_insight.dart';
import '../../domain/repositories/addon_repository.dart';

class AddOnRepositoryImpl implements AddOnRepository {
  @override
  Future<List<AddOn>> getAddOnList() async {
    final client = ApiSession.client;

    if (client == null) {
      return <AddOn>[];
    }

    final rows = await client.fetchAddOns();

    return rows
        .map(
          (item) => AddOn(
            id: item.id.toString(),
            nama: item.name,
            deskripsi: item.description,
            harga: item.price.round(),
            iconType: _iconType(item.name),
            statusType: item.effectiveAvailableStock == null
                ? AddOnStatusType.available
                : AddOnStatusType.stockLevel,
            sisaStok: item.effectiveAvailableStock,
            isHighlightedLeft:
                item.effectiveStockStatus == 'low' ||
                item.effectiveStockStatus == 'out',
          ),
        )
        .toList();
  }

  @override
  Future<AddonInsight> getInsight() async {
    final addOns = await getAddOnList();
    final lowStock = addOns.where((item) => item.sisaStok != null).toList()
      ..sort((a, b) => (a.sisaStok ?? 0).compareTo(b.sisaStok ?? 0));

    return AddonInsight(
      mostPopularNama: addOns.isNotEmpty ? addOns.first.nama : '-',
      revenueToday: 0,
      revenueChangePercent: 0,
      lowStockNama: lowStock.isNotEmpty ? lowStock.first.nama : '-',
      lowStockSisa: lowStock.isNotEmpty ? lowStock.first.sisaStok ?? 0 : 0,
      storageWarehouseLabel: 'Inventory',
      storageCapacityPercent: 0,
    );
  }

  String _iconType(String name) {
    final normalized = name.toLowerCase();

    if (normalized.contains('cetak') || normalized.contains('print')) {
      return 'print';
    }

    if (normalized.contains('frame')) {
      return 'frame';
    }

    if (normalized.contains('orang') || normalized.contains('person')) {
      return 'person';
    }

    if (normalized.contains('kostum') || normalized.contains('costume')) {
      return 'costume';
    }

    return 'print';
  }
}
