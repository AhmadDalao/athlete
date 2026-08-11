import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/network/api_client.dart';
import 'package:throughline_mobile/src/core/providers.dart';
import 'package:throughline_mobile/src/core/storage/secure_session_store.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';

enum AuthStatus { loading, signedOut, signedIn }

class AuthState {
  const AuthState({
    required this.status,
    this.user,
    this.organizations = const [],
    this.activeOrganizationId,
    this.error,
  });

  const AuthState.loading() : this(status: AuthStatus.loading);
  const AuthState.signedOut({String? error})
    : this(status: AuthStatus.signedOut, error: error);

  final AuthStatus status;
  final AppUser? user;
  final List<AppOrganization> organizations;
  final int? activeOrganizationId;
  final String? error;

  AuthState copyWith({
    AuthStatus? status,
    AppUser? user,
    List<AppOrganization>? organizations,
    int? activeOrganizationId,
    String? error,
  }) => AuthState(
    status: status ?? this.status,
    user: user ?? this.user,
    organizations: organizations ?? this.organizations,
    activeOrganizationId: activeOrganizationId ?? this.activeOrganizationId,
    error: error,
  );
}

class AuthController extends StateNotifier<AuthState> {
  AuthController(this._api, this._store, this._theme)
    : super(const AuthState.loading()) {
    restore();
  }

  final ApiClient _api;
  final SecureSessionStore _store;
  final ThemeController _theme;

  Future<void> restore() async {
    final token = await _store.readToken();
    final organizationId = int.tryParse(
      await _store.readOrganizationId() ?? '',
    );
    if (token == null || organizationId == null) {
      state = const AuthState.signedOut();
      return;
    }

    try {
      final envelope = await _api.get('/auth/me');
      await _applySession(envelope.object('data'), organizationId);
    } on ApiFailure catch (failure) {
      if (failure.isUnauthenticated || failure.statusCode == 403) {
        await _store.clear();
      }
      state = AuthState.signedOut(error: failure.message);
    }
  }

  Future<bool> login({required String email, required String password}) async {
    state = const AuthState.loading();
    try {
      final envelope = await _api.post(
        '/auth/login',
        data: {
          'email': email.trim(),
          'password': password,
          'device_name': 'Throughline Flutter',
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

      await _store.saveSession(
        token: data.text('token'),
        organizationId: organizationId,
      );
      await _theme.useServerPreference(user.theme);
      state = AuthState(
        status: AuthStatus.signedIn,
        user: user,
        organizations: organizations,
        activeOrganizationId: organizationId,
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
    await restore();
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

  Future<void> _applySession(JsonMap data, int organizationId) async {
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
      activeOrganizationId: organizationId,
    );
  }
}

final authControllerProvider = StateNotifierProvider<AuthController, AuthState>(
  (ref) => AuthController(
    ref.watch(apiClientProvider),
    ref.watch(secureSessionStoreProvider),
    ref.read(themeControllerProvider.notifier),
  ),
);
