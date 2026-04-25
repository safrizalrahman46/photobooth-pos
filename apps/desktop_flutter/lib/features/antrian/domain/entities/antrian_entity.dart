// domain/entities/antrian_entity.dart

enum StatusAntrian { menunggu, sedangFoto, selesai }

class AntrianEntity {
  final String id;
  final String nomorAntrian;
  final String namaCustomer;
  final int jumlahOrang;
  final String paket;
  final String booth;
  final StatusAntrian status;
  final DateTime waktuDaftar;
  final String? catatanSelesai;

  const AntrianEntity({
    required this.id,
    required this.nomorAntrian,
    required this.namaCustomer,
    required this.jumlahOrang,
    required this.paket,
    required this.booth,
    required this.status,
    required this.waktuDaftar,
    this.catatanSelesai,
  });

  String get menitLalu {
    final diff = DateTime.now().difference(waktuDaftar).inMinutes;
    return '$diff Menit lalu';
  }

  AntrianEntity copyWith({
    String? id,
    String? nomorAntrian,
    String? namaCustomer,
    int? jumlahOrang,
    String? paket,
    String? booth,
    StatusAntrian? status,
    DateTime? waktuDaftar,
    String? catatanSelesai,
  }) {
    return AntrianEntity(
      id: id ?? this.id,
      nomorAntrian: nomorAntrian ?? this.nomorAntrian,
      namaCustomer: namaCustomer ?? this.namaCustomer,
      jumlahOrang: jumlahOrang ?? this.jumlahOrang,
      paket: paket ?? this.paket,
      booth: booth ?? this.booth,
      status: status ?? this.status,
      waktuDaftar: waktuDaftar ?? this.waktuDaftar,
      catatanSelesai: catatanSelesai ?? this.catatanSelesai,
    );
  }
}
