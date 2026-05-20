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
  print('=========== STOCK PAGE DEBUG ===========');
  print('CLIENT: $client');
  print('TOKEN: ${client.token}');
  print('=======================================');
  // ==============================================

  setState(() {
    _loading = true;
    _error = null;
  });

  try {
    // ============== DEBUG SEBELUM REQUEST ==============
    print('Memulai request inventory monitoring...');
    // ===================================================

    final payload = await client.fetchInventoryMonitoring();

    // ============== DEBUG SETELAH REQUEST ==============
    print('Request berhasil!');
    print('Jumlah items: ${payload.items.length}');
    print('Jumlah movements: ${payload.movements.length}');
    // ==================================================

    if (!mounted) {
      return;
    }

    setState(() => _payload = payload);
  } on ApiException catch (error) {
    // ============== DEBUG ERROR API ====================
    print('ApiException: ${error.message}');
    // ==================================================

    if (!mounted) {
      return;
    }

    setState(() => _error = error.message);
  } catch (e, stackTrace) {
    // ============== DEBUG ERROR UMUM ===================
    print('ERROR UMUM: $e');
    print('STACK TRACE: $stackTrace');
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

    return Scaffold(
      backgroundColor: AppColors.background,
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(32),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _PageHeader(onRefresh: _loading ? null : _loadStock),
            if (_error != null) ...[
              const SizedBox(height: 16),
              _ErrorBanner(message: _error!),
            ],
            const SizedBox(height: 20),
            Row(
              children: [
                Expanded(
                  child: _StatCard(
                    label: 'Total barang',
                    value: totalItems.toString(),
                    color: const Color(0xFF0F766E),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _StatCard(
                    label: 'Low stock',
                    value: lowStock.toString(),
                    color: const Color(0xFFD97706),
                  ),
                ),
                const SizedBox(width: 16),
                Expanded(
                  child: _StatCard(
                    label: 'Out of stock',
                    value: outOfStock.toString(),
                    color: const Color(0xFFDC2626),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),
            _loading
                ? const Padding(
                    padding: EdgeInsets.symmetric(vertical: 80),
                    child: Center(child: CircularProgressIndicator()),
                  )
                : Column(
                    children: [
                      _StockTable(items: _payload.items),
                      const SizedBox(height: 20),
                      _MovementTable(movements: _payload.movements),
                    ],
                  ),
          ],
        ),
      ),
    );
  }
}

class _PageHeader extends StatelessWidget {
  const _PageHeader({required this.onRefresh});

  final VoidCallback? onRefresh;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF0F766E), Color(0xFF059669)],
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: const [
          BoxShadow(
            color: Color(0x220F766E),
            blurRadius: 24,
            offset: Offset(0, 8),
          ),
        ],
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          const Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Inventory Management',
                style: TextStyle(color: Color(0xBFFFFFFF), fontSize: 12),
              ),
              SizedBox(height: 6),
              Text(
                'Monitoring Stock Barang',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 22,
                  fontWeight: FontWeight.w800,
                ),
              ),
              SizedBox(height: 6),
              Text(
                'Pantau barang masuk, barang keluar, dan stok terkini dari dashboard owner.',
                style: TextStyle(color: Color(0xBFFFFFFF), fontSize: 13),
              ),
            ],
          ),
          OutlinedButton.icon(
            onPressed: onRefresh,
            style: OutlinedButton.styleFrom(
              foregroundColor: Colors.white,
              side: const BorderSide(color: Color(0x66FFFFFF)),
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            ),
            icon: const Icon(Icons.refresh_rounded, size: 18),
            label: const Text('Refresh'),
          ),
        ],
      ),
    );
  }
}

class _StatCard extends StatelessWidget {
  const _StatCard({
    required this.label,
    required this.value,
    required this.color,
  });

  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.cardBorder),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 14,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: AppTextStyles.bodySmall),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              color: color,
              fontSize: 30,
              fontWeight: FontWeight.w800,
            ),
          ),
        ],
      ),
    );
  }
}

class _StockTable extends StatelessWidget {
  const _StockTable({required this.items});

  final List<InventoryStockItem> items;

