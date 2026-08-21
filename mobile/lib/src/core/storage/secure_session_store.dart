import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class StoredSession {
  const StoredSession({
    required this.token,
    required this.organizationId,
    required this.keepSignedIn,
    required this.biometricEnabled,
    required this.expiresAt,
    this.email,
  });

  final String token;
  final int organizationId;
  final bool keepSignedIn;
  final bool biometricEnabled;
  final DateTime expiresAt;
  final String? email;

  bool get isExpired => !expiresAt.isAfter(DateTime.now().toUtc());
}

abstract class SessionStore {
  Future<String?> readToken();
  Future<String?> readOrganizationId();
  Future<StoredSession?> readSession();
  Future<void> saveSession({
    required String token,
    required int organizationId,
    required bool keepSignedIn,
    required bool biometricEnabled,
    required DateTime expiresAt,
    required String email,
  });
  Future<void> saveOrganization(int organizationId);
  Future<void> setBiometricEnabled(bool enabled);
  Future<void> clear();
}

class SecureSessionStore implements SessionStore {
  const SecureSessionStore(this._storage);

  static const tokenKey = 'throughline_access_token';
  static const organizationKey = 'throughline_organization_id';
  static const keepSignedInKey = 'throughline_keep_signed_in';
  static const biometricEnabledKey = 'throughline_biometric_enabled';
  static const expiresAtKey = 'throughline_session_expires_at';
  static const emailKey = 'throughline_session_email';

  final FlutterSecureStorage _storage;

  @override
  Future<String?> readToken() => _storage.read(key: tokenKey);
  @override
  Future<String?> readOrganizationId() => _storage.read(key: organizationKey);

  @override
  Future<StoredSession?> readSession() async {
    final values = await Future.wait([
      _storage.read(key: tokenKey),
      _storage.read(key: organizationKey),
      _storage.read(key: keepSignedInKey),
      _storage.read(key: biometricEnabledKey),
      _storage.read(key: expiresAtKey),
      _storage.read(key: emailKey),
    ]);
    final organizationId = int.tryParse(values[1] ?? '');
    final expiresAt = DateTime.tryParse(values[4] ?? '')?.toUtc();
    if (values[0] == null || organizationId == null || expiresAt == null) {
      return null;
    }

    return StoredSession(
      token: values[0]!,
      organizationId: organizationId,
      keepSignedIn: values[2] == 'true',
      biometricEnabled: values[3] == 'true',
      expiresAt: expiresAt,
      email: values[5],
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
    await Future.wait([
      _storage.write(key: tokenKey, value: token),
      _storage.write(key: organizationKey, value: organizationId.toString()),
      _storage.write(key: keepSignedInKey, value: keepSignedIn.toString()),
      _storage.write(
        key: biometricEnabledKey,
        value: biometricEnabled.toString(),
      ),
      _storage.write(
        key: expiresAtKey,
        value: expiresAt.toUtc().toIso8601String(),
      ),
      _storage.write(key: emailKey, value: email),
    ]);
  }

  @override
  Future<void> saveOrganization(int organizationId) =>
      _storage.write(key: organizationKey, value: organizationId.toString());

  @override
  Future<void> setBiometricEnabled(bool enabled) =>
      _storage.write(key: biometricEnabledKey, value: enabled.toString());

  @override
  Future<void> clear() => Future.wait([
    _storage.delete(key: tokenKey),
    _storage.delete(key: organizationKey),
    _storage.delete(key: keepSignedInKey),
    _storage.delete(key: biometricEnabledKey),
    _storage.delete(key: expiresAtKey),
    _storage.delete(key: emailKey),
  ]);
}
