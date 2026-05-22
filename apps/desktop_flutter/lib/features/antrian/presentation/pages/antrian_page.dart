import 'dart:async';

import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/app/theme/app_text_styles.dart';
import 'package:desktop_flutter/core/network/request_error_message.dart';
import 'package:desktop_flutter/core/session/api_session.dart';
import 'package:desktop_flutter/shared/models/queue_live_payload.dart';
import 'package:flutter/material.dart';

class AntrianPage extends StatefulWidget {
  const AntrianPage({super.key});

  @override
  State<AntrianPage> createState() => _AntrianPageState();
}

class _AntrianPageState extends State<AntrianPage> {
  static const _refreshInterval = Duration(seconds: 15);
  static const _emptyPayload = QueueLivePayload(
    stats: QueueLiveStats(
      inQueue: 0,
      inSession: 0,
      waiting: 0,
      completedToday: 0,
    ),
    current: null,
    tickets: <QueueLiveTicket>[],
  );

  QueueLivePayload _payload = _emptyPayload;
  Timer? _refreshTimer;
  bool _loading = false;
  bool _refreshing = false;
  bool _actionLoading = false;
  int? _processingTicketId;
  String? _error;
  String _lastUpdated = '-';

  @override
  void initState() {
    super.initState();
    _loadQueue();
    _refreshTimer = Timer.periodic(
      _refreshInterval,
      (_) => _loadQueue(silent: true),
    );
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    super.dispose();
  }

