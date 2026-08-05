import 'package:flutter/material.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/app/theme/app_text_styles.dart';
import 'package:desktop_flutter/shared/widgets/base_dialog.dart';
import 'package:desktop_flutter/shared/widgets/dialog_action_button.dart';

class UbahMetodeDialog extends StatefulWidget {
  final String currentMethod;

  const UbahMetodeDialog({super.key, required this.currentMethod});

  @override
  State<UbahMetodeDialog> createState() => _UbahMetodeDialogState();
}

class _UbahMetodeDialogState extends State<UbahMetodeDialog> {
  late String _selectedMethod;
  final TextEditingController _reasonCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _selectedMethod = widget.currentMethod.toLowerCase();
    if (_selectedMethod != 'cash' && _selectedMethod != 'qris' && _selectedMethod != 'transfer' && _selectedMethod != 'card') {
      _selectedMethod = 'cash';
    }
  }

  @override
  void dispose() {
    _reasonCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return BaseDialog(
      width: 400,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Ubah Metode Pembayaran', style: AppTextStyles.h2),
          const SizedBox(height: 16),
          Text(
            'Pilih metode pembayaran baru untuk transaksi ini:',
            style: AppTextStyles.bodyMedium.copyWith(color: AppColors.textSecondary),
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<String>(
            value: _selectedMethod,
            decoration: InputDecoration(
              filled: true,
              fillColor: AppColors.cardBg,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            ),
            items: const [
              DropdownMenuItem(value: 'cash', child: Text('TUNAI / CASH')),
              DropdownMenuItem(value: 'qris', child: Text('QRIS')),
              DropdownMenuItem(value: 'transfer', child: Text('TRANSFER BANK')),
              DropdownMenuItem(value: 'card', child: Text('KARTU DEBIT/KREDIT')),
            ],
            onChanged: (val) {
              if (val != null) setState(() => _selectedMethod = val);
            },
          ),
          const SizedBox(height: 16),
          Text(
            'Alasan Perubahan:',
            style: AppTextStyles.bodyMedium.copyWith(color: AppColors.textSecondary),
          ),
          const SizedBox(height: 8),
          TextField(
            controller: _reasonCtrl,
            maxLines: 3,
            decoration: InputDecoration(
              hintText: 'Masukkan alasan perubahan metode pembayaran...',
              filled: true,
              fillColor: AppColors.cardBg,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
          const SizedBox(height: 24),
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: () => Navigator.of(context).pop(),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: const Text('Batal'),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: DialogActionButton(
                  label: 'SIMPAN',
                  primary: true,
                  onPressed: () {
                    final reason = _reasonCtrl.text.trim();
                    if (reason.isEmpty) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Alasan perubahan wajib diisi')),
                      );
                      return;
                    }
                    Navigator.of(context).pop({
                      'method': _selectedMethod,
                      'reason': reason,
                    });
                  },
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
