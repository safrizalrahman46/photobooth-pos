// features/history/application/history_controller.dart

import 'package:flutter/foundation.dart';
import '../domain/entities/transaction.dart';

/// Controller untuk mengelola state halaman History Transaksi.
/// Menggunakan [ChangeNotifier] agar bisa dipakai dengan Provider / ValueListenableBuilder.
///
/// Untuk integrasi state management lain (Riverpod, Bloc, GetX),
/// logika filtering & pagination di sini bisa dipindahkan ke notifier / cubit masing-masing.
class HistoryController extends ChangeNotifier {
  // ─── Dummy data (ganti dengan repository call di production) ───────────────
  final List<Transaction> _allTransactions = [
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
      id: 'TRX-9400',
      waktu: DateTime(2024, 10, 24, 12, 10),
      namaPelanggan: 'Rizky Febian',
      paket: 'Quick Snap',
      addOns: 'Keychain',
      totalBayar: 45000,
      status: TransactionStatus.lunas,
    ),
    Transaction(
      id: 'TRX-9399',
      waktu: DateTime(2024, 10, 24, 11, 30),
      namaPelanggan: 'Maya Angelou',
      paket: 'Classic Duo',
      addOns: 'Wood Frame',
      totalBayar: 150000,
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
  static const double _colAction = 140; // Increased width for button
  void onRowAction(Transaction transaction) {
    debugPrint('Row action: ${transaction.id}');
  }

  /// Aksi Cetak Ulang
  void onReprint(Transaction transaction) {
    debugPrint('Cetak ulang: ${transaction.id}');
    // Logic untuk integrasi ke printer thermal / ESC/POS
  }
}
