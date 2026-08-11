import 'package:dio/dio.dart';
import 'package:throughline_mobile/src/core/config/app_config.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/storage/secure_session_store.dart';

class ApiClient {
  ApiClient(this._sessionStore)
    : _dio = Dio(
        BaseOptions(
          baseUrl: AppConfig.apiBaseUrl,
          connectTimeout: const Duration(seconds: 15),
          receiveTimeout: const Duration(seconds: 20),
          headers: const {'Accept': 'application/json'},
        ),
      ) {
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _sessionStore.readToken();
          final organizationId = await _sessionStore.readOrganizationId();
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          if (organizationId != null) {
            options.headers['X-Organization-ID'] = organizationId;
          }
          handler.next(options);
        },
      ),
    );
  }

  final SecureSessionStore _sessionStore;
  final Dio _dio;

  Future<JsonMap> get(String path, {Map<String, dynamic>? query}) =>
      _request(() => _dio.get<Object?>(path, queryParameters: query));

  Future<JsonMap> post(String path, {Object? data}) =>
      _request(() => _dio.post<Object?>(path, data: data));

  Future<JsonMap> put(String path, {Object? data}) =>
      _request(() => _dio.put<Object?>(path, data: data));

  Future<JsonMap> patch(String path, {Object? data}) =>
      _request(() => _dio.patch<Object?>(path, data: data));

  Future<void> delete(String path) async {
    try {
      await _dio.delete<Object?>(path);
    } on DioException catch (error) {
      throw ApiFailure.fromDio(error);
    }
  }

  Future<JsonMap> _request(Future<Response<Object?>> Function() request) async {
    try {
      final response = await request();
      return (response.data as Map).cast<String, dynamic>();
    } on DioException catch (error) {
      throw ApiFailure.fromDio(error);
    }
  }
}

class ApiFailure implements Exception {
  const ApiFailure(
    this.message, {
    this.code,
    this.statusCode,
    this.fields = const {},
  });

  factory ApiFailure.fromDio(DioException exception) {
    final response = exception.response;
    final payload = response?.data is Map
        ? (response?.data as Map).cast<String, dynamic>()
        : <String, dynamic>{};
    final error = payload['error'] is Map
        ? (payload['error'] as Map).cast<String, dynamic>()
        : <String, dynamic>{};
    final rawFields = error['fields'] is Map
        ? (error['fields'] as Map).cast<String, dynamic>()
        : <String, dynamic>{};

    return ApiFailure(
      error['message'] as String? ?? _fallbackMessage(exception),
      code: error['code'] as String?,
      statusCode: response?.statusCode,
      fields: rawFields.map(
        (key, value) => MapEntry(
          key,
          (value as List? ?? const []).map((item) => item.toString()).toList(),
        ),
      ),
    );
  }

  final String message;
  final String? code;
  final int? statusCode;
  final Map<String, List<String>> fields;

  bool get isUnauthenticated => statusCode == 401;
  bool get isConflict => statusCode == 409;

  static String _fallbackMessage(DioException exception) {
    if (exception.type == DioExceptionType.connectionError ||
        exception.type == DioExceptionType.connectionTimeout) {
      return 'Throughline could not reach the server. Check your connection and try again.';
    }
    return 'The request could not be completed.';
  }

  @override
  String toString() => message;
}
