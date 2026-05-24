import 'package:flutter/material.dart';
import 'package:desktop_flutter/app/theme/app_text_styles.dart';
import 'package:desktop_flutter/core/network/request_error_message.dart';
import 'package:desktop_flutter/core/session/api_session.dart';
import 'package:desktop_flutter/features/kasir/services/receipt_printer.dart';
import 'package:desktop_flutter/shared/models/add_on_catalog_item.dart';
import '../../application/history_controller.dart';
import '../../domain/entities/transaction.dart' as history_domain;
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
            Text('Riwayat Transaksi', style: AppTextStyles.h1),
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

            if (_controller.errorMessage != null) ...[
              Text(
                _controller.errorMessage!,
                style: const TextStyle(color: Colors.redAccent),
              ),
              const SizedBox(height: 16),
            ],

            // Table Premium with logic
            if (_controller.isLoading)
              const Center(child: CircularProgressIndicator())
            else
              HistoryTableSection(
                transactions: _controller.pagedTransactions,
                onRowAction: (transaction) {
                  _handleExtraPrint(transaction);
                },
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

  Future<void> _handleExtraPrint(history_domain.Transaction transaction) async {
    if (transaction.backendId <= 0) {
      _showSnack('Data transaksi tidak valid untuk tambah cetak.');
      return;
    }

    if (transaction.status != history_domain.TransactionStatus.lunas) {
      _showSnack('Tambah cetak hanya untuk transaksi yang sudah lunas.');
      return;
    }

    final client = ApiSession.client;

    if (client == null) {
      _showSnack('Sesi kasir tidak aktif. Silakan login ulang.');
      return;
    }

    var busyOpen = false;

    void openBusy(String message) {
      busyOpen = true;
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (context) => _BusyDialog(message: message),
      );
    }

    void closeBusy() {
      if (busyOpen && mounted) {
        Navigator.of(context, rootNavigator: true).pop();
      }
      busyOpen = false;
    }

    openBusy('Memuat opsi tambah cetak...');

    try {
      final options = await _loadExtraPrintOptions(transaction);

      if (!mounted) return;
      closeBusy();

      if (options.isEmpty) {
        _showSnack('Belum ada add-on cetak aktif yang tersedia.');
        return;
      }

      final result = await showDialog<_ExtraPrintDialogResult>(
        context: context,
        builder: (context) =>
            _ExtraPrintDialog(transaction: transaction, addOns: options),
      );

      if (result == null) {
        return;
      }

      openBusy('Memproses tambah cetak...');

      final updated = await _controller.addExtraPrint(
        transaction: transaction,
        addOn: result.addOn,
        qty: result.qty,
        paymentMethod: result.paymentMethod,
        referenceNo: result.referenceNo,
      );

      if (!mounted) return;
      closeBusy();

      if (updated == null) {
        _showSnack(_controller.errorMessage ?? 'Tambah cetak belum berhasil.');
        return;
      }

      try {
        await ReceiptPrinter.printTransactionReceipt(
          transaction: updated,
          brandName: 'Ready To Pict',
          branchName: updated.branchName.isNotEmpty
              ? updated.branchName
              : transaction.branchName,
          cashierName: ApiSession.current?.user.name ?? '-',
          receiptTitle: 'STRUK GABUNGAN',
          paperWidthMm: 80,
        );
        _showSnack('Tambah cetak berhasil dan struk gabungan siap dicetak.');
      } catch (error) {
        _showSnack(
          'Tambah cetak berhasil, tetapi struk belum tercetak: ${resolveRequestErrorMessage(error, fallback: 'Periksa printer.')}',
        );
      }
    } catch (error) {
      if (!mounted) return;
      closeBusy();
      _showSnack(
        resolveRequestErrorMessage(
          error,
          fallback: 'Tambah cetak belum dapat diproses.',
        ),
      );
    }
  }

  Future<List<AddOnCatalogItem>> _loadExtraPrintOptions(
    history_domain.Transaction transaction,
  ) async {
    final client = ApiSession.client;

    if (client == null) {
      return <AddOnCatalogItem>[];
    }

    final rows = await client.fetchAddOns(
      packageId: transaction.packageId,
      perPage: 200,
    );
    final available = rows
        .where(
          (item) =>
              item.isActive &&
              (item.effectiveAvailableStock == null ||
                  item.effectiveAvailableStock! > 0),
        )
        .toList();
    final printRows = available.where(_isPrintAddOn).toList();

    return printRows.isNotEmpty ? printRows : available;
  }

  bool _isPrintAddOn(AddOnCatalogItem item) {
    final normalized = '${item.code} ${item.name} ${item.description}'
        .toLowerCase();

    return normalized.contains('cetak') || normalized.contains('print');
  }

  void _showSnack(String message) {
    if (!mounted) return;

    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(message)));
  }
}

class _BusyDialog extends StatelessWidget {
  const _BusyDialog({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Material(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const SizedBox(
                width: 22,
                height: 22,
                child: CircularProgressIndicator(strokeWidth: 2.6),
              ),
              const SizedBox(width: 14),
              Text(message),
            ],
          ),
        ),
      ),
    );
  }
}

class _ExtraPrintDialogResult {
  const _ExtraPrintDialogResult({
    required this.addOn,
    required this.qty,
    required this.paymentMethod,
    this.referenceNo,
  });