  Future<void> _loadQueue({bool silent = false}) async {
    if (_loading || _refreshing) {
      return;
    }

    final client = ApiSession.client;

    if (client == null) {
      setState(() {
        _loading = false;
        _refreshing = false;
        _error = 'Sesi login tidak ditemukan. Silakan masuk ulang.';
      });
      return;
    }

    setState(() {
      if (silent) {
        _refreshing = true;
      } else {
        _loading = true;
      }
      _error = null;
    });

    try {
      final payload = await client.fetchQueueLive(queueDate: _todayIso());

      if (!mounted) {
        return;
      }

      setState(() {
        _payload = payload;
        _lastUpdated = _formatClock(DateTime.now());
      });
    } catch (error) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = resolveRequestErrorMessage(
          error,
          fallback: 'Antrean belum dapat dimuat. Coba lagi beberapa saat.',
        );
      });
    } finally {
      if (mounted) {
        setState(() {
          _loading = false;
          _refreshing = false;
        });
      }
    }
  }

  Future<void> _callNext() async {
    final ticket = _nextWaitingTicket;

    if (ticket == null) {
      return;
    }

    if (ticket.branchId <= 0) {
      setState(() {
        _error = 'Cabang antrean tidak valid. Muat ulang data terlebih dahulu.';
      });
      return;
    }

    await _runQueueAction(
      ticket: ticket,
      action: () => ApiSession.client!.callNext(
        branchId: ticket.branchId,
        queueDate: _todayIso(),
      ),
    );
  }

  Future<void> _promoteTicket(QueueLiveTicket ticket) async {
    final nextStatus = ticket.nextStatus;

    if (nextStatus == null || nextStatus.value.isEmpty) {
      return;
    }

    await _runQueueAction(
      ticket: ticket,
      action: () => ApiSession.client!.transitionQueueTicket(
        ticketId: ticket.id,
        status: nextStatus.value,
      ),
    );
  }

  Future<void> _skipTicket(QueueLiveTicket ticket) async {
    await _runQueueAction(
      ticket: ticket,
      action: () => ApiSession.client!.transitionQueueTicket(
        ticketId: ticket.id,
        status: QueueTicketStatus.skipped.value,
      ),
    );
  }

  Future<void> _runQueueAction({
    required QueueLiveTicket ticket,
    required Future<dynamic> Function() action,
  }) async {
    if (_actionLoading || ApiSession.client == null) {
      return;
    }

    setState(() {
      _actionLoading = true;
      _processingTicketId = ticket.id;
      _error = null;
    });

    try {
      await action();
      await _loadQueue(silent: true);
    } catch (error) {
      if (!mounted) {
        return;
      }

      setState(() {
        _error = resolveRequestErrorMessage(
          error,
          fallback: 'Status antrean belum dapat diperbarui. Coba lagi.',
        );
      });
    } finally {
      if (mounted) {
        setState(() {
          _actionLoading = false;
          _processingTicketId = null;
        });
      }
    }
  }

  QueueLiveTicket? get _nextWaitingTicket {
    for (final ticket in _payload.waitingTickets) {
      if (ticket.status == QueueTicketStatus.waiting) {
        return ticket;
      }
    }

    return null;
  }

  String _todayIso() {
    final now = DateTime.now();
    final month = now.month.toString().padLeft(2, '0');
    final day = now.day.toString().padLeft(2, '0');

    return '${now.year}-$month-$day';
  }

  String _formatClock(DateTime value) {
    return '${value.hour.toString().padLeft(2, '0')}:${value.minute.toString().padLeft(2, '0')}';
  }

  @override
  Widget build(BuildContext context) {
    final nextWaitingTicket = _nextWaitingTicket;

    return Scaffold(
      backgroundColor: const Color(0xFFF3F7FB),
      body: LayoutBuilder(
        builder: (context, constraints) {
          final isCompact = constraints.maxWidth < 1180;

          return SingleChildScrollView(
            padding: EdgeInsets.all(isCompact ? 20 : 28),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _Header(
                  lastUpdated: _lastUpdated,
                  isRefreshing: _refreshing,
                  onRefresh: _actionLoading ? null : () => _loadQueue(),
                ),
                if (_error != null) ...[
                  const SizedBox(height: 16),
                  _ErrorBanner(message: _error!),
                ],
                const SizedBox(height: 18),
                _StatsGrid(stats: _payload.stats, isCompact: isCompact),
                const SizedBox(height: 18),
                if (_loading)
                  const _LoadingState()
                else if (isCompact)
                  Column(
                    children: [
                      _CurrentQueuePanel(
                        current: _payload.current,
                        nextWaitingTicket: nextWaitingTicket,
                        actionLoading: _actionLoading,
                        processingTicketId: _processingTicketId,
                        onCallNext: nextWaitingTicket == null
                            ? null
                            : _callNext,
                        onPromote: _promoteTicket,
                        onSkip: _skipTicket,
                      ),
                      const SizedBox(height: 18),
                      _QueueListPanel(
                        waitingTickets: _payload.waitingTickets,
                        processingTickets: _payload.processingTickets,
                        actionLoading: _actionLoading,
                        processingTicketId: _processingTicketId,
                        onPromote: _promoteTicket,
                      ),
                    ],
                  )
                else
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        flex: 5,
                        child: _CurrentQueuePanel(
                          current: _payload.current,
                          nextWaitingTicket: nextWaitingTicket,
                          actionLoading: _actionLoading,
                          processingTicketId: _processingTicketId,
                          onCallNext: nextWaitingTicket == null
                              ? null
                              : _callNext,
                          onPromote: _promoteTicket,
                          onSkip: _skipTicket,
                        ),
                      ),
                      const SizedBox(width: 18),
                      Expanded(
                        flex: 7,
                        child: _QueueListPanel(
                          waitingTickets: _payload.waitingTickets,
                          processingTickets: _payload.processingTickets,
                          actionLoading: _actionLoading,
                          processingTicketId: _processingTicketId,
                          onPromote: _promoteTicket,
                        ),
                      ),
                    ],
                  ),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _Header extends StatelessWidget {
  const _Header({
    required this.lastUpdated,
    required this.isRefreshing,
    required this.onRefresh,
  });

  final String lastUpdated;
  final bool isRefreshing;
  final VoidCallback? onRefresh;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Panel Antrean', style: AppTextStyles.h1),
              const SizedBox(height: 6),
              Text(
                'Booking terverifikasi dan walk-in checkout akan masuk otomatis ke antrean hari ini.',
                style: AppTextStyles.bodyMedium.copyWith(
                  color: AppColors.textSecondary,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'Terakhir diperbarui: $lastUpdated',
                style: AppTextStyles.bodySmall,
              ),
            ],
          ),
        ),
        const SizedBox(width: 16),
        OutlinedButton.icon(
          onPressed: onRefresh,
          style: OutlinedButton.styleFrom(
            foregroundColor: AppColors.primaryDark,
            side: const BorderSide(color: Color(0xFFBFDBFE)),
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 13),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(14),
            ),
          ),
          icon: isRefreshing
              ? const SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : const Icon(Icons.refresh_rounded, size: 18),
          label: Text(isRefreshing ? 'Memuat...' : 'Muat Ulang'),
        ),
      ],
    );
  }
}

