// application/usecase/antrian_usecase.dart

import '../../domain/entities/antrian_entity.dart';
import '../../domain/entities/booth_entity.dart';
import '../../domain/repositories/antrian_repository.dart';

class GetAntrianMenungguUseCase {
  final AntrianRepository repository;
  GetAntrianMenungguUseCase(this.repository);

  Future<List<AntrianEntity>> call() => repository.getAntrianMenunggu();
  Stream<List<AntrianEntity>> watch() => repository.watchAntrianMenunggu();
}

class GetAntrianSelesaiUseCase {
  final AntrianRepository repository;
  GetAntrianSelesaiUseCase(this.repository);

  Future<List<AntrianEntity>> call() => repository.getAntrianSelesai();
}

class GetAntrianBerikutnyaUseCase {
  final AntrianRepository repository;
  GetAntrianBerikutnyaUseCase(this.repository);

  Future<AntrianEntity?> call() => repository.getAntrianBerikutnya();
}

class GetAllBoothUseCase {
  final AntrianRepository repository;
  GetAllBoothUseCase(this.repository);

  Future<List<BoothEntity>> call() => repository.getAllBooth();
  Stream<List<BoothEntity>> watch() => repository.watchBooth();
}

class PindahKeSelesaiUseCase {
  final AntrianRepository repository;
  PindahKeSelesaiUseCase(this.repository);

  Future<void> call(String antrianId, String catatan) =>
      repository.pindahKeSelesai(antrianId, catatan);
}

class SetBoothReadyUseCase {
  final AntrianRepository repository;
  SetBoothReadyUseCase(this.repository);

  Future<void> call(String boothId) => repository.setBoothReady(boothId);
}
