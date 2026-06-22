import 'package:flutter/material.dart';
import 'package:desktop_flutter/shared/constants/payment_method.dart';

class UbahMetodeDialogResult {
  final String method;
  final String? reason;

  const UbahMetodeDialogResult({required this.method, this.reason});
}

class UbahMetodeDialog extends StatefulWidget {
  final String currentMethod;
  final String transactionCode;

  const UbahMetodeDialog({
    super.key,
    required this.currentMethod,
    required this.transactionCode,
  });

  @override
  State<UbahMetodeDialog> createState() => _UbahMetodeDialogState();
}

class _UbahMetodeDialogState extends State<UbahMetodeDialog> {
  late String _method;
  final _reasonController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _method = widget.currentMethod;
  }

  @override
  void dispose() {
    _reasonController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
      insetPadding: const EdgeInsets.symmetric(horizontal: 32, vertical: 24),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(28),
        child: SizedBox(
          width: 460,
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: double.infinity,
                padding: const EdgeInsets.fromLTRB(24, 20, 24, 16),
                decoration: const BoxDecoration(
                  color: Color(0xFF1E293B),
                ),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Icon(Icons.swap_horiz_rounded, color: Colors.white, size: 22),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Ubah Metode Pembayaran',
                            style: TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: Colors.white, letterSpacing: 0.3),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            widget.transactionCode,
                            style: TextStyle(color: Colors.white.withValues(alpha: 0.7), fontSize: 12, fontWeight: FontWeight.w500),
                          ),
                        ],
                      ),
                    ),
                    Material(
                      color: Colors.transparent,
                      child: InkWell(
                        onTap: () => Navigator.of(context).pop(),
                        borderRadius: BorderRadius.circular(10),
                        child: Container(
                          padding: const EdgeInsets.all(6),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: const Icon(Icons.close_rounded, color: Colors.white, size: 18),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(24, 20, 24, 6),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _label('METODE SAAT INI'),
                    const SizedBox(height: 6),
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF1F5F9),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        children: [
                          Icon(PaymentMethod.icon(widget.currentMethod), size: 18, color: const Color(0xFF64748B)),
                          const SizedBox(width: 10),
                          Text(
                            PaymentMethod.label(widget.currentMethod),
                            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: Color(0xFF1E293B)),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),
                    _label('UBAH KE'),
                    const SizedBox(height: 10),
                    Wrap(
                      spacing: 10,
                      runSpacing: 10,
                      children: PaymentMethod.values
                          .where((m) => m != widget.currentMethod)
                          .map((m) {
                        final selected = _method == m;
                        return GestureDetector(
                          onTap: () => setState(() => _method = m),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                            decoration: BoxDecoration(
                              color: selected ? const Color(0xFF1E293B).withValues(alpha: 0.06) : Colors.white,
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(
                                color: selected ? const Color(0xFF1E293B) : const Color(0xFFE2E8F0),
                                width: selected ? 2 : 1,
                              ),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(PaymentMethod.icon(m), size: 18, color: selected ? const Color(0xFF1E293B) : const Color(0xFF94A3B8)),
                                const SizedBox(width: 8),
                                Text(PaymentMethod.label(m), style: TextStyle(fontSize: 13, fontWeight: FontWeight.w700, color: selected ? const Color(0xFF1E293B) : const Color(0xFF64748B))),
                              ],
                            ),
                          ),
                        );
                      }).toList(),
                    ),
                    const SizedBox(height: 20),
                    _label('ALASAN (OPSIONAL)'),
                    const SizedBox(height: 8),
                    Container(
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: TextField(
                        controller: _reasonController,
                        maxLines: 2,
                        style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500),
                        decoration: InputDecoration(
                          hintText: 'Contoh: Kasir salah pilih metode',
                          hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontWeight: FontWeight.normal, fontSize: 13),
                          filled: true,
                          fillColor: Colors.transparent,
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
                          enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
                          focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.fromLTRB(24, 16, 24, 24),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  border: const Border(top: BorderSide(color: Color(0xFFE2E8F0))),
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: OutlinedButton(
                        onPressed: () => Navigator.of(context).pop(),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: const Color(0xFF64748B),
                          side: const BorderSide(color: Color(0xFFE2E8F0), width: 1.5),
                          padding: const EdgeInsets.symmetric(vertical: 14),
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
                        onPressed: () => Navigator.of(context).pop(
                          UbahMetodeDialogResult(
                            method: _method,
                            reason: _reasonController.text.trim().isEmpty
                                ? null
                                : _reasonController.text.trim(),
                          ),
                        ),
                        icon: const Icon(Icons.check_rounded, size: 18),
                        label: const Text('Simpan Perubahan'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF1E293B),
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
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _label(String text) {
    return Text(text, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Color(0xFF94A3B8), letterSpacing: 1.2));
  }
}
