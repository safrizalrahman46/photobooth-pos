import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/app/theme/app_text_styles.dart';
import 'package:desktop_flutter/core/network/api_client.dart';
import 'package:desktop_flutter/core/session/api_session.dart';
import 'package:desktop_flutter/shared/models/inventory_monitoring_payload.dart';
import 'package:flutter/material.dart';

class StockPage extends StatefulWidget {
  const StockPage({super.key});

  @override
  State<StockPage> createState() => _StockPageState();
}

class _StockPageState extends State<StockPage> {
  bool _loading = true;
  String? _error;
  InventoryMonitoringPayload _payload = const InventoryMonitoringPayload(
    items: <InventoryStockItem>[],
    movements: <InventoryMovementItem>[],
  );

  @override
  void initState() {
    super.initState();
    _loadStock();
  }

  Future<void> _loadStock() async {
    final client = ApiSession.client;

    if (client == null) {
      setState(() {
        _loading = false;
        _error = 'Sesi login tidak ditemukan.';
      });
      return;
    }

    // ================= DEBUG TOKEN =================
    // print('=========== STOCK PAGE DEBUG ===========');
    // print('CLIENT: $client');
    // print('TOKEN: ${client.token}');
    // print('=======================================');
    // ==============================================

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      // ============== DEBUG SEBELUM REQUEST ==============
      // print('Memulai request inventory monitoring...');
      // ===================================================

      final payload = await client.fetchInventoryMonitoring();

      // ============== DEBUG SETELAH REQUEST ==============
      // print('Request berhasil!');
      // print('Jumlah items: ${payload.items.length}');
      // print('Jumlah movements: ${payload.movements.length}');
      // ==================================================

      if (!mounted) {
        return;
      }

      setState(() => _payload = payload);
    } on ApiException catch (error) {
      // ============== DEBUG ERROR API ====================
      // print('ApiException: ${error.message}');
      // ==================================================

      if (!mounted) {
        return;
      }

      setState(() => _error = error.message);
    } catch (e, stackTrace) {
      // ============== DEBUG ERROR UMUM ===================
      // print('ERROR UMUM: $e');
      // print('STACK TRACE: $stackTrace');
      // ==================================================

      if (!mounted) {
        return;
      }

      setState(() => _error = 'Tidak dapat memuat data monitoring stok.');
    } finally {
      if (mounted) {
        setState(() => _loading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final totalItems = _payload.items.length;
    final lowStock = _payload.items.where((item) => item.isLow).length;
    final outOfStock = _payload.items.where((item) => item.isOut).length;
    final readyStock = totalItems - lowStock - outOfStock;
    final activeItems = _payload.items.where((item) => item.isActive).length;

    return Scaffold(
      backgroundColor: const Color(0xFFF3F7FB),
      body: LayoutBuilder(
        builder: (context, constraints) {
          final isCompact = constraints.maxWidth < 1080;

          return SingleChildScrollView(
            padding: EdgeInsets.all(isCompact ? 20 : 28),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _ConsoleHeader(
                  isLoading: _loading,
                  totalItems: totalItems,
                  movementCount: _payload.movements.length,
                  onRefresh: _loading ? null : _loadStock,
                ),
                if (_error != null) ...[
                  const SizedBox(height: 16),
                  _ErrorBanner(message: _error!),
                ],
                const SizedBox(height: 20),
                if (_loading)
                  const _ConsoleLoading()
                else
                  _buildWorkspace(
                    isCompact: isCompact,
                    totalItems: totalItems,
                    readyStock: readyStock,
                    lowStock: lowStock,
                    outOfStock: outOfStock,
                    activeItems: activeItems,
                  ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildWorkspace({
    required bool isCompact,
    required int totalItems,
    required int readyStock,
    required int lowStock,
    required int outOfStock,
    required int activeItems,
  }) {
    final healthPanel = _InventoryHealthPanel(
      totalItems: totalItems,
      readyStock: readyStock,
      lowStock: lowStock,
      outOfStock: outOfStock,
      activeItems: activeItems,
    );
    final stockBoard = _StockBoard(items: _payload.items);
    final timeline = _MovementTimeline(movements: _payload.movements);

    if (isCompact) {
      return Column(
        children: [
          healthPanel,
          const SizedBox(height: 16),
          stockBoard,
          const SizedBox(height: 16),
          timeline,
        ],
      );
    }

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(width: 318, child: healthPanel),
        const SizedBox(width: 20),
        Expanded(
          child: Column(
            children: [stockBoard, const SizedBox(height: 20), timeline],
          ),
        ),
      ],
    );
  }
}

class _ConsoleHeader extends StatelessWidget {
  const _ConsoleHeader({
    required this.isLoading,
    required this.totalItems,
    required this.movementCount,
    required this.onRefresh,
  });

  final bool isLoading;
  final int totalItems;
  final int movementCount;
  final VoidCallback? onRefresh;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(28),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF0B1220), Color(0xFF0F2F3A), Color(0xFF14532D)],
        ),
        boxShadow: const [
          BoxShadow(
            color: Color(0x240B1220),
            blurRadius: 28,
            offset: Offset(0, 16),
          ),
        ],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 6,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(999),
                    border: Border.all(
                      color: Colors.white.withValues(alpha: 0.16),
                    ),
                  ),
                  child: const Text(
                    'READ-ONLY STOCK MONITOR',
                    style: TextStyle(
                      color: Color(0xFFB7F7D0),
                      fontSize: 11,
                      fontWeight: FontWeight.w800,
                      letterSpacing: 1.2,
                    ),
                  ),
                ),
                const SizedBox(height: 14),
                const Text(
                  'Inventory Control Console',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 28,
                    fontWeight: FontWeight.w900,
                    letterSpacing: -0.6,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  'Pantau stok fisik dan movement terbaru tanpa akses manage barang dari aplikasi desktop.',
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.72),
                    fontSize: 13,
                    height: 1.5,
                  ),
                ),
                const SizedBox(height: 18),
                Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: [
                    _HeaderMetric(
                      icon: Icons.inventory_2_rounded,
                      label: '$totalItems item stock',
                    ),
                    _HeaderMetric(
                      icon: Icons.sync_alt_rounded,
                      label: '$movementCount movement',
                    ),
                    const _HeaderMetric(
                      icon: Icons.lock_outline_rounded,
                      label: 'Monitoring only',
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(width: 18),
          _RefreshControl(isLoading: isLoading, onRefresh: onRefresh),
        ],
      ),
    );
  }
}

