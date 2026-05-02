import 'package:desktop_flutter/core/session/api_session.dart';
import 'package:desktop_flutter/shared/models/report_summary.dart';

import '../domain/entities/laporan_summary.dart';
import '../domain/entities/cashflow.dart';
import '../domain/entities/payment_method.dart';
import '../domain/repositories/laporan_repository.dart';

class LaporanRepositoryImpl implements LaporanRepository {
  Future<ReportSummary> _summary() async {
    final client = ApiSession.client;

    if (client == null) {
      throw Exception('Sesi API belum tersedia.');
    }

    final now = DateTime.now();
    final from = DateTime(now.year, now.month, 1);
    final to = DateTime(now.year, now.month + 1, 0);

    return client.fetchReportSummary(from: from, to: to);
  }

  @override
  Future<LaporanSummary> getLaporanSummary() async {
    final summary = await _summary();

    return LaporanSummary(
      totalPendapatan: summary.combinedPaidSales,
      percentageChange: 0,
      jumlahBooking: summary.totalBookings,
      paketTerlaris: '-',
      pesananTerkonfirmasi: summary.doneBookings,
    );
  }

  @override
  Future<Cashflow> getCashflow() async {
    final summary = await _summary();

    return Cashflow(kasMasuk: summary.combinedPaidSales, kasKeluar: 0);
  }

  @override
  Future<List<PaymentMethod>> getPaymentMethods() async {
    return const <PaymentMethod>[];
  }
}
