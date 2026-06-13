import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/app/theme/app_text_styles.dart';
import 'package:desktop_flutter/core/network/request_error_message.dart';
import 'package:desktop_flutter/core/session/api_session.dart';
import 'package:desktop_flutter/features/kasir/services/receipt_printer.dart';
import 'package:desktop_flutter/shared/models/branch_option.dart';
import 'package:desktop_flutter/shared/models/cashier_session_item.dart';
import 'package:desktop_flutter/features/kasir/presentation/widgets/dialogs/open_session_dialog.dart';
import 'package:desktop_flutter/features/kasir/presentation/widgets/dialogs/expense_dialog.dart';
import 'package:desktop_flutter/features/kasir/presentation/widgets/dialogs/close_preview_dialog.dart';
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

    final result = await showDialog<OpenSessionResult>(
      context: context,
      builder: (context) => OpenSessionDialog(branches: _branches),
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

    final result = await showDialog<ExpenseResult>(
      context: context,
      builder: (context) => const ExpenseDialog(),
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
      builder: (context) => ClosePreviewDialog(preview: _preview),
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
      return Container(
        color: const Color(0xFFF8FAFC),
        child: const Center(child: CircularProgressIndicator()),
      );
    }

    final session = _session;
    final summary = _mapAt(_preview, 'summary');
    final expenseRows = _listAt(_preview, 'expenses');

    return Container(
      color: const Color(0xFFF8FAFC),
      child: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 1200),
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(40),
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
                        child: const Icon(Icons.point_of_sale_rounded, color: Colors.white, size: 36),
                      ),
                      const SizedBox(width: 20),
                      const Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Sesi Kasir',
                              style: TextStyle(
                                fontSize: 26,
                                fontWeight: FontWeight.w900,
                                color: Colors.white,
                                letterSpacing: 0.5,
                              ),
                            ),
                            SizedBox(height: 6),
                            Text(
                              'Kelola uang laci, pengeluaran cash, dan setoran akhir shift.',
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
                          onTap: _busy ? null : _load,
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
                                if (_busy)
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
                if (_error != null) ...[
                  const SizedBox(height: 20),
                  _InfoBox(message: _error!, color: AppColors.error),
                ],
                const SizedBox(height: 24),
                if (session == null || !session.isOpen)
                  _EmptySessionCard(onOpen: _busy ? null : _openSession)
                else ...[
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // ── LEFT COLUMN: Session Info, Metrics & Actions ─────────────────────────────
                      Expanded(
                        flex: 3,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            _ActiveSessionCard(session: session),
                            const SizedBox(height: 24),
                            
                            // Grid structure for metrics
                            GridView.count(
                              shrinkWrap: true,
                              physics: const NeverScrollableScrollPhysics(),
                              crossAxisCount: 2,
                              mainAxisSpacing: 16,
                              crossAxisSpacing: 16,
                              childAspectRatio: 2.2,
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
                            const SizedBox(height: 28),
                            
                            // Actions
                            Row(
                              children: [
                                Expanded(
                                  child: ElevatedButton.icon(
                                    onPressed: _busy ? null : _addExpense,
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: AppColors.primary,
                                      foregroundColor: Colors.white,
                                      elevation: 0,
                                      padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 18),
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(14),
                                      ),
                                    ),
                                    icon: const Icon(Icons.money_off_rounded),
                                    label: const Text('Input Pengeluaran', style: AppTextStyles.bodyMedium),
                                  ),
                                ),
                                const SizedBox(width: 16),
                                Expanded(
                                  child: OutlinedButton.icon(
                                    onPressed: _busy ? null : _closeSession,
                                    style: OutlinedButton.styleFrom(
                                      foregroundColor: AppColors.primary,
                                      side: const BorderSide(color: AppColors.primary, width: 1.5),
                                      padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 18),
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(14),
                                      ),
                                    ),
                                    icon: const Icon(Icons.receipt_long_rounded),
                                    label: const Text('Tutup Sesi & Print', style: AppTextStyles.bodyMedium),
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 32),
                      
                      // ── RIGHT COLUMN: Expense List ───────────────────────────────
                      Expanded(
                        flex: 2,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Daftar Pengeluaran Cash',
                              style: AppTextStyles.h2,
                            ),
                            const SizedBox(height: 16),
                            Container(
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(20),
                                border: Border.all(color: AppColors.cardBorder),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withValues(alpha: 0.02),
                                    blurRadius: 10,
                                    offset: const Offset(0, 4),
                                  ),
                                ],
                              ),
                              child: expenseRows.isEmpty
                                  ? const Padding(
                                      padding: EdgeInsets.all(48),
                                      child: Center(
                                        child: Column(
                                          mainAxisSize: MainAxisSize.min,
                                          children: [
                                            Icon(
                                              Icons.payments_outlined,
                                              size: 44,
                                              color: AppColors.textMuted,
                                            ),
                                            SizedBox(height: 12),
                                            Text(
                                              'Belum ada pengeluaran cash',
                                              style: TextStyle(
                                                color: AppColors.textSecondary,
                                                fontSize: 13,
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    )
                                  : ListView.separated(
                                      shrinkWrap: true,
                                      physics: const NeverScrollableScrollPhysics(),
                                      itemCount: expenseRows.length,
                                      separatorBuilder: (context, index) =>
                                          const Divider(height: 1, color: AppColors.divider),
                                      itemBuilder: (context, index) {
                                        final row = expenseRows[index] as Map<String, dynamic>;
                                        final title = row['title']?.toString() ?? '-';
                                        final amountText = row['amount_text']?.toString() ?? '-';
                                        final notes = row['notes']?.toString() ??
                                            row['description']?.toString() ??
                                            '';

                                        return Padding(
                                          padding: const EdgeInsets.symmetric(
                                            horizontal: 20,
                                            vertical: 16,
                                          ),
                                          child: Row(
                                            children: [
                                              Container(
                                                padding: const EdgeInsets.all(10),
                                                decoration: BoxDecoration(
                                                  color: AppColors.error.withValues(alpha: 0.08),
                                                  shape: BoxShape.circle,
                                                ),
                                                child: const Icon(
                                                  Icons.arrow_outward_rounded,
                                                  color: AppColors.error,
                                                  size: 20,
                                                ),
                                              ),
                                              const SizedBox(width: 16),
                                              Expanded(
                                                child: Column(
                                                  crossAxisAlignment: CrossAxisAlignment.start,
                                                  children: [
                                                    Text(
                                                      title,
                                                      style: const TextStyle(
                                                        fontFamily: 'Poppins',
                                                        fontSize: 14,
                                                        fontWeight: FontWeight.bold,
                                                        color: AppColors.textPrimary,
                                                      ),
                                                    ),
                                                    if (notes.isNotEmpty) ...[
                                                      const SizedBox(height: 4),
                                                      Text(
                                                        notes,
                                                        style: const TextStyle(
                                                          fontFamily: 'Poppins',
                                                          fontSize: 12,
                                                          color: AppColors.textSecondary,
                                                        ),
                                                      ),
                                                    ],
                                                  ],
                                                ),
                                              ),
                                              Text(
                                                amountText,
                                                style: const TextStyle(
                                                  fontFamily: 'Poppins',
                                                  fontSize: 16,
                                                  fontWeight: FontWeight.w800,
                                                  color: AppColors.error,
                                                ),
                                              ),
                                            ],
                                          ),
                                        );
                                      },
                                    ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ],
            ),
          ),
        ),
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
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.cardBorder),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: const BoxDecoration(
              color: AppColors.primaryLight,
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.lock_open_rounded,
              color: AppColors.primaryDark,
              size: 24,
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Sesi Aktif',
                  style: AppTextStyles.h2,
                ),
                const SizedBox(height: 6),
                Text(
                  '${session.branchName}  |  ${session.businessDate ?? '-'}  |  Uang laci ${_currency(session.openingCash)}',
                  style: AppTextStyles.bodySmall,
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
        color: AppColors.primaryLight,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.primary.withValues(alpha: 0.3)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Belum Ada Sesi Kasir Aktif',
            style: AppTextStyles.h2.copyWith(color: AppColors.primaryDark),
          ),
          const SizedBox(height: 8),
          Text(
            'Buka sesi dan input uang laci sebelum menerima pembayaran.',
            style: AppTextStyles.bodyMedium.copyWith(
              color: AppColors.primaryDark.withValues(alpha: 0.8),
            ),
          ),
          const SizedBox(height: 20),
          ElevatedButton.icon(
            onPressed: onOpen,
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              foregroundColor: Colors.white,
              elevation: 0,
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
            icon: const Icon(Icons.play_arrow_rounded),
            label: const Text('Buka Sesi Kasir', style: AppTextStyles.bodyMedium),
          ),
        ],
      ),
    );
  }
}

class _MetricCard extends StatelessWidget {
  const _MetricCard({
    required this.label,
    required this.value,
    this.highlight = false,
  });

  final String label;
  final String value;
  final bool highlight;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: highlight ? AppColors.primaryLight : Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(
          color: highlight ? AppColors.primary : AppColors.cardBorder,
          width: highlight ? 1.5 : 1,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 8,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Text(
            label,
            style: AppTextStyles.bodySmall.copyWith(
              color:
                  highlight ? AppColors.primaryDark : AppColors.textSecondary,
              fontWeight: highlight ? FontWeight.bold : null,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            value,
            style: AppTextStyles.h1.copyWith(
              color: highlight ? AppColors.primaryDark : AppColors.textPrimary,
              fontSize: 20,
            ),
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

Map<String, dynamic> _mapAt(Map<String, dynamic>? source, String key) {
  final value = source?[key];

  return value is Map<String, dynamic> ? value : <String, dynamic>{};
}

List<dynamic> _listAt(Map<String, dynamic>? source, String key) {
  final value = source?[key];

  return value is List ? value : const [];
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
