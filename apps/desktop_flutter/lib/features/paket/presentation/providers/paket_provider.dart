// lib/presentation/providers/paket_provider.dart

import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../application/paket/get_paket_list_use_case.dart';
import '../../application/paket/paket_repository_impl.dart';
import '../../domain/entities/paket_foto.dart';

final paketRepositoryProvider = Provider((_) => PaketRepositoryImpl());

final paketUseCaseProvider = Provider((ref) {
  final repo = ref.read(paketRepositoryProvider);
  return GetPaketListUseCase(repo);
});

final paketListProvider = FutureProvider<List<PaketFoto>>((ref) async {
  final useCase = ref.read(paketUseCaseProvider);
  return useCase.execute();
});

final searchQueryProvider = StateProvider<String>((_) => '');

final filteredPaketProvider = Provider<AsyncValue<List<PaketFoto>>>((ref) {
  final query = ref.watch(searchQueryProvider).toLowerCase();
  final paketAsync = ref.watch(paketListProvider);

  return paketAsync.whenData((list) {
    if (query.isEmpty) return list;
    return list
        .where((p) =>
            p.nama.toLowerCase().contains(query) ||
            p.deskripsi.toLowerCase().contains(query))
        .toList();
  });
});
