import 'package:desktop_flutter/core/network/api_client.dart';
import 'package:desktop_flutter/features/kasir/services/receipt_printer.dart';
import 'package:desktop_flutter/shared/models/booking_item.dart';
import 'package:desktop_flutter/shared/models/branch_option.dart';
import 'package:desktop_flutter/shared/models/cashier_session_item.dart';
import 'package:desktop_flutter/shared/models/desktop_session.dart';
import 'package:desktop_flutter/shared/models/printer_setting_item.dart';
import 'package:desktop_flutter/shared/models/queue_ticket_item.dart';
import 'package:desktop_flutter/shared/models/transaction_record.dart';
import 'package:flutter/material.dart';

class KasirDashboardPanel extends StatefulWidget {
  const KasirDashboardPanel(this.session, {super.key});

  final DesktopSession session;

  @override
  State<KasirDashboardPanel> createState() => _KasirDashboardPanelState();
}

class _KasirDashboardPanelState extends State<KasirDashboardPanel> {
  late final ApiClient _client = ApiClient(
    baseUrl: widget.session.baseUrl,
    token: widget.session.token,
  );

  final TextEditingController _itemNameController = TextEditingController();
  final TextEditingController _qtyController = TextEditingController(text: '1');
  final TextEditingController _unitPriceController = TextEditingController();
  final TextEditingController _paymentAmountController =
      TextEditingController();
  final TextEditingController _referenceController = TextEditingController();
  final TextEditingController _notesController = TextEditingController();
  final TextEditingController _bookingSearchController =
      TextEditingController();
  final TextEditingController _bookingCodeController = TextEditingController();
  final TextEditingController _walkInNameController = TextEditingController();
  final TextEditingController _walkInPhoneController = TextEditingController();

  List<BranchOption> _branches = <BranchOption>[];
  List<QueueTicketItem> _tickets = <QueueTicketItem>[];
  List<BookingItem> _bookings = <BookingItem>[];
  List<TransactionRecord> _transactions = <TransactionRecord>[];
  List<PrinterSettingItem> _printers = <PrinterSettingItem>[];
  CashierSessionItem? _currentSession;
  int? _branchId;
  int _tabIndex = 0;
  String _paymentMethod = 'cash';
  String _brandName = 'Ready To Pict';
  bool _autoPrintAfterPayment = true;
  bool _loading = true;
  bool _submitting = false;
  bool _printing = false;
  bool _sessionSubmitting = false;
  String? _error;
  TransactionRecord? _latestTransaction;

  @override
  void initState() {
    super.initState();
    _loadInitial();
  }

  @override
  void dispose() {
    _itemNameController.dispose();
    _qtyController.dispose();
    _unitPriceController.dispose();
    _paymentAmountController.dispose();
    _referenceController.dispose();
    _notesController.dispose();
    _bookingSearchController.dispose();
    _bookingCodeController.dispose();
    _walkInNameController.dispose();
    _walkInPhoneController.dispose();
    super.dispose();
  }

  Future<void> _loadInitial() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final branches = await _client.fetchBranches();
      final selectedBranch =
          _branchId ?? (branches.isNotEmpty ? branches.first.id : null);

      final queue = selectedBranch == null
          ? <QueueTicketItem>[]
          : await _client.fetchQueueTickets(
              branchId: selectedBranch,
              queueDate: _today,
            );
      final bookings = selectedBranch == null
          ? <BookingItem>[]
          : await _client.fetchBookings(
              branchId: selectedBranch,
              date: _today,
              perPage: 100,
            );
      final transactions = selectedBranch == null
          ? <TransactionRecord>[]
          : await _client.fetchTransactions(
              branchId: selectedBranch,
              perPage: 15,
            );
      final settings = await _client.fetchAppSettings();
      final configuredBrand =
          settings.general['brand_name']?.toString().trim() ?? '';
      final currentSession = selectedBranch == null
          ? null
          : await _client.fetchCurrentCashierSession(branchId: selectedBranch);
      final printers = selectedBranch == null
          ? <PrinterSettingItem>[]
          : await _client.fetchPrinterSettings(
              branchId: selectedBranch,
              includeInactive: false,
            );

      if (!mounted) {
        return;
      }

