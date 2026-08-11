class AppConfig {
  const AppConfig._();

  static const apiBaseUrl = String.fromEnvironment(
    'THROUGHLINE_API_BASE',
    defaultValue: 'https://athlete.ahmaddalao.com/api/v1',
  );
}
