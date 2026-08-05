import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/core/session/api_session.dart';
import 'package:desktop_flutter/shared/models/walk_in_request_item.dart';

class WalkinReviewResult {
  final String customerName;
  final String customerPhone;
  final int packageId;
  final int? packageId2;
  final bool clearPackage2;
  final List<Map<String, dynamic>> addons;

  const WalkinReviewResult({
    required this.customerName,
    required this.customerPhone,
    required this.packageId,
    this.packageId2,
    this.clearPackage2 = false,
    required this.addons,
  });
}

class WalkinReviewDialog extends StatefulWidget {
  final WalkInRequestItem item;

  const WalkinReviewDialog({super.key, required this.item});

  @override
  State<WalkinReviewDialog> createState() => _WalkinReviewDialogState();
}

class _WalkinReviewDialogState extends State<WalkinReviewDialog> {
  late TextEditingController _nameCtrl;
  late TextEditingController _phoneCtrl;
  late int _packageId;
  late String _packageName;
  late double _packagePrice;
  int? _packageId2;
  String? _packageName2;
  double? _packagePrice2;
  late List<_EditedAddOn> _addOns;
  bool _loadingAddOns = false;
  bool _hasChanges = false;

  @override
  void initState() {
    super.initState();
    final item = widget.item;
    _nameCtrl = TextEditingController(text: item.customerName);
    _phoneCtrl = TextEditingController(text: item.customerPhone);
    _packageId = item.packageId;
    _packageName = item.packageName;
    _packagePrice = item.packagePrice;

    final pkg2List = item.addOns.where((a) => a.isPackage).toList();
    if (pkg2List.isNotEmpty) {
      final p2 = pkg2List.first;
      _packageId2 = p2.addOnId;
      _packageName2 = p2.name;
      _packagePrice2 = p2.unitPrice;
    }

    _addOns = item.addOns
        .where((a) => !a.isPackage)
        .map((a) => _EditedAddOn(addOnId: a.addOnId, name: a.name, unitPrice: a.unitPrice, qty: a.qty))
        .toList();
    _nameCtrl.addListener(_detectChanges);
    _phoneCtrl.addListener(_detectChanges);
  }

  void _detectChanges() {
    final origPkg2List = widget.item.addOns.where((a) => a.isPackage).toList();
    final int? origPkg2Id = origPkg2List.isNotEmpty ? origPkg2List.first.addOnId : null;

    final changed = _nameCtrl.text != widget.item.customerName ||
        _phoneCtrl.text != widget.item.customerPhone ||
        _packageId != widget.item.packageId ||
        _packageId2 != origPkg2Id ||
        _addOns.any((a) {
          final orig = widget.item.addOns.where((o) => o.addOnId == a.addOnId && !o.isPackage);
          final origQty = orig.isEmpty ? 0 : orig.first.qty;
          return a.qty != origQty;
        }) ||
        _addOns.length != widget.item.addOns.where((a) => !a.isPackage).length;
    if (changed != _hasChanges) {
      setState(() => _hasChanges = changed);
    }
  }

  @override
  void dispose() {
    _nameCtrl.removeListener(_detectChanges);
    _phoneCtrl.removeListener(_detectChanges);
    _nameCtrl.dispose();
    _phoneCtrl.dispose();
    super.dispose();
  }

  double get _addOnsTotal =>
      _addOns.fold(0.0, (sum, a) => sum + (a.unitPrice * a.qty));

  double get _packagePrice2Total => _packagePrice2 ?? 0.0;

  double get _grandTotal => _packagePrice + _packagePrice2Total + _addOnsTotal;

  Future<void> _pickPackage() async {
    final client = ApiSession.client;
    if (client == null) return;

    try {
      final packages = await client.fetchPackages();
      if (!mounted) return;

      final selected = await showDialog<int>(
        context: context,
        builder: (ctx) => SimpleDialog(
          title: const Text('Ganti Paket 1'),
          children: packages
              .where((p) => p.id != _packageId2)
              .map((p) => SimpleDialogOption(
                    onPressed: () => Navigator.of(ctx).pop(p.id),
                    child: ListTile(
                      title: Text(p.name),
                      subtitle: Text(_currency(p.basePrice)),
                    ),
                  ))
              .toList(),
        ),
      );

      if (selected != null && mounted) {
        final pkg = packages.firstWhere((p) => p.id == selected);
        setState(() {
          _packageId = pkg.id;
          _packageName = pkg.name;
          _packagePrice = pkg.basePrice;
        });
        _detectChanges();
      }
    } catch (_) {}
  }

