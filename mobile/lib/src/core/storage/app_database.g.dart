// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'app_database.dart';

// ignore_for_file: type=lint
class $WorkoutDraftsTable extends WorkoutDrafts
    with TableInfo<$WorkoutDraftsTable, WorkoutDraft> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $WorkoutDraftsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _workoutIdMeta = const VerificationMeta(
    'workoutId',
  );
  @override
  late final GeneratedColumn<int> workoutId = GeneratedColumn<int>(
    'workout_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: false,
  );
  static const VerificationMeta _payloadMeta = const VerificationMeta(
    'payload',
  );
  @override
  late final GeneratedColumn<String> payload = GeneratedColumn<String>(
    'payload',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _serverUpdatedAtMeta = const VerificationMeta(
    'serverUpdatedAt',
  );
  @override
  late final GeneratedColumn<DateTime> serverUpdatedAt =
      GeneratedColumn<DateTime>(
        'server_updated_at',
        aliasedName,
        true,
        type: DriftSqlType.dateTime,
        requiredDuringInsert: false,
      );
  static const VerificationMeta _updatedAtMeta = const VerificationMeta(
    'updatedAt',
  );
  @override
  late final GeneratedColumn<DateTime> updatedAt = GeneratedColumn<DateTime>(
    'updated_at',
    aliasedName,
    false,
    type: DriftSqlType.dateTime,
    requiredDuringInsert: true,
  );
  @override
  List<GeneratedColumn> get $columns => [
    workoutId,
    payload,
    serverUpdatedAt,
    updatedAt,
  ];
  @override
  String get aliasedName => _alias ?? actualTableName;
  @override
  String get actualTableName => $name;
  static const String $name = 'workout_drafts';
  @override
  VerificationContext validateIntegrity(
    Insertable<WorkoutDraft> instance, {
    bool isInserting = false,
  }) {
    final context = VerificationContext();
    final data = instance.toColumns(true);
    if (data.containsKey('workout_id')) {
      context.handle(
        _workoutIdMeta,
        workoutId.isAcceptableOrUnknown(data['workout_id']!, _workoutIdMeta),
      );
    }
    if (data.containsKey('payload')) {
      context.handle(
        _payloadMeta,
        payload.isAcceptableOrUnknown(data['payload']!, _payloadMeta),
      );
    } else if (isInserting) {
      context.missing(_payloadMeta);
    }
    if (data.containsKey('server_updated_at')) {
      context.handle(
        _serverUpdatedAtMeta,
        serverUpdatedAt.isAcceptableOrUnknown(
          data['server_updated_at']!,
          _serverUpdatedAtMeta,
        ),
      );
    }
    if (data.containsKey('updated_at')) {
      context.handle(
        _updatedAtMeta,
        updatedAt.isAcceptableOrUnknown(data['updated_at']!, _updatedAtMeta),
      );
    } else if (isInserting) {
      context.missing(_updatedAtMeta);
    }
    return context;
  }

  @override
  Set<GeneratedColumn> get $primaryKey => {workoutId};
  @override
  WorkoutDraft map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return WorkoutDraft(
      workoutId: attachedDatabase.typeMapping.read(
        DriftSqlType.int,
        data['${effectivePrefix}workout_id'],
      )!,
      payload: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}payload'],
      )!,
      serverUpdatedAt: attachedDatabase.typeMapping.read(
        DriftSqlType.dateTime,
        data['${effectivePrefix}server_updated_at'],
      ),
      updatedAt: attachedDatabase.typeMapping.read(
        DriftSqlType.dateTime,
        data['${effectivePrefix}updated_at'],
      )!,
    );
  }

  @override
  $WorkoutDraftsTable createAlias(String alias) {
    return $WorkoutDraftsTable(attachedDatabase, alias);
  }
}

