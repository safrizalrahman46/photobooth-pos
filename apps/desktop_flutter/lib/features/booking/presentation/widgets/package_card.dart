import 'package:flutter/material.dart';

class PackageCard extends StatelessWidget {
  final String title;
  final int price;
  final bool isSelected;
  final VoidCallback onTap;

  const PackageCard({
    super.key,
    required this.title,
    required this.price,
    required this.isSelected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.all(8),
        padding: const EdgeInsets.all(16),
        color: isSelected ? Colors.blue : Colors.grey[200],
        child: Column(children: [Text(title), Text("Rp $price")]),
      ),
    );
  }
}
