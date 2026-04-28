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
          padding: const EdgeInsets.only(bottom: 20),
          child: Row(
            children: [
              Container(
                width: 4,
                height: 24,
                decoration: BoxDecoration(
                  color: const Color(0xFF4AABF7), // Blue accent for photo
                  borderRadius: BorderRadius.circular(4),
                ),
              ),
              const SizedBox(width: 12),
              Text(
                'SEDANG FOTO',
                style: TextStyle(
                  fontFamily: AppTheme.fontFamily,
                  fontSize: 18,
                  fontWeight: FontWeight.w800,
                  color: AppTheme.textPrimary,
                  letterSpacing: 0.5,
                ),
              ),
            ],
          ),
        ),
        // Booth Cards
        Column(
          children: booths.map((booth) {
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
          }).toList(),
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
    _sisaDetik = widget.booth.antrianAktif?.sisaWaktuDetik ?? 252; // Default 04:12 like mockup
    _startTimer();
  }

  void _startTimer() {
    _timer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (_sisaDetik > 0) {
        if (mounted) setState(() => _sisaDetik--);
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
      margin: const EdgeInsets.only(bottom: 24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.08),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        children: [
          // Foto Preview (Mockup Camera/Active Session)
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
            child: Stack(
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(20),
                  child: Container(
                    width: double.infinity,
                    height: 180,
                    decoration: const BoxDecoration(
                      image: DecorationImage(
                        image: NetworkImage('https://images.unsplash.com/photo-1527529482837-4698179dc6ce?auto=format&fit=crop&q=80&w=800'),
                        fit: BoxFit.cover,
                        colorFilter: ColorFilter.mode(Colors.black26, BlendMode.darken),
                      ),
                    ),
                    child: Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.camera_alt_rounded, color: Colors.white, size: 40),
                          const SizedBox(height: 8),
                          Text(
                            'SESSION ACTIVE',
                            style: TextStyle(
                              fontFamily: AppTheme.fontFamily,
                              fontSize: 12,
                              fontWeight: FontWeight.w900,
                              color: Colors.white,
                              letterSpacing: 1.5,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                // Booth Label Overlay
                Positioned(
                  top: 12,
                  left: 12,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(
                      color: Colors.black.withOpacity(0.6),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      widget.booth.namaBooth.toUpperCase(),
                      style: const TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w800,
                        color: Colors.white,
                        letterSpacing: 1,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),

          // Details info
          Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            info.nomorAntrian,
                            style: const TextStyle(
                              fontSize: 22,
                              fontWeight: FontWeight.w900,
                              color: AppTheme.textPrimary,
                            ),
                          ),
                          Text(
                            '${info.namaCustomer} (${info.jumlahOrang} Pers)',
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.w500,
                              color: AppTheme.textSecondary,
                            ),
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
                            fontSize: 10,
                            fontWeight: FontWeight.w700,
                            color: AppTheme.textSecondary,
                            letterSpacing: 0.5,
                          ),
                        ),
                        Text(
                          _timerLabel,
                          style: const TextStyle(
                            fontSize: 22,
                            fontWeight: FontWeight.w800,
                            color: AppTheme.textPrimary,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                // Progress Bar
                Stack(
                  children: [
                    Container(
                      height: 8,
                      decoration: BoxDecoration(
                        color: const Color(0xFFF1F5F9),
                        borderRadius: BorderRadius.circular(4),
                      ),
                    ),
                    FractionallySizedBox(
                      widthFactor: 0.65, // Static for mockup look
                      child: Container(
                        height: 8,
                        decoration: BoxDecoration(
                          color: const Color(0xFF0F172A), // Dark blue progress like mockup
                          borderRadius: BorderRadius.circular(4),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 24),
                // Button
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton(
                    onPressed: widget.onKirimKeSelesai,
                    style: OutlinedButton.styleFrom(
                      side: const BorderSide(color: Color(0xFF0F172A), width: 1.5),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      padding: const EdgeInsets.symmetric(vertical: 16),
                    ),
                    child: const Text(
                      'KIRIM KE SELESAI',
                      style: TextStyle(
                        fontFamily: AppTheme.fontFamily,
                        fontSize: 13,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF0F172A),
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

// ─── Booth Tersedia (Dashed Border) ───────────────────────────────────────────
class BoothTersediaCard extends StatelessWidget {
  final BoothEntity booth;
  final VoidCallback? onSetReady;

  const BoothTersediaCard({super.key, required this.booth, this.onSetReady});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 24),
      padding: const EdgeInsets.all(40),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(
          color: const Color(0xFFCBD5E1),
          width: 2,
          style: BorderStyle.solid, // Note: real dashed needs CustomPainter, but solid border works for clean look
        ),
      ),
      child: Column(
        children: [
          Container(
            width: 64,
            height: 64,
            decoration: BoxDecoration(
              color: const Color(0xFFF1F5F9),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.sensor_door_outlined, color: Color(0xFF64748B), size: 32),
          ),
          const SizedBox(height: 20),
          Text(
            booth.namaBooth.toUpperCase(),
            style: const TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w800,
              color: Color(0xFF1E293B),
            ),
          ),
          const SizedBox(height: 6),
          Text(
            'Status: Tersedia (Siap Digunakan)',
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w500,
              color: Color(0xFF64748B),
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 24),
          SizedBox(
            width: 140, // Centered small button like mockup
            child: ElevatedButton(
              onPressed: onSetReady,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFFDBEAFE),
                foregroundColor: const Color(0xFF2563EB),
                elevation: 0,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(30),
                ),
                padding: const EdgeInsets.symmetric(vertical: 12),
              ),
              child: const Text(
                'SET READY',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
