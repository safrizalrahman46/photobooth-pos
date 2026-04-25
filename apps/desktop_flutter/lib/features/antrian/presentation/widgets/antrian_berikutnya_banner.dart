// presentation/widgets/antrian_berikutnya_banner.dart

import 'package:flutter/material.dart';
import '../../domain/entities/antrian_entity.dart';
import '../theme/app_theme.dart';

class AntrianBerikutnyaBanner extends StatelessWidget {
  final AntrianEntity? antrian;

  const AntrianBerikutnyaBanner({super.key, this.antrian});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 22),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF6EC6F5), Color(0xFF4AABF7)],
          begin: Alignment.centerLeft,
          end: Alignment.centerRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: AppTheme.primary.withOpacity(0.25),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: antrian == null
          ? const Center(
              child: Text(
                'Tidak ada antrian berikutnya',
                style: TextStyle(color: Colors.white70, fontSize: 15),
              ),
            )
          : Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'ANTREAN BERIKUTNYA',
                  style: TextStyle(
                    fontFamily: AppTheme.fontFamily,
                    fontSize: 11,
                    fontWeight: FontWeight.w600,
                    color: Colors.white70,
                    letterSpacing: 1.5,
                  ),
                ),
                const SizedBox(height: 6),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    Text(
                      antrian!.nomorAntrian,
                      style: AppTheme.nomorAntrian,
                    ),
                    const SizedBox(width: 16),
                    Text(
                      antrian!.namaCustomer,
                      style: const TextStyle(
                        fontFamily: AppTheme.fontFamily,
                        fontSize: 22,
                        fontWeight: FontWeight.w500,
                        color: Colors.white,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    _BadgeInfo(label: 'Paket: ${antrian!.paket}'),
                    const SizedBox(width: 8),
                    _BadgeInfo(label: 'Booth: ${antrian!.booth}'),
                  ],
                ),
              ],
            ),
    );
  }
}

class _BadgeInfo extends StatelessWidget {
  final String label;
  const _BadgeInfo({required this.label});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.25),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.white.withOpacity(0.4), width: 1),
      ),
      child: Text(
        label,
        style: const TextStyle(
          fontFamily: AppTheme.fontFamily,
          fontSize: 12,
          fontWeight: FontWeight.w500,
          color: Colors.white,
        ),
      ),
    );
  }
}
