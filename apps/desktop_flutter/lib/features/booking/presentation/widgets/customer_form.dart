import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../application/order_notifier.dart';

class CustomerForm extends ConsumerWidget {
  const CustomerForm({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Column(
      children: [
        TextField(
          decoration: const InputDecoration(labelText: "Nama"),
          onChanged: (val) {
            ref.read(orderProvider.notifier).setCustomer(val, "");
          },
        ),
      ],
    );
  }
}
