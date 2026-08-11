import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class SecureSessionStore {
  const SecureSessionStore(this._storage);

  static const tokenKey = 'throughline_access_token';
  static const organizationKey = 'throughline_organization_id';

  final FlutterSecureStorage _storage;

  Future<String?> readToken() => _storage.read(key: tokenKey);
  Future<String?> readOrganizationId() => _storage.read(key: organizationKey);

  Future<void> saveSession({
    required String token,
    required int organizationId,
  }) async {
    await Future.wait([
      _storage.write(key: tokenKey, value: token),
      _storage.write(key: organizationKey, value: organizationId.toString()),
    ]);
  }

  Future<void> saveOrganization(int organizationId) =>
      _storage.write(key: organizationKey, value: organizationId.toString());

  Future<void> clear() => _storage.deleteAll();
}
