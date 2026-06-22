import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/app/theme/app_text_styles.dart';
import 'package:desktop_flutter/features/booking/domain/entities/booking.dart';
import 'package:desktop_flutter/shared/widgets/base_dialog.dart';
import 'package:desktop_flutter/shared/widgets/dialog_action_button.dart';
import 'package:flutter/material.dart';

class ConfirmPaymentDialogResult {
  final String method;
  final double amount;
  final String? referenceNo;
  final String? notes;

  ConfirmPaymentDialogResult({
    required this.method,
    required this.amount,
    this.referenceNo,
    this.notes,
  });
}

class ConfirmPaymentDialog extends StatefulWidget {
  final Booking booking;

  const ConfirmPaymentDialog({
    super.key,
    required this.booking,
  });

  @override
  State<ConfirmPaymentDialog> createState() => _ConfirmPaymentDialogState();
}

class _ConfirmPaymentDialogState extends State<ConfirmPaymentDialog> {
  String _selectedMethod = 'cash';
  String _selectedPaymentType = 'full';
  final _amountController = TextEditingController();
  final _referenceNoController = TextEditingController();
  final _notesController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _updateAmount();
  }

  @override
  void dispose() {
    _amountController.dispose();
    _referenceNoController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  void _updateAmount() {
    double targetAmount;
    if (_selectedPaymentType == 'dp50') {
      targetAmount = widget.booking.depositAmount > 0 
          ? widget.booking.depositAmount 
          : widget.booking.totalAmount * 0.5;
    } else {
      targetAmount = widget.booking.totalAmount;
    }
    _amountController.text = targetAmount.toInt().toString();
  }

  String _formatCurrency(double amount) {
    final int p = amount.toInt();
    return 'Rp ${p.toString().replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => '.')}';
  }

  @override
  Widget build(BuildContext context) {
    return BaseDialog(
      width: 500,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Confirm Payment - ${widget.booking.id}',
                style: AppTextStyles.h2.copyWith(fontSize: 16),
              ),
            ],
          ),
          const SizedBox(height: 20),

          // Method & Payment Type
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Method', style: AppTextStyles.bodySmall),
                    const SizedBox(height: 4),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12),
                      decoration: BoxDecoration(
                        border: Border.all(color: AppColors.cardBorder),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: DropdownButtonHideUnderline(
                        child: DropdownButton<String>(
                          value: _selectedMethod,
                          isExpanded: true,
                          items: const [
                            DropdownMenuItem(value: 'cash', child: Text('CASH')),
                            DropdownMenuItem(value: 'transfer', child: Text('TRANSFER')),
                            DropdownMenuItem(value: 'qris', child: Text('QRIS')),
                            DropdownMenuItem(value: 'edc', child: Text('EDC')),
                          ],
                          onChanged: (value) {
                            if (value != null) {
                              setState(() => _selectedMethod = value);
                            }
                          },
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Payment Type', style: AppTextStyles.bodySmall),
                    const SizedBox(height: 4),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12),
                      decoration: BoxDecoration(
                        border: Border.all(color: AppColors.cardBorder),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: DropdownButtonHideUnderline(
                        child: DropdownButton<String>(
                          value: _selectedPaymentType,
                          isExpanded: true,
                          items: const [
                            DropdownMenuItem(value: 'full', child: Text('Full Payment')),
                            DropdownMenuItem(value: 'dp50', child: Text('DP 50%')),
                          ],
                          onChanged: (value) {
                            if (value != null) {
                              setState(() {
                                _selectedPaymentType = value;
                                _updateAmount();
                              });
                            }
                          },
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Amount
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Amount', style: AppTextStyles.bodySmall),
              const SizedBox(height: 4),
              TextField(
                controller: _amountController,
                keyboardType: TextInputType.number,
                decoration: InputDecoration(
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(8),
                    borderSide: BorderSide(color: AppColors.cardBorder),
                  ),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'DP 50%: ${_formatCurrency(widget.booking.depositAmount > 0 ? widget.booking.depositAmount : widget.booking.totalAmount * 0.5)} | Remaining: ${_formatCurrency(widget.booking.totalAmount)}',
                style: AppTextStyles.caption.copyWith(color: AppColors.textMuted),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Reference Number
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Reference Number', style: AppTextStyles.bodySmall),
              const SizedBox(height: 4),
              TextField(
                controller: _referenceNoController,
                decoration: InputDecoration(
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(8),
                    borderSide: BorderSide(color: AppColors.cardBorder),
                  ),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Notes
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Notes', style: AppTextStyles.bodySmall),
              const SizedBox(height: 4),
              TextField(
                controller: _notesController,
                maxLines: 3,
                decoration: InputDecoration(
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(8),
                    borderSide: BorderSide(color: AppColors.cardBorder),
                  ),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // Action Buttons
          Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              DialogActionButton(
                label: 'Cancel',
                onPressed: () => Navigator.pop(context),
              ),
              const SizedBox(width: 8),
              DialogActionButton(
                label: 'Konfirmasi Pembayaran',
                primary: true,
                color: AppColors.primary,
                onPressed: () {
                  final amount = double.tryParse(_amountController.text) ?? 0;
                  Navigator.pop(
                    context,
                    ConfirmPaymentDialogResult(
                      method: _selectedMethod,
                      amount: amount,
                      referenceNo: _referenceNoController.text.trim(),
                      notes: _notesController.text.trim(),
                    ),
                  );
                },
              ),
            ],
          ),
        ],
      ),
    );
  }
}
