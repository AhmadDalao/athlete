import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:throughline_mobile/src/core/network/api_client.dart';
import 'package:throughline_mobile/src/core/security/biometric_authenticator.dart';
import 'package:throughline_mobile/src/core/storage/app_database.dart';
import 'package:throughline_mobile/src/core/storage/secure_session_store.dart';

final secureSessionStoreProvider = Provider<SessionStore>(
  (ref) => const SecureSessionStore(FlutterSecureStorage()),
);

final biometricAuthenticatorProvider = Provider<BiometricAuthenticator>(
  (ref) => DeviceBiometricAuthenticator(),
);

final apiClientProvider = Provider<ApiClient>(
  (ref) => ApiClient(ref.watch(secureSessionStoreProvider)),
);

final appDatabaseProvider = Provider<AppDatabase>((ref) {
  final database = AppDatabase();
  ref.onDispose(database.close);
  return database;
});
