import 'dart:io';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/network/api_client.dart';
import 'package:throughline_mobile/src/core/providers.dart';
import 'package:throughline_mobile/src/core/security/biometric_authenticator.dart';
import 'package:throughline_mobile/src/core/storage/secure_session_store.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';

enum AuthStatus { loading, signedOut, locked, signedIn }

class AuthState {
  const AuthState({
    required this.status,
    this.user,
    this.organizations = const [],
    this.activeOrganizationId,
    this.keepSignedIn = false,
    this.biometricEnabled = false,
    this.biometricAvailable = false,
    this.accountEmail,
    this.error,
  });

  const AuthState.loading() : this(status: AuthStatus.loading);
  const AuthState.signedOut({String? error})
    : this(status: AuthStatus.signedOut, error: error);
  const AuthState.locked({
    required String accountEmail,
    required bool biometricAvailable,
    String? error,
  }) : this(
         status: AuthStatus.locked,
         keepSignedIn: true,
         biometricEnabled: true,
         accountEmail: accountEmail,
         biometricAvailable: biometricAvailable,
         error: error,
       );

  final AuthStatus status;
  final AppUser? user;
  final List<AppOrganization> organizations;
  final int? activeOrganizationId;
  final bool keepSignedIn;
  final bool biometricEnabled;
  final bool biometricAvailable;
  final String? accountEmail;
  final String? error;

  AuthState copyWith({
    AuthStatus? status,
    AppUser? user,
    List<AppOrganization>? organizations,
    int? activeOrganizationId,
    bool? keepSignedIn,
    bool? biometricEnabled,
    bool? biometricAvailable,
    String? accountEmail,
    String? error,
  }) => AuthState(
    status: status ?? this.status,
    user: user ?? this.user,
    organizations: organizations ?? this.organizations,
    activeOrganizationId: activeOrganizationId ?? this.activeOrganizationId,
    keepSignedIn: keepSignedIn ?? this.keepSignedIn,
    biometricEnabled: biometricEnabled ?? this.biometricEnabled,
    biometricAvailable: biometricAvailable ?? this.biometricAvailable,
    accountEmail: accountEmail ?? this.accountEmail,
    error: error,
  );
}

class AuthController extends StateNotifier<AuthState> {
  AuthController(this._api, this._store, this._theme, this._biometrics)
    : super(const AuthState.loading()) {
    restore();
  }

  final ApiClient _api;
  final SessionStore _store;
  final ThemeController _theme;
  final BiometricAuthenticator _biometrics;

  Future<void> restore() async {
    final session = await _store.readSession();
    if (session == null) {
      state = const AuthState.signedOut();
      return;
    }

    if (!session.keepSignedIn || session.isExpired) {
      await _store.clear();
      state = const AuthState.signedOut();
      return;
    }

    if (session.biometricEnabled) {
      final available = await _biometrics.isAvailable();
      state = AuthState.locked(
        accountEmail: session.email ?? 'Throughline account',
        biometricAvailable: available,
        error: available
            ? null
            : 'Biometric unlock is unavailable. Sign in with your password.',
      );
      return;
    }

    await _resumeSession(session);
  }

  Future<bool> login({
    required String email,
    required String password,
    required bool keepSignedIn,
    required bool useBiometrics,
  }) async {
    state = const AuthState.loading();
    try {
      final envelope = await _api.post(
        '/auth/login',
        data: {
          'email': email.trim(),
          'password': password,
          'device_name': _deviceName(),
          'remember_me': keepSignedIn,
        },
      );
      final data = envelope.object('data');
      final user = AppUser.fromJson(data.object('user'));
      final organizations = data
          .maps('organizations')
          .map(AppOrganization.fromJson)
          .toList();
      final organizationId = organizations.isNotEmpty
          ? organizations.first.id
          : null;
      if (organizationId == null) {
        throw const ApiFailure(
          'This account has no active organization. Contact your administrator.',
        );
      }

      var biometricEnabled = false;
      if (keepSignedIn && useBiometrics) {
        biometricEnabled = await _biometrics.authenticate();
      }
      final expiresAt =
          DateTime.tryParse(
            data.object('session').text('expires_at'),
          )?.toUtc() ??
          DateTime.now().toUtc().add(
            Duration(hours: keepSignedIn ? 24 * 90 : 12),
          );

      await _store.saveSession(
        token: data.text('token'),
        organizationId: organizationId,
        keepSignedIn: keepSignedIn,
        biometricEnabled: biometricEnabled,
        expiresAt: expiresAt,
        email: user.email,
      );
      await _theme.useServerPreference(user.theme);
      state = AuthState(
        status: AuthStatus.signedIn,
        user: user,
        organizations: organizations,
        activeOrganizationId: organizationId,
        keepSignedIn: keepSignedIn,
        biometricEnabled: biometricEnabled,
        biometricAvailable: useBiometrics,
        error: useBiometrics && !biometricEnabled
            ? 'Signed in, but biometric unlock was not enabled.'
            : null,
      );
      return true;
    } on ApiFailure catch (failure) {
      state = AuthState.signedOut(error: failure.message);
      return false;
    }
  }

