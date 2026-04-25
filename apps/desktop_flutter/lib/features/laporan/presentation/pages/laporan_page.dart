import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../app/theme/app_colors.dart';
import '../../../../app/theme/app_text_styles.dart';
import '../../application/laporan_controller.dart';
import '../../widgets/summary/summary_card.dart';
import '../../widgets/summary/highlight_card.dart';
import '../../widgets/cashflow/cashflow_item.dart';
import '../../widgets/cashflow/balance_card.dart';
import '../../data/laporan_repository_impl.dart';

class LaporanPage extends StatelessWidget {
  const LaporanPage({super.key});

  @override
  Widget build(BuildContext context) {
    final controller = Get.put(
      LaporanController(repository: LaporanRepositoryImpl()),
    );

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Row(
        children: [
          /// ─── MAIN CONTENT
          Expanded(
            child: Column(
              children: [
                /// Header (REUSE)
                // const AppHeader(title: 'Laporan'),
                Expanded(
                  child: Obx(() {
                    if (controller.isLoading.value) {
                      return const Center(child: CircularProgressIndicator());
                    }

                    if (controller.errorMessage.isNotEmpty) {
                      return Center(child: Text(controller.errorMessage.value));
                    }

                    final summary = controller.summary.value!;
                    final cashflow = controller.cashflow.value!;

                    return SingleChildScrollView(
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          /// TITLE
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text('Laporan', style: AppTextStyles.h2),
                              Text(
                                'Update ${controller.lastUpdated.value}',
                                style: AppTextStyles.bodySmall,
                              ),
                            ],
                          ),

                          const SizedBox(height: 20),

                          /// ───────── SUMMARY ─────────
                          Row(
                            children: [
                              Expanded(
                                child: SummaryCard(
                                  icon: Icons.attach_money,
                                  label: "Total Pendapatan",
                                  value: SummaryCard.formatRupiah(
                                    summary.totalPendapatan,
                                  ),
                                  percentageChange: summary.percentageChange,
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: SummaryCard(
                                  icon: Icons.receipt_long,
                                  label: "Jumlah Booking",
                                  value: summary.jumlahBooking.toString(),
                                  percentageChange: summary.percentageChange,
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: HighlightCard(
                                  label: "Paket Terlaris",
                                  title: summary.paketTerlaris,
                                  subtitle:
                                      "${summary.pesananTerkonfirmasi} Pesanan",
                                ),
                              ),
                            ],
                          ),

                          const SizedBox(height: 20),

                          /// ───────── CASHFLOW ─────────
                          Row(
                            children: [
                              Expanded(
                                child: CashflowItem(
                                  label: "Kas Masuk",
                                  amount: cashflow.kasMasuk.toDouble(),
                                  lineColor: Colors.green, // ✅ warna positif
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: CashflowItem(
                                  label: "Kas Keluar",
                                  amount: cashflow.kasKeluar.toDouble(),
                                  lineColor: Colors.red, // ✅ warna negatif
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: BalanceCard(
                                  saldo: cashflow.saldo,
                                  status: cashflow.saldo >= 0
                                      ? "Surplus"
                                      : "Defisit",
                                ),
                              ),
                            ],
                          ),

                          const SizedBox(height: 20),

                          /// ───────── PAYMENT METHOD (SIMPLE LIST) ─────────
                          Container(
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: AppColors.surface,
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  "Metode Pembayaran",
                                  style: AppTextStyles.h3,
                                ),
                                const SizedBox(height: 12),

                                ...controller.paymentMethods.map((e) {
                                  return Padding(
                                    padding: const EdgeInsets.only(bottom: 8),
                                    child: Row(
                                      mainAxisAlignment:
                                          MainAxisAlignment.spaceBetween,
                                      children: [
                                        Text(e.name),
                                        Text("${e.percentage}%"),
                                      ],
                                    ),
                                  );
                                }),
                              ],
                            ),
                          ),
                        ],
                      ),
                    );
                  }),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
