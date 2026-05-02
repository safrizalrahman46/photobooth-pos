import 'package:desktop_flutter/core/session/api_session.dart';
import '../../domain/entities/paket_foto.dart';
import '../../domain/repositories/paket_repository.dart';

class PaketRepositoryImpl implements PaketRepository {
  @override
  Future<List<PaketFoto>> getPaketList() async {
    final client = ApiSession.client;

    if (client == null) {
      return <PaketFoto>[];
    }

    final rows = await client.fetchPackages();

    return rows
        .map(
          (item) => PaketFoto(
            id: item.id.toString(),
            nama: item.name,
            deskripsi: item.description,
            harga: item.basePrice.round(),
            durasiMenit: item.durationMinutes,
            maksOrang: 0,
            iconType: 'camera',
            isLocked: true,
            isHighlighted: item.sortOrder == 0,
          ),
        )
        .toList();
  }

  @override
  Future<PaketFoto?> getPaketById(String id) async {
    try {
      return (await getPaketList()).firstWhere((p) => p.id == id);
    } catch (_) {
      return null;
    }
  }
}
