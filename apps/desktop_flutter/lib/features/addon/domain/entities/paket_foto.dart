// lib/domain/entities/paket_foto.dart

class PaketFoto {
  final String id;
  final String nama;
  final String deskripsi;
  final int harga;
  final int durasiMenit;
  final int maksOrang;
  final String iconType; // 'camera', 'party', 'store'
  final bool isLocked;
  final bool isHighlighted;

  const PaketFoto({
    required this.id,
    required this.nama,
    required this.deskripsi,
    required this.harga,
    required this.durasiMenit,
    required this.maksOrang,
    required this.iconType,
    this.isLocked = false,
    this.isHighlighted = false,
  });
}
