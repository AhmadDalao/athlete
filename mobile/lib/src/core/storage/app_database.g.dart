// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'app_database.dart';

// ignore_for_file: type=lint
class $WorkoutDraftsTable extends WorkoutDrafts
    with TableInfo<$WorkoutDraftsTable, WorkoutDraft> {
  @override
  final GeneratedDatabase attachedDatabase;
  final String? _alias;
  $WorkoutDraftsTable(this.attachedDatabase, [this._alias]);
  static const VerificationMeta _scopeKeyMeta = const VerificationMeta(
    'scopeKey',
  );
  @override
  late final GeneratedColumn<String> scopeKey = GeneratedColumn<String>(
    'scope_key',
    aliasedName,
    false,
    type: DriftSqlType.string,
    requiredDuringInsert: true,
  );
  static const VerificationMeta _workoutIdMeta = const VerificationMeta(
    'workoutId',
  );
  @override
  late final GeneratedColumn<int> workoutId = GeneratedColumn<int>(
    'workout_id',
    aliasedName,
    false,
    type: DriftSqlType.int,
    requiredDuringInsert: true,
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
    scopeKey,
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
    if (data.containsKey('scope_key')) {
      context.handle(
        _scopeKeyMeta,
        scopeKey.isAcceptableOrUnknown(data['scope_key']!, _scopeKeyMeta),
      );
    } else if (isInserting) {
      context.missing(_scopeKeyMeta);
    }
    if (data.containsKey('workout_id')) {
      context.handle(
        _workoutIdMeta,
        workoutId.isAcceptableOrUnknown(data['workout_id']!, _workoutIdMeta),
      );
    } else if (isInserting) {
      context.missing(_workoutIdMeta);
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
  Set<GeneratedColumn> get $primaryKey => {scopeKey};
  @override
  WorkoutDraft map(Map<String, dynamic> data, {String? tablePrefix}) {
    final effectivePrefix = tablePrefix != null ? '$tablePrefix.' : '';
    return WorkoutDraft(
      scopeKey: attachedDatabase.typeMapping.read(
        DriftSqlType.string,
        data['${effectivePrefix}scope_key'],
      )!,
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
  final String scopeKey;
  final int workoutId;
  final String payload;
  final DateTime? serverUpdatedAt;
  final DateTime updatedAt;
  const WorkoutDraft({
    required this.scopeKey,
    required this.workoutId,
    required this.payload,
    this.serverUpdatedAt,
    required this.updatedAt,
  });
  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    map['scope_key'] = Variable<String>(scopeKey);
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
      scopeKey: Value(scopeKey),
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
      scopeKey: serializer.fromJson<String>(json['scopeKey']),
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
      'scopeKey': serializer.toJson<String>(scopeKey),
      'workoutId': serializer.toJson<int>(workoutId),
      'payload': serializer.toJson<String>(payload),
      'serverUpdatedAt': serializer.toJson<DateTime?>(serverUpdatedAt),
      'updatedAt': serializer.toJson<DateTime>(updatedAt),
    };
  }

  WorkoutDraft copyWith({
    String? scopeKey,
    int? workoutId,
    String? payload,
    Value<DateTime?> serverUpdatedAt = const Value.absent(),
    DateTime? updatedAt,
  }) => WorkoutDraft(
    scopeKey: scopeKey ?? this.scopeKey,
    workoutId: workoutId ?? this.workoutId,
    payload: payload ?? this.payload,
    serverUpdatedAt: serverUpdatedAt.present
        ? serverUpdatedAt.value
        : this.serverUpdatedAt,
    updatedAt: updatedAt ?? this.updatedAt,
  );
  WorkoutDraft copyWithCompanion(WorkoutDraftsCompanion data) {
    return WorkoutDraft(
      scopeKey: data.scopeKey.present ? data.scopeKey.value : this.scopeKey,
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
          ..write('scopeKey: $scopeKey, ')
          ..write('workoutId: $workoutId, ')
          ..write('payload: $payload, ')
          ..write('serverUpdatedAt: $serverUpdatedAt, ')
          ..write('updatedAt: $updatedAt')
          ..write(')'))
        .toString();
  }

  @override
  int get hashCode =>
      Object.hash(scopeKey, workoutId, payload, serverUpdatedAt, updatedAt);
  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      (other is WorkoutDraft &&
          other.scopeKey == this.scopeKey &&
          other.workoutId == this.workoutId &&
          other.payload == this.payload &&
          other.serverUpdatedAt == this.serverUpdatedAt &&
          other.updatedAt == this.updatedAt);
}

class WorkoutDraftsCompanion extends UpdateCompanion<WorkoutDraft> {
  final Value<String> scopeKey;
  final Value<int> workoutId;
  final Value<String> payload;
  final Value<DateTime?> serverUpdatedAt;
  final Value<DateTime> updatedAt;
  final Value<int> rowid;
  const WorkoutDraftsCompanion({
    this.scopeKey = const Value.absent(),
    this.workoutId = const Value.absent(),
    this.payload = const Value.absent(),
    this.serverUpdatedAt = const Value.absent(),
    this.updatedAt = const Value.absent(),
    this.rowid = const Value.absent(),
  });
  WorkoutDraftsCompanion.insert({
    required String scopeKey,
    required int workoutId,
    required String payload,
    this.serverUpdatedAt = const Value.absent(),
    required DateTime updatedAt,
    this.rowid = const Value.absent(),
  }) : scopeKey = Value(scopeKey),
       workoutId = Value(workoutId),
       payload = Value(payload),
       updatedAt = Value(updatedAt);
  static Insertable<WorkoutDraft> custom({
    Expression<String>? scopeKey,
    Expression<int>? workoutId,
    Expression<String>? payload,
    Expression<DateTime>? serverUpdatedAt,
    Expression<DateTime>? updatedAt,
    Expression<int>? rowid,
  }) {
    return RawValuesInsertable({
      if (scopeKey != null) 'scope_key': scopeKey,
      if (workoutId != null) 'workout_id': workoutId,
      if (payload != null) 'payload': payload,
      if (serverUpdatedAt != null) 'server_updated_at': serverUpdatedAt,
      if (updatedAt != null) 'updated_at': updatedAt,
      if (rowid != null) 'rowid': rowid,
    });
  }

  WorkoutDraftsCompanion copyWith({
    Value<String>? scopeKey,
    Value<int>? workoutId,
    Value<String>? payload,
    Value<DateTime?>? serverUpdatedAt,
    Value<DateTime>? updatedAt,
    Value<int>? rowid,
  }) {
    return WorkoutDraftsCompanion(
      scopeKey: scopeKey ?? this.scopeKey,
      workoutId: workoutId ?? this.workoutId,
      payload: payload ?? this.payload,
      serverUpdatedAt: serverUpdatedAt ?? this.serverUpdatedAt,
      updatedAt: updatedAt ?? this.updatedAt,
      rowid: rowid ?? this.rowid,
    );
  }

  @override
  Map<String, Expression> toColumns(bool nullToAbsent) {
    final map = <String, Expression>{};
    if (scopeKey.present) {
      map['scope_key'] = Variable<String>(scopeKey.value);
    }
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
    if (rowid.present) {
      map['rowid'] = Variable<int>(rowid.value);
    }
    return map;
  }

  @override
  String toString() {
    return (StringBuffer('WorkoutDraftsCompanion(')
          ..write('scopeKey: $scopeKey, ')
          ..write('workoutId: $workoutId, ')
          ..write('payload: $payload, ')
          ..write('serverUpdatedAt: $serverUpdatedAt, ')
          ..write('updatedAt: $updatedAt, ')
          ..write('rowid: $rowid')
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
      required String scopeKey,
      required int workoutId,
      required String payload,
      Value<DateTime?> serverUpdatedAt,
      required DateTime updatedAt,
      Value<int> rowid,
    });
typedef $$WorkoutDraftsTableUpdateCompanionBuilder =
    WorkoutDraftsCompanion Function({
      Value<String> scopeKey,
      Value<int> workoutId,
      Value<String> payload,
      Value<DateTime?> serverUpdatedAt,
      Value<DateTime> updatedAt,
      Value<int> rowid,
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
  ColumnFilters<String> get scopeKey => $composableBuilder(
    column: $table.scopeKey,
    builder: (column) => ColumnFilters(column),
  );

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
  ColumnOrderings<String> get scopeKey => $composableBuilder(
    column: $table.scopeKey,
    builder: (column) => ColumnOrderings(column),
  );

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
  GeneratedColumn<String> get scopeKey =>
      $composableBuilder(column: $table.scopeKey, builder: (column) => column);

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
                Value<String> scopeKey = const Value.absent(),
                Value<int> workoutId = const Value.absent(),
                Value<String> payload = const Value.absent(),
                Value<DateTime?> serverUpdatedAt = const Value.absent(),
                Value<DateTime> updatedAt = const Value.absent(),
                Value<int> rowid = const Value.absent(),
              }) => WorkoutDraftsCompanion(
                scopeKey: scopeKey,
                workoutId: workoutId,
                payload: payload,
                serverUpdatedAt: serverUpdatedAt,
                updatedAt: updatedAt,
                rowid: rowid,
              ),
          createCompanionCallback:
              ({
                required String scopeKey,
                required int workoutId,
                required String payload,
                Value<DateTime?> serverUpdatedAt = const Value.absent(),
                required DateTime updatedAt,
                Value<int> rowid = const Value.absent(),
              }) => WorkoutDraftsCompanion.insert(
                scopeKey: scopeKey,
                workoutId: workoutId,
                payload: payload,
                serverUpdatedAt: serverUpdatedAt,
                updatedAt: updatedAt,
                rowid: rowid,
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