  @override
  Widget build(BuildContext context) {
    return _TableShell(
      title: 'Stock Barang',
      subtitle: 'Daftar barang fisik dan jumlah stok terkini.',
      child: items.isEmpty
          ? const _EmptyState(message: 'Belum ada barang stok.')
          : SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: DataTable(
                columns: const [
                  DataColumn(label: Text('Kode')),
                  DataColumn(label: Text('Barang')),
                  DataColumn(label: Text('Stok')),
                  DataColumn(label: Text('Low')),
                  DataColumn(label: Text('Status')),
                  DataColumn(label: Text('Aktif')),
                ],
                rows: items.map((item) {
                  return DataRow(
                    cells: [
                      DataCell(Text(item.code.isEmpty ? '-' : item.code)),
                      DataCell(Text(item.name)),
                      DataCell(Text('${item.availableStock} ${item.unit}')),
                      DataCell(Text('${item.lowStockThreshold} ${item.unit}')),
                      DataCell(_StockStatusBadge(item: item)),
                      DataCell(Text(item.isActive ? 'active' : 'inactive')),
                    ],
                  );
                }).toList(),
              ),
            ),
    );
  }
}

class _MovementTable extends StatelessWidget {
  const _MovementTable({required this.movements});

  final List<InventoryMovementItem> movements;

  @override
  Widget build(BuildContext context) {
    return _TableShell(
      title: 'Riwayat Movement',
      subtitle: 'Barang masuk dan keluar dari website dashboard owner/admin.',
      child: movements.isEmpty
          ? const _EmptyState(message: 'Belum ada riwayat movement.')
          : SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: DataTable(
                columns: const [
                  DataColumn(label: Text('Waktu')),
                  DataColumn(label: Text('Barang')),
                  DataColumn(label: Text('Type')),
                  DataColumn(label: Text('Qty')),
                  DataColumn(label: Text('Before')),
                  DataColumn(label: Text('After')),
                  DataColumn(label: Text('Source')),
                  DataColumn(label: Text('Actor')),
                  DataColumn(label: Text('Catatan')),
                ],
                rows: movements.map((movement) {
                  return DataRow(
                    cells: [
                      DataCell(Text(movement.createdAtText)),
                      DataCell(
                        Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(movement.inventoryItemName),
                            Text(
                              movement.inventoryItemCode,
                              style: AppTextStyles.caption,
                            ),
                          ],
                        ),
                      ),
                      DataCell(_MovementTypeBadge(movement: movement)),
                      DataCell(Text('${movement.qty} ${movement.unit}')),
                      DataCell(Text(movement.stockBefore.toString())),
                      DataCell(Text(movement.stockAfter.toString())),
                      DataCell(Text(movement.sourceLabel)),
                      DataCell(Text(movement.actorName)),
                      DataCell(
                        Text(movement.notes.isEmpty ? '-' : movement.notes),
                      ),
                    ],
                  );
                }).toList(),
              ),
            ),
    );
  }
}

class _TableShell extends StatelessWidget {
  const _TableShell({
    required this.title,
    required this.subtitle,
    required this.child,
  });

  final String title;
  final String subtitle;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: AppColors.cardBorder),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.all(20),
            child: Row(
              children: [
                const Icon(Icons.inventory_2_rounded, color: Color(0xFF0F766E)),
                const SizedBox(width: 12),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: AppTextStyles.h3),
                    const SizedBox(height: 4),
                    Text(subtitle, style: AppTextStyles.bodySmall),
                  ],
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: AppColors.divider),
          child,
        ],
      ),
    );
  }
}

class _StockStatusBadge extends StatelessWidget {
  const _StockStatusBadge({required this.item});

  final InventoryStockItem item;

  @override
  Widget build(BuildContext context) {
    final color = item.isOut
        ? const Color(0xFFB91C1C)
        : item.isLow
        ? const Color(0xFFC2410C)
        : const Color(0xFF047857);
    final background = item.isOut
        ? const Color(0xFFFEF2F2)
        : item.isLow
        ? const Color(0xFFFFF7ED)
        : const Color(0xFFECFDF5);

    return _Badge(
      label: item.stockStatusLabel,
      color: color,
      background: background,
    );
  }
}

class _MovementTypeBadge extends StatelessWidget {
  const _MovementTypeBadge({required this.movement});

  final InventoryMovementItem movement;

  @override
  Widget build(BuildContext context) {
    return _Badge(
      label: movement.typeLabel,
      color: movement.isIncoming
          ? const Color(0xFF047857)
          : const Color(0xFFB91C1C),
      background: movement.isIncoming
          ? const Color(0xFFECFDF5)
          : const Color(0xFFFEF2F2),
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
          fontWeight: FontWeight.w700,
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
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFFFCDD2)),
      ),
      child: Text(
        message,
        style: const TextStyle(color: Colors.redAccent, fontSize: 13),
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(32),
      child: Center(child: Text(message, style: AppTextStyles.bodySmall)),
    );
  }
}
