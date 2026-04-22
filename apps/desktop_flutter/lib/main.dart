import 'package:desktop_flutter/app/app.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart'; // ✅ tambah ini

void main() {
  WidgetsFlutterBinding.ensureInitialized();

  runApp(
    const ProviderScope(
      // 🔥 WAJIB
      child: ReadyToPictDesktopApp(),
    ),
  );
}
