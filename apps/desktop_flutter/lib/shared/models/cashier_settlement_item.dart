import 'package:desktop_flutter/shared/models/cashier_session_item.dart';

class CashierSettlementItem {
  const CashierSettlementItem({
    required this.id,
    required this.settlementCode,
    required this.cashierSessionId,
    required this.branchName,
    required this.cashierName,
    required this.businessDateText,
    required this.periodLabel,
    required this.totalSales,
    required this.cashReceived,
    required this.nonCashReceived,
    required this.cashExpensesTotal,
    required this.cashToDeposit,
    required this.openingCash,
    required this.correctionsTotal,
    required this.finalCashToDeposit,
    required this.printCount,
    required this.isLateClose,
    required this.snapshot,
  });

  final int id;
  final String settlementCode;
  final int cashierSessionId;
  final String branchName;
  final String cashierName;
  final String businessDateText;
  final String periodLabel;
  final double totalSales;
  final double cashReceived;
  final double nonCashReceived;
  final double cashExpensesTotal;
  final double cashToDeposit;
  final double openingCash;
  final double correctionsTotal;
  final double finalCashToDeposit;
  final int printCount;
  final bool isLateClose;
  final Map<String, dynamic> snapshot;

  bool get isReprint => printCount > 1;

  factory CashierSettlementItem.fromJson(Map<String, dynamic> json) {
    final snapshot = json['snapshot'];

    return CashierSettlementItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      settlementCode: json['settlement_code']?.toString() ?? '-',
      cashierSessionId: (json['cashier_session_id'] as num?)?.toInt() ?? 0,
      branchName: json['branch_name']?.toString() ?? '-',
      cashierName: json['cashier_name']?.toString() ?? '-',
      businessDateText: json['business_date_text']?.toString() ?? '-',
      periodLabel: json['period_label']?.toString() ?? '-',
      totalSales: (json['total_sales'] as num?)?.toDouble() ?? 0,
      cashReceived: (json['cash_received'] as num?)?.toDouble() ?? 0,
      nonCashReceived: (json['non_cash_received'] as num?)?.toDouble() ?? 0,
      cashExpensesTotal:
          (json['cash_expenses_total'] as num?)?.toDouble() ?? 0,
      cashToDeposit: (json['cash_to_deposit'] as num?)?.toDouble() ?? 0,
      openingCash: (json['opening_cash'] as num?)?.toDouble() ?? 0,
      correctionsTotal: (json['corrections_total'] as num?)?.toDouble() ?? 0,
      finalCashToDeposit:
          (json['final_cash_to_deposit'] as num?)?.toDouble() ?? 0,
      printCount: (json['print_count'] as num?)?.toInt() ?? 0,
      isLateClose: json['is_late_close'] == true,
      snapshot: snapshot is Map<String, dynamic> ? snapshot : <String, dynamic>{},
    );
  }
}

class CashierSessionCloseResult {
  const CashierSessionCloseResult({required this.session, required this.settlement});

  final CashierSessionItem session;
  final CashierSettlementItem settlement;
}
