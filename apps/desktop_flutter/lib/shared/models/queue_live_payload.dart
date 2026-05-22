class QueueLivePayload {
  const QueueLivePayload({
    required this.stats,
    required this.current,
    required this.tickets,
  });

  final QueueLiveStats stats;
  final QueueLiveTicket? current;
  final List<QueueLiveTicket> tickets;

  List<QueueLiveTicket> get waitingTickets {
    final currentId = current?.id ?? 0;

    return tickets.where((ticket) {
      if (ticket.id != 0 && ticket.id == currentId) {
        return false;
      }

      return ticket.status == QueueTicketStatus.waiting ||
          ticket.status == QueueTicketStatus.skipped;
    }).toList();
  }

  List<QueueLiveTicket> get processingTickets {
    final currentId = current?.id ?? 0;

    return tickets.where((ticket) {
      if (ticket.id != 0 && ticket.id == currentId) {
        return false;
      }

      return ticket.status == QueueTicketStatus.called ||
          ticket.status == QueueTicketStatus.checkedIn ||
          ticket.status == QueueTicketStatus.inSession;
    }).toList();
  }

  factory QueueLivePayload.fromJson(Map<String, dynamic> json) {
    final rawCurrent = json['current'];
    final rawWaiting = json['waiting'];

    return QueueLivePayload(
      stats: QueueLiveStats.fromJson(
        json['stats'] as Map<String, dynamic>? ?? <String, dynamic>{},
      ),
      current: rawCurrent is Map<String, dynamic>
          ? QueueLiveTicket.fromJson(rawCurrent)
          : null,
      tickets: rawWaiting is List
          ? rawWaiting
                .whereType<Map<String, dynamic>>()
                .map(QueueLiveTicket.fromJson)
                .toList()
          : <QueueLiveTicket>[],
    );
  }
}

class QueueLiveStats {
  const QueueLiveStats({
    required this.inQueue,
    required this.inSession,
    required this.waiting,
    required this.completedToday,
  });

  final int inQueue;
  final int inSession;
  final int waiting;
  final int completedToday;

  factory QueueLiveStats.fromJson(Map<String, dynamic> json) {
    return QueueLiveStats(
      inQueue: (json['in_queue'] as num?)?.toInt() ?? 0,
      inSession: (json['in_session'] as num?)?.toInt() ?? 0,
      waiting: (json['waiting'] as num?)?.toInt() ?? 0,
      completedToday: (json['completed_today'] as num?)?.toInt() ?? 0,
    );
  }
}

class QueueLiveTicket {
  const QueueLiveTicket({
    required this.id,
    required this.bookingId,
    required this.branchId,
    required this.branchName,
    required this.queueDate,
    required this.sourceType,
    required this.queueCode,
    required this.queueNumber,
    required this.customerName,
    required this.packageName,
    required this.status,
    required this.nextStatus,
    required this.addedAt,
    required this.sessionDurationSeconds,
    required this.remainingSeconds,
    required this.progressPercentage,
  });

  final int id;
  final int? bookingId;
  final int branchId;
  final String branchName;
  final String queueDate;
  final String sourceType;
  final String queueCode;
  final int queueNumber;
  final String customerName;
  final String packageName;
  final QueueTicketStatus status;
  final QueueTicketStatus? nextStatus;
  final String addedAt;
  final int sessionDurationSeconds;
  final int remainingSeconds;
  final double progressPercentage;

  String get statusLabel => status.label;

  String get sourceLabel {
    return sourceType.toLowerCase() == 'walk_in' ? 'Walk-in' : 'Booking';
  }

  String get packageLabel {
    final normalized = packageName.trim();
    if (normalized.isNotEmpty && normalized != '-') {
      return normalized;
    }

    return sourceLabel;
  }

  String get nextActionLabel {
    if (status == QueueTicketStatus.skipped &&
        nextStatus == QueueTicketStatus.called) {
      return 'Panggil Ulang';
    }

    return status.nextActionLabel;
  }

  bool get hasNextAction => nextStatus != null && nextActionLabel.isNotEmpty;

