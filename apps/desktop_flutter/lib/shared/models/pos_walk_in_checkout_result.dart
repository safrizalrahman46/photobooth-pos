import 'package:desktop_flutter/shared/models/queue_ticket_item.dart';
import 'package:desktop_flutter/shared/models/transaction_record.dart';

class PosWalkInCheckoutResult {
  const PosWalkInCheckoutResult({
    required this.transaction,
    required this.queueTicket,
  });

  final TransactionRecord transaction;
  final QueueTicketItem queueTicket;

  factory PosWalkInCheckoutResult.fromJson(Map<String, dynamic> json) {
    final transaction = json['transaction'];
    final queueTicket = json['queue_ticket'];

    return PosWalkInCheckoutResult(
      transaction: TransactionRecord.fromJson(
        transaction is Map<String, dynamic> ? transaction : <String, dynamic>{},
      ),
      queueTicket: QueueTicketItem.fromJson(
        queueTicket is Map<String, dynamic> ? queueTicket : <String, dynamic>{},
      ),
    );
  }
}