class WorkoutDraft extends DataClass implements Insertable<WorkoutDraft> {
  final int workoutId;
  final String payload;
  final DateTime? serverUpdatedAt;
  final DateTime updatedAt;
  const WorkoutDraft({
    required this.workoutId,
    required this.payload,
    this.serverUpdatedAt,
    required this.updatedAt,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['workout_id'] = Variable<int>(workoutId);
    map['payload'] = Variable<String>(payload);
    if (!nullToAbsent || serverUpdatedAt != null) {
      map['server_updated_at'] = Variable<DateTime>(serverUpdatedAt);
    }
    map['updated_at'] = Variable<DateTime>(updatedAt);
    return map;
  }

  WorkoutDraftsCompanion toCompanion(bool nullToAbsent) {
    return WorkoutDraftsCompanion(
      workoutId: Value(workoutId),
      payload: Value(payload),
      serverUpdatedAt: serverUpdatedAt == null && nullToAbsent
          ? const Value.absent()
          : Value(serverUpdatedAt),
      updatedAt: Value(updatedAt),
    );
  }

  factory WorkoutDraft.fromJson(
    Map<String, dynamic> json, {
    ValueSerializer? serializer,
  }) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return WorkoutDraft(
      workoutId: serializer.fromJson<int>(json['workoutId']),
      payload: serializer.fromJson<String>(json['payload']),
      serverUpdatedAt: serializer.fromJson<DateTime?>(json['serverUpdatedAt']),
      updatedAt: serializer.fromJson<DateTime>(json['updatedAt']),
    );
  }
  @override
  Map<String, dynamic> toJson({ValueSerializer? serializer}) {
    serializer ??= driftRuntimeOptions.defaultSerializer;
    return <String, dynamic>{
      'workoutId': serializer.toJson<int>(workoutId),
      'payload': serializer.toJson<String>(payload),
      'serverUpdatedAt': serializer.toJson<DateTime?>(serverUpdatedAt),
      'updatedAt': serializer.toJson<DateTime>(updatedAt),
    };
  }

  WorkoutDraft copyWith({
    int? workoutId,
    String? payload,
    Value<DateTime?> serverUpdatedAt = const Value.absent(),
    DateTime? updatedAt,
  }) => WorkoutDraft(
    workoutId: workoutId ?? this.workoutId,
    payload: payload ?? this.payload,
    serverUpdatedAt: serverUpdatedAt.present
        ? serverUpdatedAt.value
        : this.serverUpdatedAt,
    updatedAt: updatedAt ?? this.updatedAt,
  );
  WorkoutDraft copyWithCompanion(WorkoutDraftsCompanion data) {
    return WorkoutDraft(
      workoutId: data.workoutId.present ? data.workoutId.value : this.workoutId,
      payload: data.payload.present ? data.payload.value : this.payload,
      serverUpdatedAt: data.serverUpdatedAt.present
          ? data.serverUpdatedAt.value
          : this.serverUpdatedAt,
      updatedAt: data.updatedAt.present ? data.updatedAt.value : this.updatedAt,
    );
  }

  @override
  String toString() {
    return (StringBuffer('WorkoutDraft(')
          ..write('workoutId: $workoutId, ')
          ..write('payload: $payload, ')
          ..write('serverUpdatedAt: $serverUpdatedAt, ')
          ..write('updatedAt: $updatedAt')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode =>
      Object.hash(workoutId, payload, serverUpdatedAt, updatedAt);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is WorkoutDraft &&
          other.workoutId == this.workoutId &&
          other.payload == this.payload &&
          other.serverUpdatedAt == this.serverUpdatedAt &&
          other.updatedAt == this.updatedAt);
}

class WorkoutDraftsCompanion extends UpdateCompanion<WorkoutDraft> {
  final Value<int> workoutId;
  final Value<String> payload;
  final Value<DateTime?> serverUpdatedAt;
  final Value<DateTime> updatedAt;
  const WorkoutDraftsCompanion({
    this.workoutId = const Value.absent(),
    this.payload = const Value.absent(),
    this.serverUpdatedAt = const Value.absent(),
    this.updatedAt = const Value.absent(),
  });
  WorkoutDraftsCompanion.insert({
    this.workoutId = const Value.absent(),
    required String payload,
    this.serverUpdatedAt = const Value.absent(),
    required DateTime updatedAt,
  }) : payload = Value(payload),
       updatedAt = Value(updatedAt);
  static Insertable<WorkoutDraft> custom({
    Expression<int>? workoutId,
    Expression<String>? payload,
    Expression<DateTime>? serverUpdatedAt,
    Expression<DateTime>? updatedAt,
  }) {
    return RawValuesInsertable({
      if (workoutId != null) 'workout_id': workoutId,
      if (payload != null) 'payload': payload,
      if (serverUpdatedAt != null) 'server_updated_at': serverUpdatedAt,
      if (updatedAt != null) 'updated_at': updatedAt,
    });
  }

