// features/history/application/history_controller.dart

import 'package:flutter/foundation.dart';
import 'package:desktop_flutter/core/session/api_session.dart';
import '../domain/entities/transaction.dart';

/// Controller untuk mengelola state halaman History Transaksi.
/// Menggunakan [ChangeNotifier] agar bisa dipakai dengan Provider / ValueListenableBuilder.
///
/// Untuk integrasi state management lain (Riverpod, Bloc, GetX),
/// logika filtering & pagination di sini bisa dipindahkan ke notifier / cubit masing-masing.
class HistoryController extends ChangeNotifier {
  HistoryController() {
    loadTransactions();
  }

  List<Transaction> _allTransactions = [];
  bool isLoading = false;
  String? errorMessage;

  // ─── State ─────────────────────────────────────────────────────────────────
  String _searchQuery = '';
  TransactionStatus? _statusFilter;
  int _currentPage = 1;
  final int _perPage = 10;

  // ─── Getters ───────────────────────────────────────────────────────────────
  String get searchQuery => _searchQuery;
  TransactionStatus? get statusFilter => _statusFilter;
  int get currentPage => _currentPage;
  int get perPage => _perPage;

  /// Total transaksi setelah filter (untuk label "Menampilkan X-Y dari Z transaksi")
  int get totalFiltered => _filtered.length;

  /// Total halaman
  int get totalPages => (totalFiltered / _perPage).ceil().clamp(1, 9999);

  /// Transaksi yang ditampilkan di halaman saat ini
  List<Transaction> get pagedTransactions {
    final start = (_currentPage - 1) * _perPage;
    final end = (start + _perPage).clamp(0, _filtered.length);
    if (start >= _filtered.length) return [];
    return _filtered.sublist(start, end);
  }

  /// Label "Menampilkan X-Y dari Z transaksi"
  String get paginationLabel {
    if (totalFiltered == 0) return 'Tidak ada transaksi';
    final start = (_currentPage - 1) * _perPage + 1;
    final end = (start + _perPage - 1).clamp(1, totalFiltered);
    return 'Menampilkan $start-$end dari $totalFiltered transaksi';
  }

  // ─── Private helpers ───────────────────────────────────────────────────────
  List<Transaction> get _filtered {
    return _allTransactions.where((t) {
      final matchSearch =
          _searchQuery.isEmpty ||
          t.id.toLowerCase().contains(_searchQuery.toLowerCase()) ||
          t.namaPelanggan.toLowerCase().contains(_searchQuery.toLowerCase());
      final matchStatus = _statusFilter == null || t.status == _statusFilter;
      return matchSearch && matchStatus;
    }).toList();
  }

  // ─── Actions ───────────────────────────────────────────────────────────────
  Future<void> loadTransactions() async {
    final client = ApiSession.client;

    if (client == null) {
      return;
    }

    isLoading = true;
    errorMessage = null;
    notifyListeners();

    try {
      final rows = await client.fetchTransactions(perPage: 100);

      _allTransactions = rows.map((row) {
        final packageItems = row.items.where((item) => item.itemType == 'package' || item.itemType == 'booking').toList();
        final addOnItems = row.items.where((item) => item.itemType == 'add_on').toList();

        return Transaction(
          id: row.transactionCode,
          waktu: DateTime.tryParse(row.createdAt ?? '') ?? DateTime.now(),
          namaPelanggan: row.customerName.isEmpty ? '-' : row.customerName,
          paket: packageItems.isNotEmpty ? packageItems.first.itemName : '-',
          addOns: addOnItems.isEmpty ? null : addOnItems.map((item) => item.itemName).join(', '),
          totalBayar: row.totalAmount.round(),
          status: _mapTransactionStatus(row.status),
        );
      }).toList();
    } catch (error) {
      errorMessage = error.toString();
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  void onSearchChanged(String query) {
    _searchQuery = query;
    _currentPage = 1;
    notifyListeners();
  }

  void onStatusFilterChanged(TransactionStatus? status) {
    _statusFilter = status;
    _currentPage = 1;
    notifyListeners();
  }

  void goToPage(int page) {
    if (page < 1 || page > totalPages) return;
    _currentPage = page;
    notifyListeners();
  }

  void nextPage() => goToPage(_currentPage + 1);
  void prevPage() => goToPage(_currentPage - 1);

  /// Placeholder aksi export — hubungkan ke service di production
  void onExport() {
    debugPrint('Export triggered');
  }

  /// Placeholder aksi per-baris (edit / hapus / detail)
  void onRowAction(Transaction transaction) {
    debugPrint('Row action: ${transaction.id}');
  }

  /// Aksi Cetak Ulang
  void onReprint(Transaction transaction) {
    debugPrint('Cetak ulang: ${transaction.id}');
    // Logic untuk integrasi ke printer thermal / ESC/POS
  }

  TransactionStatus _mapTransactionStatus(String status) {
    return switch (status) {
      'paid' => TransactionStatus.lunas,
      'void' => TransactionStatus.batal,
      _ => TransactionStatus.pending,
    };
  }
}
