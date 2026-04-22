import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../application/order_notifier.dart';

class AddonItem extends ConsumerWidget {
  final String name;
  final int price;

  const AddonItem({super.key, required this.name, required this.price});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Row(
      children: [
        Text(name),
        IconButton(
          icon: const Icon(Icons.remove),
          onPressed: () {
            ref.read(orderProvider.notifier).removeAddon(name, price);
          },
        ),
        IconButton(
          icon: const Icon(Icons.add),
          onPressed: () {
            ref.read(orderProvider.notifier).addAddon(name, price);
          },
        ),
      ],
    );
  }
}
