import 'package:flutter/material.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/app/theme/app_text_styles.dart';
import 'package:desktop_flutter/shared/models/add_on_catalog_item.dart';
import 'package:desktop_flutter/shared/widgets/base_dialog.dart';
import 'package:desktop_flutter/features/history/domain/entities/transaction.dart' as history_domain;

class ExtraPrintDialogResult {
  const ExtraPrintDialogResult({
    required this.addOn,
    required this.qty,
    required this.paymentMethod,
    this.referenceNo,
  });

  final AddOnCatalogItem addOn;
  final int qty;
  final String paymentMethod;
  final String? referenceNo;
}

class ExtraPrintDialog extends StatefulWidget {
  const ExtraPrintDialog({super.key, required this.transaction, required this.addOns});

  final history_domain.Transaction transaction;
  final List<AddOnCatalogItem> addOns;

  @override
  State<ExtraPrintDialog> createState() => _ExtraPrintDialogState();
}

class _ExtraPrintDialogState extends State<ExtraPrintDialog> {
  late AddOnCatalogItem _selectedAddOn;
  final TextEditingController _qtyController = TextEditingController(text: '1');
  final TextEditingController _referenceController = TextEditingController();
  String _paymentMethod = 'cash';
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    _selectedAddOn = widget.addOns.first;
    _qtyController.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _qtyController.dispose();
    _referenceController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final qty = _currentQty;
    final total = qty * _selectedAddOn.price;

