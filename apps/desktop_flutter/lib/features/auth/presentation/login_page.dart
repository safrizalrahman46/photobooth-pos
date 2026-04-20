import 'package:desktop_flutter/core/config/app_config.dart';
import 'package:desktop_flutter/core/network/api_client.dart';
import 'package:desktop_flutter/shared/models/desktop_session.dart';
import 'package:flutter/material.dart';

class LoginPage extends StatefulWidget {
  const LoginPage({super.key, required this.onLoggedIn});

  final ValueChanged<DesktopSession> onLoggedIn;

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final TextEditingController _baseUrlController = TextEditingController(
    text: AppConfig.defaultApiBaseUrl,
  );
  final TextEditingController _emailController = TextEditingController(
    text: 'owner@readytopict.test',
  );
  final TextEditingController _passwordController = TextEditingController(
    text: 'password',
  );
  bool _submitting = false;
  String? _error;

  @override
  void dispose() {
    _baseUrlController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final session = await ApiClient(baseUrl: _baseUrlController.text.trim())
          .login(
            email: _emailController.text.trim(),
            password: _passwordController.text,
          );

      widget.onLoggedIn(session);
    } on ApiException catch (error) {
      setState(() {
        _error = error.message;
      });
    } catch (_) {
      setState(() {
        _error = 'Tidak dapat terhubung ke server Laravel.';
      });
    } finally {
      if (mounted) {
        setState(() {
          _submitting = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: LayoutBuilder(
        builder: (BuildContext context, BoxConstraints constraints) {
          final bool isCompact = constraints.maxWidth < 900;

          return SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 960),
                child: isCompact
                    ? Column(
                        children: <Widget>[
                          _buildHeroCard(),
                          const SizedBox(height: 24),
                          _buildLoginCard(context),
                        ],
                      )
                    : Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: <Widget>[
                          Expanded(child: _buildHeroCard()),
                          const SizedBox(width: 24),
                          Expanded(child: _buildLoginCard(context)),
                        ],
                      ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildHeroCard() {
    return Container(
      padding: const EdgeInsets.all(32),
      decoration: BoxDecoration(
        color: const Color(0xFF1C1710),
        borderRadius: BorderRadius.circular(28),
      ),
      child: const _HeroPanel(),
    );
  }

  Widget _buildLoginCard(BuildContext context) {
    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(28)),
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            Text(
              'Masuk Desktop App',
              style: Theme.of(
                context,
              ).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 8),
            Text(
              'Login ke API Laravel yang sama dengan website dan dashboard admin.',
              style: Theme.of(context).textTheme.bodyMedium,
            ),
            const SizedBox(height: 24),
            TextField(
              controller: _baseUrlController,
              decoration: const InputDecoration(
                labelText: 'API Base URL',
                hintText: 'http://127.0.0.1:8000/api/v1',
              ),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _emailController,
              decoration: const InputDecoration(labelText: 'Email'),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _passwordController,
              obscureText: true,
              decoration: const InputDecoration(labelText: 'Password'),
              onSubmitted: (_) => _submitting ? null : _submit(),
            ),
            if (_error != null) ...<Widget>[
              const SizedBox(height: 16),
              Text(_error!, style: const TextStyle(color: Colors.redAccent)),
            ],
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: _submitting ? null : _submit,
                child: Padding(
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  child: _submitting
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Text('Login'),
                ),
              ),
            ),
            const SizedBox(height: 20),
            const Text(
              'Akun contoh:\nowner@readytopict.test / password\ncashier@readytopict.test / password',
            ),
          ],
        ),
      ),
    );
  }
}

class _HeroPanel extends StatelessWidget {
  const _HeroPanel();

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: const <Widget>[
        Text(
          'READY TO PICT',
          style: TextStyle(
            color: Color(0xFFF4DFC8),
            fontSize: 14,
            fontWeight: FontWeight.w700,
            letterSpacing: 2,
          ),
        ),
        SizedBox(height: 16),
        Text(
          'Kasir dan owner dalam satu desktop app Windows.',
          style: TextStyle(
            color: Colors.white,
            fontSize: 34,
            fontWeight: FontWeight.w700,
            height: 1.2,
          ),
        ),
        SizedBox(height: 16),
        Text(
          'Fondasi app ini sudah siap untuk login ke backend Laravel, lalu berkembang ke modul queue, POS, laporan, cabang, dan pengaturan web.',
          style: TextStyle(color: Color(0xFFD9D0C3), fontSize: 15, height: 1.6),
        ),
      ],
    );
  }
}