  Future<void> _pickPackage2() async {
    final client = ApiSession.client;
    if (client == null) return;

    try {
      final packages = await client.fetchPackages();
      if (!mounted) return;

      final selected = await showDialog<int>(
        context: context,
        builder: (ctx) => SimpleDialog(
          title: const Text('Ganti Paket 2'),
          children: packages
              .where((p) => p.id != _packageId && p.id != _packageId2)
              .map((p) => SimpleDialogOption(
                    onPressed: () => Navigator.of(ctx).pop(p.id),
                    child: ListTile(
                      title: Text(p.name),
                      subtitle: Text(_currency(p.basePrice)),
                    ),
                  ))
              .toList(),
        ),
      );

      if (selected != null && mounted) {
        final pkg = packages.firstWhere((p) => p.id == selected);
        setState(() {
          _packageId2 = pkg.id;
          _packageName2 = pkg.name;
          _packagePrice2 = pkg.basePrice;
        });
        _detectChanges();
      }
    } catch (_) {}
  }

  Future<void> _addPackage2() async {
    final client = ApiSession.client;
    if (client == null) return;

    try {
      final packages = await client.fetchPackages();
      if (!mounted) return;

      final selected = await showDialog<int>(
        context: context,
        builder: (ctx) => SimpleDialog(
          title: const Text('Tambah Paket 2'),
          children: packages
              .where((p) => p.id != _packageId)
              .map((p) => SimpleDialogOption(
                    onPressed: () => Navigator.of(ctx).pop(p.id),
                    child: ListTile(
                      title: Text(p.name),
                      subtitle: Text(_currency(p.basePrice)),
                    ),
                  ))
              .toList(),
        ),
      );

      if (selected != null && mounted) {
        final pkg = packages.firstWhere((p) => p.id == selected);
        setState(() {
          _packageId2 = pkg.id;
          _packageName2 = pkg.name;
          _packagePrice2 = pkg.basePrice;
        });
        _detectChanges();
      }
    } catch (_) {}
  }

  void _removePackage2() {
    setState(() {
      _packageId2 = null;
      _packageName2 = null;
      _packagePrice2 = null;
    });
    _detectChanges();
  }

