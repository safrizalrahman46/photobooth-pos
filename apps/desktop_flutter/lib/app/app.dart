import 'package:desktop_flutter/core/network/api_client.dart';
import 'package:desktop_flutter/core/session/session_store.dart';
import 'package:desktop_flutter/features/auth/presentation/login_page.dart';
import 'package:desktop_flutter/features/home/presentation/desktop_home_page.dart';
import 'package:desktop_flutter/shared/models/desktop_session.dart';
import 'package:flutter/material.dart';

class ReadyToPictDesktopApp extends StatefulWidget {
  const ReadyToPictDesktopApp({super.key});

  @override
  State<ReadyToPictDesktopApp> createState() => _ReadyToPictDesktopAppState();
}

class _ReadyToPictDesktopAppState extends State<ReadyToPictDesktopApp> {
  final SessionStore _sessionStore = SessionStore();
  DesktopSession? _session;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _restoreSession();
  }

  Future<void> _restoreSession() async {
    final storedSession = await _sessionStore.load();

    DesktopSession? session = storedSession;

    if (storedSession != null) {
      try {
        final profile = await ApiClient(
          baseUrl: storedSession.baseUrl,
          token: storedSession.token,
        ).fetchProfile();

        session = DesktopSession(
          baseUrl: storedSession.baseUrl,
          token: storedSession.token,
          user: profile,
        );

        await _sessionStore.save(session);
      } on ApiException {
        await _sessionStore.clear();
        session = null;
      }
    }

    if (!mounted) {
      return;
    }

    setState(() {
      _session = session;
      _loading = false;
    });
  }

  Future<void> _handleLogin(DesktopSession session) async {
    await _sessionStore.save(session);

    if (!mounted) {
      return;
    }

    setState(() {
      _session = session;
    });
  }

  Future<void> _handleLogout() async {
    final activeSession = _session;

    if (activeSession != null) {
      await ApiClient(
        baseUrl: activeSession.baseUrl,
        token: activeSession.token,
      ).logout();
    }

    await _sessionStore.clear();

    if (!mounted) {
      return;
    }

    setState(() {
      _session = null;
    });
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Ready To Pict Desktop',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFFB5672A),
          brightness: Brightness.light,
        ),
        scaffoldBackgroundColor: const Color(0xFFF6F1E8),
        useMaterial3: true,
        fontFamily: 'Segoe UI',
      ),
      home: _loading
          ? const _LoadingScreen()
          : _session == null
          ? LoginPage(onLoggedIn: _handleLogin)
          : DesktopHomePage(session: _session!, onLogout: _handleLogout),
    );
  }
}

class _LoadingScreen extends StatelessWidget {
  const _LoadingScreen();

  @override
  Widget build(BuildContext context) {
    return const Scaffold(body: Center(child: CircularProgressIndicator()));
  }
}