class _StatsGrid extends StatelessWidget {
  const _StatsGrid({required this.stats, required this.isCompact});

  final QueueLiveStats stats;
  final bool isCompact;

  @override
  Widget build(BuildContext context) {
    final cards = [
      _StatCard(
        label: 'Dalam Antrean',
        value: stats.inQueue.toString(),
        icon: Icons.groups_rounded,
        color: const Color(0xFF2563EB),
      ),
      _StatCard(
        label: 'Sesi Berjalan',
        value: stats.inSession.toString(),
        icon: Icons.camera_alt_rounded,
        color: const Color(0xFF7C3AED),
      ),
      _StatCard(
        label: 'Menunggu',
        value: stats.waiting.toString(),
        icon: Icons.schedule_rounded,
        color: const Color(0xFFD97706),
      ),
      _StatCard(
        label: 'Selesai Hari Ini',
        value: stats.completedToday.toString(),
        icon: Icons.check_circle_rounded,
        color: const Color(0xFF059669),
      ),
    ];

    if (isCompact) {
      return Wrap(spacing: 12, runSpacing: 12, children: cards);
    }

    return Row(
      children: [
        for (var index = 0; index < cards.length; index++) ...[
          Expanded(child: cards[index]),
          if (index != cards.length - 1) const SizedBox(width: 12),
        ],
      ],
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({
    required this.label,
    required this.value,
    required this.icon,
    required this.color,
  });

  final String label;
  final String value;
  final IconData icon;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      constraints: const BoxConstraints(minWidth: 170),
      padding: const EdgeInsets.all(16),
      decoration: _panelDecoration(),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Icon(icon, color: color, size: 22),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label, style: AppTextStyles.bodySmall),
                const SizedBox(height: 3),
                Text(
                  value,
                  style: AppTextStyles.h1.copyWith(color: color, fontSize: 30),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _CurrentQueuePanel extends StatelessWidget {
  const _CurrentQueuePanel({
    required this.current,
    required this.nextWaitingTicket,
    required this.actionLoading,
    required this.processingTicketId,
    required this.onCallNext,
    required this.onPromote,
    required this.onSkip,
  });

  final QueueLiveTicket? current;
  final QueueLiveTicket? nextWaitingTicket;
  final bool actionLoading;
  final int? processingTicketId;
  final VoidCallback? onCallNext;
  final ValueChanged<QueueLiveTicket> onPromote;
  final ValueChanged<QueueLiveTicket> onSkip;

  bool get _canSkipCurrent {
    final status = current?.status;

    return status == QueueTicketStatus.called ||
        status == QueueTicketStatus.checkedIn;
  }

  @override
  Widget build(BuildContext context) {
    final ticket = current;

    return Container(
      decoration: _panelDecoration(),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.fromLTRB(22, 22, 22, 24),
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: [Color(0xFF1D4ED8), Color(0xFF3B82F6)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            ),
            child: ticket == null
                ? _NoCurrentQueue(nextWaitingTicket: nextWaitingTicket)
                : _CurrentQueueInfo(ticket: ticket),
          ),
          Padding(
            padding: const EdgeInsets.all(18),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (ticket == null)
                  _PrimaryActionButton(
                    label: nextWaitingTicket == null
                        ? 'Belum Ada Antrean Menunggu'
                        : 'Panggil Berikutnya',
                    icon: Icons.campaign_rounded,
                    isLoading: actionLoading,
                    onPressed: actionLoading || nextWaitingTicket == null
                        ? null
                        : onCallNext,
                  )
                else ...[
                  _SessionProgress(ticket: ticket),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Expanded(
                        child: _PrimaryActionButton(
                          label: ticket.hasNextAction
                              ? ticket.nextActionLabel
                              : 'Tidak Ada Aksi',
                          icon: Icons.check_circle_rounded,
                          isLoading:
                              actionLoading && processingTicketId == ticket.id,
                          onPressed: actionLoading || !ticket.hasNextAction
                              ? null
                              : () => onPromote(ticket),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: _SecondaryActionButton(
                          label: 'Lewati',
                          icon: Icons.skip_next_rounded,
                          isLoading:
                              actionLoading && processingTicketId == ticket.id,
                          onPressed: actionLoading || !_canSkipCurrent
                              ? null
                              : () => onSkip(ticket),
                        ),
                      ),
                    ],
                  ),
                ],
                const SizedBox(height: 12),
                Text(
                  'Alur: Panggil -> Tandai Hadir -> Mulai Sesi -> Selesaikan.',
                  style: AppTextStyles.bodySmall,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _NoCurrentQueue extends StatelessWidget {
  const _NoCurrentQueue({required this.nextWaitingTicket});

  final QueueLiveTicket? nextWaitingTicket;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'SEDANG DILAYANI',
          style: AppTextStyles.captionWhite.copyWith(
            letterSpacing: 1.6,
            fontWeight: FontWeight.w800,
          ),
        ),
        const SizedBox(height: 22),
        Text(
          'Belum ada sesi aktif',
          style: AppTextStyles.h2White.copyWith(fontSize: 24),
        ),
        const SizedBox(height: 8),
        Text(
          nextWaitingTicket == null
              ? 'Booking terverifikasi dan walk-in checkout akan muncul otomatis di daftar antrean.'
              : 'Antrean berikutnya: ${nextWaitingTicket!.queueCode} - ${nextWaitingTicket!.customerName}',
          style: AppTextStyles.bodyWhite.copyWith(color: Colors.white70),
        ),
      ],
    );
  }
}

class _CurrentQueueInfo extends StatelessWidget {
  const _CurrentQueueInfo({required this.ticket});

  final QueueLiveTicket ticket;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Text(
              'SEDANG DILAYANI',
              style: AppTextStyles.captionWhite.copyWith(
                letterSpacing: 1.6,
                fontWeight: FontWeight.w800,
              ),
            ),
            const Spacer(),
            _StatusBadge(status: ticket.status, onDark: true),
          ],
        ),
        const SizedBox(height: 18),
        Text(
          ticket.queueCode,
          style: AppTextStyles.priceLarge.copyWith(
            color: Colors.white,
            fontSize: 52,
          ),
        ),
        const SizedBox(height: 8),
        Text(
          ticket.customerName,
          style: AppTextStyles.h2White.copyWith(fontSize: 22),
          overflow: TextOverflow.ellipsis,
        ),
        const SizedBox(height: 14),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            _SoftPill(label: ticket.packageLabel),
            _SoftPill(label: ticket.branchName),
            _SoftPill(label: ticket.sourceLabel),
          ],
        ),
      ],
    );
  }
}

