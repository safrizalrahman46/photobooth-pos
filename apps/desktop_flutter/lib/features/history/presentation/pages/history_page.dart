import 'package:flutter/material.dart';
import '../../../../shared/layout/sidebar/sidebar.dart';
import '../../../../shared/layout/header/app_header.dart';

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
  int _selectedNavIndex = 1; // sesuaikan index menu
  late final HistoryController _controller;

  @override
  void initState() {
    super.initState();
    _controller = HistoryController();
    _controller.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF9FAFB),
      body: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(40, 60, 40, 40),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Title
                  const Text(
                    'History Transaksi',
                    style: TextStyle(
                      fontSize: 26,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFF1F2937),
                    ),
                  ),

                  const SizedBox(height: 28),

                  // Header (search, filter, export)
                  HistoryHeaderSection(
                    searchQuery: _controller.searchQuery,
                    onSearchChanged: _controller.onSearchChanged,
                    selectedStatus: _controller.statusFilter,
                    onStatusFilterChanged: _controller.onStatusFilterChanged,
                    onExport: _controller.onExport,
                  ),

                  const SizedBox(height: 20),

                  // Table
                  HistoryTableSection(
                    transactions: _controller.pagedTransactions,
                    onRowAction: _controller.onRowAction,
                  ),

                  const SizedBox(height: 28),

                  // Pagination
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
          ),
        ],
      ),
    );
  }
}
