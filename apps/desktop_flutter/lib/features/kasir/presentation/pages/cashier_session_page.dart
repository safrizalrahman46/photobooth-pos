import 'package:desktop_flutter/core/network/request_error_message.dart';
import 'package:desktop_flutter/core/session/api_session.dart';
import 'package:desktop_flutter/features/kasir/services/receipt_printer.dart';
import 'package:desktop_flutter/shared/models/branch_option.dart';
import 'package:desktop_flutter/shared/models/cashier_session_item.dart';
import 'package:flutter/material.dart';

class CashierSessionPage extends StatefulWidget {
  const CashierSessionPage({super.key});

  @override
  State<CashierSessionPage> createState() => _CashierSessionPageState();
}

class _CashierSessionPageState extends State<CashierSessionPage> {
  CashierSessionItem? _session;
  List<BranchOption> _branches = const <BranchOption>[];
  Map<String, dynamic>? _preview;
  bool _loading = true;
  bool _busy = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final client = ApiSession.client;

    if (client == null) {
      setState(() {
        _loading = false;
        _error = 'Sesi login tidak ditemukan.';
      });
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final rows = await client.fetchBranches();
      final session = await client.fetchCurrentCashierSession();
      Map<String, dynamic>? preview;

      if (session != null && session.isOpen) {
        preview = await client.fetchCashierSettlementPreview(
          sessionId: session.id,
        );
      }

      if (!mounted) return;
      setState(() {
        _branches = rows;
        _session = session;
        _preview = preview;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _error = resolveRequestErrorMessage(
          error,
          fallback: 'Data sesi kasir belum dapat dimuat.',
        );
      });
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _openSession() async {
    final client = ApiSession.client;

    if (client == null) return;

    if (_branches.isEmpty) {
      _showSnack('Cabang aktif belum tersedia.');
      return;
    }

    final result = await showDialog<_OpenSessionResult>(
      context: context,
      builder: (context) => _OpenSessionDialog(branches: _branches),
    );

    if (result == null) return;

    setState(() => _busy = true);

    try {
      await client.openCashierSession(
        branchId: result.branchId,
        openingCash: result.openingCash,
        notes: result.notes,
      );
      await _load();
      _showSnack('Sesi kasir berhasil dibuka.');
    } catch (error) {
      _showSnack(
        resolveRequestErrorMessage(
          error,
          fallback: 'Sesi kasir belum dapat dibuka.',
        ),
      );
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _addExpense() async {
    final session = _session;
    final client = ApiSession.client;

    if (session == null || client == null) return;

    final result = await showDialog<_ExpenseResult>(
      context: context,
      builder: (context) => const _ExpenseDialog(),
    );

    if (result == null) return;

    setState(() => _busy = true);

    try {
      await client.createCashExpense(
        sessionId: session.id,
        amount: result.amount,
        title: result.title,
        notes: result.notes,
      );
      await _load();
      _showSnack('Pengeluaran cash berhasil dicatat.');
    } catch (error) {
      _showSnack(
        resolveRequestErrorMessage(
          error,
          fallback: 'Pengeluaran cash belum dapat dicatat.',
        ),
      );
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _closeSession() async {
    final session = _session;
    final client = ApiSession.client;

    if (session == null || client == null) return;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => _ClosePreviewDialog(preview: _preview),
    );

    if (confirmed != true) return;

    setState(() => _busy = true);

    try {
      final result = await client.closeCashierSessionWithSettlement(
        sessionId: session.id,
      );
      final printed = await client.markCashierSettlementPrinted(
        settlementId: result.settlement.id,
      );
      await ReceiptPrinter.printCashierSettlementReceipt(
        settlement: printed,
        paperWidthMm: 80,
      );
      await _load();
      _showSnack('Sesi ditutup dan struk setoran siap dicetak.');
    } catch (error) {
      _showSnack(
        resolveRequestErrorMessage(
          error,
          fallback: 'Sesi kasir belum dapat ditutup.',
        ),
      );
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }

    final session = _session;
    final summary = _mapAt(_preview, 'summary');

    return SingleChildScrollView(
      padding: const EdgeInsets.all(40),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Sesi Kasir',
                    style: TextStyle(fontSize: 28, fontWeight: FontWeight.w800),
                  ),
                  SizedBox(height: 6),
                  Text(
                    'Kelola uang laci, pengeluaran cash, dan setoran akhir shift.',
                    style: TextStyle(color: Color(0xFF64748B)),
                  ),
                ],
              ),
              FilledButton.icon(
                onPressed: _busy ? null : _load,
                icon: const Icon(Icons.refresh_rounded),
                label: const Text('Refresh'),
              ),
            ],
          ),
          if (_error != null) ...[
            const SizedBox(height: 20),
            _InfoBox(message: _error!, color: const Color(0xFFDC2626)),
          ],
          const SizedBox(height: 24),
          if (session == null || !session.isOpen)
            _EmptySessionCard(onOpen: _busy ? null : _openSession)
          else ...[
            _ActiveSessionCard(session: session),
            const SizedBox(height: 20),
            Wrap(
              spacing: 14,
              runSpacing: 14,
              children: [
                _MetricCard(
                  label: 'Total Penjualan',
                  value: _stringAt(summary, 'total_sales_text'),
                ),
                _MetricCard(
                  label: 'Cash Diterima',
                  value: _stringAt(summary, 'cash_received_text'),
                ),
                _MetricCard(
                  label: 'Non Cash',
                  value: _stringAt(summary, 'non_cash_received_text'),
                ),
                _MetricCard(
                  label: 'JML. Disetor Cash',
                  value: _stringAt(summary, 'cash_to_deposit_text'),
                  highlight: true,
                ),
              ],
            ),
            const SizedBox(height: 24),
            Row(
              children: [
                FilledButton.icon(
                  onPressed: _busy ? null : _addExpense,
                  icon: const Icon(Icons.money_off_rounded),
                  label: const Text('Input Pengeluaran'),
                ),
                const SizedBox(width: 12),
                OutlinedButton.icon(
                  onPressed: _busy ? null : _closeSession,
                  icon: const Icon(Icons.receipt_long_rounded),
                  label: const Text('Tutup Sesi & Print'),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  void _showSnack(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
  }
}

class _ActiveSessionCard extends StatelessWidget {
  const _ActiveSessionCard({required this.session});

  final CashierSessionItem session;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Row(
        children: [
          const Icon(Icons.lock_open_rounded, color: Color(0xFF059669), size: 32),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Sesi aktif',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 6),
                Text(
                  '${session.branchName} | ${session.businessDate ?? '-'} | Uang laci ${_currency(session.openingCash)}',
                  style: const TextStyle(color: Color(0xFF64748B)),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _EmptySessionCard extends StatelessWidget {
  const _EmptySessionCard({required this.onOpen});

  final VoidCallback? onOpen;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(28),
      decoration: BoxDecoration(
        color: const Color(0xFFFFFBEB),
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: const Color(0xFFFDE68A)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Belum ada sesi kasir aktif',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 8),
          const Text(
            'Buka sesi dan input uang laci sebelum menerima pembayaran.',
            style: TextStyle(color: Color(0xFF92400E)),
          ),
          const SizedBox(height: 20),
          FilledButton.icon(
            onPressed: onOpen,
            icon: const Icon(Icons.play_arrow_rounded),
            label: const Text('Buka Sesi Kasir'),
          ),
        ],
      ),
    );
  }
}

class _MetricCard extends StatelessWidget {
  const _MetricCard({required this.label, required this.value, this.highlight = false});

  final String label;
  final String value;
  final bool highlight;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 230,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: highlight ? const Color(0xFFECFDF5) : Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: highlight ? const Color(0xFFA7F3D0) : const Color(0xFFE2E8F0),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(color: Color(0xFF64748B))),
          const SizedBox(height: 8),
          Text(
            value,
            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900),
          ),
        ],
      ),
    );
  }
}

