import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../application/order_notifier.dart';
import 'package_card.dart';

class PackageSection extends ConsumerWidget {
  const PackageSection({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final order = ref.watch(orderProvider);

    return Row(
      children: [
        PackageCard(
          title: "Basic",
          price: 20000,
          isSelected: order.selectedPackage == "Basic",
          onTap: () {
            ref.read(orderProvider.notifier).selectPackage("Basic", 20000);
          },
        ),
        PackageCard(
          title: "Premium",
          price: 50000,
          isSelected: order.selectedPackage == "Premium",
          onTap: () {
            ref.read(orderProvider.notifier).selectPackage("Premium", 50000);
          },
        ),
      ],
    );
  }
}
