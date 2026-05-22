import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../../../../app/theme/app_colors.dart';
import '../../../../../app/theme/app_text_styles.dart';
import '../../../application/booking_controller.dart';

class PaymentCalculationDialog extends StatefulWidget {
  final BookingController controller;
  final VoidCallback onConfirm;

  const PaymentCalculationDialog({
    super.key,
    required this.controller,
    required this.onConfirm,
  });

  @override
  State<PaymentCalculationDialog> createState() =>
      _PaymentCalculationDialogState();
}

class _PaymentCalculationDialogState extends State<PaymentCalculationDialog> {
  String _paidAmountString = '0';
  final FocusNode _focusNode = FocusNode();

  double get _paidAmount => double.tryParse(_paidAmountString) ?? 0;
  double get _changeAmount => _paidAmount - widget.controller.grandTotal;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _focusNode.requestFocus();
    });
  }

  @override
  void dispose() {
    _focusNode.dispose();
    super.dispose();
  }

  String _formatPrice(double price) {
    final int p = price.toInt();
    return 'Rp ${p.toString().replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => '.')}';
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

  void _handleKeyEvent(KeyEvent event) {
    if (event is KeyDownEvent) {
      final key = event.logicalKey;

      // Numbers 0-9 and Numpad 0-9
      if (key == LogicalKeyboardKey.digit0 ||
          key == LogicalKeyboardKey.numpad0) {
        _onNumberPress('0');
      } else if (key == LogicalKeyboardKey.digit1 ||
          key == LogicalKeyboardKey.numpad1) {
        _onNumberPress('1');
      } else if (key == LogicalKeyboardKey.digit2 ||
          key == LogicalKeyboardKey.numpad2) {
        _onNumberPress('2');
      } else if (key == LogicalKeyboardKey.digit3 ||
          key == LogicalKeyboardKey.numpad3) {
        _onNumberPress('3');
      } else if (key == LogicalKeyboardKey.digit4 ||
          key == LogicalKeyboardKey.numpad4) {
        _onNumberPress('4');
      } else if (key == LogicalKeyboardKey.digit5 ||
          key == LogicalKeyboardKey.numpad5) {
        _onNumberPress('5');
      } else if (key == LogicalKeyboardKey.digit6 ||
          key == LogicalKeyboardKey.numpad6) {
        _onNumberPress('6');
      } else if (key == LogicalKeyboardKey.digit7 ||
          key == LogicalKeyboardKey.numpad7) {
        _onNumberPress('7');
      } else if (key == LogicalKeyboardKey.digit8 ||
          key == LogicalKeyboardKey.numpad8) {
        _onNumberPress('8');
      } else if (key == LogicalKeyboardKey.digit9 ||
          key == LogicalKeyboardKey.numpad9) {
        _onNumberPress('9');
      } else if (key == LogicalKeyboardKey.backspace) {
        _onBackspace();
      } else if (key == LogicalKeyboardKey.enter ||
          key == LogicalKeyboardKey.numpadEnter) {
        final isQris = widget.controller.selectedPayment == 'QRIS';
        final canConfirm =
            isQris || _paidAmount >= widget.controller.grandTotal;
        if (canConfirm) {
          widget.onConfirm();
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final isQris = widget.controller.selectedPayment == 'QRIS';
    final canConfirm = isQris || _paidAmount >= widget.controller.grandTotal;

    return KeyboardListener(
      focusNode: _focusNode,
      onKeyEvent: _handleKeyEvent,
      child: Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: const EdgeInsets.symmetric(horizontal: 80, vertical: 40),
        child: Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(24),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.2),
                blurRadius: 20,
                offset: const Offset(0, 10),
              ),
            ],
          ),
          child: Row(
            children: [
              // Left: Order Summary Info
              Expanded(
                flex: 2,
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: AppColors.background,
                    borderRadius: const BorderRadius.only(
                      topLeft: Radius.circular(24),
                      bottomLeft: Radius.circular(24),
                    ),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          IconButton(
                            onPressed: () => Navigator.pop(context),
                            icon: const Icon(Icons.arrow_back),
                          ),
                          const SizedBox(width: 8),
                          Text('Detail Pembayaran', style: AppTextStyles.h2),
                        ],
                      ),
                      const SizedBox(height: 12),
                      _InfoRow(
                        label: 'Pelanggan',
                        value: widget.controller.customerName,
                      ),
                      _InfoRow(
                        label: 'Paket',
                        value:
                            '${widget.controller.selectedPackage.name} (${widget.controller.selectedPackage.duration})',
                      ),
                      _InfoRow(
                        label: 'Metode',
                        value: widget.controller.selectedPayment,
                      ),

                      if (widget.controller.selectedAddons.isNotEmpty) ...[
                        const SizedBox(height: 16),
                        Text(
                          'Add-ons:',
                          style: AppTextStyles.bodyMedium.copyWith(
                            color: AppColors.textPrimary,
                            fontWeight: FontWeight.w800,
                            fontSize: 13,
                          ),
                        ),
                        const SizedBox(height: 12),
                        Expanded(
                          child: ListView.builder(
                            shrinkWrap: true,
                            itemCount: widget.controller.selectedAddons.length,
                            itemBuilder: (context, index) {
                              final addon =
                                  widget.controller.selectedAddons[index];
                              return Padding(
                                padding: const EdgeInsets.only(bottom: 8),
                                child: Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Expanded(
                                      child: Text(
                                        addon.name,
                                        style: AppTextStyles.bodySmall.copyWith(
                                          fontSize: 12,
                                          color: AppColors.textPrimary
                                              .withValues(alpha: 0.8),
                                        ),
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                    const SizedBox(width: 8),
                                    Text(
                                      'x${addon.quantity}',
                                      style: AppTextStyles.bodySmall.copyWith(
                                        fontSize: 12,
                                        fontWeight: FontWeight.w700,
                                        color: AppColors.textPrimary,
                                      ),
                                    ),
                                  ],
                                ),
                              );
                            },
                          ),
                        ),
                      ] else
                        const Spacer(),

                      const SizedBox(height: 12),
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: AppColors.cardBorder),
                        ),
                        child: Column(
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  'Total Tagihan',
                                  style: AppTextStyles.bodyMedium,
                                ),
                                Text(
                                  _formatPrice(widget.controller.grandTotal),
                                  style: AppTextStyles.h3.copyWith(
                                    color: AppColors.primary,
                                  ),
                                ),
                              ],
                            ),
                            if (!isQris) ...[
                              const SizedBox(height: 16),
                              const Divider(),
                              const SizedBox(height: 16),
                              Row(
                                mainAxisAlignment:
                                    MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    'Kembalian',
                                    style: AppTextStyles.bodyMedium,
                                  ),
                                  Text(
                                    _formatPrice(
                                      _changeAmount < 0 ? 0 : _changeAmount,
                                    ),
                                    style: AppTextStyles.h3.copyWith(
                                      color: _changeAmount >= 0
                                          ? Colors.green
                                          : Colors.grey,
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

              // Right: Numpad & Input
              Expanded(
                flex: 3,
                child: Container(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      Text('Uang Pembayaran', style: AppTextStyles.h3),
                      const SizedBox(height: 12),
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(vertical: 12),
                        decoration: BoxDecoration(
                          color: AppColors.background,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                            color: AppColors.primary,
                            width: 2,
                          ),
                        ),
                        child: Text(
                          _formatPrice(_paidAmount),
                          style: AppTextStyles.h2.copyWith(
                            color: AppColors.primary,
                          ),
                          textAlign: TextAlign.center,
                        ),
                      ),
                      const SizedBox(height: 12),
                      Expanded(
                        child: GridView.count(
                          crossAxisCount: 3,
                          mainAxisSpacing: 8,
                          crossAxisSpacing: 8,
                          childAspectRatio: 2.8,
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
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 12),
                      SizedBox(
                        width: double.infinity,
                        height: 44,
                        child: ElevatedButton(
                          onPressed: canConfirm ? widget.onConfirm : null,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.primary,
                            foregroundColor: Colors.white,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16),
                            ),
                            disabledBackgroundColor: Colors.grey.shade300,
                          ),
                          child: Text(
                            'KONFIRMASI & CETAK',
                            style: AppTextStyles.h4.copyWith(
                              color: Colors.white,
                            ),
                          ),
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

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;

  const _InfoRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: AppTextStyles.bodySmall.copyWith(
              color: AppColors.textSecondary,
            ),
          ),
          Text(
            value,
            style: AppTextStyles.bodyMedium.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }
}

class _NumButton extends StatelessWidget {
  final String val;
  final VoidCallback onTap;
  final bool isAction;

  const _NumButton({
    required this.val,
    required this.onTap,
    this.isAction = false,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: isAction ? Colors.orange : Colors.grey.shade100,
      borderRadius: BorderRadius.circular(12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Center(
          child: Text(
            val,
            style: AppTextStyles.h3.copyWith(
              color: isAction ? Colors.white : AppColors.textPrimary,
            ),
          ),
        ),
      ),
    );
  }
}