class _SessionProgress extends StatelessWidget {
  const _SessionProgress({required this.ticket});

  final QueueLiveTicket ticket;

  @override
  Widget build(BuildContext context) {
    final progress = (ticket.progressPercentage / 100).clamp(0.0, 1.0);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Text('Progress Sesi', style: AppTextStyles.bodyMedium),
            const Spacer(),
            Text(
              '${_formatDuration(ticket.remainingSeconds)} tersisa',
              style: AppTextStyles.bodyMedium.copyWith(
                color: AppColors.textSecondary,
              ),
            ),
          ],
        ),
        const SizedBox(height: 8),
        ClipRRect(
          borderRadius: BorderRadius.circular(999),
          child: LinearProgressIndicator(
            value: progress,
            minHeight: 8,
            backgroundColor: const Color(0xFFE2E8F0),
            valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFF2563EB)),
          ),
        ),
      ],
    );
  }
}

class _QueueListPanel extends StatelessWidget {
  const _QueueListPanel({
    required this.waitingTickets,
    required this.processingTickets,
    required this.actionLoading,
    required this.processingTicketId,
    required this.onPromote,
  });

  final List<QueueLiveTicket> waitingTickets;
  final List<QueueLiveTicket> processingTickets;
  final bool actionLoading;
  final int? processingTicketId;
  final ValueChanged<QueueLiveTicket> onPromote;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: _panelDecoration(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.all(18),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Antrean Menunggu', style: AppTextStyles.h2),
                      const SizedBox(height: 4),
                      Text(
                        '${waitingTickets.length} pelanggan menunggu giliran',
                        style: AppTextStyles.bodySmall,
                      ),
                    ],
                  ),
                ),
                _CountBadge(label: '${waitingTickets.length} antrean'),
              ],
            ),
          ),
          const Divider(height: 1, color: AppColors.divider),
          if (waitingTickets.isEmpty)
            const _EmptyQueueState()
          else
            for (final ticket in waitingTickets)
              _QueueTicketRow(
                ticket: ticket,
                actionLoading: actionLoading,
                processingTicketId: processingTicketId,
                onPromote: onPromote,
              ),
          if (processingTickets.isNotEmpty) ...[
            const Divider(height: 1, color: AppColors.divider),
            Padding(
              padding: const EdgeInsets.fromLTRB(18, 18, 18, 10),
              child: Text(
                'Sedang Diproses',
                style: AppTextStyles.captionMedium.copyWith(
                  color: AppColors.textSecondary,
                  letterSpacing: 1,
                ),
              ),
            ),
            for (final ticket in processingTickets)
              _QueueTicketRow(
                ticket: ticket,
                actionLoading: actionLoading,
                processingTicketId: processingTicketId,
                onPromote: onPromote,
                compact: true,
              ),
          ],
        ],
      ),
    );
  }
}