  Future<void> selectOrganization(int organizationId) async {
    await _api.post('/organizations/$organizationId/select');
    await _store.saveOrganization(organizationId);
    state = state.copyWith(activeOrganizationId: organizationId);
    final session = await _store.readSession();
    if (session != null) await _resumeSession(session);
  }

  Future<bool> unlockWithBiometrics() async {
    final session = await _store.readSession();
    if (session == null || session.isExpired || !session.keepSignedIn) {
      await _store.clear();
      state = const AuthState.signedOut(
        error: 'Your saved session expired. Sign in again.',
      );
      return false;
    }

    if (!await _biometrics.authenticate()) {
      state = AuthState.locked(
        accountEmail: session.email ?? 'Throughline account',
        biometricAvailable: await _biometrics.isAvailable(),
        error: 'Biometric verification was not completed.',
      );
      return false;
    }

    await _resumeSession(session);
    return state.status == AuthStatus.signedIn;
  }

  Future<void> usePasswordInstead() async {
    try {
      await _api.post('/auth/logout');
    } on ApiFailure {
      // Local fallback must work even when the retained token cannot reach the API.
    }
    await _store.clear();
    state = const AuthState.signedOut();
  }

  Future<bool> setBiometricEnabled(bool enabled) async {
    if (enabled && !state.keepSignedIn) return false;
    if (enabled && !await _biometrics.authenticate()) return false;

    await _store.setBiometricEnabled(enabled);
    state = state.copyWith(
      biometricEnabled: enabled,
      biometricAvailable: enabled || state.biometricAvailable,
    );
    return true;
  }

  Future<void> logout() async {
    try {
      await _api.post('/auth/logout');
    } on ApiFailure {
      // Local logout must still work if the token is expired or the network is down.
    }
    await _store.clear();
    state = const AuthState.signedOut();
  }

  Future<void> refreshProfile() async {
    final envelope = await _api.get('/profile');
    state = state.copyWith(user: AppUser.fromJson(envelope.object('data')));
  }

  Future<void> _resumeSession(StoredSession session) async {
    state = const AuthState.loading();
    try {
      final envelope = await _api.get('/auth/me');
      await _applySession(envelope.object('data'), session);
    } on ApiFailure catch (failure) {
      if (failure.isUnauthenticated || failure.statusCode == 403) {
        await _store.clear();
        state = const AuthState.signedOut();
        return;
      }
      state = AuthState.locked(
        accountEmail: session.email ?? 'Throughline account',
        biometricAvailable: session.biometricEnabled,
        error: failure.message,
      );
    }
  }

  Future<void> _applySession(JsonMap data, StoredSession session) async {
    final user = AppUser.fromJson(data.object('user'));
    final organizations = data
        .maps('organizations')
        .map(AppOrganization.fromJson)
        .toList();
    await _theme.useServerPreference(user.theme);
    state = AuthState(
      status: AuthStatus.signedIn,
      user: user,
      organizations: organizations,
      activeOrganizationId: session.organizationId,
      keepSignedIn: session.keepSignedIn,
      biometricEnabled: session.biometricEnabled,
      biometricAvailable: session.biometricEnabled,
    );
  }

  String _deviceName() {
    final name =
        'Throughline ${Platform.operatingSystem} ${Platform.operatingSystemVersion}';
    return name.length <= 120 ? name : name.substring(0, 120);
  }
}

final authControllerProvider = StateNotifierProvider<AuthController, AuthState>(
  (ref) => AuthController(
    ref.watch(apiClientProvider),
    ref.watch(secureSessionStoreProvider),
    ref.read(themeControllerProvider.notifier),
    ref.watch(biometricAuthenticatorProvider),
  ),
);