class _HeaderMetric extends StatelessWidget {
  const _HeaderMetric({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: Colors.white.withValues(alpha: 0.12)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 15, color: const Color(0xFFB7F7D0)),
          const SizedBox(width: 8),
          Text(
            label,
            style: const TextStyle(
              color: Color(0xFFE2E8F0),
              fontSize: 12,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }
}

class _RefreshControl extends StatelessWidget {
  const _RefreshControl({required this.isLoading, required this.onRefresh});

  final bool isLoading;
  final VoidCallback? onRefresh;

  @override
  Widget build(BuildContext context) {
    return OutlinedButton.icon(
      onPressed: onRefresh,
      style: OutlinedButton.styleFrom(
        foregroundColor: Colors.white,
        disabledForegroundColor: Colors.white.withValues(alpha: 0.54),
        side: BorderSide(color: Colors.white.withValues(alpha: 0.24)),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
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
          : const Icon(Icons.refresh_rounded, size: 18),
      label: Text(isLoading ? 'Loading' : 'Refresh'),
    );
  }
}

class _ConsoleLoading extends StatelessWidget {
  const _ConsoleLoading();

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 260,
      width: double.infinity,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppColors.cardBorder),
      ),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const CircularProgressIndicator(),
          const SizedBox(height: 16),
          Text(
            'Memuat snapshot inventory...',
            style: AppTextStyles.bodyMedium.copyWith(
              color: AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }
}

class _InventoryHealthPanel extends StatelessWidget {
  const _InventoryHealthPanel({
    required this.totalItems,
    required this.readyStock,
    required this.lowStock,
    required this.outOfStock,
    required this.activeItems,
  });

  final int totalItems;
  final int readyStock;
  final int lowStock;
  final int outOfStock;
  final int activeItems;

  @override
  Widget build(BuildContext context) {
    return _ConsolePanel(
      title: 'Inventory Health',
      subtitle: 'Ringkasan kondisi stok saat ini.',
      icon: Icons.monitor_heart_rounded,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _HealthBar(
            totalItems: totalItems,
            readyStock: readyStock,
            lowStock: lowStock,
            outOfStock: outOfStock,
          ),
          const SizedBox(height: 18),
          _MetricTile(
            label: 'Ready',
            value: readyStock.toString(),
            icon: Icons.check_circle_rounded,
            color: const Color(0xFF047857),
            background: const Color(0xFFECFDF5),
          ),
          const SizedBox(height: 10),
          _MetricTile(
            label: 'Low stock',
            value: lowStock.toString(),
            icon: Icons.warning_amber_rounded,
            color: const Color(0xFFD97706),
            background: const Color(0xFFFFF7ED),
          ),
          const SizedBox(height: 10),
          _MetricTile(
            label: 'Out of stock',
            value: outOfStock.toString(),
            icon: Icons.error_rounded,
            color: const Color(0xFFDC2626),
            background: const Color(0xFFFEF2F2),
          ),
          const SizedBox(height: 10),
          _MetricTile(
            label: 'Active items',
            value: '$activeItems/$totalItems',
            icon: Icons.toggle_on_rounded,
            color: AppColors.primaryDark,
            background: AppColors.primaryLight,
          ),
          const SizedBox(height: 18),
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: const Color(0xFF0F172A),
              borderRadius: BorderRadius.circular(18),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(
                  Icons.lock_outline_rounded,
                  color: Color(0xFFB7F7D0),
                  size: 18,
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    'Cashier desktop hanya bisa memonitor stok. Penyesuaian stok tetap dilakukan dari dashboard admin/owner.',
                    style: AppTextStyles.bodySmall.copyWith(
                      color: Colors.white.withValues(alpha: 0.72),
                      height: 1.5,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _HealthBar extends StatelessWidget {
  const _HealthBar({
    required this.totalItems,
    required this.readyStock,
    required this.lowStock,
    required this.outOfStock,
  });

  final int totalItems;
  final int readyStock;
  final int lowStock;
  final int outOfStock;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        ClipRRect(
          borderRadius: BorderRadius.circular(999),
          child: SizedBox(
            height: 12,
            child: totalItems == 0
                ? Container(color: const Color(0xFFE5E7EB))
                : Row(
                    children: [
                      if (readyStock > 0)
                        Expanded(
                          flex: readyStock,
                          child: Container(color: const Color(0xFF10B981)),
                        ),
                      if (lowStock > 0)
                        Expanded(
                          flex: lowStock,
                          child: Container(color: const Color(0xFFF59E0B)),
                        ),
                      if (outOfStock > 0)
                        Expanded(
                          flex: outOfStock,
                          child: Container(color: const Color(0xFFEF4444)),
                        ),
                    ],
                  ),
          ),
        ),
        const SizedBox(height: 10),
        const Wrap(
          spacing: 12,
          runSpacing: 8,
          children: [
            _HealthLegend(color: Color(0xFF10B981), label: 'Ready'),
            _HealthLegend(color: Color(0xFFF59E0B), label: 'Low'),
            _HealthLegend(color: Color(0xFFEF4444), label: 'Out'),
          ],
        ),
      ],
    );
  }
}

class _HealthLegend extends StatelessWidget {
  const _HealthLegend({required this.color, required this.label});

  final Color color;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 8,
          height: 8,
          decoration: BoxDecoration(color: color, shape: BoxShape.circle),
        ),
        const SizedBox(width: 6),
        Text(label, style: AppTextStyles.captionMedium),
      ],
    );
  }
}