class _QueueTicketRow extends StatelessWidget {
  const _QueueTicketRow({
    required this.ticket,
    required this.actionLoading,
    required this.processingTicketId,
    required this.onPromote,
    this.compact = false,
  });

  final QueueLiveTicket ticket;
  final bool actionLoading;
  final int? processingTicketId;
  final ValueChanged<QueueLiveTicket> onPromote;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final isProcessing = actionLoading && processingTicketId == ticket.id;

    return Container(
      padding: EdgeInsets.symmetric(
        horizontal: 18,
        vertical: compact ? 12 : 16,
      ),
      decoration: const BoxDecoration(
        border: Border(bottom: BorderSide(color: AppColors.divider)),
      ),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: const Color(0xFFEFF6FF),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Center(
              child: Text(
                ticket.queueNumber <= 0 ? '-' : ticket.queueNumber.toString(),
                style: AppTextStyles.h4.copyWith(
                  color: AppColors.primaryDark,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Wrap(
                  spacing: 8,
                  runSpacing: 6,
                  crossAxisAlignment: WrapCrossAlignment.center,
                  children: [
                    Text(
                      ticket.queueCode,
                      style: AppTextStyles.h3.copyWith(
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    _StatusBadge(status: ticket.status),
                  ],
                ),
                const SizedBox(height: 5),
                Text(
                  ticket.customerName,
                  style: AppTextStyles.bodyMedium,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 3),
                Text(
                  '${ticket.branchName} | ${ticket.packageLabel} | Masuk ${ticket.addedAt}',
                  style: AppTextStyles.bodySmall,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          _PrimaryActionButton(
            label: ticket.hasNextAction ? ticket.nextActionLabel : 'Selesai',
            icon: Icons.arrow_forward_rounded,
            isLoading: isProcessing,
            compact: true,
            onPressed: actionLoading || !ticket.hasNextAction
                ? null
                : () => onPromote(ticket),
          ),
        ],
      ),
    );
  }
}

class _PrimaryActionButton extends StatelessWidget {
  const _PrimaryActionButton({
    required this.label,
    required this.icon,
    required this.isLoading,
    required this.onPressed,
    this.compact = false,
  });

  final String label;
  final IconData icon;
  final bool isLoading;
  final VoidCallback? onPressed;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    return ElevatedButton.icon(
      onPressed: isLoading ? null : onPressed,
      style: ElevatedButton.styleFrom(
        backgroundColor: const Color(0xFF2563EB),
        foregroundColor: Colors.white,
        disabledBackgroundColor: const Color(0xFFE2E8F0),
        disabledForegroundColor: AppColors.textMuted,
        elevation: 0,
        padding: EdgeInsets.symmetric(
          horizontal: compact ? 12 : 16,
          vertical: compact ? 12 : 15,
        ),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      ),
      icon: isLoading
          ? const SizedBox(
              width: 16,
              height: 16,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
              ),
            )
          : Icon(icon, size: 18),
      label: Text(label, textAlign: TextAlign.center),
    );
  }
}

class _SecondaryActionButton extends StatelessWidget {
  const _SecondaryActionButton({
    required this.label,
    required this.icon,
    required this.isLoading,
    required this.onPressed,
  });