      setState(() {
        _branches = branches;
        _branchId = selectedBranch;
        _tickets = queue;
        _bookings = bookings;
        _transactions = transactions;
        _brandName = configuredBrand.isEmpty
            ? 'Ready To Pict'
            : configuredBrand;
        _currentSession = currentSession;
        _printers = printers;
      });
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    } finally {
      if (mounted) {
        setState(() {
          _loading = false;
        });
      }
    }
  }

  Future<void> _refreshQueue() async {
    if (_branchId == null) {
      return;
    }

    try {
      final tickets = await _client.fetchQueueTickets(
        branchId: _branchId,
        queueDate: _today,
      );

      if (!mounted) {
        return;
      }

      setState(() {
        _tickets = tickets;
      });
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    }
  }

  Future<void> _refreshTransactions() async {
    if (_branchId == null) {
      return;
    }

    try {
      final transactions = await _client.fetchTransactions(
        branchId: _branchId,
        perPage: 15,
      );

      if (!mounted) {
        return;
      }

      setState(() {
        _transactions = transactions;
      });
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    }
  }

  Future<void> _refreshSessionAndPrinters() async {
    if (_branchId == null) {
      return;
    }

    try {
      final currentSession = await _client.fetchCurrentCashierSession(
        branchId: _branchId,
      );
      final printers = await _client.fetchPrinterSettings(
        branchId: _branchId,
        includeInactive: false,
      );

      if (!mounted) {
        return;
      }

      setState(() {
        _currentSession = currentSession;
        _printers = printers;
      });
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    }
  }

  Future<void> _refreshBookings({String? bookingCode}) async {
    if (_branchId == null) {
      return;
    }

    try {
      final bookings = await _client.fetchBookings(
        branchId: _branchId,
        date: _today,
        bookingCode: bookingCode,
        perPage: 100,
      );

      if (!mounted) {
        return;
      }

      setState(() {
        _bookings = bookings;
      });
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    }
  }

  Future<void> _openCashierSession() async {
    if (_branchId == null || _sessionSubmitting) {
      return;
    }

    final openingCashController = TextEditingController(text: '0');
    final notesController = TextEditingController();

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Buka Sesi Kasir'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: <Widget>[
              TextField(
                controller: openingCashController,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(labelText: 'Kas awal'),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: notesController,
                decoration: const InputDecoration(
                  labelText: 'Catatan (opsional)',
                ),
              ),
            ],
          ),
          actions: <Widget>[
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: const Text('Batal'),
            ),
            FilledButton(
              onPressed: () => Navigator.of(context).pop(true),
              child: const Text('Buka Sesi'),
            ),
          ],
        );
      },
    );

    if (confirmed != true) {
      openingCashController.dispose();
      notesController.dispose();
      return;
    }

    final openingCash = double.tryParse(
      openingCashController.text.trim().replaceAll(',', '.'),
    );
    final notes = notesController.text.trim();

    openingCashController.dispose();
    notesController.dispose();

    setState(() {
      _sessionSubmitting = true;
      _error = null;
    });

    try {
      final session = await _client.openCashierSession(
        branchId: _branchId!,
        openingCash: openingCash,
        notes: notes.isEmpty ? null : notes,
      );

      await _refreshSessionAndPrinters();

      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Sesi kasir dibuka (${session.branchName}).')),
      );
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    } finally {
      if (mounted) {
        setState(() {
          _sessionSubmitting = false;
        });
      }
    }
  }

  Future<void> _closeCashierSession() async {
    final session = _currentSession;

    if (session == null || !session.isOpen || _sessionSubmitting) {
      return;
    }

    final closingCashController = TextEditingController();
    final notesController = TextEditingController();

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Tutup Sesi Kasir'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: <Widget>[
              TextField(
                controller: closingCashController,
                keyboardType: TextInputType.number,
                decoration: const InputDecoration(
                  labelText: 'Kas akhir (opsional)',
                ),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: notesController,
                decoration: const InputDecoration(
                  labelText: 'Catatan (opsional)',
                ),
              ),
            ],
          ),
          actions: <Widget>[
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: const Text('Batal'),
            ),
            FilledButton(
              onPressed: () => Navigator.of(context).pop(true),
              child: const Text('Tutup Sesi'),
            ),
          ],
        );
      },
    );

    if (confirmed != true) {
      closingCashController.dispose();
      notesController.dispose();
      return;
    }

    final closingCash = double.tryParse(
      closingCashController.text.trim().replaceAll(',', '.'),
    );
    final notes = notesController.text.trim();

    closingCashController.dispose();
    notesController.dispose();

    setState(() {
      _sessionSubmitting = true;
      _error = null;
    });

    try {
      await _client.closeCashierSession(
        sessionId: session.id,
        closingCash: closingCash,
        notes: notes.isEmpty ? null : notes,
      );

      await _refreshSessionAndPrinters();

      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(
        context,
      ).showSnackBar(const SnackBar(content: Text('Sesi kasir ditutup.')));
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    } finally {
      if (mounted) {
        setState(() {
          _sessionSubmitting = false;
        });
      }
    }
  }

  Future<void> _callNext() async {
    if (_branchId == null || _submitting) {
      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final ticket = await _client.callNext(
        branchId: _branchId!,
        queueDate: _today,
      );

      await _refreshQueue();

      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            ticket == null
                ? 'Tidak ada antrean waiting untuk dipanggil.'
                : 'Antrean ${ticket.queueCode} berhasil dipanggil.',
          ),
        ),
      );
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }
      setState(() {
        _error = exception.message;
      });
    } finally {
      if (mounted) {
        setState(() {
          _submitting = false;
        });
      }
    }
  }

  Future<void> _advanceTicket(QueueTicketItem ticket) async {
    final nextStatus = _nextStatus(ticket.status);

    if (nextStatus == null || _submitting) {
      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      await _client.transitionQueueTicket(
        ticketId: ticket.id,
        status: nextStatus,
      );
      await _refreshQueue();
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }
      setState(() {
        _error = exception.message;
      });
    } finally {
      if (mounted) {
        setState(() {
          _submitting = false;
        });
      }
    }
  }

  Future<void> _checkInBooking(BookingItem booking) async {
    if (_submitting) {
      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final ticket = await _client.checkInBooking(bookingId: booking.id);
      await _refreshQueue();
      await _refreshBookings(bookingCode: _bookingSearchController.text.trim());

      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Booking ${ticket.queueCode} berhasil check-in.'),
        ),
      );
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    } finally {
      if (mounted) {
        setState(() {
          _submitting = false;
        });
      }
    }
  }

  Future<void> _checkInBookingByCode() async {
    if (_branchId == null || _submitting) {
      return;
    }

    final bookingCode = _bookingCodeController.text.trim().toUpperCase();

    if (bookingCode.isEmpty) {
      setState(() {
        _error = 'Kode booking wajib diisi.';
      });

      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final bookings = await _client.fetchBookings(
        branchId: _branchId,
        date: _today,
        bookingCode: bookingCode,
        perPage: 20,
      );

      BookingItem? booking;

      for (final item in bookings) {
        if (item.bookingCode.toUpperCase() == bookingCode) {
          booking = item;
          break;
        }
      }

      if (booking == null) {
        throw ApiException(
          'Booking $bookingCode tidak ditemukan untuk cabang dan tanggal hari ini.',
        );
      }

      if (!_canCheckInBooking(booking.status)) {
        throw ApiException(
          'Booking $bookingCode tidak bisa di-check-in (status ${_statusLabel(booking.status)}).',
        );
      }

      final ticket = await _client.checkInBooking(bookingId: booking.id);
      _bookingCodeController.clear();

      await _refreshQueue();
      await _refreshBookings(bookingCode: _bookingSearchController.text.trim());

      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Booking ${booking.bookingCode} check-in berhasil (${ticket.queueCode}).',
          ),
        ),
      );
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    } finally {
      if (mounted) {
        setState(() {
          _submitting = false;
        });
      }
    }
  }

  Future<void> _createWalkInQueue() async {
    if (_branchId == null || _submitting) {
      return;
    }

    final customerName = _walkInNameController.text.trim();
    final customerPhone = _walkInPhoneController.text.trim();

    if (customerName.isEmpty) {
      setState(() {
        _error = 'Nama customer walk-in wajib diisi.';
      });

      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final ticket = await _client.createWalkInTicket(
        branchId: _branchId!,
        queueDate: _today,
        customerName: customerName,
        customerPhone: customerPhone.isEmpty ? null : customerPhone,
      );

      await _refreshQueue();

      if (!mounted) {
        return;
      }

      _walkInNameController.clear();
      _walkInPhoneController.clear();

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Walk-in ${ticket.queueCode} berhasil dibuat.')),
      );
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    } finally {
      if (mounted) {
        setState(() {
          _submitting = false;
        });
      }
    }
  }

  Future<void> _createTransaction() async {
    if (_branchId == null || _submitting) {
      return;
    }

    if (!_hasOpenSession) {
      setState(() {
        _error = 'Buka sesi kasir terlebih dahulu sebelum membuat transaksi.';
      });

      return;
    }

    final itemName = _itemNameController.text.trim();
    final qty = double.tryParse(_qtyController.text.replaceAll(',', '.')) ?? 0;
    final unitPrice =
        double.tryParse(_unitPriceController.text.replaceAll(',', '.')) ?? 0;

    if (itemName.isEmpty || qty <= 0 || unitPrice <= 0) {
      setState(() {
        _error = 'Isi item, qty, dan harga dengan benar.';
      });

      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final transaction = await _client.createTransaction(
        branchId: _branchId!,
        itemName: itemName,
        qty: qty,
        unitPrice: unitPrice,
        notes: _notesController.text.trim().isEmpty
            ? null
            : _notesController.text.trim(),
      );

      _paymentAmountController.text = transaction.totalAmount.toStringAsFixed(
        0,
      );

      if (!mounted) {
        return;
      }

      setState(() {
        _latestTransaction = transaction;
      });

      await _refreshTransactions();

      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Transaksi ${transaction.transactionCode} dibuat.'),
        ),
      );
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }
      setState(() {
        _error = exception.message;
      });
    } finally {
      if (mounted) {
        setState(() {
          _submitting = false;
        });
      }
    }
  }

  Future<void> _submitPayment() async {
    final transaction = _latestTransaction;

    if (transaction == null || _submitting) {
      return;
    }

    if (!_hasOpenSession) {
      setState(() {
        _error = 'Sesi kasir tidak aktif. Buka sesi kasir sebelum pembayaran.';
      });

      return;
    }

    final amount =
        double.tryParse(_paymentAmountController.text.replaceAll(',', '.')) ??
        0;

    if (amount <= 0) {
      setState(() {
        _error = 'Nominal pembayaran harus lebih dari 0.';
      });

      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      await _client.addTransactionPayment(
        transactionId: transaction.id,
        method: _paymentMethod,
        amount: amount,
        referenceNo: _referenceController.text.trim().isEmpty
            ? null
            : _referenceController.text.trim(),
      );

      await _refreshTransactions();

      if (!mounted) {
        return;
      }

      final updatedTransaction = _transactions.firstWhere(
        (item) => item.id == transaction.id,
        orElse: () => transaction,
      );

      final refreshedTransaction = await _client.fetchTransactionDetail(
        transactionId: updatedTransaction.id,
      );

      if (!mounted) {
        return;
      }

      setState(() {
        _latestTransaction = refreshedTransaction;
      });

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Pembayaran berhasil ditambahkan.')),
      );

      if (_autoPrintAfterPayment) {
        await _printTransactionReceipt(refreshedTransaction);
      }
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    } finally {
      if (mounted) {
        setState(() {
          _submitting = false;
        });
      }
    }
  }

  Future<void> _updateBookingStatus({
    required BookingItem booking,
    required String status,
  }) async {
    if (_submitting) {
      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      await _client.updateBookingStatus(bookingId: booking.id, status: status);
      await _refreshBookings(bookingCode: _bookingSearchController.text.trim());
      await _refreshQueue();

      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Status booking ${booking.bookingCode} diubah ke ${_statusLabel(status)}.',
          ),
        ),
      );
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    } finally {
      if (mounted) {
        setState(() {
          _submitting = false;
        });
      }
    }
  }

  Future<void> _selectTransaction(TransactionRecord transaction) async {
    setState(() {
      _error = null;
    });

    try {
      final detail = await _client.fetchTransactionDetail(
        transactionId: transaction.id,
      );

      if (!mounted) {
        return;
      }

      setState(() {
        _latestTransaction = detail;
        _paymentAmountController.text = detail.totalAmount.toStringAsFixed(0);
      });
    } on ApiException catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = exception.message;
      });
    }
  }

  String _branchNameById(int branchId) {
    for (final branch in _branches) {
      if (branch.id == branchId) {
        return branch.name;
      }
    }

    return 'Cabang #$branchId';
  }

  bool get _hasOpenSession {
    final session = _currentSession;

    if (session == null || _branchId == null) {
      return false;
    }

    return session.isOpen && session.branchId == _branchId;
  }

  String get _sessionStatusLabel {
    final session = _currentSession;

    if (session == null || !_hasOpenSession) {
      return 'Sesi kasir: belum dibuka';
    }

    return 'Sesi kasir: OPEN (${session.branchName})';
  }

  PrinterSettingItem? get _activePrinter {
    if (_printers.isEmpty) {
      return null;
    }

    for (final printer in _printers) {
      if (printer.isDefault && printer.isActive) {
        return printer;
      }
    }

    for (final printer in _printers) {
      if (printer.isActive) {
        return printer;
      }
    }

    return _printers.first;
  }

  Future<void> _printTransactionReceipt(TransactionRecord transaction) async {
    if (_printing) {
      return;
    }

    setState(() {
      _printing = true;
      _error = null;
    });

    try {
      final printer = _activePrinter;

      await ReceiptPrinter.printTransactionReceipt(
        transaction: transaction,
        brandName: _brandName,
        branchName: _branchNameById(transaction.branchId),
        cashierName: widget.session.user.name,
        paperWidthMm: printer?.paperWidthMm,
      );

      if (!mounted) {
        return;
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            printer == null
                ? 'Dialog cetak untuk ${transaction.transactionCode} terbuka.'
                : 'Dialog cetak ${printer.deviceName} untuk ${transaction.transactionCode} terbuka.',
          ),
        ),
      );
    } catch (exception) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = 'Gagal menyiapkan cetak receipt: $exception';
      });
    } finally {
      if (mounted) {
        setState(() {
          _printing = false;
        });
      }
    }
  }

  String? _nextStatus(String status) {
    switch (status) {
      case 'waiting':
        return 'called';
      case 'called':
      case 'checked_in':
        return 'in_session';
      case 'in_session':
        return 'finished';
      default:
        return null;
    }
  }

  String _actionLabel(String status) {
    switch (status) {
      case 'waiting':
        return 'Call';
      case 'called':
      case 'checked_in':
        return 'Start';
      case 'in_session':
        return 'Finish';
      default:
        return '-';
    }
  }

  String _statusLabel(String status) {
    return status.replaceAll('_', ' ');
  }

  Color _statusBackgroundColor(String status) {
    switch (status) {
      case 'finished':
      case 'done':
      case 'paid':
        return const Color(0xFFDCFCE7);
      case 'called':
      case 'checked_in':
      case 'in_session':
      case 'in_queue':
      case 'confirmed':
        return const Color(0xFFDBEAFE);
      case 'cancelled':
      case 'skipped':
        return const Color(0xFFFEE2E2);
      default:
        return const Color(0xFFF3F4F6);
    }
  }

  Color _statusForegroundColor(String status) {
    switch (status) {
      case 'finished':
      case 'done':
      case 'paid':
        return const Color(0xFF166534);
      case 'called':
      case 'checked_in':
      case 'in_session':
      case 'in_queue':
      case 'confirmed':
        return const Color(0xFF1D4ED8);
      case 'cancelled':
      case 'skipped':
        return const Color(0xFFB91C1C);
      default:
        return const Color(0xFF374151);
    }
  }

  Color _sourceBackgroundColor(String sourceType) {
    switch (sourceType) {
      case 'walk_in':
        return const Color(0xFFFFF7ED);
      default:
        return const Color(0xFFEFF6FF);
    }
  }

  Color _sourceForegroundColor(String sourceType) {
    switch (sourceType) {
      case 'walk_in':
        return const Color(0xFF9A3412);
      default:
        return const Color(0xFF1E40AF);
    }
  }

  String _sourceLabel(String sourceType) {
    switch (sourceType) {
      case 'walk_in':
        return 'walk-in';
      default:
        return 'booking';
    }
  }

  bool _canCheckInBooking(String status) {
    return status != 'cancelled' &&
        status != 'done' &&
        status != 'in_session' &&
        status != 'in_queue';
  }

  List<({String status, String label})> _bookingStatusActions(
    BookingItem booking,
  ) {
    final current = booking.status;

    if (current == 'done' || current == 'cancelled') {
      return const <({String status, String label})>[];
    }

    final actions = <({String status, String label})>[];

    if (current == 'pending') {
      actions.add((status: 'confirmed', label: 'Set Confirmed'));
    }

    if (current == 'confirmed') {
      actions.add((status: 'paid', label: 'Set Paid'));
    }

    if (current == 'in_session' ||
        current == 'checked_in' ||
        current == 'in_queue') {
      actions.add((status: 'done', label: 'Set Done'));
    }

    actions.add((status: 'cancelled', label: 'Set Cancelled'));

    return actions;
  }

  List<BookingItem> get _visibleBookings {
    final search = _bookingSearchController.text.trim().toLowerCase();

    return _bookings.where((booking) {
      if (search.isEmpty) {
        return true;
      }

      return booking.bookingCode.toLowerCase().contains(search) ||
          booking.customerName.toLowerCase().contains(search);
    }).toList();
  }

  String get _today {
    final now = DateTime.now();
    final year = now.year.toString().padLeft(4, '0');
    final month = now.month.toString().padLeft(2, '0');
    final day = now.day.toString().padLeft(2, '0');

    return '$year-$month-$day';
  }

  String _rupiah(double value) {
    final integer = value.round();
    final chars = integer.toString().split('').reversed.toList();
    final buffer = StringBuffer();

    for (var i = 0; i < chars.length; i++) {
      if (i > 0 && i % 3 == 0) {
        buffer.write('.');
      }
      buffer.write(chars[i]);
    }

    return 'Rp ${buffer.toString().split('').reversed.join()}';
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Text(
          'Workspace Kasir',
          style: Theme.of(
            context,
          ).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w700),
        ),
        const SizedBox(height: 12),
        Wrap(
          spacing: 12,
          runSpacing: 12,
          crossAxisAlignment: WrapCrossAlignment.center,
          children: <Widget>[
            SizedBox(
              width: 260,
              child: DropdownButtonFormField<int>(
                key: ValueKey<int?>(_branchId),
                initialValue: _branchId,
                decoration: const InputDecoration(labelText: 'Cabang Studio'),
                items: _branches
                    .map(
                      (branch) => DropdownMenuItem<int>(
                        value: branch.id,
                        child: Text(branch.name),
                      ),
                    )
                    .toList(),
                onChanged: _loading
                    ? null
                    : (value) {
                        setState(() {
                          _branchId = value;
                        });
                        _loadInitial();
                      },
              ),
            ),
            SegmentedButton<int>(
              segments: const <ButtonSegment<int>>[
                ButtonSegment<int>(
                  value: 0,
                  label: Text('Queue'),
                  icon: Icon(Icons.confirmation_number_outlined),
                ),
                ButtonSegment<int>(
                  value: 1,
                  label: Text('POS'),
                  icon: Icon(Icons.point_of_sale_outlined),
                ),
              ],
              selected: <int>{_tabIndex},
              onSelectionChanged: (selection) {
                setState(() {
                  _tabIndex = selection.first;
                });
              },
            ),
            FilledButton.icon(
              onPressed: _loading ? null : _loadInitial,
              icon: const Icon(Icons.refresh_rounded),
              label: const Text('Refresh'),
            ),
            Chip(
              avatar: Icon(
                _hasOpenSession ? Icons.lock_open : Icons.lock_outline,
                size: 18,
                color: _hasOpenSession
                    ? const Color(0xFF166534)
                    : const Color(0xFFB45309),
              ),
              label: Text(_sessionStatusLabel),
            ),
            OutlinedButton.icon(
              onPressed:
                  _sessionSubmitting || _branchId == null || _hasOpenSession
                  ? null
                  : _openCashierSession,
              icon: const Icon(Icons.login_rounded),
              label: const Text('Buka Sesi'),
            ),
            OutlinedButton.icon(
              onPressed: _sessionSubmitting || !_hasOpenSession
                  ? null
                  : _closeCashierSession,
              icon: const Icon(Icons.logout_rounded),
              label: const Text('Tutup Sesi'),
            ),
          ],
        ),
        if (_error != null) ...<Widget>[
          const SizedBox(height: 12),
          Text(_error!, style: const TextStyle(color: Colors.redAccent)),
        ],
        const SizedBox(height: 14),
        Expanded(
          child: _loading
              ? const Center(child: CircularProgressIndicator())
              : _tabIndex == 0
              ? _buildQueueTab()
              : _buildPosTab(),
        ),
      ],
    );
  }

  Widget _buildQueueTab() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: <Widget>[
        Text('Queue hari ini • $_today'),
        const SizedBox(height: 10),
        Wrap(
          spacing: 12,
          runSpacing: 8,
          children: <Widget>[
            FilledButton.icon(
              onPressed: _submitting || _branchId == null ? null : _callNext,
              icon: const Icon(Icons.campaign_outlined),
              label: const Text('Call Next'),
            ),
            OutlinedButton.icon(
              onPressed: _submitting ? null : _refreshQueue,
              icon: const Icon(Icons.refresh_rounded),
              label: const Text('Refresh Queue'),
            ),
            OutlinedButton.icon(
              onPressed: _submitting
                  ? null
                  : () => _refreshBookings(
                      bookingCode: _bookingSearchController.text.trim(),
                    ),
              icon: const Icon(Icons.badge_outlined),
              label: const Text('Refresh Booking'),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Expanded(
          child: LayoutBuilder(
            builder: (context, constraints) {
              final stacked = constraints.maxWidth < 1100;

              if (stacked) {
                return ListView(
                  children: <Widget>[
                    _buildQueueListCard(scrollable: false),
                    const SizedBox(height: 12),
                    _buildBookingCheckInCard(scrollable: false),
                    const SizedBox(height: 12),
                    _buildWalkInCard(),
                  ],
                );
              }

              return Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Expanded(
                    flex: 5,
                    child: _buildQueueListCard(scrollable: true),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    flex: 4,
                    child: Column(
                      children: <Widget>[
                        Expanded(
                          child: _buildBookingCheckInCard(scrollable: true),
                        ),
                        const SizedBox(height: 12),
                        Expanded(child: _buildWalkInCard()),
                      ],
                    ),
                  ),
                ],
              );
            },
          ),
        ),
      ],
    );
  }

  Widget _buildQueueListCard({required bool scrollable}) {
    final queueList = ListView.separated(
      padding: const EdgeInsets.all(16),
      shrinkWrap: !scrollable,
      physics: scrollable
          ? const AlwaysScrollableScrollPhysics()
          : const NeverScrollableScrollPhysics(),
      itemBuilder: (context, index) {
        final ticket = _tickets[index];
        final nextStatus = _nextStatus(ticket.status);

        return ListTile(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
            side: const BorderSide(color: Color(0xFFE5E7EB)),
          ),
          title: Text(
            '${ticket.queueCode} • ${ticket.customerName}',
            style: const TextStyle(fontWeight: FontWeight.w600),
          ),
          subtitle: Wrap(
            spacing: 8,
            runSpacing: 6,
            crossAxisAlignment: WrapCrossAlignment.center,
            children: <Widget>[
              Text('No. ${ticket.queueNumber}'),
              _StatusPill(
                label: _statusLabel(ticket.status),
                backgroundColor: _statusBackgroundColor(ticket.status),
                foregroundColor: _statusForegroundColor(ticket.status),
              ),
              _StatusPill(
                label: _sourceLabel(ticket.sourceType),
                backgroundColor: _sourceBackgroundColor(ticket.sourceType),
                foregroundColor: _sourceForegroundColor(ticket.sourceType),
              ),
            ],
          ),
          trailing: nextStatus == null
              ? null
              : OutlinedButton(
                  onPressed: _submitting ? null : () => _advanceTicket(ticket),
                  child: Text(_actionLabel(ticket.status)),
                ),
        );
      },
      separatorBuilder: (_, __) => const SizedBox(height: 10),
      itemCount: _tickets.length,
    );

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
      child: _tickets.isEmpty
          ? const Center(child: Text('Belum ada antrean untuk cabang ini.'))
          : queueList,
    );
  }

  Widget _buildBookingCheckInCard({required bool scrollable}) {
    final bookings = _visibleBookings;

    final bookingList = bookings.isEmpty
        ? const Center(
            child: Text('Tidak ada booking hari ini untuk dicheck-in.'),
          )
        : ListView.separated(
            shrinkWrap: !scrollable,
            physics: scrollable
                ? const AlwaysScrollableScrollPhysics()
                : const NeverScrollableScrollPhysics(),
            itemBuilder: (context, index) {
              final booking = bookings[index];
              final canCheckIn = _canCheckInBooking(booking.status);
              final statusActions = _bookingStatusActions(booking);

              return ListTile(
                dense: true,
                contentPadding: EdgeInsets.zero,
                title: Text(
                  '${booking.bookingCode} • ${booking.customerName}',
                  style: const TextStyle(fontWeight: FontWeight.w600),
                ),
                subtitle: Wrap(
                  spacing: 8,
                  runSpacing: 6,
                  children: <Widget>[
                    _StatusPill(
                      label: _statusLabel(booking.status),
                      backgroundColor: _statusBackgroundColor(booking.status),
                      foregroundColor: _statusForegroundColor(booking.status),
                    ),
                    Text(booking.customerPhone),
                  ],
                ),
                trailing: Wrap(
                  spacing: 4,
                  children: <Widget>[
                    OutlinedButton(
                      onPressed: !canCheckIn || _submitting
                          ? null
                          : () => _checkInBooking(booking),
                      child: const Text('Check-in'),
                    ),
                    PopupMenuButton<String>(
                      enabled: statusActions.isNotEmpty && !_submitting,
                      tooltip: 'Aksi status booking',
                      icon: const Icon(Icons.more_vert),
                      onSelected: (value) =>
                          _updateBookingStatus(booking: booking, status: value),
                      itemBuilder: (context) {
                        return statusActions
                            .map(
                              (option) => PopupMenuItem<String>(
                                value: option.status,
                                child: Text(option.label),
                              ),
                            )
                            .toList();
                      },
                    ),
                  ],
                ),
              );
            },
            separatorBuilder: (_, __) => const Divider(),
            itemCount: bookings.length,
          );

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Text(
              'Check-in Booking',
              style: Theme.of(
                context,
              ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 10),
            Row(
              children: <Widget>[
                Expanded(
                  child: TextField(
                    controller: _bookingCodeController,
                    textCapitalization: TextCapitalization.characters,
                    decoration: const InputDecoration(
                      labelText: 'Check-in cepat: kode booking',
                    ),
                    onSubmitted: (_) => _checkInBookingByCode(),
                  ),
                ),
                const SizedBox(width: 8),
                FilledButton.icon(
                  onPressed: _submitting || _branchId == null
                      ? null
                      : _checkInBookingByCode,
                  icon: const Icon(Icons.qr_code_2_outlined),
                  label: const Text('Check-in'),
                ),
              ],
            ),
            const SizedBox(height: 10),
            Row(
              children: <Widget>[
                Expanded(
                  child: TextField(
                    controller: _bookingSearchController,
                    decoration: const InputDecoration(
                      labelText: 'Cari kode booking / nama',
                    ),
                    onChanged: (_) {
                      setState(() {});
                    },
                  ),
                ),
                const SizedBox(width: 8),
                FilledButton(
                  onPressed: _submitting
                      ? null
                      : () => _refreshBookings(
                          bookingCode: _bookingSearchController.text.trim(),
                        ),
                  child: const Text('Cari'),
                ),
              ],
            ),
            const SizedBox(height: 10),
            if (scrollable)
              Expanded(child: bookingList)
            else
              SizedBox(height: 320, child: bookingList),
          ],
        ),
      ),
    );
  }

  Widget _buildWalkInCard() {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Text(
              'Walk-in Queue',
              style: Theme.of(
                context,
              ).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _walkInNameController,
              decoration: const InputDecoration(labelText: 'Nama customer'),
            ),
            const SizedBox(height: 10),
            TextField(
              controller: _walkInPhoneController,
              decoration: const InputDecoration(labelText: 'No. HP (opsional)'),
            ),
            const SizedBox(height: 12),
            FilledButton.icon(
              onPressed: _submitting || _branchId == null
                  ? null
                  : _createWalkInQueue,
              icon: const Icon(Icons.person_add_alt_1_outlined),
              label: const Text('Tambah Walk-in'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPosTab() {
    return Column(
      children: <Widget>[
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Expanded(
              child: Card(
                elevation: 0,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(18),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Text(
                        'Buat Transaksi',
                        style: Theme.of(context).textTheme.titleMedium
                            ?.copyWith(fontWeight: FontWeight.w700),
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: _itemNameController,
                        decoration: const InputDecoration(
                          labelText: 'Nama item',
                        ),
                      ),
                      const SizedBox(height: 10),
                      Row(
                        children: <Widget>[
                          Expanded(
                            child: TextField(
                              controller: _qtyController,
                              keyboardType: TextInputType.number,
                              decoration: const InputDecoration(
                                labelText: 'Qty',
                              ),
                            ),
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: TextField(
                              controller: _unitPriceController,
                              keyboardType: TextInputType.number,
                              decoration: const InputDecoration(
                                labelText: 'Harga satuan',
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 10),
                      TextField(
                        controller: _notesController,
                        decoration: const InputDecoration(
                          labelText: 'Catatan transaksi (opsional)',
                        ),
                      ),
                      const SizedBox(height: 12),
                      FilledButton.icon(
                        onPressed: _submitting || _branchId == null
                            ? null
                            : _createTransaction,
                        icon: const Icon(Icons.add_shopping_cart_rounded),
                        label: const Text('Buat Transaksi'),
                      ),
                      const SizedBox(height: 18),
                      const Divider(),
                      const SizedBox(height: 10),
                      Text(
                        'Tambah Pembayaran',
                        style: Theme.of(context).textTheme.titleSmall?.copyWith(
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 10),
                      DropdownButtonFormField<String>(
                        initialValue: _paymentMethod,
                        decoration: const InputDecoration(labelText: 'Metode'),
                        items: const <DropdownMenuItem<String>>[
                          DropdownMenuItem(value: 'cash', child: Text('Cash')),
                          DropdownMenuItem(value: 'qris', child: Text('QRIS')),
                          DropdownMenuItem(
                            value: 'transfer',
                            child: Text('Transfer'),
                          ),
                          DropdownMenuItem(value: 'card', child: Text('Card')),
                        ],
                        onChanged: (value) {
                          if (value == null) {
                            return;
                          }

                          setState(() {
                            _paymentMethod = value;
                          });
                        },
                      ),
                      const SizedBox(height: 10),
                      TextField(
                        controller: _paymentAmountController,
                        keyboardType: TextInputType.number,
                        decoration: const InputDecoration(
                          labelText: 'Nominal bayar',
                        ),
                      ),
                      const SizedBox(height: 10),
                      TextField(
                        controller: _referenceController,
                        decoration: const InputDecoration(
                          labelText: 'Reference no (opsional)',
                        ),
                      ),
                      SwitchListTile(
                        contentPadding: EdgeInsets.zero,
                        title: const Text('Auto print setelah bayar'),
                        value: _autoPrintAfterPayment,
                        onChanged: (value) {
                          setState(() {
                            _autoPrintAfterPayment = value;
                          });
                        },
                      ),
                      const SizedBox(height: 12),
                      FilledButton.icon(
                        onPressed:
                            _latestTransaction == null ||
                                _submitting ||
                                !_hasOpenSession
                            ? null
                            : _submitPayment,
                        icon: const Icon(Icons.payments_outlined),
                        label: Text(
                          _latestTransaction == null
                              ? 'Pilih/Buat transaksi dulu'
                              : !_hasOpenSession
                              ? 'Buka sesi kasir dulu'
                              : 'Tambah Pembayaran',
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Card(
                elevation: 0,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(18),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: <Widget>[
                      Text(
                        'Transaksi Terakhir',
                        style: Theme.of(context).textTheme.titleMedium
                            ?.copyWith(fontWeight: FontWeight.w700),
                      ),
                      const SizedBox(height: 10),
                      if (_latestTransaction != null)
                        _TransactionSummary(
                          transaction: _latestTransaction!,
                          rupiah: _rupiah,
                          printing: _printing,
                          onPrint: () =>
                              _printTransactionReceipt(_latestTransaction!),
                        )
                      else
                        const Text('Belum ada transaksi baru di sesi ini.'),
                      const SizedBox(height: 16),
                      const Divider(),
                      const SizedBox(height: 10),
                      Row(
                        children: <Widget>[
                          Text(
                            'Riwayat Transaksi',
                            style: Theme.of(context).textTheme.titleSmall
                                ?.copyWith(fontWeight: FontWeight.w700),
                          ),
                          const Spacer(),
                          TextButton(
                            onPressed: _submitting
                                ? null
                                : _refreshTransactions,
                            child: const Text('Refresh'),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Expanded(
                        child: _transactions.isEmpty
                            ? const Center(
                                child: Text('Belum ada data transaksi.'),
                              )
                            : ListView.separated(
                                itemBuilder: (context, index) {
                                  final transaction = _transactions[index];

                                  return ListTile(
                                    dense: true,
                                    contentPadding: EdgeInsets.zero,
                                    title: Text(
                                      transaction.transactionCode,
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                    subtitle: Text(
                                      '${transaction.status.toUpperCase()} • ${_rupiah(transaction.totalAmount)}',
                                    ),
                                    trailing: Wrap(
                                      spacing: 6,
                                      children: <Widget>[
                                        IconButton(
                                          tooltip: 'Cetak receipt',
                                          onPressed: _printing
                                              ? null
                                              : () => _printTransactionReceipt(
                                                  transaction,
                                                ),
                                          icon: const Icon(
                                            Icons.print_outlined,
                                          ),
                                        ),
                                        TextButton(
                                          onPressed: () =>
                                              _selectTransaction(transaction),
                                          child: const Text('Pilih'),
                                        ),
                                      ],
                                    ),
                                  );
                                },
                                separatorBuilder: (_, __) => const Divider(),
                                itemCount: _transactions.length,
                              ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ],
        ),
      ],
    );
  }
}

class _StatusPill extends StatelessWidget {
  const _StatusPill({
    required this.label,
    required this.backgroundColor,
    required this.foregroundColor,
  });

  final String label;
  final Color backgroundColor;
  final Color foregroundColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: backgroundColor,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: foregroundColor,
          fontSize: 12,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}

class _TransactionSummary extends StatelessWidget {
  const _TransactionSummary({
    required this.transaction,
    required this.rupiah,
    required this.printing,
    required this.onPrint,
  });

  final TransactionRecord transaction;
  final String Function(double value) rupiah;
  final bool printing;
  final VoidCallback onPrint;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        color: const Color(0xFFF8FAFC),
        border: Border.all(color: const Color(0xFFE5E7EB)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: <Widget>[
          Text(
            transaction.transactionCode,
            style: const TextStyle(fontWeight: FontWeight.w700),
          ),
          const SizedBox(height: 6),
          Text('Status: ${transaction.status.toUpperCase()}'),
          Text('Total: ${rupiah(transaction.totalAmount)}'),
          Text('Paid: ${rupiah(transaction.paidAmount)}'),
          Text('Change: ${rupiah(transaction.changeAmount)}'),
          const SizedBox(height: 10),
          OutlinedButton.icon(
            onPressed: printing ? null : onPrint,
            icon: const Icon(Icons.print_outlined),
            label: Text(printing ? 'Menyiapkan cetak...' : 'Cetak Receipt'),
          ),
        ],
      ),
    );
  }
}
