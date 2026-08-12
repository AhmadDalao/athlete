import 'package:drift/native.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:throughline_mobile/src/core/storage/app_database.dart';

void main() {
  test('workout drafts are isolated by user and organization scope', () async {
    final database = AppDatabase.forTesting(NativeDatabase.memory());
    addTearDown(database.close);

    await database.saveWorkoutDraft(
      scopeKey: '10:20:30',
      workoutId: 30,
      payload: '{"notes":"first"}',
    );
    await database.saveWorkoutDraft(
      scopeKey: '11:21:30',
      workoutId: 30,
      payload: '{"notes":"second"}',
    );

    expect(
      (await database.workoutDraft('10:20:30'))?.payload,
      '{"notes":"first"}',
    );
    expect(
      (await database.workoutDraft('11:21:30'))?.payload,
      '{"notes":"second"}',
    );

    await database.removeWorkoutDraft('10:20:30');

    expect(await database.workoutDraft('10:20:30'), isNull);
    expect(await database.workoutDraft('11:21:30'), isNotNull);
  });
}
