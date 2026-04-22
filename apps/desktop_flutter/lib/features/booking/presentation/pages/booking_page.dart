import 'package:flutter/material.dart';
import '../widgets/booking_queue_list.dart';
import '../widgets/customer_form.dart';
import '../widgets/package_section.dart';
import '../widgets/addon_section.dart';
import '../widgets/order_summary.dart';

class BookingPage extends StatelessWidget {
  const BookingPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(flex: 2, child: BookingQueueList()),

        Expanded(
          flex: 5,
          child: Column(
            children: const [
              CustomerForm(),
              Expanded(child: PackageSection()),
              Expanded(child: AddonSection()),
            ],
          ),
        ),

        Expanded(flex: 3, child: OrderSummary()),
      ],
    );
  }
}
