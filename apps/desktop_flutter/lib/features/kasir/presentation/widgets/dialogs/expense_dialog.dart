import 'package:flutter/material.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/app/theme/app_text_styles.dart';
import 'package:desktop_flutter/shared/widgets/base_dialog.dart';

class ExpenseResult {
  const ExpenseResult({
    required this.amount,
    required this.title,
    required this.notes,
  });

  final double amount;
  final String title;
  final String notes;
}

class ExpenseDialog extends StatefulWidget {
  const ExpenseDialog({super.key});

  @override
  State<ExpenseDialog> createState() => _ExpenseDialogState();
}

class _ExpenseDialogState extends State<ExpenseDialog> {
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
    return BaseDialog(
      width: 420,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Input Pengeluaran Cash', style: AppTextStyles.h2),
          const SizedBox(height: 20),
          TextField(
            controller: _titleController,
            decoration: const InputDecoration(
              labelText: 'Judul',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 14),
          TextField(
            controller: _amountController,
            keyboardType: TextInputType.number,
            decoration: const InputDecoration(
              labelText: 'Nominal',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 14),
          TextField(
            controller: _notesController,
            decoration: const InputDecoration(
              labelText: 'Catatan',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 24),
          Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              OutlinedButton(
                onPressed: () => Navigator.pop(context),
                style: OutlinedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 20,
                    vertical: 14,
                  ),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: const Text('Batal'),
              ),
              const SizedBox(width: 12),
              ElevatedButton(
                onPressed: () {
                  final amount = double.tryParse(
                        _amountController.text.replaceAll('.', ''),
                      ) ??
                      0;
                  final title = _titleController.text.trim();

                  if (amount <= 0 || title.isEmpty) return;
                  Navigator.pop(
                    context,
                    ExpenseResult(
                      amount: amount,
                      title: title,
                      notes: _notesController.text.trim(),
                    ),
                  );
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  foregroundColor: Colors.white,
                  elevation: 0,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 24,
                    vertical: 14,
                  ),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: const Text('Simpan'),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