  WorkoutDraftsCompanion copyWith({
    Value<int>? workoutId,
    Value<String>? payload,
    Value<DateTime?>? serverUpdatedAt,
    Value<DateTime>? updatedAt,
  }) {
    return WorkoutDraftsCompanion(
      workoutId: workoutId ?? this.workoutId,
      payload: payload ?? this.payload,
      serverUpdatedAt: serverUpdatedAt ?? this.serverUpdatedAt,
      updatedAt: updatedAt ?? this.updatedAt,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (workoutId.present) {
      map['workout_id'] = Variable<int>(workoutId.value);
    }
    if (payload.present) {
      map['payload'] = Variable<String>(payload.value);
    }
    if (serverUpdatedAt.present) {
      map['server_updated_at'] = Variable<DateTime>(serverUpdatedAt.value);
    }
    if (updatedAt.present) {
      map['updated_at'] = Variable<DateTime>(updatedAt.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('WorkoutDraftsCompanion(')
          ..write('workoutId: $workoutId, ')
          ..write('payload: $payload, ')
          ..write('serverUpdatedAt: $serverUpdatedAt, ')
          ..write('updatedAt: $updatedAt')
          ..write(')'))
        .toString();
  }
}

abstract class _$AppDatabase extends GeneratedDatabase {
  _$AppDatabase(QueryExecutor e) : super(e);
  $AppDatabaseManager get managers => $AppDatabaseManager(this);
  late final $WorkoutDraftsTable workoutDrafts = $WorkoutDraftsTable(this);
  @override
  Iterable<TableInfo<Table, Object?>> get allTables =>
      allSchemaEntities.whereType<TableInfo<Table, Object?>>();
  @override
  List<DatabaseSchemaEntity> get allSchemaEntities => [workoutDrafts];
}

typedef $$WorkoutDraftsTableCreateCompanionBuilder =
    WorkoutDraftsCompanion Function({
      Value<int> workoutId,
      required String payload,
      Value<DateTime?> serverUpdatedAt,
      required DateTime updatedAt,
    });
typedef $$WorkoutDraftsTableUpdateCompanionBuilder =
    WorkoutDraftsCompanion Function({
      Value<int> workoutId,
      Value<String> payload,
      Value<DateTime?> serverUpdatedAt,
      Value<DateTime> updatedAt,
    });

class $$WorkoutDraftsTableFilterComposer
    extends Composer<_$AppDatabase, $WorkoutDraftsTable> {
  $$WorkoutDraftsTableFilterComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnFilters<int> get workoutId => $composableBuilder(
    column: $table.workoutId,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<String> get payload => $composableBuilder(
    column: $table.payload,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<DateTime> get serverUpdatedAt => $composableBuilder(
    column: $table.serverUpdatedAt,
    builder: (column) => ColumnFilters(column),
  );

  ColumnFilters<DateTime> get updatedAt => $composableBuilder(
    column: $table.updatedAt,
    builder: (column) => ColumnFilters(column),
  );
}

class $$WorkoutDraftsTableOrderingComposer
    extends Composer<_$AppDatabase, $WorkoutDraftsTable> {
  $$WorkoutDraftsTableOrderingComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  ColumnOrderings<int> get workoutId => $composableBuilder(
    column: $table.workoutId,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<String> get payload => $composableBuilder(
    column: $table.payload,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<DateTime> get serverUpdatedAt => $composableBuilder(
    column: $table.serverUpdatedAt,
    builder: (column) => ColumnOrderings(column),
  );

  ColumnOrderings<DateTime> get updatedAt => $composableBuilder(
    column: $table.updatedAt,
    builder: (column) => ColumnOrderings(column),
  );
}

class $$WorkoutDraftsTableAnnotationComposer
    extends Composer<_$AppDatabase, $WorkoutDraftsTable> {
  $$WorkoutDraftsTableAnnotationComposer({
    required super.$db,
    required super.$table,
    super.joinBuilder,
    super.$addJoinBuilderToRootComposer,
    super.$removeJoinBuilderFromRootComposer,
  });
  GeneratedColumn<int> get workoutId =>
      $composableBuilder(column: $table.workoutId, builder: (column) => column);

  GeneratedColumn<String> get payload =>
      $composableBuilder(column: $table.payload, builder: (column) => column);

  GeneratedColumn<DateTime> get serverUpdatedAt => $composableBuilder(
    column: $table.serverUpdatedAt,
    builder: (column) => column,
  );

  GeneratedColumn<DateTime> get updatedAt =>
      $composableBuilder(column: $table.updatedAt, builder: (column) => column);
}

class $$WorkoutDraftsTableTableManager
    extends
        RootTableManager<
          _$AppDatabase,
          $WorkoutDraftsTable,
          WorkoutDraft,
          $$WorkoutDraftsTableFilterComposer,
          $$WorkoutDraftsTableOrderingComposer,
          $$WorkoutDraftsTableAnnotationComposer,
          $$WorkoutDraftsTableCreateCompanionBuilder,
          $$WorkoutDraftsTableUpdateCompanionBuilder,
          (
            WorkoutDraft,
            BaseReferences<_$AppDatabase, $WorkoutDraftsTable, WorkoutDraft>,
          ),
          WorkoutDraft,
          PrefetchHooks Function()
        > {
  $$WorkoutDraftsTableTableManager(_$AppDatabase db, $WorkoutDraftsTable table)
    : super(
        TableManagerState(
          db: db,
          table: table,
          createFilteringComposer: () =>
              $$WorkoutDraftsTableFilterComposer($db: db, $table: table),
          createOrderingComposer: () =>
              $$WorkoutDraftsTableOrderingComposer($db: db, $table: table),
          createComputedFieldComposer: () =>
              $$WorkoutDraftsTableAnnotationComposer($db: db, $table: table),
          updateCompanionCallback:
              ({
                Value<int> workoutId = const Value.absent(),
                Value<String> payload = const Value.absent(),
                Value<DateTime?> serverUpdatedAt = const Value.absent(),
                Value<DateTime> updatedAt = const Value.absent(),
              }) => WorkoutDraftsCompanion(
                workoutId: workoutId,
                payload: payload,
                serverUpdatedAt: serverUpdatedAt,
                updatedAt: updatedAt,
              ),
          createCompanionCallback:
              ({
                Value<int> workoutId = const Value.absent(),
                required String payload,
                Value<DateTime?> serverUpdatedAt = const Value.absent(),
                required DateTime updatedAt,
              }) => WorkoutDraftsCompanion.insert(
                workoutId: workoutId,
                payload: payload,
                serverUpdatedAt: serverUpdatedAt,
                updatedAt: updatedAt,
              ),
          withReferenceMapper: (p0) => p0
              .map((e) => (e.readTable(table), BaseReferences(db, table, e)))
              .toList(),
          prefetchHooksCallback: null,
        ),
      );
}

typedef $$WorkoutDraftsTableProcessedTableManager =
    ProcessedTableManager<
      _$AppDatabase,
      $WorkoutDraftsTable,
      WorkoutDraft,
      $$WorkoutDraftsTableFilterComposer,
      $$WorkoutDraftsTableOrderingComposer,
      $$WorkoutDraftsTableAnnotationComposer,
      $$WorkoutDraftsTableCreateCompanionBuilder,
      $$WorkoutDraftsTableUpdateCompanionBuilder,
      (
        WorkoutDraft,
        BaseReferences<_$AppDatabase, $WorkoutDraftsTable, WorkoutDraft>,
      ),
      WorkoutDraft,
      PrefetchHooks Function()
    >;

class $AppDatabaseManager {
  final _$AppDatabase _db;
  $AppDatabaseManager(this._db);
  $$WorkoutDraftsTableTableManager get workoutDrafts =>
      $$WorkoutDraftsTableTableManager(_db, _db.workoutDrafts);
}
