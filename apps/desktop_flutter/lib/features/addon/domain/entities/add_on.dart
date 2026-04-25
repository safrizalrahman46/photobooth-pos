// lib/domain/entities/add_on.dart

enum AddOnStatusType { stockLevel, available }

class AddOn {
  final String id;
  final String nama;
  final String deskripsi;
  final int harga;
  final String iconType; // 'print', 'frame', 'person', 'costume'
  final AddOnStatusType statusType;
  final int? sisaStok; // null jika statusType == available
  final bool isHighlightedLeft; // garis biru di kiri card

  const AddOn({
    required this.id,
    required this.nama,
    required this.deskripsi,
    required this.harga,
    required this.iconType,
    required this.statusType,
    this.sisaStok,
    this.isHighlightedLeft = false,
  });
}
