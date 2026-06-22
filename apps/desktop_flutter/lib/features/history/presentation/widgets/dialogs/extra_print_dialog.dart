import 'package:flutter/material.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/shared/models/add_on_catalog_item.dart';
import 'package:desktop_flutter/shared/widgets/base_dialog.dart';
import 'package:desktop_flutter/features/history/domain/entities/transaction.dart' as history_domain;
import 'package:desktop_flutter/shared/constants/payment_method.dart';

class ExtraPrintItem {
  final AddOnCatalogItem addOn;
  final int qty;
  const ExtraPrintItem({required this.addOn, required this.qty});
}

class ExtraPrintDialogResult {
  final List<ExtraPrintItem> items;
  final String paymentMethod;
  final String? referenceNo;

  const ExtraPrintDialogResult({
    required this.items,
    required this.paymentMethod,
    this.referenceNo,
  });
}

class ExtraPrintDialog extends StatefulWidget {
  const ExtraPrintDialog({super.key, required this.transaction, required this.addOns});

  final history_domain.Transaction transaction;
  final List<AddOnCatalogItem> addOns;

  @override
  State<ExtraPrintDialog> createState() => _ExtraPrintDialogState();
}

class _ExtraPrintDialogState extends State<ExtraPrintDialog> {
  final Map<int, TextEditingController> _qtyControllers = {};
  final Set<int> _selectedIds = {};
  final TextEditingController _referenceController = TextEditingController();
  String _paymentMethod = 'cash';
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    for (final item in widget.addOns) {
      _qtyControllers[item.id] = TextEditingController(text: '1');
    }
  }

  @override
  void dispose() {
    for (final c in _qtyControllers.values) {
      c.dispose();
    }
    _referenceController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final selectedItems = widget.addOns.where((a) => _selectedIds.contains(a.id)).toList();
    final total = selectedItems.fold<double>(0, (sum, a) {
      final qty = _currentQty(a.id);
      return sum + qty * a.price;
    });

    return BaseDialog(
      width: 620,
      padding: EdgeInsets.zero,
      child: ClipRRect(
        borderRadius: BorderRadius.circular(28),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            _buildHeader(),
            Flexible(
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(28, 24, 28, 6),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildTransactionSummary(total: total),
                    const SizedBox(height: 24),
                    _sectionLabel('PILIH ADD-ON'),
                    const SizedBox(height: 10),
                    ...widget.addOns.map((item) => _buildSelectableItem(item)),
                    if (selectedItems.isNotEmpty) ...[
                      const SizedBox(height: 20),
                      _sectionLabel('METODE PEMBAYARAN'),
                      const SizedBox(height: 10),
                      _buildPaymentMethodSelector(),
                      const SizedBox(height: 18),
                      _sectionLabel('REFERENSI (OPSIONAL)'),
                      const SizedBox(height: 10),
                      _buildTextField(
                        controller: _referenceController,
                        icon: Icons.tag_rounded,
                        hint: 'No. referensi pembayaran',
                      ),
                    ],
                    const SizedBox(height: 24),
                  ],
                ),
              ),
            ),
            _buildFooter(total),
          ],
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(28, 24, 28, 20),
      decoration: BoxDecoration(
        color: AppColors.primary,
        boxShadow: [
          BoxShadow(
            color: AppColors.primary.withValues(alpha: 0.15),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: Colors.white.withValues(alpha: 0.2)),
            ),
            child: const Icon(Icons.print_rounded, color: Colors.white, size: 24),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Tambah Add-on & Cetak',
                  style: TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: Colors.white, letterSpacing: 0.3),
                ),
                const SizedBox(height: 4),
                Text(
                  '${widget.transaction.id} • ${widget.transaction.namaPelanggan}',
                  style: TextStyle(color: Colors.white.withValues(alpha: 0.8), fontSize: 13, fontWeight: FontWeight.w500),
                ),
              ],
            ),
          ),
          Material(
            color: Colors.transparent,
            child: InkWell(
              onTap: () => Navigator.of(context).pop(),
              borderRadius: BorderRadius.circular(12),
              child: Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Icons.close_rounded, color: Colors.white, size: 20),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSelectableItem(AddOnCatalogItem item) {
    final selected = _selectedIds.contains(item.id);

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: selected ? AppColors.primary.withValues(alpha: 0.04) : Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: selected ? AppColors.primary.withValues(alpha: 0.4) : const Color(0xFFE2E8F0),
          width: selected ? 1.5 : 1,
        ),
      ),
      child: InkWell(
        onTap: () {
          setState(() {
            if (selected) {
              _selectedIds.remove(item.id);
            } else {
              _selectedIds.add(item.id);
            }
            _errorMessage = null;
          });
        },
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          child: Row(
            children: [
              Icon(
                selected ? Icons.check_box_rounded : Icons.check_box_outline_blank_rounded,
                color: selected ? AppColors.primary : const Color(0xFFCBD5E1),
                size: 22,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(item.name, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: Color(0xFF1E293B))),
                    const SizedBox(height: 2),
                    Text(_currency(item.price), style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF64748B))),
                  ],
                ),
              ),
              if (selected) ...[
                const SizedBox(width: 8),
                SizedBox(
                  width: 80,
                  child: _buildQtyField(item.id),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildQtyField(int addOnId) {
    final controller = _qtyControllers[addOnId]!;
    return TextField(
      controller: controller,
      keyboardType: TextInputType.number,
      style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: Color(0xFF1E293B)),
      decoration: InputDecoration(
        isDense: true,
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide.none,
        ),
        filled: true,
        fillColor: const Color(0xFFF1F5F9),
        hintText: 'Qty',
        hintStyle: const TextStyle(color: Color(0xFFCBD5E1), fontSize: 12),
      ),
      onChanged: (_) => setState(() => _errorMessage = null),
    );
  }

  Widget _buildTransactionSummary({required double total}) {
    return Container(
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14),
            decoration: const BoxDecoration(
              border: Border(bottom: BorderSide(color: Color(0xFFE2E8F0))),
            ),
            child: Row(
              children: [
                Icon(Icons.info_outline_rounded, size: 16, color: AppColors.primary),
                const SizedBox(width: 8),
                const Text('Detail Transaksi', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF1E293B))),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(18),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _summaryRow('No Transaksi', widget.transaction.id),
                _summaryRow('Pelanggan', widget.transaction.namaPelanggan),
                _summaryRow('Paket', widget.transaction.paket),
                if (widget.transaction.addOns != null && widget.transaction.addOns!.isNotEmpty)
                  _summaryRow('Add-on lama', widget.transaction.addOns!),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPaymentMethodSelector() {
    return Wrap(
      spacing: 10,
      runSpacing: 10,
      children: PaymentMethod.values.map((m) {
        final selected = _paymentMethod == m;
        return GestureDetector(
          onTap: () => setState(() => _paymentMethod = m),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
            decoration: BoxDecoration(
              color: selected ? AppColors.primary.withValues(alpha: 0.08) : Colors.white,
              borderRadius: BorderRadius.circular(14),
              border: Border.all(
                color: selected ? AppColors.primary : const Color(0xFFE2E8F0),
                width: selected ? 2 : 1,
              ),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(PaymentMethod.icon(m), size: 18, color: selected ? AppColors.primary : const Color(0xFF94A3B8)),
                const SizedBox(width: 8),
                Text(PaymentMethod.label(m), style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: selected ? AppColors.primary : const Color(0xFF64748B))),
              ],
            ),
          ),
        );
      }).toList(),
    );
  }

  Widget _buildTextField({required TextEditingController controller, required IconData icon, required String hint}) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: TextField(
        controller: controller,
        style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontWeight: FontWeight.normal),
          prefixIcon: Icon(icon, size: 20, color: AppColors.primary),
          filled: true,
          fillColor: Colors.transparent,
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide.none),
          enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide.none),
          focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide.none),
        ),
      ),
    );
  }

  Widget _buildFooter(double total) {
    return Container(
      padding: const EdgeInsets.fromLTRB(28, 16, 28, 24),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        border: const Border(top: BorderSide(color: Color(0xFFE2E8F0))),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (_errorMessage != null) ...[
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              margin: const EdgeInsets.only(bottom: 12),
              decoration: BoxDecoration(
                color: AppColors.error.withValues(alpha: 0.06),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: AppColors.error.withValues(alpha: 0.2)),
              ),
              child: Row(
                children: [
                  Icon(Icons.error_outline_rounded, size: 16, color: AppColors.error),
                  const SizedBox(width: 8),
                  Expanded(child: Text(_errorMessage!, style: TextStyle(color: AppColors.error, fontSize: 13, fontWeight: FontWeight.w600))),
                ],
              ),
            ),
          ],
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: () => Navigator.of(context).pop(),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFF64748B),
                    side: const BorderSide(color: Color(0xFFE2E8F0), width: 1.5),
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    textStyle: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                  ),
                  child: const Text('Batal'),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                flex: 2,
                child: ElevatedButton.icon(
                  onPressed: _submit,
                  icon: const Icon(Icons.print_rounded, size: 18),
                  label: const Text('Simpan & Cetak Gabungan'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.primary,
                    foregroundColor: Colors.white,
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    textStyle: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _sectionLabel(String text) {
    return Text(text, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Color(0xFF94A3B8), letterSpacing: 1.2));
  }

  Widget _summaryRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(label, style: const TextStyle(fontSize: 13, color: Color(0xFF94A3B8), fontWeight: FontWeight.w500)),
          ),
          Expanded(
            child: Text(value.isEmpty ? '-' : value, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: Color(0xFF1E293B))),
          ),
        ],
      ),
    );
  }

  int _currentQty(int addOnId) {
    return int.tryParse(_qtyControllers[addOnId]?.text.trim() ?? '') ?? 0;
  }

  void _submit() {
    final selected = widget.addOns.where((a) => _selectedIds.contains(a.id)).toList();

    if (selected.isEmpty) {
      setState(() => _errorMessage = 'Pilih minimal 1 add-on.');
      return;
    }

    final items = <ExtraPrintItem>[];
    for (final addOn in selected) {
      final qty = _currentQty(addOn.id);
      final stock = addOn.effectiveAvailableStock;

      if (qty <= 0) {
        setState(() => _errorMessage = 'Qty untuk ${addOn.name} harus lebih dari 0.');
        return;
      }

      if (qty > addOn.maxQty) {
        setState(() => _errorMessage = 'Maksimum qty ${addOn.name} adalah ${addOn.maxQty}.');
        return;
      }

      if (stock != null && qty > stock) {
        setState(() => _errorMessage = 'Stok ${addOn.name} tidak mencukupi. Tersedia $stock.');
        return;
      }

      items.add(ExtraPrintItem(addOn: addOn, qty: qty));
    }

    Navigator.of(context).pop(
      ExtraPrintDialogResult(
        items: items,
        paymentMethod: _paymentMethod,
        referenceNo: _referenceController.text.trim().isEmpty
            ? null
            : _referenceController.text.trim(),
      ),
    );
  }

  String _currency(double value) {
    final chars = value.round().toString().split('').reversed.toList();
    final buffer = StringBuffer();
    for (var i = 0; i < chars.length; i++) {
      if (i > 0 && i % 3 == 0) buffer.write('.');
      buffer.write(chars[i]);
    }
    return 'Rp ${buffer.toString().split('').reversed.join()}';
  }
}
