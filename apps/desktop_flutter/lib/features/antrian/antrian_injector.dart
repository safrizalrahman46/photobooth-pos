import 'package:desktop_flutter/core/session/api_session.dart';
import 'package:desktop_flutter/shared/models/queue_ticket_item.dart';

import 'application/bloc/antrian_bloc.dart';
import 'application/usecase/antrian_usecase.dart';
import 'domain/repositories/antrian_repository.dart';
import 'domain/entities/antrian_entity.dart';
import 'domain/entities/booth_entity.dart';

class DummyAntrianRepository implements AntrianRepository {
  @override
  Future<List<AntrianEntity>> getAntrianMenunggu() async {
    final tickets = await _fetchTickets(status: 'waiting');
    return tickets.map(_mapTicket).toList();
  }

  @override
  Future<List<AntrianEntity>> getAntrianSelesai() async {
    final tickets = await _fetchTickets(status: 'finished');
    return tickets.map(_mapTicket).toList();
  }

  @override
  Future<AntrianEntity?> getAntrianBerikutnya() async {
    final waiting = await getAntrianMenunggu();
    return waiting.isEmpty ? null : waiting.first;
  }

  @override
  Future<List<BoothEntity>> getAllBooth() async {
    return [
      const BoothEntity(
        id: 'b1',
        namaBooth: 'Booth #01',
        status: StatusBooth.aktif,
        antrianAktif: AntrianSedangFotoInfo(
          nomorAntrian: '-',
          namaCustomer: '-',
          jumlahOrang: 0,
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
  Future<void> pindahKeSelesai(String id, String catatan) async {
    final client = ApiSession.client;

    if (client == null) {
      return;
    }

    await client.transitionQueueTicket(ticketId: int.parse(id), status: 'finished');
  }

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

  Future<List<QueueTicketItem>> _fetchTickets({required String status}) async {
    final client = ApiSession.client;

    if (client == null) {
      return <QueueTicketItem>[];
    }

    return client.fetchQueueTickets(status: status, perPage: 100);
  }

  AntrianEntity _mapTicket(QueueTicketItem ticket) {
    return AntrianEntity(
      id: ticket.id.toString(),
      nomorAntrian: ticket.queueCode,
      namaCustomer: ticket.customerName,
      jumlahOrang: 0,
      paket: ticket.sourceType == 'booking' ? 'Booking' : 'Walk-in',
      booth: '-',
      status: _mapStatus(ticket.status),
      waktuDaftar: DateTime.tryParse(ticket.queueDate) ?? DateTime.now(),
      catatanSelesai: ticket.status == 'finished' ? 'Selesai' : null,
    );
  }

  StatusAntrian _mapStatus(String status) {
    if (status == 'finished') {
      return StatusAntrian.selesai;
    }

    if (status == 'in_session') {
      return StatusAntrian.sedangFoto;
    }

    return StatusAntrian.menunggu;
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
