// presentation/widgets/kolom_sedang_foto_widget.dart

import 'dart:async';
import 'package:flutter/material.dart';
import '../../domain/entities/booth_entity.dart';
import '../theme/app_theme.dart';

class KolomSedangFotoWidget extends StatelessWidget {
  final List<BoothEntity> booths;
  final VoidCallback? onKirimKeSelesai;
  final VoidCallback? onSetReady;

  const KolomSedangFotoWidget({
    super.key,
    required this.booths,
    this.onKirimKeSelesai,
    this.onSetReady,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Header
        Padding(
          padding: const EdgeInsets.only(bottom: 14),
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
              Text('SEDANG FOTO', style: AppTheme.sectionTitle),
            ],
          ),
        ),
        // Booth Cards
        Expanded(
          child: ListView.separated(
            itemCount: booths.length,
            separatorBuilder: (_, __) => const SizedBox(height: 16),
            itemBuilder: (context, i) {
              final booth = booths[i];
              if (booth.status == StatusBooth.aktif && booth.antrianAktif != null) {
                return BoothAktifCard(
                  booth: booth,
                  onKirimKeSelesai: onKirimKeSelesai,
                );
              }
              return BoothTersediaCard(
                booth: booth,
                onSetReady: onSetReady,
              );
            },
          ),
        ),
      ],
    );
  }
}

// ─── Booth Aktif ─────────────────────────────────────────────────────────────
class BoothAktifCard extends StatefulWidget {
  final BoothEntity booth;
  final VoidCallback? onKirimKeSelesai;

  const BoothAktifCard({super.key, required this.booth, this.onKirimKeSelesai});

  @override
  State<BoothAktifCard> createState() => _BoothAktifCardState();
}

class _BoothAktifCardState extends State<BoothAktifCard> {
  late int _sisaDetik;
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _sisaDetik = widget.booth.antrianAktif?.sisaWaktuDetik ?? 0;
    _startTimer();
  }

  void _startTimer() {
    _timer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (_sisaDetik > 0) {
        setState(() => _sisaDetik--);
      } else {
        _timer?.cancel();
      }
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  String get _timerLabel {
    final m = _sisaDetik ~/ 60;
    final s = _sisaDetik % 60;
    return '${m.toString().padLeft(2, '0')}:${s.toString().padLeft(2, '0')}';
  }

  @override
  Widget build(BuildContext context) {
    final info = widget.booth.antrianAktif!;
    return Container(
      decoration: BoxDecoration(
        color: AppTheme.surface,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.06),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          // Foto Preview
          Stack(
            children: [
              ClipRRect(
                borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
                child: Container(
                  width: double.infinity,
                  height: 140,
                  color: const Color(0xFF0A1628),
                  child: const Center(
                    child: Icon(Icons.camera_enhance, color: Colors.white54, size: 40),
                  ),
                ),
              ),
              // Booth Label
              Positioned(
                top: 12,
                left: 12,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.black54,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    widget.booth.namaBooth.toUpperCase(),
                    style: const TextStyle(
                      fontFamily: AppTheme.fontFamily,
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                      letterSpacing: 1,
                    ),
                  ),
                ),
              ),
              // Session Active Badge
              Positioned(
                bottom: 12,
                left: 0,
                right: 0,
                child: Center(
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                    decoration: BoxDecoration(
                      color: Colors.black.withOpacity(0.7),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: Colors.white24),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Container(
                          width: 8,
                          height: 8,
                          decoration: const BoxDecoration(
                            color: AppTheme.success,
                            shape: BoxShape.circle,
                          ),
                        ),
                        const SizedBox(width: 6),
                        const Text(
                          'SESSION ACTIVE',
                          style: TextStyle(
                            fontFamily: AppTheme.fontFamily,
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                            letterSpacing: 1,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ],
          ),

          // Info
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            info.nomorAntrian,
                            style: const TextStyle(
                              fontFamily: AppTheme.fontFamily,
                              fontSize: 18,
                              fontWeight: FontWeight.w800,
                              color: AppTheme.textPrimary,
                            ),
                          ),
                          Text(
                            '${info.namaCustomer} (${info.jumlahOrang} Pers)',
                            style: AppTheme.bodySmall,
                          ),
                        ],
                      ),
                    ),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        const Text(
                          'SISA WAKTU',
                          style: TextStyle(
                            fontFamily: AppTheme.fontFamily,
                            fontSize: 9,
                            fontWeight: FontWeight.w600,
                            color: AppTheme.textSecondary,
                            letterSpacing: 1,
                          ),
                        ),
                        Text(_timerLabel, style: AppTheme.timerText),
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                // Progress bar
                ClipRRect(
                  borderRadius: BorderRadius.circular(4),
                  child: LinearProgressIndicator(
                    value: _sisaDetik /
                        (widget.booth.antrianAktif!.sisaWaktuDetik > 0
                            ? widget.booth.antrianAktif!.sisaWaktuDetik
                            : 1),
                    backgroundColor: AppTheme.divider,
                    color: AppTheme.primary,
                    minHeight: 6,
                  ),
                ),
                const SizedBox(height: 14),
                // Button
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton(
                    onPressed: widget.onKirimKeSelesai,
                    style: OutlinedButton.styleFrom(
                      side: const BorderSide(color: AppTheme.textPrimary, width: 1.5),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(10),
                      ),
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                    child: const Text(
                      'KIRIM KE SELESAI',
                      style: TextStyle(
                        fontFamily: AppTheme.fontFamily,
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: AppTheme.textPrimary,
                        letterSpacing: 1,
                      ),
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

// ─── Booth Tersedia ───────────────────────────────────────────────────────────
class BoothTersediaCard extends StatelessWidget {
  final BoothEntity booth;
  final VoidCallback? onSetReady;

  const BoothTersediaCard({super.key, required this.booth, this.onSetReady});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppTheme.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppTheme.divider, width: 1.5),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      padding: const EdgeInsets.all(24),
      child: Column(
        children: [
          Container(
            width: 52,
            height: 52,
            decoration: BoxDecoration(
              color: AppTheme.background,
              borderRadius: BorderRadius.circular(14),
            ),
            child: const Icon(Icons.meeting_room_outlined,
                color: AppTheme.textSecondary, size: 26),
          ),
          const SizedBox(height: 14),
          Text(
            booth.namaBooth.toUpperCase(),
            style: const TextStyle(
              fontFamily: AppTheme.fontFamily,
              fontSize: 16,
              fontWeight: FontWeight.w700,
              color: AppTheme.textPrimary,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            'Status: ${booth.statusLabel}',
            style: AppTheme.bodySmall,
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 18),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: onSetReady,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.primary.withOpacity(0.15),
                elevation: 0,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10),
                ),
                padding: const EdgeInsets.symmetric(vertical: 12),
              ),
              child: const Text(
                'SET READY',
                style: TextStyle(
                  fontFamily: AppTheme.fontFamily,
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: AppTheme.primary,
                  letterSpacing: 1,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
