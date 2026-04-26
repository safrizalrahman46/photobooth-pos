import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:intl/intl.dart';

import '../../../../app/theme/app_colors.dart';
import '../../../../app/theme/app_text_styles.dart';
import '../../application/laporan_controller.dart';
import '../../widgets/summary/summary_card.dart';
import '../../data/laporan_repository_impl.dart';
import '../../../history/domain/entities/transaction.dart';
import '../../widgets/cashflow/cashflow_chart.dart';
import '../../widgets/common/report_table.dart';

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

              return SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(40, 60, 40, 40),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    /// TITLE & FILTER
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Laporan Keuangan', style: AppTextStyles.h2),
                            const SizedBox(height: 4),
                            Obx(() => Text(
                              'Periode: ${controller.selectedPeriod.value.name.capitalizeFirst}',
                              style: AppTextStyles.bodySmall,
                            )),
                          ],
                        ),
                        // Segmented Filter
                        Container(
                          padding: const EdgeInsets.all(4),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF1F5F9),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Row(
                            children: LaporanPeriod.values.map((period) {
                               return Obx(() {
                                 final isActive = controller.selectedPeriod.value == period;
                                 return GestureDetector(
                                   onTap: () => controller.setPeriod(period),
                                   child: Container(
                                     padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                                     decoration: BoxDecoration(
                                       color: isActive ? Colors.white : Colors.transparent,
                                       borderRadius: BorderRadius.circular(8),
                                       boxShadow: isActive ? [
                                         BoxShadow(
                                           color: Colors.black.withOpacity(0.05),
                                           blurRadius: 4,
                                           offset: const Offset(0, 2),
                                         )
                                       ] : null,
                                     ),
                                     child: Text(
                                       period.name.capitalizeFirst!,
                                       style: TextStyle(
                                         fontSize: 13,
                                         fontWeight: isActive ? FontWeight.w700 : FontWeight.w500,
                                         color: isActive ? const Color(0xFF1E293B) : const Color(0xFF64748B),
                                       ),
                                     ),
                                   ),
                                 );
                               });
                            }).toList(),
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 16),

                    /// GRANULAR PICKER (Day/Month/Year selector)
                    Obx(() {
                      final period = controller.selectedPeriod.value;
                      final date = controller.selectedDate.value;
                      String label = "";
                      
                      if (period == LaporanPeriod.hari) {
                        label = DateFormat('dd MMMM yyyy').format(date);
                      } else if (period == LaporanPeriod.minggu) {
                        final weekNum = ((date.day - 1) / 7).floor() + 1;
                        label = "Minggu ke-$weekNum, ${DateFormat('MMMM yyyy').format(date)}";
                      } else if (period == LaporanPeriod.bulan) {
                        label = DateFormat('MMMM yyyy').format(date);
                      } else {
                        label = DateFormat('yyyy').format(date);
                      }

                      return GestureDetector(
                        onTap: () => _selectDate(context, controller),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: const Color(0xFFE2E8F0)),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(Icons.event_note_rounded, size: 18, color: Color(0xFF64748B)),
                              const SizedBox(width: 10),
                              Text(
                                label,
                                style: const TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w600,
                                  color: Color(0xFF1E293B),
                                ),
                              ),
                              const SizedBox(width: 8),
                              const Icon(Icons.arrow_drop_down_rounded, color: Color(0xFF64748B)),
                            ],
                          ),
                        ),
                      );
                    }),

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

                    /// ───────── CHART ─────────
                    const CashflowChart(),

                    const SizedBox(height: 48),

                    /// ───────── LAPORAN TRANSAKSI ─────────
                    Text('Rincian Transaksi Terkonfirmasi', style: AppTextStyles.h3),
                    const SizedBox(height: 16),
                    ReportTable(transactions: filteredTransactions),
                    
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

  Future<void> _selectDate(BuildContext context, LaporanController controller) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: controller.selectedDate.value,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: Color(0xFF6366F1),
              onPrimary: Colors.white,
              onSurface: Color(0xFF1E293B),
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null) {
      controller.setDate(picked);
    }
  }
}
