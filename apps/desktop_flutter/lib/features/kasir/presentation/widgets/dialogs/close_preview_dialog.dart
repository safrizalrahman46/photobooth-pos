import 'package:flutter/material.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/app/theme/app_text_styles.dart';
import 'package:desktop_flutter/shared/widgets/base_dialog.dart';

class ClosePreviewDialog extends StatelessWidget {
  const ClosePreviewDialog({super.key, required this.preview});

  final Map<String, dynamic>? preview;

  String _stringAt(Map<String, dynamic> source, String key) {
    return source[key]?.toString() ?? '-';
  }

  Map<String, dynamic> _mapAt(Map<String, dynamic>? source, String key) {
    final value = source?[key];
    return value is Map<String, dynamic> ? value : <String, dynamic>{};
  }

  @override
  Widget build(BuildContext context) {
    final summary = _mapAt(preview, 'summary');

    return BaseDialog(
      width: 460,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Tutup Sesi Kasir', style: AppTextStyles.h2),
          const SizedBox(height: 20),
          _PreviewLine(
            label: 'Total Penjualan',
            value: _stringAt(summary, 'total_sales_text'),
          ),
          _PreviewLine(
            label: 'Cash Diterima',
            value: _stringAt(summary, 'cash_received_text'),
          ),
          _PreviewLine(
            label: 'Non Cash',
            value: _stringAt(summary, 'non_cash_received_text'),
          ),
          _PreviewLine(
            label: 'Pengeluaran',
            value: _stringAt(summary, 'cash_expenses_total_text'),
          ),
          const Divider(height: 1, color: AppColors.divider),
          const SizedBox(height: 8),
          _PreviewLine(
            label: 'JML. DISETOR CASH',
            value: _stringAt(summary, 'cash_to_deposit_text'),
            bold: true,
          ),
          _PreviewLine(
            label: 'Uang Laci Disisakan',
            value: _stringAt(summary, 'opening_cash_text'),
          ),
          const SizedBox(height: 24),
          Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              OutlinedButton(
                onPressed: () => Navigator.pop(context, false),
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
                onPressed: () => Navigator.pop(context, true),
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
                child: const Text('Tutup & Print'),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _PreviewLine extends StatelessWidget {
  const _PreviewLine({
    required this.label,
    required this.value,
    this.bold = false,
  });

  final String label;
  final String value;
  final bool bold;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label),
          Text(
            value,
            style: TextStyle(
              fontWeight: bold ? FontWeight.w900 : FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}
