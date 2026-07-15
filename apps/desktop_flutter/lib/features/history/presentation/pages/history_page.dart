import 'package:flutter/material.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';
import '../../application/history_controller.dart';
import '../sections/history_header_section.dart';
import '../sections/history_table_section.dart';
import '../sections/history_pagination_section.dart';
import '../handlers/pelunasan_handler.dart';
import '../handlers/cetak_ulang_handler.dart';
import '../handlers/extra_print_handler.dart';
import '../handlers/ubah_metode_handler.dart';

class HistoryPage extends StatefulWidget {
  const HistoryPage({super.key});

  @override
  State<HistoryPage> createState() => _HistoryPageState();
}

class _HistoryPageState extends State<HistoryPage> {
  late final HistoryController _controller;

  @override
  void initState() {
    super.initState();
    _controller = HistoryController();
    _controller.addListener(_onControllerUpdate);
  }

  void _onControllerUpdate() {
    if (mounted) setState(() {});
  }

  @override
  void dispose() {
    _controller.removeListener(_onControllerUpdate);
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.pageBg,
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(60, 28, 60, 60),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(32),
              decoration: BoxDecoration(
                color: const Color(0xFF2563EB),
                borderRadius: BorderRadius.circular(24),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF2563EB).withValues(alpha: 0.15),
                    blurRadius: 16,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: Colors.white.withValues(alpha: 0.2)),
                    ),
                    child: const Icon(Icons.receipt_long_rounded, color: Colors.white, size: 36),
                  ),
                  const SizedBox(width: 20),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text('Riwayat Transaksi', style: TextStyle(fontSize: 26, fontWeight: FontWeight.w900, color: Colors.white, letterSpacing: 0.5)),
                        SizedBox(height: 6),
                        Text('Kelola, filter, dan tinjau seluruh riwayat transaksi photobooth serta lakukan tambah cetak add-on.', style: TextStyle(color: Colors.white70, fontSize: 13, height: 1.4)),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            HistoryHeaderSection(
              searchQuery: _controller.searchQuery,
              onSearchChanged: _controller.onSearchChanged,
              selectedStatus: _controller.statusFilter,
              onStatusFilterChanged: _controller.onStatusFilterChanged,
              onExport: _controller.onExport,
            ),
            const SizedBox(height: 24),
            if (_controller.errorMessage != null) ...[
              Text(_controller.errorMessage!, style: const TextStyle(color: Colors.redAccent)),
              const SizedBox(height: 16),
            ],
            if (_controller.isLoading)
              const Center(child: CircularProgressIndicator())
            else
              HistoryTableSection(
                transactions: _controller.pagedTransactions,
                onLunasi: (tx) => handlePelunasan(context: context, controller: _controller, transaction: tx),
                onReprint: (tx) => handleCetakUlang(context: context, controller: _controller, transaction: tx),
                onExtraPrint: (tx) => handleExtraPrint(context: context, controller: _controller, transaction: tx),
                onUbahMetode: (tx) => handleUbahMetode(context: context, controller: _controller, transaction: tx),
              ),
            const SizedBox(height: 32),
            HistoryPaginationSection(
              currentPage: _controller.currentPage,
              totalPages: _controller.totalPages,
              paginationLabel: _controller.paginationLabel,
              onPageChanged: _controller.goToPage,
              onPrev: _controller.prevPage,
              onNext: _controller.nextPage,
            ),
          ],
        ),
      ),
    );
  }
}
