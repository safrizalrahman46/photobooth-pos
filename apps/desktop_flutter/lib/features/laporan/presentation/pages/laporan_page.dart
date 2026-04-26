import 'package:flutter/material.dart';
import 'package:get/get.dart';

import '../../../../app/theme/app_colors.dart';
import '../../../../app/theme/app_text_styles.dart';
import '../../application/laporan_controller.dart';
import '../../widgets/summary/summary_card.dart';
import '../../widgets/cashflow/cashflow_item.dart';
import '../../widgets/cashflow/balance_card.dart';
import '../../data/laporan_repository_impl.dart';
import '../../../history/domain/entities/transaction.dart';
import '../../../history/presentation/widgets/history/history_table.dart';

class LaporanPage extends StatelessWidget {
  const LaporanPage({super.key});

  @override
  Widget build(BuildContext context) {
    final controller = Get.put(
      LaporanController(repository: LaporanRepositoryImpl()),
    );

    // Dummy data untuk tabel laporan (hanya Lunas & Batal)
    final List<Transaction> filteredTransactions = [
      Transaction(
        id: 'TRX-9402',
        waktu: DateTime(2024, 10, 24, 14, 22),
        namaPelanggan: 'Andi Pratama',
        paket: 'Mandi Bola',
        addOns: 'Cetak 4R',
        totalBayar: 90000,
        status: TransactionStatus.lunas,
      ),
      Transaction(
        id: 'TRX-9401',
        waktu: DateTime(2024, 10, 24, 13, 45),
        namaPelanggan: 'Siska Amelia',
        paket: 'Neon Splash',
        addOns: 'USB Drive',
        totalBayar: 125000,
        status: TransactionStatus.lunas,
      ),
      Transaction(
        id: 'TRX-9398',
        waktu: DateTime(2024, 10, 24, 10, 15),
        namaPelanggan: 'Dani Ramdan',
        paket: 'Selfie Party',
        addOns: null,
        totalBayar: 35000,
        status: TransactionStatus.batal,
      ),
    ];

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
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
                padding: const EdgeInsets.fromLTRB(40, 60, 40, 40), // Padding atas 60px
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    /// TITLE
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text('Laporan Keuangan', style: AppTextStyles.h2),
                        Text(
                          'Update terakhir: ${controller.lastUpdated.value}',
                          style: AppTextStyles.bodySmall,
                        ),
                      ],
                    ),

                    const SizedBox(height: 28),

                    /// ───────── SUMMARY (2 Cards) ─────────
                    Row(
                      children: [
                        Expanded(
                          child: SummaryCard(
                            icon: Icons.payments_rounded,
                            label: "Total Pendapatan",
                            value: SummaryCard.formatRupiah(
                              summary.totalPendapatan,
                            ),
                            percentageChange: summary.percentageChange,
                          ),
                        ),
                        const SizedBox(width: 20),
                        Expanded(
                          child: SummaryCard(
                            icon: Icons.receipt_long_rounded,
                            label: "Jumlah Booking",
                            value: summary.jumlahBooking.toString(),
                            percentageChange: summary.percentageChange,
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 32),

                    /// ───────── LAPORAN TRANSAKSI (Lunas & Batal) ─────────
                    Text('Rincian Transaksi Terkonfirmasi', style: AppTextStyles.h3),
                    const SizedBox(height: 16),
                    HistoryTable(
                      transactions: filteredTransactions,
                      onRowAction: (tx) => debugPrint('Action on ${tx.id}'),
                    ),
                    
                    const SizedBox(height: 24),
                    // Label info
                    Text(
                      '* Hanya menampilkan transaksi berstatus Lunas atau Batal.',
                      style: AppTextStyles.caption.copyWith(fontStyle: FontStyle.italic),
                    ),
                  ],
                ),
              );
            }),
          ),
        ],
      ),
    );
  }
}
