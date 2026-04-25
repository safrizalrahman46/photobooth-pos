// domain/entities/booth_entity.dart

enum StatusBooth { aktif, tersedia, maintenance }

class BoothEntity {
  final String id;
  final String namaBooth;
  final StatusBooth status;
  final AntrianSedangFotoInfo? antrianAktif;

  const BoothEntity({
    required this.id,
    required this.namaBooth,
    required this.status,
    this.antrianAktif,
  });

  String get statusLabel {
    switch (status) {
      case StatusBooth.aktif:
        return 'Aktif';
      case StatusBooth.tersedia:
        return 'Tersedia (Siap Digunakan)';
      case StatusBooth.maintenance:
        return 'Maintenance';
    }
  }

  BoothEntity copyWith({
    String? id,
    String? namaBooth,
    StatusBooth? status,
    AntrianSedangFotoInfo? antrianAktif,
  }) {
    return BoothEntity(
      id: id ?? this.id,
      namaBooth: namaBooth ?? this.namaBooth,
      status: status ?? this.status,
      antrianAktif: antrianAktif ?? this.antrianAktif,
    );
  }
}

class AntrianSedangFotoInfo {
  final String nomorAntrian;
  final String namaCustomer;
  final int jumlahOrang;
  final int sisaWaktuDetik;

  const AntrianSedangFotoInfo({
    required this.nomorAntrian,
    required this.namaCustomer,
    required this.jumlahOrang,
    required this.sisaWaktuDetik,
  });

  String get sisaWaktuFormatted {
    final menit = sisaWaktuDetik ~/ 60;
    final detik = sisaWaktuDetik % 60;
    return '${menit.toString().padLeft(2, '0')}:${detik.toString().padLeft(2, '0')}';
  }
}
