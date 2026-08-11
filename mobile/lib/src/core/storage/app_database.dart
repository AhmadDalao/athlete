import 'dart:io';

import 'package:drift/drift.dart';
import 'package:drift/native.dart';
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';

part 'app_database.g.dart';

class WorkoutDrafts extends Table {
  IntColumn get workoutId => integer()();
  TextColumn get payload => text()();
  DateTimeColumn get serverUpdatedAt => dateTime().nullable()();
  DateTimeColumn get updatedAt => dateTime()();

  @override
  Set<Column<Object>> get primaryKey => {workoutId};
}

@DriftDatabase(tables: [WorkoutDrafts])
class AppDatabase extends _$AppDatabase {
  AppDatabase() : super(_openConnection());

  @override
  int get schemaVersion => 1;

  Future<void> saveWorkoutDraft({
    required int workoutId,
    required String payload,
    DateTime? serverUpdatedAt,
  }) => into(workoutDrafts).insertOnConflictUpdate(
    WorkoutDraftsCompanion.insert(
      workoutId: Value(workoutId),
      payload: payload,
      serverUpdatedAt: Value(serverUpdatedAt),
      updatedAt: DateTime.now().toUtc(),
    ),
  );

  Future<void> removeWorkoutDraft(int workoutId) => (delete(
    workoutDrafts,
  )..where((row) => row.workoutId.equals(workoutId))).go();

  Future<WorkoutDraft?> workoutDraft(int workoutId) => (select(
    workoutDrafts,
  )..where((row) => row.workoutId.equals(workoutId))).getSingleOrNull();
}

LazyDatabase _openConnection() => LazyDatabase(() async {
  final directory = await getApplicationDocumentsDirectory();
  return NativeDatabase.createInBackground(
    File(p.join(directory.path, 'throughline.sqlite')),
  );
});
