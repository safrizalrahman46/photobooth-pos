import 'application/bloc/antrian_bloc.dart';
import 'application/usecase/antrian_usecase.dart';
import 'domain/repositories/antrian_repository.dart';
import 'domain/entities/antrian_entity.dart';
import 'domain/entities/booth_entity.dart';

/// 🔥 Dummy repo sementara
class DummyAntrianRepository implements AntrianRepository {
  @override
  Future<List<AntrianEntity>> getAntrianMenunggu() async => [];

  @override
  Future<List<AntrianEntity>> getAntrianSelesai() async => [];

  @override
  Future<AntrianEntity?> getAntrianBerikutnya() async => null;

  @override
  Future<List<BoothEntity>> getAllBooth() async => [];

  @override
  Future<void> pindahKeSelesai(String id, String catatan) async {}

  @override
  Future<void> setBoothReady(String id) async {}

  @override
  Stream<List<AntrianEntity>> watchAntrianMenunggu() => const Stream.empty();

  @override
  Stream<List<BoothEntity>> watchBooth() => const Stream.empty();
}

/// 🔥 Injector
class AntrianInjector {
  static AntrianBloc create() {
    final repo = DummyAntrianRepository();

    return AntrianBloc(
      getAntrianMenunggu: GetAntrianMenungguUseCase(repo),
      getAntrianSelesai: GetAntrianSelesaiUseCase(repo),
      getAntrianBerikutnya: GetAntrianBerikutnyaUseCase(repo),
      getAllBooth: GetAllBoothUseCase(repo),
      pindahKeSelesai: PindahKeSelesaiUseCase(repo),
      setBoothReady: SetBoothReadyUseCase(repo), // DIANCOK
    );
  }
}