class _InfoBox extends StatelessWidget {
  const _InfoBox({required this.message, required this.color});

  final String message;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: color.withValues(alpha: 0.25)),
      ),
      child: Text(message, style: TextStyle(color: color)),
    );
  }
}

class _OpenSessionDialog extends StatefulWidget {
  const _OpenSessionDialog({required this.branches});

  final List<BranchOption> branches;

  @override
  State<_OpenSessionDialog> createState() => _OpenSessionDialogState();
}

class _OpenSessionDialogState extends State<_OpenSessionDialog> {
  late int _branchId = widget.branches.first.id;
  final _cashController = TextEditingController(text: '100000');
  final _notesController = TextEditingController();

  @override
  void dispose() {
    _cashController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Buka Sesi Kasir'),
      content: SizedBox(
        width: 420,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            DropdownButtonFormField<int>(
              initialValue: _branchId,
              decoration: const InputDecoration(labelText: 'Cabang'),
              items: widget.branches
                  .map((branch) => DropdownMenuItem(value: branch.id, child: Text(branch.name)))
                  .toList(),
              onChanged: (value) {
                if (value != null) setState(() => _branchId = value);
              },
            ),
            TextField(
              controller: _cashController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(labelText: 'Uang laci awal'),
            ),
            TextField(
              controller: _notesController,
              decoration: const InputDecoration(labelText: 'Catatan (opsional)'),
            ),
          ],
        ),
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text('Batal')),
        FilledButton(
          onPressed: () {
            final amount = double.tryParse(_cashController.text.replaceAll('.', '')) ?? 0;
            Navigator.pop(
              context,
              _OpenSessionResult(
                branchId: _branchId,
                openingCash: amount,
                notes: _notesController.text.trim(),
              ),
            );
          },
          child: const Text('Buka Sesi'),
        ),
      ],
    );
  }
}

