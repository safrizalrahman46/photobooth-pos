// presentation/widgets/customer_form.dart

import 'package:flutter/material.dart';

class CustomerForm extends StatelessWidget {
  final String customerName;
  final String whatsapp;
  final int jumlahOrang;

  const CustomerForm({
    super.key,
    required this.customerName,
    required this.whatsapp,
    required this.jumlahOrang,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        _FormField(label: 'NAMA PELANGGAN', value: customerName, flex: 2),
        const SizedBox(width: 12),
        _FormField(label: 'WHATSAPP (OPSIONAL)', value: whatsapp, flex: 2),
        const SizedBox(width: 12),
        _FormField(
          label: 'JUMLAH ORANG',
          value: jumlahOrang.toString(),
          flex: 1,
        ),
      ],
    );
  }
}

class _FormField extends StatelessWidget {
  final String label;
  final String value;
  final int flex;

  const _FormField({
    required this.label,
    required this.value,
    required this.flex,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      flex: flex,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(
              fontSize: 10,
              fontWeight: FontWeight.w600,
              color: Color(0xFF9CA3AF),
              letterSpacing: 0.5,
            ),
          ),
          const SizedBox(height: 6),
          Container(
            height: 44,
            padding: const EdgeInsets.symmetric(horizontal: 12),
            decoration: BoxDecoration(
              color: Colors.white,
              border: Border.all(color: const Color(0xFFE5E7EB)),
              borderRadius: BorderRadius.circular(8),
            ),
            alignment: Alignment.centerLeft,
            child: Text(
              value,
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w500,
                color: Color(0xFF111827),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