class _MetricTile extends StatelessWidget {
  const _MetricTile({
    required this.label,
    required this.value,
    required this.icon,
    required this.color,
    required this.background,
  });

  final String label;
  final String value;
  final IconData icon;
  final Color color;
  final Color background;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        children: [
          Icon(icon, color: color, size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              label,
              style: AppTextStyles.bodyMedium.copyWith(color: color),
            ),
          ),
          Text(
            value,
            style: TextStyle(
              color: color,
              fontSize: 18,
              fontWeight: FontWeight.w900,
            ),
          ),
        ],
      ),
    );
  }
}

class _StockBoard extends StatelessWidget {
  const _StockBoard({required this.items});

  final List<InventoryStockItem> items;

  @override
  Widget build(BuildContext context) {
    return _ConsolePanel(
      title: 'Stock Board',
      subtitle: 'Daftar barang fisik dalam mode monitor.',
      icon: Icons.view_agenda_rounded,
      trailing: _CountBadge(label: '${items.length} items'),
      child: items.isEmpty
          ? const _EmptyState(message: 'Belum ada barang stok.')
          : Column(
              children: [
                for (var index = 0; index < items.length; index++) ...[
                  _StockItemRow(item: items[index]),
                  if (index != items.length - 1) const SizedBox(height: 10),
                ],
              ],
            ),
    );
  }
}

