// presentation/widgets/addon_section.dart

import 'package:flutter/material.dart';
import '../../domain/order_state.dart';
import 'addon_item.dart';

class AddonSection extends StatelessWidget {
  final List<AddOn> addOns;
  final ValueChanged<String> onAdd;
  final ValueChanged<String> onIncrement;
  final ValueChanged<String> onDecrement;

  const AddonSection({
    super.key,
    required this.addOns,
    required this.onAdd,
    required this.onIncrement,
    required this.onDecrement,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Experience Add-ons',
          style: TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.w700,
            color: Color(0xFF111827),
          ),
        ),
        const SizedBox(height: 8),
        ...addOns.map(
          (addon) => AddonItem(
            addOn: addon,
            onAdd: () => onAdd(addon.id),
            onIncrement: () => onIncrement(addon.id),
            onDecrement: () => onDecrement(addon.id),
          ),
        ),
      ],
    );
  }
}
