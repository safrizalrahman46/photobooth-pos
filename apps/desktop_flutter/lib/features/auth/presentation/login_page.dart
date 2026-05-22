import 'package:desktop_flutter/core/config/app_config.dart';
import 'package:desktop_flutter/core/network/api_client.dart';
import 'package:desktop_flutter/shared/models/desktop_session.dart';
import 'package:flutter/material.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/app/theme/app_text_styles.dart';

class LoginPage extends StatefulWidget {
  const LoginPage({super.key, required this.onLoggedIn});

  final ValueChanged<DesktopSession> onLoggedIn;

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage>
    with SingleTickerProviderStateMixin {
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final FocusNode _emailFocus = FocusNode();
  final FocusNode _passwordFocus = FocusNode();

  bool _submitting = false;
  bool _obscurePassword = true;
  String? _error;

  late final AnimationController _animController;
  late final Animation<double> _fadeAnim;
  late final Animation<Offset> _slideAnim;

  @override
  void initState() {
    super.initState();
    _animController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 600),
    );
    _fadeAnim = CurvedAnimation(parent: _animController, curve: Curves.easeOut);
    _slideAnim = Tween<Offset>(
      begin: const Offset(0, 0.06),
      end: Offset.zero,
    ).animate(CurvedAnimation(parent: _animController, curve: Curves.easeOut));
    _animController.forward();
  }

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    _emailFocus.dispose();
    _passwordFocus.dispose();
    _animController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    final email = _emailController.text.trim();
    final password = _passwordController.text;

    if (email.isEmpty || password.isEmpty) {
      setState(() => _error = 'Email dan password wajib diisi.');
      return;
    }

    setState(() {
      _submitting = true;
      _error = null;
    });

    try {
      final session = await ApiClient(
        baseUrl: AppConfig.apiBaseUrl,
      ).login(email: email, password: password);
      widget.onLoggedIn(session);
    } on ApiException catch (error) {
      if (!mounted) {
        return;
      }

      setState(() => _error = error.message);
    } catch (_) {
      if (!mounted) {
        return;
      }

      setState(() => _error = AppConfig.connectionErrorMessage);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: FadeTransition(
        opacity: _fadeAnim,
        child: SlideTransition(
          position: _slideAnim,
          child: LayoutBuilder(
            builder: (context, constraints) {
              final bool isCompact = constraints.maxWidth < 900;
              return SingleChildScrollView(
                padding: const EdgeInsets.all(32),
                child: Center(
                  child: ConstrainedBox(
                    constraints: const BoxConstraints(maxWidth: 980),
                    child: isCompact
                        ? Column(
                            children: [
                              _buildHeroCard(),
                              const SizedBox(height: 24),
                              _buildLoginCard(),
                            ],
                          )
                        : IntrinsicHeight(
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.stretch,
                              children: [
                                Expanded(flex: 5, child: _buildHeroCard()),
                                const SizedBox(width: 20),
                                Expanded(flex: 5, child: _buildLoginCard()),
                              ],
                            ),
                          ),
                  ),
                ),
              );
            },
          ),
        ),
      ),
    );
  }

  // ─── HERO CARD ──────────────────────────────────────────────────────────────

  Widget _buildHeroCard() {
    return Container(
      padding: const EdgeInsets.all(40),
      decoration: BoxDecoration(
        color: const Color(0xFF1C1710),
        borderRadius: BorderRadius.circular(24),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          // Brand chip
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(
              color: AppColors.primary.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(20),
              border: Border.all(
                color: AppColors.primary.withValues(alpha: 0.35),
              ),
            ),
            child: Text(
              'READY TO PICT',
              style: TextStyle(
                color: AppColors.primary,
                fontSize: 11,
                fontWeight: FontWeight.w700,
                letterSpacing: 2.5,
              ),
            ),
          ),
          const SizedBox(height: 28),

          // Headline
          const Text(
            'Login Admin\nReady To Pict.',
            style: TextStyle(
              color: Colors.white,
              fontSize: 36,
              fontWeight: FontWeight.w700,
              height: 1.18,
            ),
          ),
          const SizedBox(height: 20),

          // Body
          const Text(
            'Masuk untuk membuka POS desktop dan monitoring operasional Ready To Pict.',
            style: TextStyle(
              color: Color(0xFFB8AFA4),
              fontSize: 14,
              height: 1.65,
            ),
          ),
          const SizedBox(height: 40),

          // Feature pills
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: const [
              _FeatureChip(label: 'POS Kasir'),
              _FeatureChip(label: 'Queue System'),
              _FeatureChip(label: 'Laporan'),
              _FeatureChip(label: 'Multi Cabang'),
            ],
          ),

          const SizedBox(height: 48),

          // Footer divider
          Container(height: 1, color: Colors.white.withValues(alpha: 0.07)),
          const SizedBox(height: 20),

          Row(
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(
                  Icons.desktop_windows_rounded,
                  color: AppColors.primary,
                  size: 18,
                ),
              ),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: const [
                  Text(
                    'Windows Desktop App',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  Text(
                    'Built for Ready to pict',
                    style: TextStyle(color: Color(0xFF7A7168), fontSize: 12),
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }

  // ─── LOGIN CARD ─────────────────────────────────────────────────────────────

  Widget _buildLoginCard() {
    return Container(
      padding: const EdgeInsets.all(40),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppColors.cardBorder),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Row(
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: AppColors.primaryLight,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  Icons.lock_open_rounded,
                  color: AppColors.primary,
                  size: 22,
                ),
              ),
              const SizedBox(width: 14),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Login Admin', style: AppTextStyles.h2),
                  Text(
                    'Ready To Pict',
                    style: AppTextStyles.bodySmall.copyWith(
                      color: AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            ],
          ),

          const SizedBox(height: 8),
          Container(height: 1, color: AppColors.divider),
          const SizedBox(height: 28),

          // Subtitle
          Text(
            'Gunakan akun yang sama dengan website dashboard.',
            style: AppTextStyles.bodySmall.copyWith(
              color: AppColors.textSecondary,
              height: 1.6,
            ),
          ),
          const SizedBox(height: 28),

          // Email field
          _buildLabel('Email'),
          const SizedBox(height: 8),
          _buildTextField(
            controller: _emailController,
            focusNode: _emailFocus,
            nextFocus: _passwordFocus,
            hint: 'contoh@domain.com',
            prefixIcon: Icons.alternate_email_rounded,
            keyboardType: TextInputType.emailAddress,
          ),
          const SizedBox(height: 18),

          // Password field
          _buildLabel('Password'),
          const SizedBox(height: 8),
          _buildTextField(
            controller: _passwordController,
            focusNode: _passwordFocus,
            hint: '••••••••',
            prefixIcon: Icons.key_rounded,
            obscureText: _obscurePassword,
            suffixIcon: GestureDetector(
              onTap: () => setState(() => _obscurePassword = !_obscurePassword),
              child: Icon(
                _obscurePassword
                    ? Icons.visibility_off_rounded
                    : Icons.visibility_rounded,
                color: AppColors.textMuted,
                size: 20,
              ),
            ),
            onSubmitted: (_) => _submitting ? null : _submit(),
          ),

          // Error
          if (_error != null) ...[
            const SizedBox(height: 16),
            _buildErrorBanner(_error!),
          ],

          const SizedBox(height: 28),

          // Login button
          _buildLoginButton(),
        ],
      ),
    );
  }

  Widget _buildLabel(String text) {
    return Text(
      text,
      style: TextStyle(
        fontSize: 13,
        fontWeight: FontWeight.w600,
        color: AppColors.textPrimary,
        letterSpacing: 0.1,
      ),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required FocusNode focusNode,
    FocusNode? nextFocus,
    required String hint,
    required IconData prefixIcon,
    bool obscureText = false,
    Widget? suffixIcon,
    TextInputType? keyboardType,
    ValueChanged<String>? onSubmitted,
  }) {
    return TextField(
      controller: controller,
      focusNode: focusNode,
      obscureText: obscureText,
      keyboardType: keyboardType,
      style: AppTextStyles.body.copyWith(color: AppColors.textPrimary),
      onSubmitted:
          onSubmitted ??
          (nextFocus != null
              ? (_) => FocusScope.of(context).requestFocus(nextFocus)
              : null),
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: TextStyle(color: AppColors.textMuted, fontSize: 14),
        filled: true,
        fillColor: AppColors.inputBg,
        prefixIcon: Padding(
          padding: const EdgeInsets.only(left: 14, right: 10),
          child: Icon(prefixIcon, color: AppColors.textMuted, size: 18),
        ),
        prefixIconConstraints: const BoxConstraints(),
        suffixIcon: suffixIcon != null
            ? Padding(
                padding: const EdgeInsets.only(right: 12),
                child: suffixIcon,
              )
            : null,
        suffixIconConstraints: const BoxConstraints(),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 14,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: AppColors.inputBorder),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: AppColors.inputBorder),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: AppColors.inputFocus, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Colors.redAccent),
        ),
      ),
    );
  }

  Widget _buildErrorBanner(String message) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF1F1),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: const Color(0xFFFFCDD2)),
      ),
      child: Row(
        children: [
          const Icon(
            Icons.error_outline_rounded,
            color: Colors.redAccent,
            size: 18,
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: const TextStyle(
                color: Colors.redAccent,
                fontSize: 13,
                height: 1.4,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLoginButton() {
    return SizedBox(
      width: double.infinity,
      height: 50,
      child: Material(
        color: _submitting
            ? AppColors.primary.withValues(alpha: 0.7)
            : AppColors.primary,
        borderRadius: BorderRadius.circular(12),
        child: InkWell(
          borderRadius: BorderRadius.circular(12),
          onTap: _submitting ? null : _submit,
          splashColor: Colors.white.withValues(alpha: 0.1),
          highlightColor: Colors.white.withValues(alpha: 0.05),
          child: AnimatedSwitcher(
            duration: const Duration(milliseconds: 200),
            child: _submitting
                ? const Center(
                    key: ValueKey('loading'),
                    child: SizedBox(
                      width: 22,
                      height: 22,
                      child: CircularProgressIndicator(
                        color: Colors.white,
                        strokeWidth: 2.5,
                      ),
                    ),
                  )
                : Row(
                    key: const ValueKey('label'),
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        'Masuk',
                        style: AppTextStyles.bodyWhite.copyWith(
                          fontSize: 15,
                          fontWeight: FontWeight.w600,
                          letterSpacing: 0.3,
                        ),
                      ),
                      const SizedBox(width: 8),
                      const Icon(
                        Icons.arrow_forward_rounded,
                        color: Colors.white,
                        size: 18,
                      ),
                    ],
                  ),
          ),
        ),
      ),
    );
  }
}

// ─── FEATURE CHIP ────────────────────────────────────────────────────────────

class _FeatureChip extends StatelessWidget {
  const _FeatureChip({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.06),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.white.withValues(alpha: 0.12)),
      ),
      child: Text(
        label,
        style: const TextStyle(
          color: Color(0xFFD9D0C3),
          fontSize: 12,
          fontWeight: FontWeight.w500,
        ),
      ),
    );
  }
}