class _StockItemRow extends StatelessWidget {
  const _StockItemRow({required this.item});

  final InventoryStockItem item;

  Color get _statusColor {
    if (item.isOut) {
      return const Color(0xFFDC2626);
    }

    if (item.isLow) {
      return const Color(0xFFD97706);
    }

    return const Color(0xFF059669);
  }

  Color get _statusBackground {
    if (item.isOut) {
      return const Color(0xFFFEF2F2);
    }

    if (item.isLow) {
      return const Color(0xFFFFF7ED);
    }

    return const Color(0xFFECFDF5);
  }

  @override
  Widget build(BuildContext context) {
    final code = item.code.isEmpty ? 'NO CODE' : item.code;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFFBFCFE),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE5E7EB)),
      ),
      child: Row(
        children: [
          Container(
            width: 4,
            height: 54,
            decoration: BoxDecoration(
              color: _statusColor,
              borderRadius: BorderRadius.circular(999),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        item.name,
                        style: AppTextStyles.h3.copyWith(
                          fontWeight: FontWeight.w800,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    const SizedBox(width: 8),
                    _SmallPill(
                      label: item.isActive ? 'Active' : 'Inactive',
                      color: item.isActive
                          ? const Color(0xFF047857)
                          : AppColors.textMuted,
                      background: item.isActive
                          ? const Color(0xFFEAFBF2)
                          : const Color(0xFFF3F4F6),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    _SmallPill(
                      label: code,
                      color: const Color(0xFF334155),
                      background: const Color(0xFFEFF6FF),
                    ),
                    _SmallPill(
                      label:
                          'Low threshold ${item.lowStockThreshold} ${item.unit}',
                      color: AppColors.textSecondary,
                      background: const Color(0xFFF8FAFC),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(width: 16),
          _StockQuantity(item: item, color: _statusColor),
          const SizedBox(width: 16),
          _Badge(
            label: item.stockStatusLabel,
            color: _statusColor,
            background: _statusBackground,
          ),
        ],
      ),
    );
  }
}

class _StockQuantity extends StatelessWidget {
  const _StockQuantity({required this.item, required this.color});

  final InventoryStockItem item;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      constraints: const BoxConstraints(minWidth: 92),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE5E7EB)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Text(
            item.availableStock.toString(),
            style: TextStyle(
              color: color,
              fontSize: 24,
              fontWeight: FontWeight.w900,
              height: 1,
            ),
          ),
          const SizedBox(height: 4),
          Text(item.unit, style: AppTextStyles.captionMedium),
        ],
      ),
    );
  }
}

class _MovementTimeline extends StatelessWidget {
  const _MovementTimeline({required this.movements});

  final List<InventoryMovementItem> movements;

  @override
  Widget build(BuildContext context) {
    return _ConsolePanel(
      title: 'Movement Timeline',
      subtitle: 'Aktivitas stok terbaru dari dashboard web.',
      icon: Icons.timeline_rounded,
      trailing: _CountBadge(label: '${movements.length} logs'),
      child: movements.isEmpty
          ? const _EmptyState(message: 'Belum ada riwayat movement.')
          : Column(
              children: [
                for (var index = 0; index < movements.length; index++) ...[
                  _MovementTile(movement: movements[index]),
                  if (index != movements.length - 1) const SizedBox(height: 10),
                ],
              ],
            ),
    );
  }
}

