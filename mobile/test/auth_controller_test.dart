import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/network/api_client.dart';
import 'package:throughline_mobile/src/core/security/biometric_authenticator.dart';
import 'package:throughline_mobile/src/core/storage/secure_session_store.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/features/auth/auth_controller.dart';

void main() {
  setUp(() => SharedPreferences.setMockInitialValues({}));

  test(
    'a retained biometric session starts behind the biometric lock',
    () async {
      final api = _FakeApiClient();
      final controller = AuthController(
        api,
        _FakeSessionStore(_rememberedSession(biometricEnabled: true)),
        ThemeController(),
        _FakeBiometrics(available: true, authenticates: true),
      );
      addTearDown(controller.dispose);

      await _settle();

      expect(controller.state.status, AuthStatus.locked);
      expect(controller.state.accountEmail, 'athlete@example.test');
      expect(api.getCalls, 0, reason: 'The API token must stay locked.');
    },
  );

  test('biometric success restores and validates the API session', () async {
    final api = _FakeApiClient();
    final controller = AuthController(
      api,
      _FakeSessionStore(_rememberedSession(biometricEnabled: true)),
      ThemeController(),
      _FakeBiometrics(available: true, authenticates: true),
    );
    addTearDown(controller.dispose);
    await _settle();

    final unlocked = await controller.unlockWithBiometrics();

    expect(unlocked, isTrue);
    expect(controller.state.status, AuthStatus.signedIn);
    expect(controller.state.user?.name, 'Demo Athlete');
    expect(api.getCalls, 1);
  });

  test('a session without keep signed in is cleared on app restart', () async {
    final store = _FakeSessionStore(
      _rememberedSession(biometricEnabled: false, keepSignedIn: false),
    );
    final controller = AuthController(
      _FakeApiClient(),
      store,
      ThemeController(),
      _FakeBiometrics(available: true, authenticates: true),
    );
    addTearDown(controller.dispose);

    await _settle();

    expect(controller.state.status, AuthStatus.signedOut);
    expect(store.session, isNull);
  });

  test('an expired server token returns to a clean login screen', () async {
    final store = _FakeSessionStore(
      _rememberedSession(biometricEnabled: false),
    );
    final controller = AuthController(
      _FakeApiClient(
        getFailure: const ApiFailure(
          'Authentication is required.',
          statusCode: 401,
        ),
      ),
      store,
      ThemeController(),
      _FakeBiometrics(available: false, authenticates: false),
    );
    addTearDown(controller.dispose);
    await _settle();

    expect(controller.state.status, AuthStatus.signedOut);
    expect(controller.state.error, isNull);
    expect(store.session, isNull);
  });

  test(
    'login accepts the current live response without session metadata',
    () async {
      final store = _FakeSessionStore(null);
      final api = _FakeApiClient(postResponse: _authEnvelope());
      final controller = AuthController(
        api,
        store,
        ThemeController(),
        _FakeBiometrics(available: false, authenticates: false),
      );
      addTearDown(controller.dispose);
      await _settle();

      final signedIn = await controller.login(
        email: 'athlete@example.test',
        password: 'password',
        keepSignedIn: true,
        useBiometrics: false,
      );

      expect(signedIn, isTrue);
      expect(controller.state.status, AuthStatus.signedIn);
      expect(store.session?.keepSignedIn, isTrue);
      expect(store.session?.token, 'token');
      expect(
        store.session!.expiresAt.isAfter(
          DateTime.now().toUtc().add(const Duration(days: 89)),
        ),
        isTrue,
      );
    },
  );

  test('login uses server-provided remembered session expiry', () async {
    final expiresAt = DateTime.now().toUtc().add(const Duration(days: 30));
    final response = _authEnvelope();
    (response['data'] as Map<String, dynamic>)['session'] = {
      'expires_at': expiresAt.toIso8601String(),
    };
    final store = _FakeSessionStore(null);
    final controller = AuthController(
      _FakeApiClient(postResponse: response),
      store,
      ThemeController(),
      _FakeBiometrics(available: false, authenticates: false),
    );
    addTearDown(controller.dispose);
    await _settle();

    await controller.login(
      email: 'athlete@example.test',
      password: 'password',
      keepSignedIn: true,
      useBiometrics: false,
    );

    expect(store.session?.expiresAt, expiresAt);
  });
}

