// presentation/theme/app_theme.dart

import 'package:flutter/material.dart';

class AppTheme {
  // ─── Colors ────────────────────────────────────────────────────────────────
  static const Color primary = Color(0xFF4AABF7);
  static const Color primaryDark = Color(0xFF1A7FD4);
  static const Color primaryLight = Color(0xFFB8DCFC);
  static const Color background = Color(0xFFF0F2F5);
  static const Color surface = Colors.white;
  static const Color textPrimary = Color(0xFF1A2B4A);
  static const Color textSecondary = Color(0xFF6B7A99);
  static const Color accent = Color(0xFF2DD4BF);
  static const Color success = Color(0xFF22C55E);
  static const Color warning = Color(0xFFF59E0B);
  static const Color sidebarBg = Color(0xFFFFFFFF);
  static const Color divider = Color(0xFFE8EBF0);

  // ─── Typography ────────────────────────────────────────────────────────────
  static const String fontFamily = 'Poppins';

  static ThemeData get theme {
    return ThemeData(
      fontFamily: fontFamily,
      scaffoldBackgroundColor: background,
      colorScheme: ColorScheme.fromSeed(
        seedColor: primary,
        background: background,
        surface: surface,
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: surface,
        elevation: 0,
        centerTitle: false,
        titleTextStyle: TextStyle(
          fontFamily: fontFamily,
          color: textPrimary,
          fontSize: 18,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }

  // ─── Text Styles ───────────────────────────────────────────────────────────
  static const nomorAntrian = TextStyle(
    fontFamily: fontFamily,
    fontSize: 42,
    fontWeight: FontWeight.w800,
    color: Colors.white,
    letterSpacing: -1,
  );

  static const nomorAntrianCard = TextStyle(
    fontFamily: fontFamily,
    fontSize: 22,
    fontWeight: FontWeight.w800,
    color: textPrimary,
    letterSpacing: -0.5,
  );

  static const namaCustomer = TextStyle(
    fontFamily: fontFamily,
    fontSize: 15,
    fontWeight: FontWeight.w600,
    color: textPrimary,
  );

  static const bodySmall = TextStyle(
    fontFamily: fontFamily,
    fontSize: 13,
    fontWeight: FontWeight.w400,
    color: textSecondary,
  );

  static const sectionTitle = TextStyle(
    fontFamily: fontFamily,
    fontSize: 13,
    fontWeight: FontWeight.w700,
    color: textPrimary,
    letterSpacing: 1.2,
  );

  static const timerText = TextStyle(
    fontFamily: fontFamily,
    fontSize: 28,
    fontWeight: FontWeight.w800,
    color: textPrimary,
    letterSpacing: 2,
  );
}
