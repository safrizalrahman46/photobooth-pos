// lib/application/paket/get_paket_list_use_case.dart

import '../../domain/entities/paket_foto.dart';
import '../../domain/repositories/paket_repository.dart';

class GetPaketListUseCase {
  final PaketRepository repository;

  GetPaketListUseCase(this.repository);

  Future<List<PaketFoto>> execute() async {
    return repository.getPaketList();
  }
}
