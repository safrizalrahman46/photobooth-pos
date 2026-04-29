// presentation/widgets/kolom_selesai_widget.dart

import 'package:flutter/material.dart';
import '../../domain/entities/antrian_entity.dart';
import '../theme/app_theme.dart';

class KolomSelesaiWidget extends StatelessWidget {
  final List<AntrianEntity> antrian;
  final VoidCallback? onLihatSemua;

  const KolomSelesaiWidget({
    super.key,
    required this.antrian,
    this.onLihatSemua,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Header
        Padding(
          padding: const EdgeInsets.only(bottom: 20),
          child: Row(
            children: [
              Container(
                width: 4,
                height: 24,
                decoration: BoxDecoration(
                  color: const Color(0xFF10B981), // Emerald for finished
                  borderRadius: BorderRadius.circular(4),
                ),
              ),
              const SizedBox(width: 12),
              Text(
                'SELESAI',
                style: TextStyle(
                  fontFamily: AppTheme.fontFamily,
                  fontSize: 18,
                  fontWeight: FontWeight.w800,
                  color: AppTheme.textPrimary,
                  letterSpacing: 0.5,
                ),
              ),
              const Spacer(),
              TextButton(
                onPressed: onLihatSemua,
                child: const Text(
                  'LIHAT SEMUA',
                  style: TextStyle(
                    fontFamily: AppTheme.fontFamily,
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF64748B),
                    letterSpacing: 0.5,
                  ),
                ),
              ),
            ],
          ),
        ),

        // List of Cards
        Column(
          children: antrian.map((a) => AntrianSelesaiCard(antrian: a)).toList(),
        ),
      ],
    );
  }
}

class AntrianSelesaiCard extends StatelessWidget {
  final AntrianEntity antrian;

  const AntrianSelesaiCard({super.key, required this.antrian});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: const Color(0xFFF1F5F9).withOpacity(0.5), // Subtle gray background like mockup
        borderRadius: BorderRadius.circular(24),
      ),
      child: Row(
        children: [
          // Circle with ID
          Container(
            width: 44,
            height: 44,
            decoration: const BoxDecoration(
              color: Colors.white,
              shape: BoxShape.circle,
            ),
            child: Center(
              child: Text(
                antrian.nomorAntrian.replaceAll('#A-0', '#').replaceAll('#A-', '#'),
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF1E293B),
                ),
              ),
            ),
          ),
          const SizedBox(width: 16),

          // Customer Info
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  antrian.namaCustomer,
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF1E293B),
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  antrian.catatanSelesai ?? 'Selesai: Bagus & Cetak',
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                    color: Color(0xFF64748B),
                  ),
                ),
              ],
            ),
          ),

          // Green Check Icon
          Container(
            width: 24,
            height: 24,
            decoration: const BoxDecoration(
              color: Color(0xFF10B981),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.check, color: Colors.white, size: 14),
          ),
        ],
      ),
    );
  }
}
