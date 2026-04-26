import 'package:flutter/material.dart';
import '../../../app/theme/app_text_styles.dart';
import '../../application/history_controller.dart';
import '../sections/history_header_section.dart';
import '../sections/history_table_section.dart';
import '../sections/history_pagination_section.dart';

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
    return Container(
      color: const Color(0xFFF8FAFC),
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(40, 60, 40, 40),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Title Premium
            Text('History Transaksi', style: AppTextStyles.h1),
            const SizedBox(height: 8),
            Text(
              'Kelola dan tinjau seluruh riwayat transaksi photobooth.',
              style: AppTextStyles.bodyMedium.copyWith(color: Colors.grey[600]),
            ),

            const SizedBox(height: 40),

            // Header (Integrated Logic)
            HistoryHeaderSection(
              searchQuery: _controller.searchQuery,
              onSearchChanged: _controller.onSearchChanged,
              selectedStatus: _controller.statusFilter,
              onStatusFilterChanged: _controller.onStatusFilterChanged,
              onExport: _controller.onExport,
            ),

            const SizedBox(height: 24),

            // Table Premium with logic
            HistoryTableSection(
              transactions: _controller.pagedTransactions,
              onRowAction: _controller.onRowAction,
            ),

            const SizedBox(height: 32),

            // Pagination Logic
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
