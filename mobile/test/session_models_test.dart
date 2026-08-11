import 'package:flutter_test/flutter_test.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';

void main() {
  group('AppUser', () {
    test('identifies an athlete organization role', () {
      final user = AppUser.fromJson({
        'id': 12,
        'name': 'Athlete Test',
        'email': 'athlete@example.com',
        'platform_role': 'user',
        'organization_role': 'athlete',
        'theme_preference': 'dark',
      });

      expect(user.isAthlete, isTrue);
      expect(user.isCoach, isFalse);
      expect(user.supportsMobile, isTrue);
      expect(user.theme, 'dark');
    });

    test('rejects platform-only roles from the mobile workspace', () {
      final user = AppUser.fromJson({
        'id': 1,
        'name': 'Platform Owner',
        'email': 'owner@example.com',
        'platform_role': 'owner',
      });

      expect(user.supportsMobile, isFalse);
    });
  });

  test('JSON helpers safely read nested API envelopes', () {
    final envelope = <String, dynamic>{
      'data': {
        'profile': {'name': 'Lina'},
        'items': [
          {'id': 1},
          {'id': 2},
        ],
      },
    };

    final data = envelope.object('data');
    expect(data.object('profile').text('name'), 'Lina');
    expect(data.maps('items').length, 2);
    expect(data.object('missing'), isEmpty);
  });
}
