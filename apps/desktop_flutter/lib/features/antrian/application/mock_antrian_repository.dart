// application/mock_antrian_repository.dart
// Implementasi sementara (mock) untuk development & testing

import 'dart:async';
import '../domain/entities/antrian_entity.dart';
import '../domain/entities/booth_entity.dart';
import '../domain/repositories/antrian_repository.dart';

class MockAntrianRepository implements AntrianRepository {
  final _antrianMenungguController =
      StreamController<List<AntrianEntity>>.broadcast();
  final _boothController = StreamController<List<BoothEntity>>.broadcast();

  List<AntrianEntity> _menunggu = [
    AntrianEntity(
      id: '1',
      nomorAntrian: '#A-042',
      namaCustomer: 'Handoko Saputra',
      jumlahOrang: 6,
      paket: 'Cinematic 4R',
      booth: 'Booth #02',
      status: StatusAntrian.menunggu,
      waktuDaftar: DateTime.now().subtract(const Duration(minutes: 10)),
    ),
    AntrianEntity(
      id: '2',
      nomorAntrian: '#A-043',
      namaCustomer: 'Riana Wijaya',
      jumlahOrang: 2,
      paket: 'Classic Strip',
      booth: 'Booth #01',
      status: StatusAntrian.menunggu,
      waktuDaftar: DateTime.now().subtract(const Duration(minutes: 15)),
    ),
    AntrianEntity(
      id: '3',
      nomorAntrian: '#A-044',
      namaCustomer: 'Daffa Pratama',
      jumlahOrang: 4,
      paket: 'Duo Package',
      booth: 'Booth #01',
      status: StatusAntrian.menunggu,
      waktuDaftar: DateTime.now().subtract(const Duration(minutes: 22)),
    ),
  ];

  List<AntrianEntity> _selesai = [
    AntrianEntity(
      id: '39',
      nomorAntrian: '#39',
      namaCustomer: 'Maya Angelia',
      jumlahOrang: 2,
      paket: 'Classic Strip',
      booth: 'Booth #01',
      status: StatusAntrian.selesai,
      waktuDaftar: DateTime.now().subtract(const Duration(hours: 1)),
      catatanSelesai: 'Selesai: 14:05 • Cetak Berhasil',
    ),
    AntrianEntity(
      id: '38',
      nomorAntrian: '#38',
      namaCustomer: 'Zaki Alamsyah',
      jumlahOrang: 1,
      paket: 'Solo Package',
      booth: 'Booth #02',
      status: StatusAntrian.selesai,
      waktuDaftar: DateTime.now().subtract(const Duration(hours: 2)),
      catatanSelesai: 'Selesai: 13:48 • Email Terkirim',
    ),
    AntrianEntity(
      id: '37',
      nomorAntrian: '#37',
      namaCustomer: 'Siska & Doni',
      jumlahOrang: 2,
      paket: 'Duo Package',
      booth: 'Booth #01',
      status: StatusAntrian.selesai,
      waktuDaftar: DateTime.now().subtract(const Duration(hours: 3)),
      catatanSelesai: 'Selesai: 13:30 • Cetak Berhasil',
    ),
    AntrianEntity(
      id: '36',
      nomorAntrian: '#36',
      namaCustomer: 'Erlangga Jaya',
      jumlahOrang: 3,
      paket: 'Cinematic 4R',
      booth: 'Booth #02',
      status: StatusAntrian.selesai,
      waktuDaftar: DateTime.now().subtract(const Duration(hours: 4)),
      catatanSelesai: 'Selesai: 13:12 • Cloud Uploaded',
    ),
  ];

  List<BoothEntity> _booths = [
    BoothEntity(
      id: 'booth01',
      namaBooth: 'Booth #01',
      status: StatusBooth.aktif,
      antrianAktif: const AntrianSedangFotoInfo(
        nomorAntrian: '#A-040',
        namaCustomer: 'Kel. Budiman',
        jumlahOrang: 4,
        sisaWaktuDetik: 252, // 4:12
      ),
    ),
    BoothEntity(
      id: 'booth02',
      namaBooth: 'Booth #02',
      status: StatusBooth.tersedia,
    ),
  ];

  @override
  Future<List<AntrianEntity>> getAntrianMenunggu() async => _menunggu;

  @override
  Future<List<AntrianEntity>> getAntrianSelesai() async => _selesai;

  @override
  Future<AntrianEntity?> getAntrianBerikutnya() async =>
      _menunggu.isNotEmpty ? _menunggu.first : null;

  @override
  Future<List<BoothEntity>> getAllBooth() async => _booths;

  @override
  Future<void> pindahKeSelesai(String antrianId, String catatan) async {
    final idx = _menunggu.indexWhere((a) => a.id == antrianId);
    if (idx != -1) {
      final item = _menunggu.removeAt(idx);
      _selesai.insert(
        0,
        item.copyWith(
          status: StatusAntrian.selesai,
          catatanSelesai: catatan,
        ),
      );
      _antrianMenungguController.add(_menunggu);
    }
  }

  @override
  Future<void> setBoothReady(String boothId) async {
    final idx = _booths.indexWhere((b) => b.id == boothId);
    if (idx != -1) {
      _booths[idx] = _booths[idx].copyWith(status: StatusBooth.aktif);
      _boothController.add(_booths);
    }
  }

  @override
  Stream<List<AntrianEntity>> watchAntrianMenunggu() {
    Future.microtask(() => _antrianMenungguController.add(_menunggu));
    return _antrianMenungguController.stream;
  }

  @override
  Stream<List<BoothEntity>> watchBooth() {
    Future.microtask(() => _boothController.add(_booths));
    return _boothController.stream;
  }

  void dispose() {
    _antrianMenungguController.close();
    _boothController.close();
  }
}
