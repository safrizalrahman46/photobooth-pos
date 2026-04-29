import 'dart:math';
import 'package:flutter/material.dart';

class CashflowChart extends StatelessWidget {
  const CashflowChart({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(32),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Distribusi Pendapatan',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFF1E293B),
                    ),
                  ),
                  SizedBox(height: 4),
                  Text(
                    'Berdasarkan kategori produk & layanan',
                    style: TextStyle(fontSize: 13, color: Color(0xFF64748B)),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: const Row(
                  children: [
                    Icon(Icons.calendar_today_rounded, size: 14, color: Color(0xFF64748B)),
                    SizedBox(width: 8),
                    Text(
                      'Bulan Ini',
                      style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF475569)),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 40),
          Row(
            children: [
              // Donut Chart
              Expanded(
                flex: 2,
                child: SizedBox(
                  height: 220,
                  child: Stack(
                    alignment: Alignment.center,
                    children: [
                      CustomPaint(
                        size: const Size(200, 200),
                        painter: _DonutChartPainter(
                          data: [
                            _ChartData(color: const Color(0xFF6366F1), value: 65), // Indigo (Paket)
                            _ChartData(color: const Color(0xFF10B981), value: 25), // Emerald (Add-ons)
                            _ChartData(color: const Color(0xFFF59E0B), value: 10), // Amber (Lainnya)
                          ],
                        ),
                      ),
                      const Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            'TOTAL',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              color: Color(0xFF94A3B8),
                              letterSpacing: 2,
                            ),
                          ),
                          SizedBox(height: 4),
                          Text(
                            '100%',
                            style: TextStyle(
                              fontSize: 24,
                              fontWeight: FontWeight.w900,
                              color: Color(0xFF1E293B),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(width: 48),
              // Legend
              const Expanded(
                flex: 3,
                child: Column(
                  children: [
                    _LegendItem(
                      label: 'Penjualan Paket',
                      percentage: '65%',
                      color: Color(0xFF6366F1),
                      amount: 'Rp 9.262.500',
                    ),
                    Divider(height: 32, color: Color(0xFFF1F5F9)),
                    _LegendItem(
                      label: 'Add-ons Service',
                      percentage: '25%',
                      color: Color(0xFF10B981),
                      amount: 'Rp 3.562.500',
                    ),
                    Divider(height: 32, color: Color(0xFFF1F5F9)),
                    _LegendItem(
                      label: 'Layanan Lainnya',
                      percentage: '10%',
                      color: Color(0xFFF59E0B),
                      amount: 'Rp 1.425.000',
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _ChartData {
  final Color color;
  final double value;
  _ChartData({required this.color, required this.value});
}

class _DonutChartPainter extends CustomPainter {
  final List<_ChartData> data;

  _DonutChartPainter({required this.data});

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = min(size.width / 2, size.height / 2);
    final thickness = radius * 0.35;

    final paint = Paint()
      ..style = PaintingStyle.stroke
      ..strokeWidth = thickness
      ..strokeCap = StrokeCap.round;

    double startAngle = -pi / 2;
    double total = data.fold(0, (sum, item) => sum + item.value);

    for (var item in data) {
      final sweepAngle = (item.value / total) * 2 * pi;
      
      // Draw background/shadow for each segment slightly larger (optional for premium feel)
      paint.color = item.color;
      
      canvas.drawArc(
        Rect.fromCircle(center: center, radius: radius - (thickness / 2)),
        startAngle + 0.05, // Small padding for round caps
        sweepAngle - 0.1,
        false,
        paint,
      );
      
      startAngle += sweepAngle;
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => true;
}

class _LegendItem extends StatelessWidget {
  final String label;
  final String percentage;
  final Color color;
  final String amount;

  const _LegendItem({
    required this.label,
    required this.percentage,
    required this.color,
    required this.amount,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 14,
          height: 14,
          decoration: BoxDecoration(
            color: color,
            borderRadius: BorderRadius.circular(4),
          ),
        ),
        const SizedBox(width: 16),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              label,
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w600,
                color: Color(0xFF1E293B),
              ),
            ),
            const SizedBox(height: 2),
            Text(
              amount,
              style: const TextStyle(
                fontSize: 12,
                color: Color(0xFF94A3B8),
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
        const Spacer(),
        Text(
          percentage,
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w800,
            color: color,
          ),
        ),
      ],
    );
  }
}