class _MovementTile extends StatelessWidget {
  const _MovementTile({required this.movement});

  final InventoryMovementItem movement;

  @override
  Widget build(BuildContext context) {
    final color = movement.isIncoming
        ? const Color(0xFF047857)
        : const Color(0xFFB91C1C);
    final background = movement.isIncoming
        ? const Color(0xFFECFDF5)
        : const Color(0xFFFEF2F2);
    final code = movement.inventoryItemCode.isEmpty
        ? '-'
        : movement.inventoryItemCode;

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE5E7EB)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: background,
              shape: BoxShape.circle,
            ),
            child: Icon(
              movement.isIncoming
                  ? Icons.call_received_rounded
                  : Icons.call_made_rounded,
              color: color,
              size: 18,
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        movement.inventoryItemName,
                        style: AppTextStyles.bodyMedium.copyWith(
                          fontWeight: FontWeight.w800,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    const SizedBox(width: 10),
                    _Badge(
                      label: movement.typeLabel,
                      color: color,
                      background: background,
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    _SmallPill(
                      label: code,
                      color: const Color(0xFF334155),
                      background: const Color(0xFFEFF6FF),
                    ),
                    _SmallPill(
                      label: movement.sourceLabel,
                      color: AppColors.textSecondary,
                      background: const Color(0xFFF8FAFC),
                    ),
                    _SmallPill(
                      label: movement.actorName,
                      color: AppColors.textSecondary,
                      background: const Color(0xFFF8FAFC),
                    ),
                  ],
                ),
                if (movement.notes.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Text(
                    movement.notes,
                    style: AppTextStyles.bodySmall.copyWith(
                      color: AppColors.textSecondary,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(width: 16),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(movement.createdAtText, style: AppTextStyles.captionMedium),
              const SizedBox(height: 10),
              Text(
                '${movement.isIncoming ? '+' : '-'}${movement.qty} ${movement.unit}',
                style: TextStyle(
                  color: color,
                  fontSize: 18,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                '${movement.stockBefore} -> ${movement.stockAfter}',
                style: AppTextStyles.caption.copyWith(
                  color: AppColors.textSecondary,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _ConsolePanel extends StatelessWidget {
  const _ConsolePanel({
    required this.title,
    required this.subtitle,
    required this.icon,
    required this.child,
    this.trailing,
  });

  final String title;
  final String subtitle;
  final IconData icon;
  final Widget child;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
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
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(18, 18, 18, 14),
            child: Row(
              children: [
                Container(
                  width: 40,
                  height: 40,
                  decoration: BoxDecoration(
                    color: const Color(0xFFEFF6FF),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Icon(icon, color: AppColors.primaryDark, size: 20),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(title, style: AppTextStyles.h3),
                      const SizedBox(height: 3),
                      Text(subtitle, style: AppTextStyles.bodySmall),
                    ],
                  ),
                ),
                if (trailing != null) ...[const SizedBox(width: 12), trailing!],
              ],
            ),
          ),
          const Divider(height: 1, color: AppColors.divider),
          Padding(padding: const EdgeInsets.all(16), child: child),
        ],
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

class _SmallPill extends StatelessWidget {
  const _SmallPill({
    required this.label,
    required this.color,
    required this.background,
  });

  final String label;
  final Color color;
  final Color background;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontSize: 10,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}

class _Badge extends StatelessWidget {
  const _Badge({
    required this.label,
    required this.color,
    required this.background,
  });

  final String label;
  final Color color;
  final Color background;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: background,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: color,
          fontSize: 11,
          fontWeight: FontWeight.w800,
        ),
      ),
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

class _EmptyState extends StatelessWidget {
  const _EmptyState({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(28),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        children: [
          const Icon(
            Icons.inventory_2_outlined,
            color: AppColors.textMuted,
            size: 28,
          ),
          const SizedBox(height: 10),
          Text(message, style: AppTextStyles.bodySmall),
        ],
      ),
    );
  }
}