  Future<void> _addAddOn() async {
    final client = ApiSession.client;
    if (client == null) return;

    setState(() => _loadingAddOns = true);

    try {
      final addOns = await client.fetchAddOns();
      if (!mounted) return;
      setState(() => _loadingAddOns = false);

      final existingIds = _addOns.map((a) => a.addOnId).toSet();
      final available = addOns.where((a) => !existingIds.contains(a.id)).toList();

      if (available.isEmpty) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Tidak ada add-on tersedia.')),
          );
        }
        return;
      }

      final selected = await showDialog<int>(
        context: context,
        builder: (ctx) => SimpleDialog(
          title: const Text('Tambah Add-on'),
          children: available
              .map((a) => SimpleDialogOption(
                    onPressed: () => Navigator.of(ctx).pop(a.id),
                    child: ListTile(
                      title: Text(a.name),
                      subtitle: Text(_currency(a.price)),
                    ),
                  ))
              .toList(),
        ),
      );

      if (selected != null && mounted) {
        final ao = addOns.firstWhere((a) => a.id == selected);
        setState(() {
          _addOns.add(_EditedAddOn(addOnId: ao.id, name: ao.name, unitPrice: ao.price, qty: 1));
        });
        _detectChanges();
      }
    } catch (_) {
      if (mounted) setState(() => _loadingAddOns = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
      insetPadding: const EdgeInsets.symmetric(horizontal: 40, vertical: 24),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(28),
        child: SizedBox(
          width: 560,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              _header(),
              Flexible(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(24, 16, 24, 8),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _customerSection(),
                      const SizedBox(height: 20),
                      _packageSection(),
                      const SizedBox(height: 20),
                      _addOnsSection(),
                      const SizedBox(height: 20),
                      _totalSection(),
                    ],
                  ),
                ),
              ),
              _actions(),
            ],
          ),
        ),
      ),
    );
  }

  Widget _header() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(24, 20, 24, 16),
      decoration: const BoxDecoration(color: Color(0xFF1E293B)),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(Icons.receipt_long_rounded, color: Colors.white, size: 22),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Review & Edit Pesanan',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: Colors.white)),
                const SizedBox(height: 2),
                Text(widget.item.requestCode,
                    style: TextStyle(color: Colors.white.withValues(alpha: 0.7), fontSize: 12)),
              ],
            ),
          ),
          _StatusBadge(status: widget.item.status),
        ],
      ),
    );
  }

  Widget _customerSection() {
    return _section('DATA CUSTOMER', [
      _labelField('Nama', _nameCtrl, hint: widget.item.customerName),
      const SizedBox(height: 12),
      _labelField('No. HP', _phoneCtrl, hint: widget.item.customerPhone, digitsOnly: true),
    ]);
  }

  Widget _packageSection() {
    return _section('PAKET & TEMA', [
      Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Paket 1: $_packageName',
                    style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14, color: Color(0xFF1E293B))),
                const SizedBox(height: 2),
                Text(_currency(_packagePrice),
                    style: const TextStyle(fontSize: 13, color: Color(0xFF64748B))),
              ],
            ),
          ),
          TextButton.icon(
            onPressed: _pickPackage,
            icon: const Icon(Icons.swap_horiz_rounded, size: 16),
            label: const Text('Ganti'),
            style: TextButton.styleFrom(foregroundColor: AppColors.primary),
          ),
        ],
      ),
      if (_packageId2 != null) ...[
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Paket 2: $_packageName2',
                      style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14, color: Color(0xFF1E293B))),
                  const SizedBox(height: 2),
                  Text(_currency(_packagePrice2!),
                      style: const TextStyle(fontSize: 13, color: Color(0xFF64748B))),
                ],
              ),
            ),
            TextButton.icon(
              onPressed: _pickPackage2,
              icon: const Icon(Icons.swap_horiz_rounded, size: 16),
              label: const Text('Ganti'),
              style: TextButton.styleFrom(foregroundColor: AppColors.primary),
            ),
            const SizedBox(width: 8),
            TextButton.icon(
              onPressed: _removePackage2,
              icon: const Icon(Icons.delete_outline_rounded, size: 16, color: Colors.redAccent),
              label: const Text('Hapus', style: TextStyle(color: Colors.redAccent)),
              style: TextButton.styleFrom(foregroundColor: Colors.redAccent),
            ),
          ],
        ),
      ] else ...[
        const SizedBox(height: 8),
        Center(
          child: TextButton.icon(
            onPressed: _addPackage2,
            icon: const Icon(Icons.add_circle_outline_rounded, size: 16),
            label: const Text('Tambah Paket 2'),
            style: TextButton.styleFrom(foregroundColor: AppColors.primary),
          ),
        ),
      ],
    ]);
  }

  Widget _addOnsSection() {
    return _section('ADD-ON${_loadingAddOns ? ' (memuat...)' : ''}', [
      if (_addOns.isEmpty)
        const Padding(
          padding: EdgeInsets.symmetric(vertical: 8),
          child: Text('Tidak ada add-on', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 13)),
        )
      else
        ..._addOns.map((a) => Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(a.name,
                            style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: Color(0xFF1E293B))),
                        Text('${_currency(a.unitPrice)}/item',
                            style: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8))),
                      ],
                    ),
                  ),
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      _qtyButton(Icons.remove_rounded, () {
                        if (a.qty > 1) {
                          setState(() => a.qty--);
                          _detectChanges();
                        }
                      }),
                      const SizedBox(width: 8),
                      Text('${a.qty}',
                          style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 14)),
                      const SizedBox(width: 8),
                      _qtyButton(Icons.add_rounded, () {
                        if (a.qty < 99) {
                          setState(() => a.qty++);
                          _detectChanges();
                        }
                      }),
                      const SizedBox(width: 12),
                      Text(_currency(a.unitPrice * a.qty),
                          style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13, color: Color(0xFF1E293B))),
                    ],
                  ),
                ],
              ),
            )),
      const SizedBox(height: 4),
      Center(
        child: TextButton.icon(
          onPressed: _addAddOn,
          icon: const Icon(Icons.add_circle_outline_rounded, size: 16),
          label: const Text('Tambah Add-on'),
          style: TextButton.styleFrom(foregroundColor: AppColors.primary),
        ),
      ),
    ]);
  }

  Widget _totalSection() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        children: [
          _totalRow('Paket 1', _currency(_packagePrice)),
          if (_packageId2 != null) ...[
            const SizedBox(height: 6),
            _totalRow('Paket 2', _currency(_packagePrice2!)),
          ],
          if (_addOns.isNotEmpty) ...[
            const SizedBox(height: 6),
            _totalRow('Add-on', _currency(_addOnsTotal)),
          ],
          const Divider(height: 20),
          _totalRow('Total', _currency(_grandTotal), bold: true, large: true),
        ],
      ),
    );
  }

  Widget _actions() {
    return Container(
      padding: const EdgeInsets.fromLTRB(24, 12, 24, 20),
      decoration: const BoxDecoration(
        border: Border(top: BorderSide(color: Color(0xFFE2E8F0))),
      ),
      child: Row(
        children: [
          Expanded(
            child: OutlinedButton(
              onPressed: () => Navigator.of(context).pop(),
              style: OutlinedButton.styleFrom(
                foregroundColor: const Color(0xFF64748B),
                side: const BorderSide(color: Color(0xFFE2E8F0)),
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                textStyle: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
              ),
              child: const Text('Kembali'),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            flex: 2,
            child: ElevatedButton.icon(
              onPressed: () {
                final origPkg2List = widget.item.addOns.where((a) => a.isPackage).toList();
                final int? origPkg2Id = origPkg2List.isNotEmpty ? origPkg2List.first.addOnId : null;
                final clearPkg2 = origPkg2Id != null && _packageId2 == null;

                Navigator.of(context).pop(
                  WalkinReviewResult(
                    customerName: _nameCtrl.text.trim(),
                    customerPhone: _phoneCtrl.text.trim(),
                    packageId: _packageId,
                    packageId2: _packageId2,
                    clearPackage2: clearPkg2,
                    addons: _addOns
                        .map((a) => {'add_on_id': a.addOnId, 'qty': a.qty})
                        .toList(),
                  ),
                );
              },
              icon: const Icon(Icons.payments_rounded, size: 18),
              label: Text(_hasChanges ? 'Simpan & Lanjutkan' : 'Lanjutkan ke Pembayaran'),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                elevation: 0,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                textStyle: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _section(String title, List<Widget> children) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title,
            style: const TextStyle(
                fontSize: 11, fontWeight: FontWeight.w800, color: Color(0xFF94A3B8), letterSpacing: 1.2)),
        const SizedBox(height: 10),
        ...children,
      ],
    );
  }

  Widget _labelField(String label, TextEditingController ctrl,
      {String? hint, bool digitsOnly = false}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label,
            style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF1E293B))),
        const SizedBox(height: 4),
        TextField(
          controller: ctrl,
          keyboardType: digitsOnly ? TextInputType.phone : TextInputType.text,
          inputFormatters: digitsOnly
              ? [FilteringTextInputFormatter.digitsOnly]
              : null,
          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500),
          decoration: InputDecoration(
            hintText: hint,
            filled: true,
            fillColor: Colors.white,
            contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
            border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
            enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
            focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: const BorderSide(color: AppColors.primary, width: 2)),
          ),
        ),
      ],
    );
  }

  Widget _qtyButton(IconData icon, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 28,
        height: 28,
        decoration: BoxDecoration(
          color: const Color(0xFFF1F5F9),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Icon(icon, size: 16, color: const Color(0xFF64748B)),
      ),
    );
  }

  static Widget _totalRow(String label, String value, {bool bold = false, bool large = false}) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label,
            style: TextStyle(fontSize: large ? 14 : 13, fontWeight: bold ? FontWeight.w700 : FontWeight.w500)),
        Text(value,
            style: TextStyle(
                fontSize: large ? 18 : 13,
                fontWeight: FontWeight.w900,
                color: large ? AppColors.primary : const Color(0xFF1E293B))),
      ],
    );
  }
}

