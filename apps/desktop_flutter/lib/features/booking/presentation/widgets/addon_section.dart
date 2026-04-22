import 'package:flutter/material.dart';
import 'addon_item.dart';

class AddonSection extends StatelessWidget {
  const AddonSection({super.key});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: const [
        AddonItem(name: "Frame", price: 5000),
        AddonItem(name: "Extra Print", price: 5000),
      ],
    );
  }
}
