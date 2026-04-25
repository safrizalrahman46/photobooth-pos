// lib/domain/repositories/paket_repository.dart

import '../entities/paket_foto.dart';

abstract class PaketRepository {
  Future<List<PaketFoto>> getPaketList();
  Future<PaketFoto?> getPaketById(String id);
}