  final String label;
  final IconData icon;
  final bool isLoading;
  final VoidCallback? onPressed;

  @override
  Widget build(BuildContext context) {
    return OutlinedButton.icon(
      onPressed: isLoading ? null : onPressed,
      style: OutlinedButton.styleFrom(
        foregroundColor: const Color(0xFFD97706),
        side: const BorderSide(color: Color(0xFFFDE68A)),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 15),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      ),
      icon: isLoading
          ? const SizedBox(
              width: 16,
              height: 16,
              child: CircularProgressIndicator(strokeWidth: 2),
            )
          : Icon(icon, size: 18),
      label: Text(label),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  const _StatusBadge({required this.status, this.onDark = false});

  final QueueTicketStatus status;
  final bool onDark;

  @override
  Widget build(BuildContext context) {
    final color = _statusColor(status);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: onDark
            ? Colors.white.withValues(alpha: 0.18)
            : color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        status.label,
        style: AppTextStyles.captionMedium.copyWith(
          color: onDark ? Colors.white : color,
          fontWeight: FontWeight.w800,
        ),
      ),
    );
  }
}

class _SoftPill extends StatelessWidget {
  const _SoftPill({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.18),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: AppTextStyles.captionWhite.copyWith(color: Colors.white),
      ),
    );
  }
}

class _CountBadge extends StatelessWidget {
  const _CountBadge({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Text(label, style: AppTextStyles.captionMedium),
    );
  }
}

class _EmptyQueueState extends StatelessWidget {
  const _EmptyQueueState();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(34),
      child: Center(
        child: Column(
          children: [
            const Icon(
              Icons.hourglass_empty_rounded,
              color: AppColors.textMuted,
              size: 30,
            ),
            const SizedBox(height: 10),
            Text(
              'Belum ada pelanggan yang menunggu.',
              style: AppTextStyles.bodyMedium.copyWith(
                color: AppColors.textSecondary,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              'Booking terverifikasi dan walk-in checkout akan muncul otomatis di sini.',
              style: AppTextStyles.bodySmall,
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}

class _LoadingState extends StatelessWidget {
  const _LoadingState();

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 260,
      decoration: _panelDecoration(),
      child: const Center(child: CircularProgressIndicator()),
    );
  }
}

class _ErrorBanner extends StatelessWidget {
  const _ErrorBanner({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF1F1),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFFFCDD2)),
      ),
      child: Row(
        children: [
          const Icon(Icons.error_outline_rounded, color: Colors.redAccent),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(color: Colors.redAccent, fontSize: 13),
            ),
          ),
        ],
      ),
    );
  }
}

BoxDecoration _panelDecoration() {
  return BoxDecoration(
    color: Colors.white,
    borderRadius: BorderRadius.circular(24),
    border: Border.all(color: AppColors.cardBorder),
    boxShadow: [
      BoxShadow(
        color: const Color(0xFF0F172A).withValues(alpha: 0.04),
        blurRadius: 18,
        offset: const Offset(0, 10),
      ),
    ],
  );
}

Color _statusColor(QueueTicketStatus status) {
  return switch (status) {
    QueueTicketStatus.waiting => const Color(0xFF2563EB),
    QueueTicketStatus.called => const Color(0xFFD97706),
    QueueTicketStatus.checkedIn => const Color(0xFF059669),
    QueueTicketStatus.inSession => const Color(0xFF7C3AED),
    QueueTicketStatus.finished => const Color(0xFF047857),
    QueueTicketStatus.skipped => const Color(0xFFB91C1C),
    QueueTicketStatus.cancelled => const Color(0xFF64748B),
    QueueTicketStatus.unknown => AppColors.textSecondary,
  };
}

String _formatDuration(int seconds) {
  final safeSeconds = seconds < 0 ? 0 : seconds;
  final minutes = safeSeconds ~/ 60;
  final rest = safeSeconds % 60;

  return '${minutes.toString().padLeft(2, '0')}:${rest.toString().padLeft(2, '0')}';
}
