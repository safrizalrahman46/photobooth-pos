// lib/presentation/widgets/paket_card_widget.dart

import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../domain/entities/paket_foto.dart';
import '../theme/app_theme.dart';

class PaketCardWidget extends StatelessWidget {
  final PaketFoto paket;

  const PaketCardWidget({super.key, required this.paket});

  IconData _resolveIcon() {
    switch (paket.iconType) {
      case 'party':
        return Icons.celebration_outlined;
      case 'store':
        return Icons.storefront_outlined;
      case 'camera':
      default:
        return Icons.photo_camera_outlined;
    }
  }

  @override
  Widget build(BuildContext context) {
    final bool hi = paket.isHighlighted;

    return AnimatedContainer(
      duration: const Duration(milliseconds: 250),
      width: 240,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: hi ? AppColors.highlightCard : AppColors.cardBg,
        borderRadius: BorderRadius.circular(20),
        border: hi ? null : Border.all(color: AppColors.cardBorder, width: 1),
        boxShadow: hi
            ? [
                BoxShadow(
                  color: AppColors.primaryBlue.withOpacity(0.25),
                  blurRadius: 24,
                  offset: const Offset(0, 8),
                ),
              ]
            : [
                BoxShadow(
                  color: AppColors.shadowColor,
                  blurRadius: 12,
                  offset: const Offset(0, 4),
                ),
              ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Top row: icon + lock badge
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              _IconBadge(icon: _resolveIcon(), isHighlighted: hi),
              if (paket.isLocked) _LockedBadge(isHighlighted: hi),
            ],
          ),

          const SizedBox(height: 20),

          // Package name
          Text(
            paket.nama,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 18,
              fontWeight: FontWeight.w700,
              color: hi ? AppColors.highlightCardText : AppColors.textPrimary,
              letterSpacing: -0.4,
              height: 1.2,
            ),
          ),

          const SizedBox(height: 8),

          // Description
          Text(
            paket.deskripsi,
            style: GoogleFonts.plusJakartaSans(
              fontSize: 12.5,
              fontWeight: FontWeight.w400,
              color: hi
                  ? AppColors.highlightCardText.withOpacity(0.75)
                  : AppColors.textSecondary,
              height: 1.5,
            ),
          ),

          const SizedBox(height: 28),

          // Price label
          Text(
            'HARGA PAKET',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 9.5,
              fontWeight: FontWeight.w600,
              letterSpacing: 1.2,
              color: hi
                  ? AppColors.highlightCardText.withOpacity(0.65)
                  : AppColors.textMuted,
            ),
          ),

          const SizedBox(height: 4),

          // Price value
          Text(
            'Rp ${_formatHarga(paket.harga)}',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 26,
              fontWeight: FontWeight.w700,
              color: hi ? AppColors.highlightCardText : AppColors.textPrimary,
              letterSpacing: -0.5,
            ),
          ),

          const SizedBox(height: 16),

          // Duration
          _InfoRow(
            icon: Icons.access_time_outlined,
            text: '${paket.durasiMenit} Menit Sesi',
            isHighlighted: hi,
          ),
          const SizedBox(height: 8),

          // Capacity
          _InfoRow(
            icon: Icons.people_outline,
            text: 'Maks. ${paket.maksOrang} Orang',
            isHighlighted: hi,
          ),
        ],
      ),
    );
  }

  String _formatHarga(int harga) {
    final s = harga.toString();
    if (s.length > 3) {
      return '${s.substring(0, s.length - 3)}.${s.substring(s.length - 3)}';
    }
    return s;
  }
}

class _IconBadge extends StatelessWidget {
  final IconData icon;
  final bool isHighlighted;

  const _IconBadge({required this.icon, required this.isHighlighted});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 44,
      height: 44,
      decoration: BoxDecoration(
        color: isHighlighted
            ? Colors.white.withOpacity(0.2)
            : AppColors.iconBg,
        borderRadius: BorderRadius.circular(12),
      ),
      child: Icon(
        icon,
        size: 22,
        color: isHighlighted ? Colors.white : AppColors.primaryBlue,
      ),
    );
  }
}

class _LockedBadge extends StatelessWidget {
  final bool isHighlighted;

  const _LockedBadge({required this.isHighlighted});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: isHighlighted
            ? Colors.white.withOpacity(0.2)
            : AppColors.lockedBadgeBg,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            Icons.lock_outline,
            size: 11,
            color: isHighlighted
                ? Colors.white.withOpacity(0.85)
                : AppColors.lockedBadgeText,
          ),
          const SizedBox(width: 4),
          Text(
            'LOCKED',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 10,
              fontWeight: FontWeight.w700,
              letterSpacing: 0.8,
              color: isHighlighted
                  ? Colors.white.withOpacity(0.85)
                  : AppColors.lockedBadgeText,
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final IconData icon;
  final String text;
  final bool isHighlighted;

  const _InfoRow({
    required this.icon,
    required this.text,
    required this.isHighlighted,
  });

  @override
  Widget build(BuildContext context) {
    final color = isHighlighted
        ? Colors.white.withOpacity(0.85)
        : AppColors.textSecondary;

    return Row(
      children: [
        Icon(icon, size: 15, color: color),
        const SizedBox(width: 7),
        Text(
          text,
          style: GoogleFonts.plusJakartaSans(
            fontSize: 12.5,
            fontWeight: FontWeight.w500,
            color: color,
          ),
        ),
      ],
    );
  }
}
