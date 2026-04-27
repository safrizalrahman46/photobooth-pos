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
      padding: const EdgeInsets.symmetric(horizontal: 40, vertical: 32),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF6EC6F5), Color(0xFF4AABF7)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(32), // More rounded corners like mockup
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF4AABF7).withOpacity(0.3),
            blurRadius: 30,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      child: antrian == null
          ? const Center(
              child: Text(
                'Tidak ada antrian berikutnya',
                style: TextStyle(color: Colors.white70, fontSize: 16, fontWeight: FontWeight.w600),
              ),
            )
          : Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'ANTREAN BERIKUTNYA',
                  style: TextStyle(
                    fontFamily: AppTheme.fontFamily,
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: Colors.white.withOpacity(0.7),
                    letterSpacing: 2.0,
                  ),
                ),
                const SizedBox(height: 12),
                Row(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    Text(
                      antrian!.nomorAntrian,
                      style: const TextStyle(
                        fontFamily: AppTheme.fontFamily,
                        fontSize: 48, // Large ID
                        fontWeight: FontWeight.w900,
                        color: Colors.white,
                        letterSpacing: -1,
                      ),
                    ),
                    const SizedBox(width: 24),
                    Expanded(
                      child: Text(
                        antrian!.namaCustomer,
                        style: const TextStyle(
                          fontFamily: AppTheme.fontFamily,
                          fontSize: 28, // Large Name
                          fontWeight: FontWeight.w500,
                          color: Colors.white,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 24),
                Row(
                  children: [
                    _BadgeInfo(label: 'Paket: ${antrian!.paket}'),
                    const SizedBox(width: 12),
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
      padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.18),
        borderRadius: BorderRadius.circular(30),
        border: Border.all(color: Colors.white.withOpacity(0.3), width: 1.5),
      ),
      child: Text(
        label,
        style: const TextStyle(
          fontFamily: AppTheme.fontFamily,
          fontSize: 13,
          fontWeight: FontWeight.w600,
          color: Colors.white,
        ),
      ),
    );
  }
}
