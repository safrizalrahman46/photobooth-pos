import 'application/bloc/antrian_bloc.dart';
import 'application/usecase/antrian_usecase.dart';
import 'domain/repositories/antrian_repository.dart';
import 'domain/entities/antrian_entity.dart';
import 'domain/entities/booth_entity.dart';

/// 🔥 Dummy repo dengan data Mockup (Handoko Saputra, dsb)
class DummyAntrianRepository implements AntrianRepository {
  @override
  Future<List<AntrianEntity>> getAntrianMenunggu() async {
    return [
      AntrianEntity(
        id: '42',
        nomorAntrian: '#A-042',
        namaCustomer: 'Handoko Saputra',
        jumlahOrang: 6,
        paket: 'Cinematic 4R',
        booth: '#02 (Tersedia)',
        status: StatusAntrian.menunggu,
        waktuDaftar: DateTime.now().subtract(const Duration(minutes: 10)),
      ),
      AntrianEntity(
        id: '43',
        nomorAntrian: '#A-043',
        namaCustomer: 'Riana Wijaya',
        jumlahOrang: 2,
        paket: 'Classic Strip',
        booth: '-',
        status: StatusAntrian.menunggu,
        waktuDaftar: DateTime.now().subtract(const Duration(minutes: 15)),
      ),
      AntrianEntity(
        id: '44',
        nomorAntrian: '#A-044',
        namaCustomer: 'Daffa Pratama',
        jumlahOrang: 4,
        paket: 'Duo Package',
        booth: '-',
        status: StatusAntrian.menunggu,
        waktuDaftar: DateTime.now().subtract(const Duration(minutes: 22)),
      ),
    ];
  }

  @override
  Future<List<AntrianEntity>> getAntrianSelesai() async {
    return [
      AntrianEntity(
        id: '39',
        nomorAntrian: '#A-039',
        namaCustomer: 'Maya Angelia',
        jumlahOrang: 1,
        paket: 'Cetak 4R',
        booth: '#01',
        status: StatusAntrian.selesai,
        waktuDaftar: DateTime.now().subtract(const Duration(hours: 1)),
        catatanSelesai: 'Selesai: 14:05 • Cetak Berhasil',
      ),
      AntrianEntity(
        id: '38',
        nomorAntrian: '#A-038',
        namaCustomer: 'Zaki Alamsyah',
        jumlahOrang: 1,
        paket: 'Digital Only',
        booth: '#01',
        status: StatusAntrian.selesai,
        waktuDaftar: DateTime.now().subtract(const Duration(hours: 2)),
        catatanSelesai: 'Selesai: 13:48 • Email Terkirim',
      ),
      AntrianEntity(
        id: '37',
        nomorAntrian: '#A-037',
        namaCustomer: 'Siska & Doni',
        jumlahOrang: 2,
        paket: 'Duo Package',
        booth: '#02',
        status: StatusAntrian.selesai,
        waktuDaftar: DateTime.now().subtract(const Duration(hours: 2, minutes: 30)),
        catatanSelesai: 'Selesai: 13:30 • Cetak Berhasil',
      ),
    ];
  }

  @override
  Future<AntrianEntity?> getAntrianBerikutnya() async {
    return (await getAntrianMenunggu()).first;
  }

  @override
  Future<List<BoothEntity>> getAllBooth() async {
    return [
      const BoothEntity(
        id: 'b1',
        namaBooth: 'Booth #01',
        status: StatusBooth.aktif,
        antrianAktif: AntrianSedangFotoInfo(
          nomorAntrian: '#A-040',
          namaCustomer: 'Kel. Budiman',
          jumlahOrang: 4,
          sisaWaktuDetik: 252, // 04:12 like mockup
        ),
      ),
      const BoothEntity(
        id: 'b2',
        namaBooth: 'Booth #02',
        status: StatusBooth.tersedia,
      ),
    ];
  }

  @override
  Future<void> pindahKeSelesai(String id, String catatan) async {}

  @override
  Future<void> setBoothReady(String id) async {}

  @override
  Stream<List<AntrianEntity>> watchAntrianMenunggu() async* {
    yield await getAntrianMenunggu();
  }

  @override
  Stream<List<BoothEntity>> watchBooth() async* {
    yield await getAllBooth();
  }
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
      setBoothReady: SetBoothReadyUseCase(repo),
    );
  }
}
