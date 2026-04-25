// lib/application/paket/paket_repository_impl.dart

import '../../domain/entities/paket_foto.dart';
import '../../domain/repositories/paket_repository.dart';

class PaketRepositoryImpl implements PaketRepository {
  static const List<PaketFoto> _data = [
    PaketFoto(
      id: '1',
      nama: 'Paket Basic',
      deskripsi: 'Sesi minimalis untuk ekspresi maksimal individu atau pasangan.',
      harga: 35000,
      durasiMenit: 15,
      maksOrang: 2,
      iconType: 'camera',
      isLocked: true,
      isHighlighted: false,
    ),
    PaketFoto(
      id: '2',
      nama: 'Paket Mandi Bola',
      deskripsi: 'Area tematik, ceria dengan ribuan bola penuh warna.',
      harga: 45000,
      durasiMenit: 20,
      maksOrang: 3,
      iconType: 'party',
      isLocked: true,
      isHighlighted: true,
    ),
    PaketFoto(
      id: '3',
      nama: 'Paket Minimarket',
      deskripsi: 'Tema ikonik yang estetik dengan berbagai properti belanja.',
      harga: 50000,
      durasiMenit: 30,
      maksOrang: 3,
      iconType: 'store',
      isLocked: true,
      isHighlighted: false,
    ),
  ];

  @override
  Future<List<PaketFoto>> getPaketList() async {
    await Future.delayed(const Duration(milliseconds: 300));
    return _data;
  }

  @override
  Future<PaketFoto?> getPaketById(String id) async {
    await Future.delayed(const Duration(milliseconds: 100));
    try {
      return _data.firstWhere((p) => p.id == id);
    } catch (_) {
      return null;
    }
  }
}
