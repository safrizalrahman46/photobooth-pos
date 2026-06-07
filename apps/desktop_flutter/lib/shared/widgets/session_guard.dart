import 'package:flutter/material.dart';

import '../../../app/theme/app_colors.dart';
import '../../../app/theme/app_text_styles.dart';
import '../../../core/session/api_session.dart';

class SessionGuard extends StatefulWidget {
  final Widget Function() builder;
  final VoidCallback onNavigateToSession;

  const SessionGuard({
    super.key,
    required this.builder,
    required this.onNavigateToSession,
  });

  static void invalidateCache() {
    _SessionCache.invalidate();
  }

  @override
  State<SessionGuard> createState() => _SessionGuardState();
}

class _SessionGuardState extends State<SessionGuard> {
  bool _loading = true;
  bool _hasActiveSession = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _checkSession();
  }

  Future<void> _checkSession() async {
    final cached = _SessionCache.get();
    if (cached != null) {
      if (mounted) {
        setState(() {
          _hasActiveSession = cached;
          _loading = false;
        });
      }
      return;
    }

    final user = ApiSession.current?.user;
    if (user == null) {
      if (mounted) {
        setState(() {
          _hasActiveSession = true;
          _loading = false;
        });
      }
      return;
    }

    final isCashier = user.roles.contains('cashier') &&
        !user.roles.contains('owner') &&
        !user.roles.contains('admin');

    if (!isCashier) {
      if (mounted) {
        setState(() {
          _hasActiveSession = true;
          _loading = false;
        });
      }
      return;
    }

    final client = ApiSession.client;
    if (client == null) {
      if (mounted) {
        setState(() {
          _hasActiveSession = false;
          _loading = false;
          _error = 'Sesi login tidak ditemukan.';
        });
      }
      return;
    }

    try {
      final session = await client.fetchCurrentCashierSession();
      final isActive = session != null && session.isOpen;
      _SessionCache.set(isActive);

      if (mounted) {
        setState(() {
          _hasActiveSession = isActive;
          _loading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = 'Gagal memeriksa sesi kasir. Periksa koneksi internet.';
          _loading = false;
          _hasActiveSession = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      );
    }

    if (!_hasActiveSession) {
      return Scaffold(
        body: _SessionBlockedPage(
          error: _error,
          onRetry: () {
            _SessionCache.invalidate();
            setState(() => _loading = true);
            _checkSession();
          },
          onNavigateToSession: widget.onNavigateToSession,
        ),
      );
    }

    return widget.builder();
  }
}

class _SessionBlockedPage extends StatelessWidget {
  final String? error;
  final VoidCallback onRetry;
  final VoidCallback onNavigateToSession;

  const _SessionBlockedPage({
    this.error,
    required this.onRetry,
    required this.onNavigateToSession,
  });

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppColors.warning.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(16),
              ),
              child: const Icon(
                Icons.point_of_sale_rounded,
                size: 48,
                color: AppColors.warning,
              ),
            ),
            const SizedBox(height: 24),
            const Text(
              'Sesi Kasir Belum Aktif',
              style: AppTextStyles.h2,
            ),
            const SizedBox(height: 12),
            Text(
              error ??
                  'Anda harus membuka sesi kasir terlebih dahulu\nsebelum dapat menggunakan fitur ini.',
              textAlign: TextAlign.center,
              style: AppTextStyles.body.copyWith(
                color: AppColors.textSecondary,
              ),
            ),
            const SizedBox(height: 32),
            FilledButton.icon(
              onPressed: onNavigateToSession,
              icon: const Icon(Icons.point_of_sale_rounded),
              label: const Text('Buka Sesi Kasir'),
              style: FilledButton.styleFrom(
                padding: const EdgeInsets.symmetric(
                  horizontal: 24,
                  vertical: 14,
                ),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
            const SizedBox(height: 12),
            TextButton(
              onPressed: onRetry,
              child: const Text('Coba Lagi'),
            ),
          ],
        ),
      ),
    );
  }
}

class _SessionCache {
  static DateTime? _lastCheck;
  static bool? _isActive;
  static const _ttl = Duration(seconds: 15);

  static bool? get() {
    if (_lastCheck == null) return null;
    if (DateTime.now().difference(_lastCheck!) > _ttl) return null;
    return _isActive;
  }

  static void set(bool isActive) {
    _isActive = isActive;
    _lastCheck = DateTime.now();
  }

  static void invalidate() {
    _lastCheck = null;
    _isActive = null;
  }
}
