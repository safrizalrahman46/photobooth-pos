// domain/repositories/antrian_repository.dart

import '../entities/antrian_entity.dart';
import '../entities/booth_entity.dart';

abstract class AntrianRepository {
  Future<List<AntrianEntity>> getAntrianMenunggu();
  Future<List<AntrianEntity>> getAntrianSelesai();
  Future<AntrianEntity?> getAntrianBerikutnya();
  Future<List<BoothEntity>> getAllBooth();
  Future<void> pindahKeSelesai(String antrianId, String catatan);
  Future<void> setBoothReady(String boothId);
  Stream<List<AntrianEntity>> watchAntrianMenunggu();
  Stream<List<BoothEntity>> watchBooth();
}
