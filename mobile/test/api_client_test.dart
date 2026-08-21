import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:throughline_mobile/src/core/network/api_client.dart';

void main() {
  test('validation failures show the useful field message', () {
    final request = RequestOptions(path: '/auth/login');
    final failure = ApiFailure.fromDio(
      DioException(
        requestOptions: request,
        response: Response<Object?>(
          requestOptions: request,
          statusCode: 422,
          data: {
            'error': {
              'code': 'validation_failed',
              'message': 'The submitted data is invalid.',
              'fields': {
                'email': ['These credentials do not match an active account.'],
              },
            },
          },
        ),
      ),
    );

    expect(
      failure.message,
      'These credentials do not match an active account.',
    );
    expect(failure.fields['email'], hasLength(1));
  });

  test('scalar server validation fields do not break error parsing', () {
    final request = RequestOptions(path: '/auth/login');
    final failure = ApiFailure.fromDio(
      DioException(
        requestOptions: request,
        response: Response<Object?>(
          requestOptions: request,
          statusCode: 422,
          data: {
            'error': {
              'fields': {'email': 'Enter a valid email.'},
            },
          },
        ),
      ),
    );

    expect(failure.message, 'Enter a valid email.');
  });
}
