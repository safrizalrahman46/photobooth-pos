import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/app/theme/app_text_styles.dart';
import 'package:desktop_flutter/features/history/domain/entities/transaction.dart';
import 'package:desktop_flutter/shared/widgets/base_dialog.dart';
import 'package:intl/intl.dart';

class BookingPayoffResult {
  final String paymentMethod;
  final double paidAmount;

  const BookingPayoffResult({
    required this.paymentMethod,
    required this.paidAmount,
  });
}

class BookingPayoffDialog extends StatefulWidget {
  final Transaction transaction;

  const BookingPayoffDialog({super.key, required this.transaction});

  @override
  State<BookingPayoffDialog> createState() => _BookingPayoffDialogState();
}

class _BookingPayoffDialogState extends State<BookingPayoffDialog> {
  String _selectedMethod = 'cash'; // 'cash' or 'qris'
  String _paidAmountString = '0';
  final FocusNode _focusNode = FocusNode();

  double get _paidAmount => double.tryParse(_paidAmountString) ?? 0;
  double get _changeAmount => _paidAmount - widget.transaction.sisaBayar;

  @override
  void initState() {
    super.initState();
    HardwareKeyboard.instance.addHandler(_handleKeyEvent);
  }

  @override
  void dispose() {
    HardwareKeyboard.instance.removeHandler(_handleKeyEvent);
    _focusNode.dispose();
    super.dispose();
  }

  String _formatPrice(double value) {
    final rounded = value.round();
    final formatted = NumberFormat('#,###', 'id_ID').format(rounded);
    return 'Rp $formatted';
  }

  void _onNumberPress(String val) {
    setState(() {
      if (_paidAmountString == '0') {
        _paidAmountString = val;
      } else {
        _paidAmountString += val;
      }
    });
  }

  void _onBackspace() {
    setState(() {
      if (_paidAmountString.length <= 1) {
        _paidAmountString = '0';
      } else {
        _paidAmountString = _paidAmountString.substring(
          0,
          _paidAmountString.length - 1,
        );
      }
    });
  }

  bool _handleKeyEvent(KeyEvent event) {
    if (event is KeyDownEvent) {
      final key = event.logicalKey;

      if (key == LogicalKeyboardKey.digit0 ||
          key == LogicalKeyboardKey.numpad0) {
        _onNumberPress('0');
        return true;
      } else if (key == LogicalKeyboardKey.digit1 ||
          key == LogicalKeyboardKey.numpad1) {
        _onNumberPress('1');
        return true;
      } else if (key == LogicalKeyboardKey.digit2 ||
          key == LogicalKeyboardKey.numpad2) {
        _onNumberPress('2');
        return true;
      } else if (key == LogicalKeyboardKey.digit3 ||
          key == LogicalKeyboardKey.numpad3) {
        _onNumberPress('3');
        return true;
      } else if (key == LogicalKeyboardKey.digit4 ||
          key == LogicalKeyboardKey.numpad4) {
        _onNumberPress('4');
        return true;
      } else if (key == LogicalKeyboardKey.digit5 ||
          key == LogicalKeyboardKey.numpad5) {
        _onNumberPress('5');
        return true;
      } else if (key == LogicalKeyboardKey.digit6 ||
          key == LogicalKeyboardKey.numpad6) {
        _onNumberPress('6');
        return true;
      } else if (key == LogicalKeyboardKey.digit7 ||
          key == LogicalKeyboardKey.numpad7) {
        _onNumberPress('7');
        return true;
      } else if (key == LogicalKeyboardKey.digit8 ||
          key == LogicalKeyboardKey.numpad8) {
        _onNumberPress('8');
        return true;
      } else if (key == LogicalKeyboardKey.digit9 ||
          key == LogicalKeyboardKey.numpad9) {
        _onNumberPress('9');
        return true;
      } else if (key == LogicalKeyboardKey.backspace) {
        _onBackspace();
        return true;
      } else if (key == LogicalKeyboardKey.enter ||
          key == LogicalKeyboardKey.numpadEnter) {
        final isQris = _selectedMethod == 'qris';
        final canConfirm = isQris || _paidAmount >= widget.transaction.sisaBayar;
        if (canConfirm) {
          Navigator.of(context).pop(BookingPayoffResult(
            paymentMethod: _selectedMethod,
            paidAmount: isQris ? widget.transaction.sisaBayar.toDouble() : _paidAmount,
          ));
        }
        return true;
      }
    }
    return false;
  }

