import 'package:flutter/material.dart';

class PaymentMethod {
  static const String cash = 'cash';
  static const String qris = 'qris';
  static const String transfer = 'transfer';
  static const String card = 'card';

  static const List<String> values = [cash, qris, transfer, card];

  static String label(String method) {
    return switch (method) {
      cash => 'Tunai',
      qris => 'QRIS',
      transfer => 'Transfer',
      card => 'Kartu',
      _ => method,
    };
  }

  static IconData icon(String method) {
    return switch (method) {
      cash => Icons.payments_rounded,
      qris => Icons.qr_code_2_rounded,
      transfer => Icons.account_balance_rounded,
      card => Icons.credit_card_rounded,
      _ => Icons.payments_rounded,
    };
  }
}
