import '../domain/entities/laporan_summary.dart';
import '../domain/entities/cashflow.dart';
import '../domain/entities/payment_method.dart';
import '../domain/repositories/laporan_repository.dart';

class LaporanRepositoryImpl implements LaporanRepository {
  @override
  Future<LaporanSummary> getLaporanSummary() async {
    await Future.delayed(const Duration(milliseconds: 500));

    return LaporanSummary(
      totalPendapatan: 14250000,
      percentageChange: 12.5,
      jumlahBooking: 48,
      paketTerlaris: "Premium Cinematic",
      pesananTerkonfirmasi: 22,
    );
  }

  @override
  Future<Cashflow> getCashflow() async {
    await Future.delayed(const Duration(milliseconds: 500));

    return Cashflow(kasMasuk: 15800000, kasKeluar: 1550000);
  }

  @override
  Future<List<PaymentMethod>> getPaymentMethods() async {
    await Future.delayed(const Duration(milliseconds: 500));

    return [
      PaymentMethod(name: "QRIS", percentage: 75),
      PaymentMethod(name: "Cash", percentage: 25),
    ];
  }
}