class _ExpenseDialog extends StatefulWidget {
  const _ExpenseDialog();

  @override
  State<_ExpenseDialog> createState() => _ExpenseDialogState();
}

class _ExpenseDialogState extends State<_ExpenseDialog> {
  final _titleController = TextEditingController();
  final _amountController = TextEditingController();
  final _notesController = TextEditingController();

  @override
  void dispose() {
    _titleController.dispose();
    _amountController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: const Text('Input Pengeluaran Cash'),
      content: SizedBox(
        width: 420,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(controller: _titleController, decoration: const InputDecoration(labelText: 'Judul')),
            TextField(
              controller: _amountController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(labelText: 'Nominal'),
            ),
            TextField(controller: _notesController, decoration: const InputDecoration(labelText: 'Catatan')),
          ],
        ),
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context), child: const Text('Batal')),
        FilledButton(
          onPressed: () {
            final amount = double.tryParse(_amountController.text.replaceAll('.', '')) ?? 0;
            final title = _titleController.text.trim();

            if (amount <= 0 || title.isEmpty) return;
            Navigator.pop(
              context,
              _ExpenseResult(amount: amount, title: title, notes: _notesController.text.trim()),
            );
          },
          child: const Text('Simpan'),
        ),
      ],
    );
  }
}

class _ClosePreviewDialog extends StatelessWidget {
  const _ClosePreviewDialog({required this.preview});

  final Map<String, dynamic>? preview;

  @override
  Widget build(BuildContext context) {
    final summary = _mapAt(preview, 'summary');

    return AlertDialog(
      title: const Text('Tutup Sesi Kasir'),
      content: SizedBox(
        width: 460,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            _PreviewLine(label: 'Total Penjualan', value: _stringAt(summary, 'total_sales_text')),
            _PreviewLine(label: 'Cash Diterima', value: _stringAt(summary, 'cash_received_text')),
            _PreviewLine(label: 'Non Cash', value: _stringAt(summary, 'non_cash_received_text')),
            _PreviewLine(label: 'Pengeluaran', value: _stringAt(summary, 'cash_expenses_total_text')),
            const Divider(),
            _PreviewLine(label: 'JML. DISETOR CASH', value: _stringAt(summary, 'cash_to_deposit_text'), bold: true),
            _PreviewLine(label: 'Uang Laci Disisakan', value: _stringAt(summary, 'opening_cash_text')),
          ],
        ),
      ),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Batal')),
        FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Tutup & Print')),
      ],
    );
  }
}

class _PreviewLine extends StatelessWidget {
  const _PreviewLine({required this.label, required this.value, this.bold = false});

  final String label;
  final String value;
  final bool bold;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label),
          Text(value, style: TextStyle(fontWeight: bold ? FontWeight.w900 : FontWeight.w600)),
        ],
      ),
    );
  }
}

class _OpenSessionResult {
  const _OpenSessionResult({required this.branchId, required this.openingCash, required this.notes});

  final int branchId;
  final double openingCash;
  final String notes;
}

class _ExpenseResult {
  const _ExpenseResult({required this.amount, required this.title, required this.notes});

  final double amount;
  final String title;
  final String notes;
}

Map<String, dynamic> _mapAt(Map<String, dynamic>? source, String key) {
  final value = source?[key];

  return value is Map<String, dynamic> ? value : <String, dynamic>{};
}

String _stringAt(Map<String, dynamic> source, String key) {
  return source[key]?.toString() ?? '-';
}

String _currency(double value) {
  final rounded = value.round().toString();
  final buffer = StringBuffer();

  for (var i = 0; i < rounded.length; i++) {
    final reverseIndex = rounded.length - i;
    buffer.write(rounded[i]);
    if (reverseIndex > 1 && reverseIndex % 3 == 1) {
      buffer.write('.');
    }
  }

  return 'Rp $buffer';
}