  List<double> _getQuickCashSuggestions(double total) {
    final List<double> suggestions = [total];
    
    final List<double> increments = [10000, 20000, 50000, 100000, 200000, 500000];
    for (var inc in increments) {
      if (inc > total && suggestions.length < 4) {
        suggestions.add(inc);
      }
    }
    
    if (suggestions.length < 3) {
      final double next50k = ((total / 50000).ceil() * 50000).toDouble();
      if (!suggestions.contains(next50k)) {
        suggestions.add(next50k);
      }
      final double next100k = ((total / 100000).ceil() * 100000).toDouble();
      if (!suggestions.contains(next100k)) {
        suggestions.add(next100k);
      }
    }
    
    final unique = suggestions.toSet().toList()..sort();
    return unique.take(4).toList();
  }

  void _onQuickCashTap(double val) {
    setState(() {
      _paidAmountString = val.toInt().toString();
    });
  }

  @override
  Widget build(BuildContext context) {
    final isQris = _selectedMethod == 'qris';
    final canConfirm = isQris || _paidAmount >= widget.transaction.sisaBayar;

    return GestureDetector(
      onTap: () {
        _focusNode.requestFocus();
      },
      child: BaseDialog(
        padding: EdgeInsets.zero,
        width: 860,
        maxHeight: 560,
        child: ClipRRect(
          borderRadius: BorderRadius.circular(28),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Left Panel: Details and Totals
              Expanded(
                flex: 4,
                child: Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: AppColors.background,
                    border: Border(
                      right: BorderSide(
                        color: AppColors.cardBorder.withValues(alpha: 0.6),
                      ),
                    ),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Back Arrow and Title
                      Row(
                        children: [
                          Material(
                            color: Colors.white,
                            shape: const CircleBorder(),
                            elevation: 2,
                            shadowColor: Colors.black.withValues(alpha: 0.1),
                            child: IconButton(
                              onPressed: () => Navigator.of(context).pop(null),
                              icon: const Icon(
                                Icons.arrow_back_rounded,
                                color: AppColors.textPrimary,
                                size: 20,
                              ),
                              constraints: const BoxConstraints(
                                minWidth: 40,
                                minHeight: 40,
                              ),
                              padding: EdgeInsets.zero,
                            ),
                          ),
                          const SizedBox(width: 16),
                          const Text('Pelunasan Booking', style: AppTextStyles.h2),
                        ],
                      ),
                      const SizedBox(height: 20),

                      // Info cards
                      _InfoRow(
                        label: 'Pelanggan',
                        value: widget.transaction.namaPelanggan,
                        icon: Icons.person_rounded,
                      ),
                      _InfoRow(
                        label: 'Kode Transaksi',
                        value: widget.transaction.id,
                        icon: Icons.receipt_long_rounded,
                      ),
                      _InfoRow(
                        label: 'Paket / Add-On',
                        value: widget.transaction.paketDanAddOns,
                        icon: Icons.inventory_2_rounded,
                      ),
                      const SizedBox(height: 12),

                      // Payment Method Toggle Selector
                      Container(
                        padding: const EdgeInsets.all(4),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: AppColors.cardBorder.withValues(alpha: 0.6),
                          ),
                        ),
                        child: Row(
                          children: [
                            Expanded(
                              child: _PaymentToggleTab(
                                label: 'TUNAI / CASH',
                                isSelected: !isQris,
                                onTap: () {
                                  setState(() {
                                    _selectedMethod = 'cash';
                                  });
                                  _focusNode.requestFocus();
                                },
                              ),
                            ),
                            Expanded(
                              child: _PaymentToggleTab(
                                label: 'QRIS',
                                isSelected: isQris,
                                onTap: () {
                                  setState(() {
                                    _selectedMethod = 'qris';
                                  });
                                  _focusNode.requestFocus();
                                },
                              ),
                            ),
                          ],
                        ),
                      ),
                      const Spacer(),

                      // Bottom Total & Change Card
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: AppColors.primaryDark,
                          borderRadius: BorderRadius.circular(20),
                          boxShadow: [
                            BoxShadow(
                              color: AppColors.primaryDark.withValues(alpha: 0.3),
                              blurRadius: 12,
                              offset: const Offset(0, 6),
                            ),
                          ],
                        ),
                        child: Column(
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  'SISA PEMBAYARAN',
                                  style: AppTextStyles.caption.copyWith(
                                    color: Colors.white.withValues(alpha: 0.6),
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                                Text(
                                  _formatPrice(widget.transaction.sisaBayar.toDouble()),
                                  style: AppTextStyles.h2White.copyWith(fontSize: 22),
                                ),
                              ],
                            ),
                            if (!isQris) ...[
                              const SizedBox(height: 10),
                              const Divider(color: Colors.white24, height: 1),
                              const SizedBox(height: 10),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    'KEMBALIAN',
                                    style: AppTextStyles.caption.copyWith(
                                      color: Colors.white.withValues(alpha: 0.6),
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                  Text(
                                    _formatPrice(
                                      _changeAmount >= 0 ? _changeAmount : 0,
                                    ),
                                    style: AppTextStyles.h2White.copyWith(
                                      fontSize: 20,
                                      color: _changeAmount >= 0
                                          ? const Color(0xFF4ADE80)
                                          : Colors.white.withValues(alpha: 0.5),
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),

              // Right Panel: Numpad Calculator or QRIS
              Expanded(
                flex: 5,
                child: Container(
                  color: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
                  child: SingleChildScrollView(
                    physics: const BouncingScrollPhysics(),
                    child: isQris
                        ? _buildQrisInfoPanel(context)
                        : _buildCashNumpadPanel(context, canConfirm),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildQrisInfoPanel(BuildContext context) {
    return Column(
      children: [
        const SizedBox(height: 12),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.qr_code_rounded, color: AppColors.primary, size: 20),
            const SizedBox(width: 8),
            Text(
              'PEMBAYARAN QRIS',
              style: AppTextStyles.caption.copyWith(
                fontWeight: FontWeight.bold,
                color: AppColors.textSecondary,
                letterSpacing: 0.8,
              ),
            ),
          ],
        ),
        const SizedBox(height: 24),
        Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: AppColors.primaryLight,
            shape: BoxShape.circle,
            border: Border.all(
              color: AppColors.primary.withValues(alpha: 0.2),
              width: 6,
            ),
          ),
          child: const Icon(
            Icons.qr_code_scanner_rounded,
            color: AppColors.primaryDark,
            size: 56,
          ),
        ),
        const SizedBox(height: 24),
        Text(
          'Tunjukkan QRIS pada Layar Pelanggan',
          style: AppTextStyles.h3.copyWith(
            fontWeight: FontWeight.bold,
            color: AppColors.textPrimary,
          ),
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 8),
        Text(
          'Minta pelanggan memindai kode QRIS dinamis yang muncul di layar monitor luar.',
          style: AppTextStyles.bodySmall.copyWith(
            color: AppColors.textSecondary,
            height: 1.4,
          ),
          textAlign: TextAlign.center,
        ),
        const SizedBox(height: 24),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 20),
          decoration: BoxDecoration(
            color: AppColors.primaryLight.withValues(alpha: 0.4),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: AppColors.primary.withValues(alpha: 0.3),
            ),
          ),
          child: Column(
            children: [
              Text(
                'TOTAL PELUNASAN',
                style: AppTextStyles.caption.copyWith(
                  fontWeight: FontWeight.bold,
                  color: AppColors.primaryDark,
                  letterSpacing: 0.5,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                _formatPrice(widget.transaction.sisaBayar.toDouble()),
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 28,
                  fontWeight: FontWeight.w800,
                  color: AppColors.primaryDark,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 32),
        SizedBox(
          width: double.infinity,
          height: 50,
          child: ElevatedButton(
            onPressed: () => Navigator.of(context).pop(BookingPayoffResult(
              paymentMethod: 'qris',
              paidAmount: widget.transaction.sisaBayar.toDouble(),
            )),
            focusNode: FocusNode(canRequestFocus: false),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.success,
              foregroundColor: Colors.white,
              elevation: 4,
              shadowColor: AppColors.success.withValues(alpha: 0.4),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(14),
              ),
            ),
            child: const Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.check_circle_outline_rounded, size: 18),
                SizedBox(width: 8),
                Text(
                  'KONFIRMASI PELUNASAN SUKSES',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    letterSpacing: 0.5,
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildCashNumpadPanel(BuildContext context, bool canConfirm) {
    return Column(
      children: [
        const SizedBox(height: 4),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          decoration: BoxDecoration(
            color: const Color(0xFFF8FAFC),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: const Color(0xFFE2E8F0)),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Diterima:',
                style: AppTextStyles.bodyMedium.copyWith(
                  fontWeight: FontWeight.w600,
                  color: AppColors.textSecondary,
                ),
              ),
              Text(
                _formatPrice(_paidAmount),
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 20,
                  fontWeight: FontWeight.w800,
                  color: AppColors.textPrimary,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 10),
        
        // Quick cash suggestions
        Wrap(
          spacing: 8,
          runSpacing: 8,
          alignment: WrapAlignment.center,
          children: [
            for (var val in _getQuickCashSuggestions(widget.transaction.sisaBayar.toDouble()))
              InkWell(
                onTap: () => _onQuickCashTap(val),
                borderRadius: BorderRadius.circular(8),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    color: AppColors.primaryLight,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(
                      color: AppColors.primary.withValues(alpha: 0.15),
                    ),
                  ),
                  child: Text(
                    _formatPrice(val),
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 11,
                      fontWeight: FontWeight.bold,
                      color: AppColors.primaryDark,
                    ),
                  ),
                ),
              ),
          ],
        ),
        const SizedBox(height: 12),
        
        // Numpad
        GridView.count(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisCount: 3,
          mainAxisSpacing: 8,
          crossAxisSpacing: 8,
          childAspectRatio: 3.2,
          children: [
            for (var i = 1; i <= 9; i++)
              _NumButton(
                val: '$i',
                onTap: () => _onNumberPress('$i'),
              ),
            _NumButton(
              val: '000',
              onTap: () => _onNumberPress('000'),
            ),
            _NumButton(
              val: '0',
              onTap: () => _onNumberPress('0'),
            ),
            _NumButton(
              val: 'X',
              onTap: _onBackspace,
              isAction: true,
              child: const Icon(
                Icons.backspace_outlined,
                color: AppColors.primaryDark,
                size: 20,
              ),
            ),
          ],
        ),
        const SizedBox(height: 16),
        SizedBox(
          width: double.infinity,
          height: 48,
          child: ElevatedButton(
            onPressed: canConfirm
                ? () => Navigator.of(context).pop(BookingPayoffResult(
                      paymentMethod: 'cash',
                      paidAmount: _paidAmount,
                    ))
                : null,
            focusNode: FocusNode(canRequestFocus: false),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primaryDark,
              foregroundColor: Colors.white,
              disabledBackgroundColor: Colors.grey.shade200,
              disabledForegroundColor: Colors.grey.shade400,
              elevation: canConfirm ? 4 : 0,
              shadowColor: AppColors.primaryDark.withValues(alpha: 0.4),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(14),
              ),
            ),
            child: const Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.print_rounded, size: 18),
                SizedBox(width: 8),
                Text(
                  'KONFIRMASI & CETAK NOTA',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 13,
                    fontWeight: FontWeight.bold,
                    letterSpacing: 0.5,
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;

  const _InfoRow({
    required this.label,
    required this.value,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.cardBorder.withValues(alpha: 0.6)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.01),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: AppColors.primaryLight,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(
              icon,
              size: 16,
              color: AppColors.primaryDark,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label.toUpperCase(),
                  style: AppTextStyles.caption.copyWith(
                    fontWeight: FontWeight.bold,
                    letterSpacing: 0.5,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: AppTextStyles.bodyMedium.copyWith(
                    fontWeight: FontWeight.w600,
                    color: AppColors.textPrimary,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _PaymentToggleTab extends StatelessWidget {
  final String label;
  final bool isSelected;
  final VoidCallback onTap;

  const _PaymentToggleTab({
    required this.label,
    required this.isSelected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.primary : Colors.transparent,
          borderRadius: BorderRadius.circular(8),
          boxShadow: isSelected
              ? [
                  BoxShadow(
                    color: AppColors.primary.withValues(alpha: 0.3),
                    blurRadius: 4,
                    offset: const Offset(0, 2),
                  ),
                ]
              : null,
        ),
        child: Text(
          label,
          style: AppTextStyles.caption.copyWith(
            fontWeight: FontWeight.bold,
            color: isSelected ? Colors.white : AppColors.textSecondary,
          ),
          textAlign: TextAlign.center,
        ),
      ),
    );
  }
}

class _NumButton extends StatelessWidget {
  final String val;
  final VoidCallback onTap;
  final bool isAction;
  final Widget? child;

  const _NumButton({
    required this.val,
    required this.onTap,
    this.isAction = false,
    this.child,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: isAction ? AppColors.primaryLight : Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: isAction
              ? AppColors.primary.withValues(alpha: 0.3)
              : AppColors.cardBorder.withValues(alpha: 0.7),
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(14),
        child: InkWell(
          onTap: onTap,
          canRequestFocus: false,
          borderRadius: BorderRadius.circular(14),
          child: Center(
            child: child ??
                Text(
                  val,
                  style: AppTextStyles.h3.copyWith(
                    color: isAction ? AppColors.primaryDark : AppColors.textPrimary,
                    fontWeight: FontWeight.bold,
                    fontSize: 18,
                  ),
                ),
          ),
        ),
      ),
    );
  }
}
