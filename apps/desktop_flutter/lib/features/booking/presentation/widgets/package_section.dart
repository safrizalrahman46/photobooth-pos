// presentation/widgets/package_section.dart

import 'package:flutter/material.dart';
import '../../domain/order_state.dart';
import 'package_card.dart';

class PackageSection extends StatelessWidget {
  final List<Package> packages;
  final String? selectedPackageId;
  final ValueChanged<String> onPackageSelected;

  const PackageSection({
    super.key,
    required this.packages,
    required this.selectedPackageId,
    required this.onPackageSelected,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Pilih Paket',
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.w700,
            color: Color(0xFF111827),
          ),
        ),
        const SizedBox(height: 16),
        Row(
          children: packages.map((pkg) {
            return Padding(
              padding: const EdgeInsets.only(right: 12),
              child: PackageCard(
                package: pkg,
                isSelected: selectedPackageId == pkg.id,
                onTap: () => onPackageSelected(pkg.id),
              ),
            );
          }).toList(),
        ),
      ],
    );
  }
}