JsonMap _authEnvelope() => {
  'data': {
    'token': 'token',
    'user': {
      'id': 12,
      'name': 'Demo Athlete',
      'email': 'athlete@example.test',
      'platform_role': 'athlete',
      'organization_role': 'athlete',
      'theme_preference': 'system',
    },
    'organizations': [
      {
        'id': 7,
        'name': 'Throughline',
        'role': 'athlete',
        'timezone': 'Asia/Riyadh',
      },
    ],
  },
};

StoredSession _rememberedSession({
  required bool biometricEnabled,
  bool keepSignedIn = true,
}) => StoredSession(
  token: 'token',
  organizationId: 7,
  keepSignedIn: keepSignedIn,
  biometricEnabled: biometricEnabled,
  expiresAt: DateTime.now().toUtc().add(const Duration(days: 1)),
  email: 'athlete@example.test',
);

Future<void> _settle() async {
  for (var index = 0; index < 8; index++) {
    await Future<void>.delayed(Duration.zero);
  }
}

class _FakeBiometrics implements BiometricAuthenticator {
  _FakeBiometrics({required this.available, required this.authenticates});

  final bool available;
  final bool authenticates;

  @override
  Future<bool> authenticate() async => authenticates;

  @override
  Future<bool> isAvailable() async => available;
}

class _FakeSessionStore implements SessionStore {
  _FakeSessionStore(this.session);

  StoredSession? session;

  @override
  Future<void> clear() async => session = null;

  @override
  Future<String?> readOrganizationId() async =>
      session?.organizationId.toString();

  @override
  Future<StoredSession?> readSession() async => session;

  @override
  Future<String?> readToken() async => session?.token;

  @override
  Future<void> saveOrganization(int organizationId) async {
    final current = session;
    if (current == null) return;
    session = StoredSession(
      token: current.token,
      organizationId: organizationId,
      keepSignedIn: current.keepSignedIn,
      biometricEnabled: current.biometricEnabled,
      expiresAt: current.expiresAt,
      email: current.email,
    );
  }

  @override
  Future<void> saveSession({
    required String token,
    required int organizationId,
    required bool keepSignedIn,
    required bool biometricEnabled,
    required DateTime expiresAt,
    required String email,
  }) async {
    session = StoredSession(
      token: token,
      organizationId: organizationId,
      keepSignedIn: keepSignedIn,
      biometricEnabled: biometricEnabled,
      expiresAt: expiresAt,
      email: email,
    );
  }

  @override
  Future<void> setBiometricEnabled(bool enabled) async {
    final current = session;
    if (current == null) return;
    session = StoredSession(
      token: current.token,
      organizationId: current.organizationId,
      keepSignedIn: current.keepSignedIn,
      biometricEnabled: enabled,
      expiresAt: current.expiresAt,
      email: current.email,
    );
  }
}

class _FakeApiClient implements ApiClient {
  _FakeApiClient({this.postResponse, this.getFailure});

  final JsonMap? postResponse;
  final ApiFailure? getFailure;
  int getCalls = 0;

  @override
  Future<Map<String, String>> authHeaders() async => {};

  @override
  Future<void> delete(String path) async {}

  @override
  Future<JsonMap> get(String path, {Map<String, dynamic>? query}) async {
    getCalls++;
    if (getFailure case final failure?) throw failure;
    return {
      'data': {
        'user': {
          'id': 12,
          'name': 'Demo Athlete',
          'email': 'athlete@example.test',
          'platform_role': 'athlete',
          'organization_role': 'athlete',
          'theme_preference': 'system',
        },
        'organizations': [
          {
            'id': 7,
            'name': 'Throughline',
            'role': 'athlete',
            'timezone': 'Asia/Riyadh',
          },
        ],
      },
    };
  }

  @override
  Future<JsonMap> patch(String path, {Object? data}) async => {};

  @override
  Future<JsonMap> post(String path, {Object? data}) async => postResponse ?? {};

  @override
  Future<JsonMap> put(String path, {Object? data}) async => {};
}
