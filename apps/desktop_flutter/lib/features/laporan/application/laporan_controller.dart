import 'package:get/get.dart';
import '../domain/entities/laporan_summary.dart';
import '../domain/entities/cashflow.dart';
import '../domain/entities/payment_method.dart';
import '../domain/repositories/laporan_repository.dart';

enum LaporanPeriod { hari, minggu, bulan, tahun }

class LaporanController extends GetxController {
  final LaporanRepository repository;

  LaporanController({required this.repository});

  final Rx<LaporanSummary?> summary = Rx<LaporanSummary?>(null);
  final Rx<Cashflow?> cashflow = Rx<Cashflow?>(null);
  final RxList<PaymentMethod> paymentMethods = <PaymentMethod>[].obs;
  final RxBool isLoading = true.obs;
  final RxString errorMessage = ''.obs;
  final RxString lastUpdated = ''.obs;
  final Rx<LaporanPeriod> selectedPeriod = LaporanPeriod.bulan.obs;
  final Rx<DateTime> selectedDate = DateTime.now().obs;

  @override
  void onInit() {
    super.onInit();
    fetchLaporan();
  }

  Future<void> fetchLaporan() async {
    try {
      isLoading.value = true;
      errorMessage.value = '';

      final results = await Future.wait([
        repository.getLaporanSummary(),
        repository.getCashflow(),
        repository.getPaymentMethods(),
      ]);

      summary.value = results[0] as LaporanSummary;
      cashflow.value = results[1] as Cashflow;
      paymentMethods.value = results[2] as List<PaymentMethod>;

      _updateLastUpdated();
    } catch (e) {
      errorMessage.value = e.toString();
    } finally {
      isLoading.value = false;
    }
  }

  void setPeriod(LaporanPeriod period) {
    selectedPeriod.value = period;
    fetchLaporan(); // Reload data based on period
  }

  void setDate(DateTime date) {
    selectedDate.value = date;
    fetchLaporan();
  }

  void _updateLastUpdated() {
    final now = DateTime.now();
    lastUpdated.value =
        '${now.hour.toString().padLeft(2, '0')}:${now.minute.toString().padLeft(2, '0')}';
  }
}
