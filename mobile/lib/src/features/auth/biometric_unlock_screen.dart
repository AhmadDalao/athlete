import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';
import 'package:throughline_mobile/src/features/auth/auth_controller.dart';

class BiometricUnlockScreen extends ConsumerStatefulWidget {
  const BiometricUnlockScreen({super.key});

  @override
  ConsumerState<BiometricUnlockScreen> createState() =>
      _BiometricUnlockScreenState();
}

class _BiometricUnlockScreenState extends ConsumerState<BiometricUnlockScreen> {
  bool _authenticating = false;
  bool _prompted = false;

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authControllerProvider);
    if (!_prompted && auth.biometricAvailable) {
      _prompted = true;
      WidgetsBinding.instance.addPostFrameCallback((_) => _unlock());
    }

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(22),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 480),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const ThroughlineMark(),
                  const SizedBox(height: 48),
                  PremiumCard(
                    accent: ThroughlineColors.lime,
                    child: Column(
                      children: [
                        const Icon(Icons.fingerprint_rounded, size: 72),
                        const SizedBox(height: 18),
                        Text(
                          'Welcome back.',
                          style: Theme.of(context).textTheme.headlineSmall
                              ?.copyWith(fontWeight: FontWeight.w900),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          auth.accountEmail ?? 'Throughline account',
                          style: const TextStyle(
                            color: ThroughlineColors.muted,
                          ),
                        ),
                        if (auth.error != null) ...[
                          const SizedBox(height: 16),
                          Text(
                            auth.error!,
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                              color: ThroughlineColors.danger,
                            ),
                          ),
                        ],
                        const SizedBox(height: 24),
                        FilledButton.icon(
                          onPressed: _authenticating || !auth.biometricAvailable
                              ? null
                              : _unlock,
                          icon: _authenticating
                              ? const SizedBox.square(
                                  dimension: 18,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                  ),
                                )
                              : const Icon(Icons.fingerprint_rounded),
                          label: Text(
                            _authenticating
                                ? 'Checking...'
                                : 'Unlock Throughline',
                          ),
                        ),
                        const SizedBox(height: 8),
                        TextButton(
                          onPressed: _authenticating
                              ? null
                              : ref
                                    .read(authControllerProvider.notifier)
                                    .usePasswordInstead,
                          child: const Text('Sign in with password instead'),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _unlock() async {
    if (_authenticating || !mounted) return;
    setState(() => _authenticating = true);
    await ref.read(authControllerProvider.notifier).unlockWithBiometrics();
    if (mounted) setState(() => _authenticating = false);
  }
}
