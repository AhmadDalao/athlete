import 'dart:io';

import 'package:drift/drift.dart';
import 'package:drift/native.dart';
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';

part 'app_database.g.dart';

class WorkoutDrafts extends Table {
  TextColumn get scopeKey => text()();
  IntColumn get workoutId => integer()();
  TextColumn get payload => text()();
  DateTimeColumn get serverUpdatedAt => dateTime().nullable()();
  DateTimeColumn get updatedAt => dateTime()();

  @override
  Set<Column<Object>> get primaryKey => {scopeKey};
}

@DriftDatabase(tables: [WorkoutDrafts])
class AppDatabase extends _$AppDatabase {
  AppDatabase() : super(_openConnection());
  AppDatabase.forTesting(super.executor);

  @override
  int get schemaVersion => 2;

  @override
  MigrationStrategy get migration => MigrationStrategy(
    onCreate: (migrator) => migrator.createAll(),
    onUpgrade: (migrator, from, to) async {
      if (from < 2) {
        await migrator.deleteTable(workoutDrafts.actualTableName);
        await migrator.createTable(workoutDrafts);
      }
    },
  );

  Future<void> saveWorkoutDraft({
    required String scopeKey,
    required int workoutId,
    required String payload,
    DateTime? serverUpdatedAt,
  }) => into(workoutDrafts).insertOnConflictUpdate(
    WorkoutDraftsCompanion.insert(
      scopeKey: scopeKey,
      workoutId: workoutId,
      payload: payload,
      serverUpdatedAt: Value(serverUpdatedAt),
      updatedAt: DateTime.now().toUtc(),
    ),
  );

  Future<void> removeWorkoutDraft(String scopeKey) => (delete(
    workoutDrafts,
  )..where((row) => row.scopeKey.equals(scopeKey))).go();

  Future<WorkoutDraft?> workoutDraft(String scopeKey) => (select(
    workoutDrafts,
  )..where((row) => row.scopeKey.equals(scopeKey))).getSingleOrNull();
}

LazyDatabase _openConnection() => LazyDatabase(() async {
  final directory = await getApplicationDocumentsDirectory();
  return NativeDatabase.createInBackground(
    File(p.join(directory.path, 'throughline.sqlite')),
  );
});
