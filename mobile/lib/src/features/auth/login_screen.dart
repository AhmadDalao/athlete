import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/network/api_client.dart';
import 'package:throughline_mobile/src/core/providers.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';
import 'package:throughline_mobile/src/features/auth/auth_controller.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _obscure = true;
  bool _keepSignedIn = false;
  bool _useBiometrics = false;
  bool _biometricAvailable = false;

  @override
  void initState() {
    super.initState();
    _checkBiometrics();
  }

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final auth = ref.watch(authControllerProvider);
    final loading = auth.status == AuthStatus.loading;

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
                  const SizedBox(height: 52),
                  Text(
                    'Your work starts here.',
                    style: Theme.of(context).textTheme.displaySmall?.copyWith(
                      fontWeight: FontWeight.w900,
                      height: 0.95,
                    ),
                  ),
                  const SizedBox(height: 14),
                  const Text(
                    'Athletes train. Coaches decide. Throughline keeps the work connected.',
                    style: TextStyle(
                      color: ThroughlineColors.muted,
                      height: 1.5,
                      fontSize: 16,
                    ),
                  ),
                  const SizedBox(height: 30),
                  PremiumCard(
                    accent: ThroughlineColors.lime,
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Text(
                            'Sign in',
                            style: Theme.of(context).textTheme.headlineSmall
                                ?.copyWith(fontWeight: FontWeight.w900),
                          ),
                          const SizedBox(height: 20),
                          TextFormField(
                            controller: _email,
                            keyboardType: TextInputType.emailAddress,
                            textInputAction: TextInputAction.next,
                            autofillHints: const [AutofillHints.email],
                            decoration: const InputDecoration(
                              labelText: 'Email',
                              prefixIcon: Icon(Icons.alternate_email_rounded),
                            ),
                            validator: (value) =>
                                value == null || !value.contains('@')
                                ? 'Enter a valid email.'
                                : null,
                          ),
                          const SizedBox(height: 14),
                          TextFormField(
                            controller: _password,
                            obscureText: _obscure,
                            autofillHints: const [AutofillHints.password],
                            onFieldSubmitted: (_) => _submit(),
                            decoration: InputDecoration(
                              labelText: 'Password',
                              prefixIcon: const Icon(
                                Icons.lock_outline_rounded,
                              ),
                              suffixIcon: IconButton(
                                onPressed: () =>
                                    setState(() => _obscure = !_obscure),
                                icon: Icon(
                                  _obscure
                                      ? Icons.visibility_outlined
                                      : Icons.visibility_off_outlined,
                                ),
                              ),
                            ),
                            validator: (value) => value == null || value.isEmpty
                                ? 'Enter your password.'
                                : null,
                          ),
                          CheckboxListTile(
                            value: _keepSignedIn,
                            contentPadding: EdgeInsets.zero,
                            controlAffinity: ListTileControlAffinity.leading,
                            title: const Text('Keep me signed in'),
                            subtitle: const Text(
                              'Keep this device connected for up to 90 days.',
                            ),
                            onChanged: loading
                                ? null
                                : (value) => setState(() {
                                    _keepSignedIn = value ?? false;
                                    if (!_keepSignedIn) _useBiometrics = false;
                                  }),
                          ),
                          if (_biometricAvailable)
                            CheckboxListTile(
                              value: _useBiometrics,
                              contentPadding: EdgeInsets.zero,
                              controlAffinity: ListTileControlAffinity.leading,
                              secondary: const Icon(Icons.fingerprint_rounded),
                              title: const Text('Use biometric unlock'),
                              subtitle: const Text(
                                'Require your fingerprint or face when reopening the app.',
                              ),
                              onChanged: loading || !_keepSignedIn
                                  ? null
                                  : (value) => setState(
                                      () => _useBiometrics = value ?? false,
                                    ),
                            ),
                          Align(
                            alignment: Alignment.centerRight,
                            child: TextButton(
                              onPressed: loading ? null : _showPasswordRecovery,
                              child: const Text('Forgot password?'),
                            ),
                          ),
                          if (auth.error != null) ...[
                            const SizedBox(height: 14),
                            Text(
                              auth.error!,
                              style: const TextStyle(
                                color: ThroughlineColors.danger,
                              ),
                            ),
                          ],
                          const SizedBox(height: 20),
                          FilledButton.icon(
                            onPressed: loading ? null : _submit,
                            icon: loading
                                ? const SizedBox.square(
                                    dimension: 18,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                    ),
                                  )
                                : const Icon(Icons.arrow_forward_rounded),
                            label: Text(
                              loading ? 'Signing in...' : 'Enter Throughline',
                            ),
                          ),
                        ],
                      ),
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

  Future<void> _submit() async {
    FocusScope.of(context).unfocus();
    if (!_formKey.currentState!.validate()) return;
    await ref
        .read(authControllerProvider.notifier)
        .login(
          email: _email.text,
          password: _password.text,
          keepSignedIn: _keepSignedIn,
          useBiometrics: _useBiometrics,
        );
  }

  Future<void> _checkBiometrics() async {
    final available = await ref
        .read(biometricAuthenticatorProvider)
        .isAvailable();
    if (mounted) setState(() => _biometricAvailable = available);
  }

  Future<void> _showPasswordRecovery() async {
    final email = TextEditingController(text: _email.text.trim());
    final formKey = GlobalKey<FormState>();
    var sending = false;
    String? error;

    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (sheetContext) => StatefulBuilder(
        builder: (context, setSheetState) => Padding(
          padding: EdgeInsets.fromLTRB(
            22,
            22,
            22,
            MediaQuery.viewInsetsOf(context).bottom + 22,
          ),
          child: Form(
            key: formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        'Reset password',
                        style: Theme.of(context).textTheme.headlineSmall
                            ?.copyWith(fontWeight: FontWeight.w900),
                      ),
                    ),
                    IconButton(
                      onPressed: sending
                          ? null
                          : () => Navigator.of(sheetContext).pop(),
                      icon: const Icon(Icons.close_rounded),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                const Text(
                  'We will email a secure reset link if this address belongs to an active account.',
                  style: TextStyle(
                    color: ThroughlineColors.muted,
                    height: 1.45,
                  ),
                ),
                const SizedBox(height: 20),
                TextFormField(
                  controller: email,
                  keyboardType: TextInputType.emailAddress,
                  autofillHints: const [AutofillHints.email],
                  decoration: const InputDecoration(
                    labelText: 'Email',
                    prefixIcon: Icon(Icons.alternate_email_rounded),
                  ),
                  validator: (value) => value == null || !value.contains('@')
                      ? 'Enter a valid email.'
                      : null,
                ),
                if (error != null) ...[
                  const SizedBox(height: 12),
                  Text(
                    error!,
                    style: const TextStyle(color: ThroughlineColors.danger),
                  ),
                ],
                const SizedBox(height: 20),
                FilledButton.icon(
                  onPressed: sending
                      ? null
                      : () async {
                          if (!formKey.currentState!.validate()) return;
                          setSheetState(() {
                            sending = true;
                            error = null;
                          });
                          try {
                            final envelope = await ref
                                .read(apiClientProvider)
                                .post(
                                  '/auth/password/forgot',
                                  data: {'email': email.text.trim()},
                                );
                            if (!sheetContext.mounted) return;
                            Navigator.of(sheetContext).pop();
                            if (!mounted) return;
                            final data = (envelope['data'] as Map)
                                .cast<String, dynamic>();
                            ScaffoldMessenger.of(this.context).showSnackBar(
                              SnackBar(
                                content: Text(data['message'] as String),
                              ),
                            );
                          } on ApiFailure catch (failure) {
                            setSheetState(() {
                              sending = false;
                              error = failure.message;
                            });
                          }
                        },
                  icon: sending
                      ? const SizedBox.square(
                          dimension: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.mail_outline_rounded),
                  label: Text(sending ? 'Sending...' : 'Send reset link'),
                ),
              ],
            ),
          ),
        ),
      ),
    );

    email.dispose();
  }
}

class LaunchScreen extends StatelessWidget {
  const LaunchScreen({super.key});

  @override
  Widget build(BuildContext context) => const Scaffold(
    body: Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          ThroughlineMark(),
          SizedBox(height: 28),
          CircularProgressIndicator(),
        ],
      ),
    ),
  );
}