  factory QueueLiveTicket.fromJson(Map<String, dynamic> json) {
    return QueueLiveTicket(
      id:
          (json['ticket_id'] as num?)?.toInt() ??
          (json['id'] as num?)?.toInt() ??
          0,
      bookingId: (json['booking_id'] as num?)?.toInt(),
      branchId: (json['branch_id'] as num?)?.toInt() ?? 0,
      branchName: json['branch_name']?.toString() ?? '-',
      queueDate: json['queue_date']?.toString() ?? '-',
      sourceType: json['source_type']?.toString() ?? 'booking',
      queueCode: json['queue_code']?.toString() ?? '-',
      queueNumber: (json['queue_number'] as num?)?.toInt() ?? 0,
      customerName: json['customer_name']?.toString() ?? '-',
      packageName: json['package_name']?.toString() ?? '-',
      status: QueueTicketStatus.fromValue(json['status']?.toString()),
      nextStatus:
          QueueTicketStatus.tryParse(json['next_status']?.toString()) ??
          QueueTicketStatus.nextAfter(
            QueueTicketStatus.fromValue(json['status']?.toString()),
          ),
      addedAt: json['added_at']?.toString() ?? '-',
      sessionDurationSeconds:
          (json['session_duration_seconds'] as num?)?.toInt() ?? 0,
      remainingSeconds: (json['remaining_seconds'] as num?)?.toInt() ?? 0,
      progressPercentage:
          (json['progress_percentage'] as num?)?.toDouble() ?? 0,
    );
  }
}

enum QueueTicketStatus {
  waiting,
  called,
  checkedIn,
  inSession,
  finished,
  skipped,
  cancelled,
  unknown;

  String get value {
    return switch (this) {
      QueueTicketStatus.waiting => 'waiting',
      QueueTicketStatus.called => 'called',
      QueueTicketStatus.checkedIn => 'checked_in',
      QueueTicketStatus.inSession => 'in_session',
      QueueTicketStatus.finished => 'finished',
      QueueTicketStatus.skipped => 'skipped',
      QueueTicketStatus.cancelled => 'cancelled',
      QueueTicketStatus.unknown => '',
    };
  }

  String get label {
    return switch (this) {
      QueueTicketStatus.waiting => 'Menunggu',
      QueueTicketStatus.called => 'Dipanggil',
      QueueTicketStatus.checkedIn => 'Hadir',
      QueueTicketStatus.inSession => 'Sesi Berjalan',
      QueueTicketStatus.finished => 'Selesai',
      QueueTicketStatus.skipped => 'Dilewati',
      QueueTicketStatus.cancelled => 'Dibatalkan',
      QueueTicketStatus.unknown => 'Status Tidak Dikenal',
    };
  }

  String get nextActionLabel {
    return switch (this) {
      QueueTicketStatus.waiting => 'Panggil',
      QueueTicketStatus.called => 'Tandai Hadir',
      QueueTicketStatus.checkedIn => 'Mulai Sesi',
      QueueTicketStatus.inSession => 'Selesaikan Sesi',
      QueueTicketStatus.skipped => 'Panggil Ulang',
      QueueTicketStatus.finished ||
      QueueTicketStatus.cancelled ||
      QueueTicketStatus.unknown => '',
    };
  }

  static QueueTicketStatus fromValue(String? value) {
    return tryParse(value) ?? QueueTicketStatus.unknown;
  }

  static QueueTicketStatus? tryParse(String? value) {
    return switch ((value ?? '').trim().toLowerCase()) {
      'waiting' => QueueTicketStatus.waiting,
      'called' => QueueTicketStatus.called,
      'checked_in' => QueueTicketStatus.checkedIn,
      'in_session' => QueueTicketStatus.inSession,
      'finished' => QueueTicketStatus.finished,
      'skipped' => QueueTicketStatus.skipped,
      'cancelled' => QueueTicketStatus.cancelled,
      _ => null,
    };
  }

  static QueueTicketStatus? nextAfter(QueueTicketStatus status) {
    return switch (status) {
      QueueTicketStatus.waiting => QueueTicketStatus.called,
      QueueTicketStatus.called => QueueTicketStatus.checkedIn,
      QueueTicketStatus.checkedIn => QueueTicketStatus.inSession,
      QueueTicketStatus.inSession => QueueTicketStatus.finished,
      QueueTicketStatus.skipped => QueueTicketStatus.called,
      QueueTicketStatus.finished ||
      QueueTicketStatus.cancelled ||
      QueueTicketStatus.unknown => null,
    };
  }
}
