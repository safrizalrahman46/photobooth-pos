// presentation/widgets/kolom_menunggu_widget.dart

import 'package:flutter/material.dart';
import '../../domain/entities/antrian_entity.dart';
import '../theme/app_theme.dart';

class KolomMenungguWidget extends StatelessWidget {
  final List<AntrianEntity> antrian;

  const KolomMenungguWidget({super.key, required this.antrian});

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
                    color: AppTheme.primary,
                    borderRadius: BorderRadius.circular(4),
                  ),
                ),
                const SizedBox(width: 10),
                Text(
                  'MENUNGGU',
                  style: AppTheme.sectionTitle,
                ),
                const Spacer(),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: AppTheme.background,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Text(
                    '${antrian.length} Antrean',
                    style: const TextStyle(
                      fontFamily: AppTheme.fontFamily,
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: AppTheme.textSecondary,
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
              itemBuilder: (context, i) => AntrianMenungguCard(antrian: antrian[i]),
            ),
          ),
        ],
      ),
    );
  }
}

class AntrianMenungguCard extends StatelessWidget {
  final AntrianEntity antrian;

  const AntrianMenungguCard({super.key, required this.antrian});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 16),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Nomor & nama
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(antrian.nomorAntrian, style: AppTheme.nomorAntrianCard),
                const SizedBox(height: 4),
                Text(antrian.namaCustomer, style: AppTheme.namaCustomer),
                const SizedBox(height: 2),
                Text(
                  '${antrian.jumlahOrang} Orang • ${antrian.paket}',
                  style: AppTheme.bodySmall,
                ),
              ],
            ),
          ),
          // Waktu
          Text(antrian.menitLalu, style: AppTheme.bodySmall),
        ],
      ),
    );
  }
}
