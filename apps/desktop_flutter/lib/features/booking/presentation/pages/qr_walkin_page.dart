import 'package:desktop_flutter/core/network/request_error_message.dart';
import 'package:desktop_flutter/core/session/api_session.dart';
import 'package:desktop_flutter/features/kasir/services/receipt_printer.dart';
import 'package:desktop_flutter/shared/models/walk_in_request_item.dart';
import 'package:flutter/material.dart';

class QrWalkinPage extends StatefulWidget {
  const QrWalkinPage({super.key});

  @override
  State<QrWalkinPage> createState() => _QrWalkinPageState();
}

class _QrWalkinPageState extends State<QrWalkinPage> {
  final TextEditingController _searchController = TextEditingController();
  List<WalkInRequestItem> _rows = const [];
  bool _loading = false;
  int? _confirmingId;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _loadRows();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadRows() async {
    final client = ApiSession.client;

    if (client == null) {
      setState(() => _errorMessage = 'Sesi kasir tidak aktif.');
      return;
    }

    setState(() {
      _loading = true;
      _errorMessage = null;
    });

    try {
      final rows = await client.fetchWalkInRequests(
        search: _searchController.text.trim(),
      );

      if (!mounted) return;
      setState(() => _rows = rows);
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _errorMessage = resolveRequestErrorMessage(
          error,
          fallback: 'Daftar QR walk-in belum dapat dimuat.',
        );
      });
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  Future<void> _confirmPayment(WalkInRequestItem item) async {
    if (!item.isPendingPayment || _confirmingId != null) {
      return;
    }

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Konfirmasi Pembayaran Tunai'),
          content: Text(
            'Pastikan ${item.customerName} sudah membayar ${_currency(item.totalAmount)}. Setelah dikonfirmasi, transaksi dan antrean akan dibuat.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: const Text('Batal'),
            ),
            ElevatedButton(
              onPressed: () => Navigator.of(context).pop(true),
              child: const Text('Sudah Bayar'),
            ),
          ],
        );
      },
    );

    if (confirmed != true) {
      return;
    }

    final client = ApiSession.client;

    if (client == null) {
      _showSnack('Sesi kasir tidak aktif.');
      return;
    }

    setState(() {
      _confirmingId = item.id;
      _errorMessage = null;
    });

    try {
      final result = await client.confirmWalkInRequestPayment(
        requestId: item.id,
      );

      if (!mounted) return;

      try {
        await ReceiptPrinter.printTransactionReceipt(
          transaction: result.transaction,
          brandName: 'Ready To Pict',
          branchName: result.transaction.branchName.isNotEmpty
              ? result.transaction.branchName
              : item.branchName,
          cashierName: ApiSession.current?.user.name ?? '-',
          queueCode: result.queueTicket.queueCode,
          receiptTitle: 'STRUK WALK-IN QR',
          paperWidthMm: 80,
        );
      } catch (error) {
        _showSnack(
          'Pembayaran berhasil, tetapi struk belum tercetak: ${resolveRequestErrorMessage(error, fallback: 'Periksa printer.')}',
        );
      }

      _showSnack(
        'Pembayaran ${item.requestCode} berhasil. Antrean ${result.queueTicket.queueCode} dibuat.',
      );
      await _loadRows();
    } catch (error) {
      if (!mounted) return;
      _showSnack(
        resolveRequestErrorMessage(
          error,
          fallback: 'Konfirmasi pembayaran belum berhasil.',
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _confirmingId = null);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      color: const Color(0xFFF8FAFC),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(40, 40, 40, 32),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'QR Walk-in',
                        style: TextStyle(
                          fontSize: 28,
                          fontWeight: FontWeight.w800,
                          color: Color(0xFF111827),
                        ),
                      ),
                      SizedBox(height: 6),
                      Text(
                        'Konfirmasi customer yang scan QR dan bayar tunai di kasir.',
                        style: TextStyle(color: Color(0xFF6B7280)),
                      ),
                    ],
                  ),
                ),
                OutlinedButton.icon(
                  onPressed: _loading ? null : _loadRows,
                  icon: const Icon(Icons.refresh_rounded, size: 18),
                  label: const Text('Refresh'),
                ),
              ],
            ),
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _searchController,
                    decoration: InputDecoration(
                      hintText: 'Cari kode WLK, nama, atau nomor HP',
                      prefixIcon: const Icon(Icons.search_rounded),
                      filled: true,
                      fillColor: Colors.white,
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: const BorderSide(color: Color(0xFFE5E7EB)),
                      ),
                    ),
                    onSubmitted: (_) => _loadRows(),
                  ),
                ),
                const SizedBox(width: 12),
                ElevatedButton.icon(
                  onPressed: _loading ? null : _loadRows,
                  icon: const Icon(Icons.filter_alt_rounded, size: 18),
                  label: const Text('Cari'),
                ),
              ],
            ),
            if (_errorMessage != null) ...[
              const SizedBox(height: 16),
              Text(
                _errorMessage!,
                style: const TextStyle(color: Colors.redAccent),
              ),
            ],
            const SizedBox(height: 20),
            Expanded(
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _rows.isEmpty
                  ? const Center(
                      child: Text(
                        'Belum ada request QR walk-in hari ini.',
                        style: TextStyle(color: Color(0xFF6B7280)),
                      ),
                    )
                  : ListView.separated(
                      itemCount: _rows.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 12),
                      itemBuilder: (context, index) => _WalkInRequestCard(
                        item: _rows[index],
                        confirming: _confirmingId == _rows[index].id,
                        onConfirm: () => _confirmPayment(_rows[index]),
                      ),
                    ),
            ),
          ],
        ),
      ),
    );
  }

  void _showSnack(String message) {
    if (!mounted) return;

    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(message)));
  }
}

