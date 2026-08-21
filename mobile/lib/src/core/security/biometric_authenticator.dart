import 'package:local_auth/local_auth.dart';

abstract class BiometricAuthenticator {
  Future<bool> isAvailable();
  Future<bool> authenticate();
}

class DeviceBiometricAuthenticator implements BiometricAuthenticator {
  DeviceBiometricAuthenticator([LocalAuthentication? localAuthentication])
    : _localAuthentication = localAuthentication ?? LocalAuthentication();

  final LocalAuthentication _localAuthentication;

  @override
  Future<bool> isAvailable() async {
    try {
      final biometrics = await _localAuthentication.getAvailableBiometrics();
      return biometrics.isNotEmpty;
    } on LocalAuthException {
      return false;
    }
  }

  @override
  Future<bool> authenticate() async {
    try {
      return await _localAuthentication.authenticate(
        localizedReason: 'Unlock your Throughline account',
        biometricOnly: true,
        persistAcrossBackgrounding: true,
      );
    } on LocalAuthException {
      return false;
    }
  }
}