  final AddOnCatalogItem addOn;
  final int qty;
  final String paymentMethod;
  final String? referenceNo;
}

class _ExtraPrintDialog extends StatefulWidget {
  const _ExtraPrintDialog({required this.transaction, required this.addOns});

  final history_domain.Transaction transaction;
  final List<AddOnCatalogItem> addOns;

  @override
  State<_ExtraPrintDialog> createState() => _ExtraPrintDialogState();
}

class _ExtraPrintDialogState extends State<_ExtraPrintDialog> {
  late AddOnCatalogItem _selectedAddOn;
  final TextEditingController _qtyController = TextEditingController(text: '1');
  final TextEditingController _referenceController = TextEditingController();
  String _paymentMethod = 'cash';
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _selectedAddOn = widget.addOns.first;
    _qtyController.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _qtyController.dispose();
    _referenceController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final qty = _currentQty;
    final total = qty * _selectedAddOn.price;

    return AlertDialog(
      title: const Text('Tambah Cetak'),
      content: SizedBox(
        width: 460,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              widget.transaction.id,
              style: const TextStyle(
                fontWeight: FontWeight.w700,
                color: Color(0xFF111827),
              ),
            ),
            const SizedBox(height: 4),
            Text(
              widget.transaction.namaPelanggan,
              style: const TextStyle(color: Color(0xFF6B7280)),
            ),
            const SizedBox(height: 18),
            DropdownButtonFormField<AddOnCatalogItem>(
              initialValue: _selectedAddOn,
              decoration: const InputDecoration(
                labelText: 'Item cetak',
                border: OutlineInputBorder(),
              ),
              items: widget.addOns.map((item) {
                final stock = item.effectiveAvailableStock;
                final stockText = stock == null ? '' : ' • stok $stock';

                return DropdownMenuItem(
                  value: item,
                  child: Text(
                    '${item.name} • ${_currency(item.price)}$stockText',
                  ),
                );
              }).toList(),
              onChanged: (item) {
                if (item == null) return;
                setState(() {
                  _selectedAddOn = item;
                  _errorMessage = null;
                });
              },
            ),
            const SizedBox(height: 14),
            TextField(
              controller: _qtyController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Qty',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 14),
            DropdownButtonFormField<String>(
              initialValue: _paymentMethod,
              decoration: const InputDecoration(
                labelText: 'Metode bayar',
                border: OutlineInputBorder(),
              ),
              items: const [
                DropdownMenuItem(value: 'cash', child: Text('Tunai')),
                DropdownMenuItem(value: 'qris', child: Text('QRIS')),
                DropdownMenuItem(value: 'transfer', child: Text('Transfer')),
                DropdownMenuItem(value: 'card', child: Text('Kartu')),
              ],
              onChanged: (value) {
                if (value == null) return;
                setState(() => _paymentMethod = value);
              },
            ),
            const SizedBox(height: 14),
            TextField(
              controller: _referenceController,
              decoration: const InputDecoration(
                labelText: 'Referensi (opsional)',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFE5E7EB)),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Total tambah cetak',
                    style: TextStyle(fontWeight: FontWeight.w700),
                  ),
                  Text(
                    _currency(total),
                    style: const TextStyle(
                      fontWeight: FontWeight.w800,
                      color: Color(0xFF111827),
                    ),
                  ),
                ],
              ),
            ),
            if (_errorMessage != null) ...[
              const SizedBox(height: 12),
              Text(
                _errorMessage!,
                style: const TextStyle(color: Colors.redAccent),
              ),
            ],
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(),
          child: const Text('Batal'),
        ),
        ElevatedButton.icon(
          onPressed: _submit,
          icon: const Icon(Icons.add_circle_outline_rounded, size: 18),
          label: const Text('Simpan & Cetak'),
        ),
      ],
    );
  }

  int get _currentQty => int.tryParse(_qtyController.text.trim()) ?? 0;

  void _submit() {
    final qty = _currentQty;
    final stock = _selectedAddOn.effectiveAvailableStock;

    if (qty <= 0) {
      setState(() => _errorMessage = 'Qty harus lebih dari 0.');
      return;
    }

    if (qty > _selectedAddOn.maxQty) {
      setState(
        () => _errorMessage =
            'Maksimum qty untuk ${_selectedAddOn.name} adalah ${_selectedAddOn.maxQty}.',
      );
      return;
    }

    if (stock != null && qty > stock) {
      setState(() => _errorMessage = 'Stok tidak mencukupi. Tersedia $stock.');
      return;
    }

    Navigator.of(context).pop(
      _ExtraPrintDialogResult(
        addOn: _selectedAddOn,
        qty: qty,
        paymentMethod: _paymentMethod,
        referenceNo: _referenceController.text.trim().isEmpty
            ? null
            : _referenceController.text.trim(),
      ),
    );
  }

  String _currency(double value) {
    final rounded = value.round();
    final chars = rounded.toString().split('').reversed.toList();
    final buffer = StringBuffer();

    for (var i = 0; i < chars.length; i++) {
      if (i > 0 && i % 3 == 0) {
        buffer.write('.');
      }
      buffer.write(chars[i]);
    }

    return 'Rp ${buffer.toString().split('').reversed.join()}';
  }
}
