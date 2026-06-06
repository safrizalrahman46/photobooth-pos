import 'package:flutter/material.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/app/theme/app_text_styles.dart';
import 'package:desktop_flutter/shared/models/branch_option.dart';
import 'package:desktop_flutter/shared/widgets/base_dialog.dart';

class OpenSessionResult {
  const OpenSessionResult({
    required this.branchId,
    required this.openingCash,
    required this.notes,
  });

  final int branchId;
  final double openingCash;
  final String notes;
}

class OpenSessionDialog extends StatefulWidget {
  const OpenSessionDialog({super.key, required this.branches});

  final List<BranchOption> branches;

  @override
  State<OpenSessionDialog> createState() => _OpenSessionDialogState();
}

class _OpenSessionDialogState extends State<OpenSessionDialog> {
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
    return BaseDialog(
      width: 420,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Buka Sesi Kasir', style: AppTextStyles.h2),
          const SizedBox(height: 20),
          DropdownButtonFormField<int>(
            initialValue: _branchId,
            decoration: const InputDecoration(
              labelText: 'Cabang',
              border: OutlineInputBorder(),
            ),
            items: widget.branches
                .map((branch) =>
                    DropdownMenuItem(value: branch.id, child: Text(branch.name)))
                .toList(),
            onChanged: (value) {
              if (value != null) setState(() => _branchId = value);
            },
          ),
          const SizedBox(height: 14),
          TextField(
            controller: _cashController,
            keyboardType: TextInputType.number,
            decoration: const InputDecoration(
              labelText: 'Uang laci awal',
              border: OutlineInputBorder(),
            ),
          ),
          const SizedBox(height: 14),
          TextField(
            controller: _notesController,
            decoration: const InputDecoration(
              labelText: 'Catatan (opsional)',
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
                  final amount =
                      double.tryParse(_cashController.text.replaceAll('.', '')) ??
                          0;
                  Navigator.pop(
                    context,
                    OpenSessionResult(
                      branchId: _branchId,
                      openingCash: amount,
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
                child: const Text('Buka Sesi'),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
