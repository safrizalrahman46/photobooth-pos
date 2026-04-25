import '../entities/laporan_summary.dart';
import '../entities/cashflow.dart';
import '../entities/payment_method.dart';

abstract class LaporanRepository {
  Future<LaporanSummary> getLaporanSummary();
  Future<Cashflow> getCashflow();
  Future<List<PaymentMethod>> getPaymentMethods();
}
