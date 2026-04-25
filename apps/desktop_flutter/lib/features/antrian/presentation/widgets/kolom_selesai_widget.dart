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
    return Container(
      decoration: BoxDecoration(
        color: AppTheme.surface,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          // Header
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 18, 20, 14),
            child: Row(
              children: [
                Container(
                  width: 3,
                  height: 18,
                  decoration: BoxDecoration(
                    color: AppTheme.success,
                    borderRadius: BorderRadius.circular(4),
                  ),
                ),
                const SizedBox(width: 10),
                Text('SELESAI', style: AppTheme.sectionTitle),
                const Spacer(),
                GestureDetector(
                  onTap: onLihatSemua,
                  child: const Text(
                    'LIHAT SEMUA',
                    style: TextStyle(
                      fontFamily: AppTheme.fontFamily,
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: AppTheme.primary,
                      letterSpacing: 0.5,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1, color: AppTheme.divider),

          // List
          Expanded(
            child: ListView.separated(
              padding: const EdgeInsets.symmetric(vertical: 8),
              itemCount: antrian.length,
              separatorBuilder: (_, __) => const Divider(
                height: 1,
                indent: 20,
                endIndent: 20,
                color: AppTheme.divider,
              ),
              itemBuilder: (context, i) => AntrianSelesaiCard(antrian: antrian[i]),
            ),
          ),
        ],
      ),
    );
  }
}

class AntrianSelesaiCard extends StatelessWidget {
  final AntrianEntity antrian;

  const AntrianSelesaiCard({super.key, required this.antrian});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 14, 20, 14),
      child: Row(
        children: [
          // Nomor circle
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              color: AppTheme.background,
              shape: BoxShape.circle,
            ),
            child: Center(
              child: Text(
                antrian.nomorAntrian.replaceAll('#A-0', '#').replaceAll('#A-', '#'),
                style: const TextStyle(
                  fontFamily: AppTheme.fontFamily,
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                  color: AppTheme.textPrimary,
                ),
              ),
            ),
          ),
          const SizedBox(width: 12),

          // Info
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(antrian.namaCustomer, style: AppTheme.namaCustomer),
                const SizedBox(height: 2),
                Text(
                  antrian.catatanSelesai ?? 'Selesai',
                  style: AppTheme.bodySmall,
                ),
              ],
            ),
          ),

          // Check icon
          Container(
            width: 28,
            height: 28,
            decoration: const BoxDecoration(
              color: AppTheme.success,
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.check, color: Colors.white, size: 16),
          ),
        ],
      ),
    );
  }
}