class _WalkInRequestCard extends StatelessWidget {
  const _WalkInRequestCard({
    required this.item,
    required this.confirming,
    required this.onConfirm,
  });

  final WalkInRequestItem item;
  final bool confirming;
  final VoidCallback onConfirm;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE5E7EB)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.035),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: [
          Expanded(
            flex: 2,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.requestCode,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w900,
                    color: Color(0xFF111827),
                    letterSpacing: 0.8,
                  ),
                ),
                const SizedBox(height: 6),
                _StatusBadge(status: item.status),
              ],
            ),
          ),
          Expanded(
            flex: 3,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.customerName,
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF111827),
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  item.customerPhone,
                  style: const TextStyle(color: Color(0xFF6B7280)),
                ),
              ],
            ),
          ),
          Expanded(
            flex: 3,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item.packageName,
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF111827),
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  item.branchName,
                  style: const TextStyle(color: Color(0xFF6B7280)),
                ),
              ],
            ),
          ),
          Expanded(
            flex: 2,
            child: Text(
              _currency(item.totalAmount),
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w900,
                color: Color(0xFF111827),
              ),
            ),
          ),
          SizedBox(
            width: 220,
            child: ElevatedButton.icon(
              onPressed: item.isPendingPayment && !confirming
                  ? onConfirm
                  : null,
              icon: confirming
                  ? const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.payments_rounded, size: 18),
              label: Text(confirming ? 'Memproses...' : 'Bayar & Antrekan'),
            ),
          ),
        ],
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  const _StatusBadge({required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final paid = status == 'paid';
    final expired = status == 'expired';
    final color = paid
        ? const Color(0xFF16A34A)
        : expired
        ? const Color(0xFFDC2626)
        : const Color(0xFFD97706);
    final label = paid
        ? 'Sudah Dibayar'
        : expired
        ? 'Expired'
        : 'Menunggu Bayar';

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontSize: 12,
          fontWeight: FontWeight.w800,
        ),
      ),
    );
  }
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
