import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/core/network/request_error_message.dart';
import 'package:desktop_flutter/core/session/api_session.dart';
import 'package:desktop_flutter/features/kasir/services/receipt_printer.dart';
import 'package:desktop_flutter/shared/models/walk_in_request_item.dart';
import 'package:desktop_flutter/features/booking/presentation/widgets/dialogs/payment_confirm_dialog.dart';
import 'package:desktop_flutter/features/booking/presentation/widgets/dialogs/walkin_review_dialog.dart';
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
      final session = await client.fetchCurrentCashierSession();
      final rows = await client.fetchWalkInRequests(
        branchId: session?.branchId,
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

    // Step 1: Review & edit dialog
    final reviewResult = await showDialog<WalkinReviewResult>(
      context: context,
      builder: (context) => WalkinReviewDialog(item: item),
    );

    if (reviewResult == null) return;

    // Step 1.5: If data changed, update via API
    final changed = reviewResult.customerName != item.customerName ||
        reviewResult.customerPhone != item.customerPhone ||
        reviewResult.packageId != item.packageId ||
        reviewResult.addons.any((a) {
          final orig = item.addOns.where((o) => o.addOnId == (a['add_on_id'] as int)).firstOrNull;
          return orig == null || orig.qty != (a['qty'] as int);
        }) ||
        reviewResult.addons.length != item.addOns.length;

    final client = ApiSession.client;

    if (changed && client != null) {
      try {
        final updated = await client.updateWalkInRequest(
          requestId: item.id,
          customerName: reviewResult.customerName,
          customerPhone: reviewResult.customerPhone,
          packageId: reviewResult.packageId,
          addons: reviewResult.addons,
        );

        if (updated != null) {
          item = updated;
        }
      } catch (_) {
        if (mounted) {
          _showSnack('Gagal menyimpan perubahan. Coba lagi.');
        }
        return;
      }
    }

    // Step 2: Payment dialog
    if (!mounted) return;
    final paymentMethod = await showDialog<String>(
      context: context,
      builder: (context) => PaymentConfirmDialog(item: item),
    );

    if (paymentMethod == null) {
      return;
    }

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
        paymentMethod: paymentMethod,
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
    final hasSearchFilter = _searchController.text.trim().isNotEmpty;

    return Container(
      color: const Color(0xFFF8FAFC),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(40, 40, 40, 32),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Premium Solid Blue Header Card
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(28),
              decoration: BoxDecoration(
                color: AppColors.primary,
                borderRadius: BorderRadius.circular(24),
                boxShadow: [
                  BoxShadow(
                    color: AppColors.primary.withValues(alpha: 0.15),
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
                    child: const Icon(Icons.qr_code_scanner_rounded, color: Colors.white, size: 36),
                  ),
                  const SizedBox(width: 20),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'QR Walk-in',
                          style: TextStyle(
                            fontSize: 26,
                            fontWeight: FontWeight.w900,
                            color: Colors.white,
                            letterSpacing: 0.5,
                          ),
                        ),
                        SizedBox(height: 6),
                        Text(
                          'Konfirmasi data customer yang scan QR dan selesaikan pembayaran cash/QRIS untuk membuat nomor antrean.',
                          style: TextStyle(color: Colors.white70, fontSize: 13, height: 1.4),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 20),
                  Material(
                    color: Colors.white.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(16),
                    child: InkWell(
                      onTap: _loading ? null : _loadRows,
                      borderRadius: BorderRadius.circular(16),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: Colors.white.withValues(alpha: 0.15)),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            if (_loading)
                              const SizedBox(
                                width: 16,
                                height: 16,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                                ),
                              )
                            else
                              const Icon(Icons.refresh_rounded, color: Colors.white, size: 18),
                            const SizedBox(width: 8),
                            const Text(
                              'Refresh',
                              style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Search Bar Section
            Row(
              children: [
                Expanded(
                  child: Container(
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(18),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.02),
                          blurRadius: 10,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: TextField(
                      controller: _searchController,
                      style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
                      onChanged: (_) => setState(() {}),
                      decoration: InputDecoration(
                        hintText: 'Cari berdasarkan kode request, nama customer, atau nomor HP...',
                        hintStyle: const TextStyle(color: Colors.grey, fontWeight: FontWeight.normal),
                        prefixIcon: const Icon(Icons.search_rounded, color: Color(0xFF64748B)),
                        suffixIcon: _searchController.text.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.close_rounded, size: 20),
                                onPressed: () {
                                  _searchController.clear();
                                  setState(() {});
                                  _loadRows();
                                },
                              )
                            : null,
                        filled: true,
                        fillColor: Colors.transparent,
                        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 18),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(18),
                          borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                        ),
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(18),
                          borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(18),
                          borderSide: const BorderSide(color: AppColors.primary, width: 2),
                        ),
                      ),
                      onSubmitted: (_) => _loadRows(),
                    ),
                  ),
                ),
                const SizedBox(width: 14),
                Container(
                  height: 56,
                  decoration: BoxDecoration(
                    boxShadow: [
                      BoxShadow(
                        color: AppColors.primary.withValues(alpha: 0.15),
                        blurRadius: 10,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  child: ElevatedButton.icon(
                    onPressed: _loading ? null : _loadRows,
                    icon: const Icon(Icons.search_rounded, size: 18),
                    label: const Text('Cari Data'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.primary,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(horizontal: 28),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(18),
                      ),
                      elevation: 0,
                      textStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                    ),
                  ),
                ),
              ],
            ),
            if (_errorMessage != null) ...[
              const SizedBox(height: 16),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                decoration: BoxDecoration(
                  color: AppColors.error.withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: AppColors.error.withValues(alpha: 0.15)),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.error_outline_rounded, color: AppColors.error, size: 20),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        _errorMessage!,
                        style: const TextStyle(color: AppColors.error, fontSize: 13, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ],
                ),
              ),
            ],
            const SizedBox(height: 24),

            // List Section
            Expanded(
              child: _loading
                  ? const Center(child: CircularProgressIndicator())
                  : _rows.isEmpty
                      ? _EmptyStateWidget(hasFilter: hasSearchFilter)
                      : ListView.separated(
                          itemCount: _rows.length,
                          separatorBuilder: (_, __) => const SizedBox(height: 14),
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
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
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
    final bool isPaid = item.status == 'paid';

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 15,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(20),
        child: IntrinsicHeight(
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                width: 12,
                color: isPaid ? AppColors.success : AppColors.primary,
              ),
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
                  child: Row(
                    children: [
                      // Section 1: Code, Status, and Package/Branch Details
                      Expanded(
                        flex: 5,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Row(
                              children: [
                                const Icon(Icons.confirmation_num_outlined, color: Color(0xFF64748B), size: 16),
                                const SizedBox(width: 6),
                                Text(
                                  item.requestCode,
                                  style: const TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.w900,
                                    color: Color(0xFF1E293B),
                                    letterSpacing: 0.5,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            _StatusBadge(status: item.status, paymentMethod: item.paymentMethod),
                            const SizedBox(height: 12),
                            // Package & Branch metadata rows
                            Row(
                              children: [
                                const Icon(Icons.photo_library_rounded, size: 14, color: AppColors.primary),
                                const SizedBox(width: 6),
                                Expanded(
                                  child: Text(
                                    item.packageName,
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w700,
                                      fontSize: 13,
                                      color: Color(0xFF334155),
                                    ),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                const Icon(Icons.store_rounded, size: 14, color: Color(0xFF64748B)),
                                const SizedBox(width: 6),
                                Expanded(
                                  child: Text(
                                    item.branchName,
                                    style: const TextStyle(
                                      fontSize: 12,
                                      color: Color(0xFF64748B),
                                    ),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      const VerticalDivider(color: Color(0xFFE2E8F0), width: 32, thickness: 1, indent: 4, endIndent: 4),

                      // Section 2: Customer Details
                      Expanded(
                        flex: 4,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(6),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFF1F5F9),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: const Icon(Icons.person_rounded, size: 14, color: Color(0xFF64748B)),
                                ),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: Text(
                                    item.customerName,
                                    style: const TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 14,
                                      color: Color(0xFF0F172A),
                                    ),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 10),
                            Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(6),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFF1F5F9),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: const Icon(Icons.phone_rounded, size: 14, color: Color(0xFF64748B)),
                                ),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: Text(
                                    item.customerPhone,
                                    style: const TextStyle(
                                      color: Color(0xFF64748B),
                                      fontSize: 13,
                                    ),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      const VerticalDivider(color: Color(0xFFE2E8F0), width: 32, thickness: 1, indent: 4, endIndent: 4),

                      // Section 3: Price & Pay Button (Vertically stacked to avoid overflow)
                      Expanded(
                        flex: 4,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Text(
                              'TOTAL TAGIHAN',
                              style: TextStyle(
                                color: Color(0xFF94A3B8),
                                fontSize: 10,
                                fontWeight: FontWeight.bold,
                                letterSpacing: 0.5,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              _currency(item.totalAmount),
                              style: const TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.w900,
                                color: Color(0xFF0F172A),
                              ),
                            ),
                            const SizedBox(height: 12),
                            SizedBox(
                              width: double.infinity,
                              height: 40,
                              child: ElevatedButton(
                                onPressed: item.isPendingPayment && !confirming ? onConfirm : null,
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: AppColors.primary,
                                  foregroundColor: Colors.white,
                                  elevation: 0,
                                  disabledBackgroundColor: const Color(0xFFE2E8F0),
                                  disabledForegroundColor: const Color(0xFF94A3B8),
                                  padding: EdgeInsets.zero,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  textStyle: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 12,
                                  ),
                                ),
                                child: confirming
                                    ? const SizedBox(
                                        width: 16,
                                        height: 16,
                                        child: CircularProgressIndicator(
                                          strokeWidth: 2,
                                          valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                                        ),
                                      )
                                    : Row(
                                        mainAxisAlignment: MainAxisAlignment.center,
                                        children: const [
                                          Icon(Icons.payments_rounded, size: 14),
                                          SizedBox(width: 6),
                                          Text('Bayar & Antrekan'),
                                        ],
                                      ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  const _StatusBadge({required this.status, this.paymentMethod});

  final String status;
  final String? paymentMethod;

  @override
  Widget build(BuildContext context) {
    final paid = status == 'paid';
    final expired = status == 'expired';

    final Color color;
    final Color bgColor;
    final String label;

    if (paid) {
      color = const Color(0xFF0D9488);
      bgColor = const Color(0xFFF0FDFA);
      final String methodDisplay = paymentMethod != null
          ? (paymentMethod!.toLowerCase() == 'qris' ? 'QRIS' : 'Tunai')
          : '';
      label = methodDisplay.isNotEmpty ? 'Sudah Dibayar ($methodDisplay)' : 'Sudah Dibayar';
    } else if (expired) {
      color = const Color(0xFFE11D48);
      bgColor = const Color(0xFFFFF1F2);
      label = 'Kedaluwarsa';
    } else {
      color = const Color(0xFFD97706);
      bgColor = const Color(0xFFFFFBEB);
      label = 'Menunggu Bayar';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(99),
        border: Border.all(color: color.withValues(alpha: 0.15)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 6,
            height: 6,
            decoration: BoxDecoration(
              color: color,
              shape: BoxShape.circle,
            ),
          ),
          const SizedBox(width: 8),
          Text(
            label,
            style: TextStyle(
              color: color,
              fontSize: 12,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _EmptyStateWidget extends StatelessWidget {
  const _EmptyStateWidget({required this.hasFilter});

  final bool hasFilter;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Container(
        padding: const EdgeInsets.all(40),
        constraints: const BoxConstraints(maxWidth: 480),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(28),
              decoration: const BoxDecoration(
                color: Color(0xFFF1F5F9),
                shape: BoxShape.circle,
              ),
              child: Icon(
                hasFilter ? Icons.search_off_rounded : Icons.qr_code_scanner_rounded,
                size: 72,
                color: const Color(0xFF94A3B8),
              ),
            ),
            const SizedBox(height: 24),
            Text(
              hasFilter ? 'Pencarian Tidak Ditemukan' : 'Belum Ada Request Hari Ini',
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w800,
                color: Color(0xFF1E293B),
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 10),
            Text(
              hasFilter
                  ? 'Tidak ada request QR walk-in yang cocok dengan kata kunci Anda. Coba periksa kembali kata kunci pencarian.'
                  : 'Saat customer scan QR dan mengisi form walk-in di website, request mereka akan muncul di sini untuk dikonfirmasi pembayarannya.',
              style: const TextStyle(
                fontSize: 13,
                color: Color(0xFF64748B),
                height: 1.5,
              ),
              textAlign: TextAlign.center,
            ),
          ],
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
