import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../application/order_notifier.dart';

class OrderSummary extends ConsumerWidget {
  const OrderSummary({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final order = ref.watch(orderProvider);

    return Container(
      padding: const EdgeInsets.all(16),
      color: Colors.grey[100],
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text("Customer: ${order.customerName}"),
          Text("Package: ${order.selectedPackage ?? "-"}"),
          Text("Total: Rp ${order.total}"),
          ElevatedButton(
            onPressed: () {
              // nanti connect API
            },
            child: const Text("Bayar"),
          ),
        ],
      ),
    );
  }
}
