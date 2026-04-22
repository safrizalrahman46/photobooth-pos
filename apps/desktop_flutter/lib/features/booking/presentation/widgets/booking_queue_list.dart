import 'package:flutter/material.dart';

class BookingQueueList extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return ListView(
      children: const [
        ListTile(title: Text("Antrian #1")),
        ListTile(title: Text("Antrian #2")),
      ],
    );
  }
}