class _StatusBadge extends StatelessWidget {
  final String status;
  const _StatusBadge({required this.status});

  @override
  Widget build(BuildContext context) {
    final paid = status == 'paid';
    final expired = status == 'expired';
    final Color color, bgColor;
    String label;

    if (paid) {
      color = const Color(0xFF0D9488);
      bgColor = const Color(0xFFF0FDFA);
      label = 'Lunas';
    } else if (expired) {
      color = const Color(0xFFE11D48);
      bgColor = const Color(0xFFFFF1F2);
      label = 'Expired';
    } else {
      color = const Color(0xFFD97706);
      bgColor = const Color(0xFFFFFBEB);
      label = 'Menunggu';
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(99),
        border: Border.all(color: color.withValues(alpha: 0.15)),
      ),
      child: Text(label,
          style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.w700)),
    );
  }
}

class _EditedAddOn {
  final int addOnId;
  final String name;
  final double unitPrice;
  int qty;

  _EditedAddOn({
    required this.addOnId,
    required this.name,
    required this.unitPrice,
    required this.qty,
  });
}

String _currency(double value) {
  final rounded = value.round();
  final chars = rounded.toString().split('').reversed.toList();
  final buffer = StringBuffer();
  for (var i = 0; i < chars.length; i++) {
    if (i > 0 && i % 3 == 0) buffer.write('.');
    buffer.write(chars[i]);
  }
  return 'Rp ${buffer.toString().split('').reversed.join()}';
}
