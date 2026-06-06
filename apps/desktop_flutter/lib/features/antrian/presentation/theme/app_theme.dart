import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart' as main;

class AppTheme {
  static const Color primary = main.AppColors.primary;
  static const Color primaryDark = main.AppColors.primaryDark;
  static const Color primaryLight = Color(0xFFB8DCFC);
  static const Color background = Color(0xFFF0F2F5);
  static const Color surface = Colors.white;
  static const Color textPrimary = main.AppColors.textPrimary;
  static const Color textSecondary = main.AppColors.textSecondary;
  static const Color accent = main.AppColors.primary;
  static const Color success = main.AppColors.success;
  static const Color warning = main.AppColors.warning;
  static const Color sidebarBg = Colors.white;
  static const Color divider = Color(0xFFE8EBF0);

  static const String fontFamily = 'Poppins';

  static ThemeData get theme {
    return ThemeData(
      fontFamily: fontFamily,
      scaffoldBackgroundColor: background,
      colorScheme: ColorScheme.fromSeed(
        seedColor: primary,
        surface: surface,
      ).copyWith(surface: surface),
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

  static TextStyle get nomorAntrian => const TextStyle(
    fontFamily: fontFamily,
    fontSize: 42,
    fontWeight: FontWeight.w800,
    color: Colors.white,
    letterSpacing: -1,
  );

  static TextStyle get nomorAntrianCard => TextStyle(
    fontFamily: fontFamily,
    fontSize: 22,
    fontWeight: FontWeight.w800,
    color: textPrimary,
    letterSpacing: -0.5,
  );

  static TextStyle get namaCustomer => TextStyle(
    fontFamily: fontFamily,
    fontSize: 15,
    fontWeight: FontWeight.w600,
    color: textPrimary,
  );

  static TextStyle get bodySmall => TextStyle(
    fontFamily: fontFamily,
    fontSize: 13,
    fontWeight: FontWeight.w400,
    color: textSecondary,
  );

  static TextStyle get sectionTitle => TextStyle(
    fontFamily: fontFamily,
    fontSize: 13,
    fontWeight: FontWeight.w700,
    color: textPrimary,
    letterSpacing: 1.2,
  );

  static TextStyle get timerText => TextStyle(
    fontFamily: fontFamily,
    fontSize: 28,
    fontWeight: FontWeight.w800,
    color: textPrimary,
    letterSpacing: 2,
  );
}