    return BaseDialog(
      width: 560,
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Tambah Add-on', style: AppTextStyles.h2),
            const SizedBox(height: 16),
            Text(
              widget.transaction.id,
              style: AppTextStyles.h3,
            ),
            const SizedBox(height: 4),
            Text(
              widget.transaction.namaPelanggan,
              style: TextStyle(color: AppColors.textSecondary),
            ),
            const SizedBox(height: 14),
            _buildHistorySummary(qty: qty, total: total),
            const SizedBox(height: 18),
            DropdownButtonFormField<AddOnCatalogItem>(
              initialValue: _selectedAddOn,
              decoration: const InputDecoration(
                labelText: 'Add-on baru',
                border: OutlineInputBorder(),
              ),
              items: widget.addOns.map((item) {
                final stock = item.effectiveAvailableStock;
                final stockText = stock == null ? '' : ' • stok $stock';
                return DropdownMenuItem(
                  value: item,
                  child: Text('${item.name} • ${_currency(item.price)}$stockText'),
                );
              }).toList(),
              onChanged: (item) {
                if (item == null) return;
                setState(() {
                  _selectedAddOn = item;
                  _errorMessage = null;
                });
              },
            ),
            const SizedBox(height: 14),
            TextField(
              controller: _qtyController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                labelText: 'Qty',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 14),
            DropdownButtonFormField<String>(
              initialValue: _paymentMethod,
              decoration: const InputDecoration(
                labelText: 'Metode bayar',
                border: OutlineInputBorder(),
              ),
              items: const [
                DropdownMenuItem(value: 'cash', child: Text('Tunai')),
                DropdownMenuItem(value: 'qris', child: Text('QRIS')),
                DropdownMenuItem(value: 'transfer', child: Text('Transfer')),
                DropdownMenuItem(value: 'card', child: Text('Kartu')),
              ],
              onChanged: (value) {
                if (value == null) return;
                setState(() => _paymentMethod = value);
              },
            ),
            const SizedBox(height: 14),
            TextField(
              controller: _referenceController,
              decoration: const InputDecoration(
                labelText: 'Referensi (opsional)',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: AppColors.cardBg,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppColors.cardBorder),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('Total tambah add-on', style: AppTextStyles.h3),
                  Text(
                    _currency(total),
                    style: TextStyle(
                      fontWeight: FontWeight.w800,
                      color: AppColors.textPrimary,
                    ),
                  ),
                ],
              ),
            ),
            if (_errorMessage != null) ...[
              const SizedBox(height: 12),
              Text(
                _errorMessage!,
                style: TextStyle(color: AppColors.error, fontSize: 13),
              ),
            ],
            const SizedBox(height: 24),
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                OutlinedButton(
                  onPressed: () => Navigator.of(context).pop(),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  child: const Text('Batal'),
                ),
                const SizedBox(width: 12),
                ElevatedButton.icon(
                  onPressed: _submit,
                  icon: const Icon(Icons.add_circle_outline_rounded, size: 18),
                  label: const Text('Simpan & Cetak Gabungan'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    foregroundColor: Colors.white,
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildHistorySummary({required int qty, required double total}) {
    final items = widget.transaction.items;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.cardBg,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.cardBorder),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Verifikasi transaksi',
            style: TextStyle(fontWeight: FontWeight.w800, color: AppColors.textPrimary),
          ),
          const SizedBox(height: 8),
          _summaryRow('No transaksi', widget.transaction.id),
          _summaryRow('Pelanggan', widget.transaction.namaPelanggan),
          _summaryRow('Paket', widget.transaction.paket),
          if (widget.transaction.addOns != null && widget.transaction.addOns!.isNotEmpty)
            _summaryRow('Add-on lama', widget.transaction.addOns!),
          const SizedBox(height: 10),
          const Divider(height: 1, color: AppColors.divider),
          const SizedBox(height: 10),
          Text(
            'Riwayat item transaksi',
            style: TextStyle(fontWeight: FontWeight.w800, color: AppColors.textPrimary),
          ),
          const SizedBox(height: 8),
          if (items.isEmpty)
            Text(
              'Detail item lama tidak tersedia dari server.',
              style: TextStyle(color: AppColors.textSecondary),
            )
          else
            ...items.map(
              (item) => _itemRow(
                name: item.itemName,
                qty: item.qty,
                unitPrice: item.unitPrice,
                lineTotal: item.lineTotal,
              ),
            ),
          const SizedBox(height: 10),
          const Divider(height: 1, color: AppColors.divider),
          const SizedBox(height: 10),
          Text(
            'Add-on baru yang akan ditambahkan',
            style: TextStyle(fontWeight: FontWeight.w800, color: AppColors.success),
          ),
          const SizedBox(height: 8),
          _itemRow(
            name: _selectedAddOn.name,
            qty: qty.toDouble(),
            unitPrice: _selectedAddOn.price,
            lineTotal: total,
            highlighted: true,
          ),
        ],
      ),
    );
  }

  Widget _summaryRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 110,
            child: Text(label, style: TextStyle(color: AppColors.textSecondary)),
          ),
          Expanded(
            child: Text(
              value.isEmpty ? '-' : value,
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ),
    );
  }

  Widget _itemRow({
    required String name,
    required double qty,
    required double unitPrice,
    required double lineTotal,
    bool highlighted = false,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Text(
              '$name • ${_formatQty(qty)} x ${_currency(unitPrice)}',
              style: TextStyle(
                color: highlighted ? AppColors.success : AppColors.textPrimary,
                fontWeight: highlighted ? FontWeight.w800 : FontWeight.w600,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Text(
            _currency(lineTotal),
            style: TextStyle(
              color: highlighted ? AppColors.success : AppColors.textPrimary,
              fontWeight: highlighted ? FontWeight.w800 : FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }

  int get _currentQty => int.tryParse(_qtyController.text.trim()) ?? 0;

  void _submit() {
    final qty = _currentQty;
    final stock = _selectedAddOn.effectiveAvailableStock;

    if (qty <= 0) {
      setState(() => _errorMessage = 'Qty harus lebih dari 0.');
      return;
    }

    if (qty > _selectedAddOn.maxQty) {
      setState(
        () => _errorMessage = 'Maksimum qty untuk ${_selectedAddOn.name} adalah ${_selectedAddOn.maxQty}.',
      );
      return;
    }

    if (stock != null && qty > stock) {
      setState(() => _errorMessage = 'Stok tidak mencukupi. Tersedia $stock.');
      return;
    }

    Navigator.of(context).pop(
      ExtraPrintDialogResult(
        addOn: _selectedAddOn,
        qty: qty,
        paymentMethod: _paymentMethod,
        referenceNo: _referenceController.text.trim().isEmpty
            ? null
            : _referenceController.text.trim(),
      ),
    );
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

  String _formatQty(double value) {
    if (value == value.roundToDouble()) {
      return value.round().toString();
    }

    return value.toStringAsFixed(2);
  }
}
