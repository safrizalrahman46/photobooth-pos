// lib/presentation/providers/addon_provider.dart

import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../application/addon/addon_repository_impl.dart';
import '../../application/addon/get_addon_insight_use_case.dart';
import '../../application/addon/get_addon_list_use_case.dart';
import '../../domain/entities/add_on.dart';
import '../../domain/entities/addon_insight.dart';

final addOnRepositoryProvider = Provider((_) => AddOnRepositoryImpl());

final addOnListProvider = FutureProvider<List<AddOn>>((ref) {
  final repo = ref.read(addOnRepositoryProvider);
  return GetAddOnListUseCase(repo).execute();
});

final addonInsightProvider = FutureProvider<AddonInsight>((ref) {
  final repo = ref.read(addOnRepositoryProvider);
  return GetAddonInsightUseCase(repo).execute();
});

// Filter state: 'ALL', 'STOCK', 'AVAILABLE'
final addonFilterProvider = StateProvider<String>((_) => 'ALL');

final filteredAddOnProvider = Provider<AsyncValue<List<AddOn>>>((ref) {
  final filter = ref.watch(addonFilterProvider);
  final listAsync = ref.watch(addOnListProvider);

  return listAsync.whenData((list) {
    if (filter == 'ALL') return list;
    if (filter == 'STOCK') {
      return list
          .where((a) => a.statusType == AddOnStatusType.stockLevel)
          .toList();
    }
    return list
        .where((a) => a.statusType == AddOnStatusType.available)
        .toList();
  });
});
