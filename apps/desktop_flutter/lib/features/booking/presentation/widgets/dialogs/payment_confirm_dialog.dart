import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/app/theme/app_text_styles.dart';
import 'package:desktop_flutter/shared/models/walk_in_request_item.dart';
import 'package:desktop_flutter/shared/widgets/base_dialog.dart';

class PaymentConfirmDialog extends StatefulWidget {
  final WalkInRequestItem item;

  const PaymentConfirmDialog({super.key, required this.item});

  @override
  State<PaymentConfirmDialog> createState() => _PaymentConfirmDialogState();
}

class _PaymentConfirmDialogState extends State<PaymentConfirmDialog> {
  String _selectedMethod = 'cash'; // 'cash' or 'qris'
  String _paidAmountString = '0';
  final FocusNode _focusNode = FocusNode();

  double get _paidAmount => double.tryParse(_paidAmountString) ?? 0;
  double get _changeAmount => _paidAmount - widget.item.totalAmount;

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
        final canConfirm = isQris || _paidAmount >= widget.item.totalAmount;
        if (canConfirm) {
          Navigator.of(context).pop(_selectedMethod);
        }
        return true;
      }
    }
    return false;
  }

  List<double> _getQuickCashSuggestions(double total) {
    final List<double> suggestions = [total];
    
    // Find next clean increments
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
    final canConfirm = isQris || _paidAmount >= widget.item.totalAmount;

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
                            const Text('Detail Pembayaran', style: AppTextStyles.h2),
                          ],
                        ),
                        const SizedBox(height: 20),

                        // Info cards
                        _InfoRow(
                          label: 'Pelanggan',
                          value: widget.item.customerName,
                          icon: Icons.person_rounded,
                        ),
                        _InfoRow(
                          label: 'Kode Booking',
                          value: widget.item.requestCode,
                          icon: Icons.qr_code_rounded,
                        ),
                        _InfoRow(
                          label: 'Paket',
                          value: widget.item.packageName,
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
                            gradient: const LinearGradient(
                              colors: [
                                AppColors.primary,
                                AppColors.primaryDark,
                              ],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            borderRadius: BorderRadius.circular(16),
                            boxShadow: [
                              BoxShadow(
                                color: AppColors.primary.withValues(alpha: 0.3),
                                blurRadius: 10,
                                offset: const Offset(0, 4),
                              ),
                            ],
                          ),
                          child: Column(
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    'Total Tagihan',
                                    style: AppTextStyles.bodyMedium.copyWith(
                                      color: Colors.white.withValues(alpha: 0.9),
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                  Text(
                                    _formatPrice(widget.item.totalAmount),
                                    style: AppTextStyles.h2White.copyWith(
                                      fontSize: 20,
                                    ),
                                  ),
                                ],
                              ),
                              if (!isQris) ...[
                                const SizedBox(height: 12),
                                Divider(
                                  height: 1,
                                  color: Colors.white.withValues(alpha: 0.2),
                                ),
                                const SizedBox(height: 12),
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(
                                      'Kembalian',
                                      style: AppTextStyles.bodyMedium.copyWith(
                                        color: Colors.white.withValues(alpha: 0.9),
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                    Text(
                                      _formatPrice(
                                        _changeAmount < 0 ? 0 : _changeAmount,
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
        // QRIS Icon with border glow look
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
        // Amount card
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
                'TOTAL PEMBAYARAN',
                style: AppTextStyles.caption.copyWith(
                  fontWeight: FontWeight.bold,
                  color: AppColors.primaryDark,
                  letterSpacing: 0.5,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                _formatPrice(widget.item.totalAmount),
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
            onPressed: () => Navigator.of(context).pop(_selectedMethod),
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
                  'KONFIRMASI PEMBAYARAN SUKSES',
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
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.calculate_rounded, color: AppColors.primary, size: 20),
            const SizedBox(width: 8),
            Text(
              'INPUT PEMBAYARAN TUNAI',
              style: AppTextStyles.caption.copyWith(
                fontWeight: FontWeight.bold,
                color: AppColors.textSecondary,
                letterSpacing: 0.8,
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        // Amount display box
        Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: AppColors.primary.withValues(alpha: 0.8),
              width: 2,
            ),
            boxShadow: [
              BoxShadow(
                color: AppColors.primary.withValues(alpha: 0.08),
                blurRadius: 8,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Column(
            children: [
              Text(
                'NOMINAL DITERIMA',
                style: AppTextStyles.caption.copyWith(
                  fontWeight: FontWeight.bold,
                  color: AppColors.textSecondary,
                  letterSpacing: 0.5,
                ),
              ),
              const SizedBox(height: 4),
              FittedBox(
                fit: BoxFit.scaleDown,
                child: Text(
                  _formatPrice(_paidAmount),
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 28,
                    fontWeight: FontWeight.w800,
                    color: _paidAmount > 0 ? AppColors.primaryDark : AppColors.textMuted,
                  ),
                  textAlign: TextAlign.center,
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 10),
        // Quick Cash Shortcuts
        SizedBox(
          height: 38,
          child: ListView(
            scrollDirection: Axis.horizontal,
            shrinkWrap: true,
            children: _getQuickCashSuggestions(widget.item.totalAmount).map((val) {
              final isExact = val == widget.item.totalAmount;
              final label = isExact ? 'Uang Pas' : _formatPrice(val);
              final isSelected = _paidAmount == val;
              return Container(
                margin: const EdgeInsets.only(right: 8),
                child: Material(
                  color: isSelected ? AppColors.primary : AppColors.primaryLight,
                  borderRadius: BorderRadius.circular(20),
                  child: InkWell(
                    borderRadius: BorderRadius.circular(20),
                    onTap: () => _onQuickCashTap(val),
                    canRequestFocus: false,
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      child: Center(
                        child: Text(
                          label,
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                            color: isSelected ? Colors.white : AppColors.primaryDark,
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
              );
            }).toList(),
          ),
        ),
        const SizedBox(height: 10),
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
        const SizedBox(height: 12),
        SizedBox(
          width: double.infinity,
          height: 48,
          child: ElevatedButton(
            onPressed: canConfirm ? () => Navigator.of(context).pop(_selectedMethod) : null,
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
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
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
