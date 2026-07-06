import { Platform } from 'react-native';
import type { Permission, RecordType } from 'react-native-health-connect';

import { apiRequest } from '@/api/client';

export type MobileHealthProvider = 'apple_health' | 'health_connect';

export type NormalizedHealthRecord = {
  metric_date: string;
  external_event_id?: string;
  metrics: {
    steps?: number;
    calories_burned?: number;
    sleep_minutes?: number;
    active_minutes?: number;
    resting_heart_rate?: number;
    heart_rate_variability?: number;
    training_load?: number;
  };
  raw_payload?: Record<string, unknown>;
};

export type HealthConnectReadDiagnostic = {
  metricDate: string;
  recordCounts: Record<string, number>;
  errors: Array<{ recordType: string; message: string }>;
};

export type NativeHealthCollectionResult = {
  records: NormalizedHealthRecord[];
  diagnostics: HealthConnectReadDiagnostic[];
  readableRecordCount: number;
  readErrors: Array<{ metricDate: string; recordType: string; message: string }>;
  permissionStatus: HealthConnectPermissionStatus;
  message: string;
};

export type MobileHealthSyncResponse = {
  connection: {
    id: number;
    publicId: string;
    provider: MobileHealthProvider;
    providerLabel: string;
    status: string;
    authType?: string | null;
    lastSyncedAt?: string | null;
  };
  receivedRecordCount: number;
  acceptedCount: number;
  acceptedMetricDates: string[];
  latestSnapshot?: Record<string, unknown> | null;
  recordCounts: Array<Record<string, number>>;
  syncMessage: string;
  snapshots: Array<Record<string, unknown> | null>;
};

type HealthRecord = Record<string, any>;
type HealthRecordReadResult = {
  records: HealthRecord[];
  error?: string;
};
type HealthConnectModule = typeof import('react-native-health-connect');
type HealthPermission = Permission | { accessType?: string; recordType?: string };

export type HealthConnectPermissionStatus = {
  available: boolean;
  platform: 'android' | 'ios' | 'unsupported';
  grantedScopes: string[];
  missingScopes: string[];
  permissionStatus: 'granted' | 'partial' | 'denied';
  canSync: boolean;
  message: string;
};

const HEALTH_CONNECT_DAYS = 14;
const integerMetricKeys = new Set<keyof NormalizedHealthRecord['metrics']>([
  'steps',
  'calories_burned',
  'sleep_minutes',
  'active_minutes',
  'resting_heart_rate',
  'training_load',
]);

const healthConnectPermissions: Permission[] = [
  { accessType: 'read', recordType: 'Steps' },
  { accessType: 'read', recordType: 'ActiveCaloriesBurned' },
  { accessType: 'read', recordType: 'TotalCaloriesBurned' },
  { accessType: 'read', recordType: 'SleepSession' },
  { accessType: 'read', recordType: 'HeartRate' },
  { accessType: 'read', recordType: 'RestingHeartRate' },
  { accessType: 'read', recordType: 'HeartRateVariabilityRmssd' },
  { accessType: 'read', recordType: 'ExerciseSession' },
];

export async function linkMobileHealthProvider({
  token,
  provider,
  deviceName,
  scopes,
  permissionStatus,
}: {
  token: string;
  provider: MobileHealthProvider;
  deviceName?: string;
  scopes?: string[];
  permissionStatus?: 'granted' | 'partial' | 'denied';
}) {
  return apiRequest('/api/v1/wearables/mobile-link', {
    method: 'POST',
    body: JSON.stringify({
      provider,
      device_name: deviceName,
      platform: provider === 'apple_health' ? 'ios' : 'android',
      scopes,
      permission_status: permissionStatus,
    }),
  }, token);
}

export async function syncMobileHealthRecords({
  token,
  provider,
  records,
  deviceName,
  scopes,
}: {
  token: string;
  provider: MobileHealthProvider;
  records: NormalizedHealthRecord[];
  deviceName?: string;
  scopes?: string[];
}) {
  return apiRequest<MobileHealthSyncResponse>('/api/v1/wearables/mobile-sync', {
    method: 'POST',
    body: JSON.stringify({
      provider,
      device_name: deviceName,
      platform: provider === 'apple_health' ? 'ios' : 'android',
      scopes,
      records,
    }),
  }, token);
}

export async function collectNativeHealthRecords(): Promise<NormalizedHealthRecord[]> {
  const result = await collectNativeHealthRecordsWithDiagnostics();

  return result.records;
}

export async function collectNativeHealthRecordsWithDiagnostics(): Promise<NativeHealthCollectionResult> {
  const permissionStatus = await requestNativeHealthAccess();
  const health = await import('react-native-health-connect');
  const result = await collectHealthConnectDailyRecords(health);

  return {
    ...result,
    permissionStatus,
  };
}

export async function requestNativeHealthAccess(): Promise<HealthConnectPermissionStatus> {
  if (Platform.OS !== 'android') {
    throw new Error('Apple Health is not wired yet. This build currently syncs Android Health Connect records.');
  }

  const health = await import('react-native-health-connect');
  const initialized = await health.initialize();

  if (!initialized) {
    throw new Error('Health Connect is not available on this Android device.');
  }

  const granted = await health.requestPermission(healthConnectPermissions);
  const status = healthConnectStatusFromPermissions(granted);

  if (!status.canSync) {
    throw new Error('Health Connect permissions were not granted.');
  }

  return status;
}

export async function getNativeHealthLinkStatus(): Promise<HealthConnectPermissionStatus> {
  if (Platform.OS !== 'android') {
    return {
      available: false,
      platform: Platform.OS === 'ios' ? 'ios' : 'unsupported',
      grantedScopes: [],
      missingScopes: healthConnectPermissions.map((permission) => permission.recordType),
      permissionStatus: 'denied',
      canSync: false,
      message: Platform.OS === 'ios'
        ? 'Apple Health permissions are coming next.'
        : 'Native health sync is only available on Android in this build.',
    };
  }

  try {
    const health = await import('react-native-health-connect');
    const initialized = await health.initialize();

    if (!initialized) {
      return {
        available: false,
        platform: 'android',
        grantedScopes: [],
        missingScopes: healthConnectPermissions.map((permission) => permission.recordType),
        permissionStatus: 'denied',
        canSync: false,
        message: 'Health Connect is not available on this device.',
      };
    }

    return healthConnectStatusFromPermissions(await health.getGrantedPermissions());
  } catch (error) {
    return {
      available: false,
      platform: 'android',
      grantedScopes: [],
      missingScopes: healthConnectPermissions.map((permission) => permission.recordType),
      permissionStatus: 'denied',
      canSync: false,
      message: error instanceof Error ? error.message : 'Could not read Health Connect permissions.',
    };
  }
}

export async function openNativeHealthSettings() {
  if (Platform.OS !== 'android') {
    return;
  }

  const health = await import('react-native-health-connect');
  await health.initialize();
  health.openHealthConnectSettings();
}

async function collectHealthConnectDailyRecords(
  health: HealthConnectModule,
): Promise<Omit<NativeHealthCollectionResult, 'permissionStatus'>> {
  const today = startOfDay(new Date());
  const records: NormalizedHealthRecord[] = [];
  const diagnostics: HealthConnectReadDiagnostic[] = [];
  const readErrors: NativeHealthCollectionResult['readErrors'] = [];

  for (let offset = HEALTH_CONNECT_DAYS - 1; offset >= 0; offset -= 1) {
    const day = addDays(today, -offset);
    const nextDay = addDays(day, 1);
    const metricDate = toDateKey(day);

    const [
      stepsResult,
      activeCaloriesResult,
      totalCaloriesResult,
      sleepSessionsResult,
      heartRateResult,
      restingHeartRateResult,
      heartRateVariabilityResult,
      exerciseSessionsResult,
    ] = await Promise.all([
      readHealthRecords(health, 'Steps', day, nextDay),
      readHealthRecords(health, 'ActiveCaloriesBurned', day, nextDay),
      readHealthRecords(health, 'TotalCaloriesBurned', day, nextDay),
      readHealthRecords(health, 'SleepSession', day, nextDay),
      readHealthRecords(health, 'HeartRate', day, nextDay),
      readHealthRecords(health, 'RestingHeartRate', day, nextDay),
      readHealthRecords(health, 'HeartRateVariabilityRmssd', day, nextDay),
      readHealthRecords(health, 'ExerciseSession', day, nextDay),
    ]);
    const steps = stepsResult.records;
    const activeCalories = activeCaloriesResult.records;
    const totalCalories = totalCaloriesResult.records;
    const sleepSessions = sleepSessionsResult.records;
    const heartRate = heartRateResult.records;
    const restingHeartRate = restingHeartRateResult.records;
    const heartRateVariability = heartRateVariabilityResult.records;
    const exerciseSessions = exerciseSessionsResult.records;
    const dayErrors = [
      ['Steps', stepsResult.error],
      ['ActiveCaloriesBurned', activeCaloriesResult.error],
      ['TotalCaloriesBurned', totalCaloriesResult.error],
      ['SleepSession', sleepSessionsResult.error],
      ['HeartRate', heartRateResult.error],
      ['RestingHeartRate', restingHeartRateResult.error],
      ['HeartRateVariabilityRmssd', heartRateVariabilityResult.error],
      ['ExerciseSession', exerciseSessionsResult.error],
    ]
      .filter((entry): entry is [string, string] => typeof entry[1] === 'string' && entry[1].length > 0)
      .map(([recordType, message]) => ({ recordType, message }));
    const recordCounts = {
      steps: steps.length,
      active_calories: activeCalories.length,
      total_calories: totalCalories.length,
      sleep: sleepSessions.length,
      heart_rate: heartRate.length,
      resting_heart_rate: restingHeartRate.length,
      heart_rate_variability: heartRateVariability.length,
      exercise: exerciseSessions.length,
    };

    diagnostics.push({
      metricDate,
      recordCounts,
      errors: dayErrors,
    });
    readErrors.push(...dayErrors.map((error) => ({ metricDate, ...error })));

    const metrics = {
      steps: sumBy(steps, (record) => numberValue(record.count)),
      calories_burned:
        sumBy(activeCalories, energyKilocalories) || sumBy(totalCalories, energyKilocalories) || undefined,
      sleep_minutes: sumBy(sleepSessions, intervalMinutes),
      active_minutes: sumBy(exerciseSessions, intervalMinutes),
      resting_heart_rate: averageBy(restingHeartRate, (record) => numberValue(record.beatsPerMinute)),
      heart_rate_variability: averageBy(heartRateVariability, (record) =>
        numberValue(record.heartRateVariabilityMillis),
      ),
      training_load: estimateTrainingLoad(exerciseSessions, heartRate),
    };

    const cleanMetrics = compactMetrics(metrics);

    if (!Object.keys(cleanMetrics).length) {
      continue;
    }

    records.push({
      metric_date: metricDate,
      external_event_id: `health-connect-${metricDate}`,
      metrics: cleanMetrics,
      raw_payload: {
        provider: 'health_connect',
        source: 'mobile_native',
        record_counts: recordCounts,
        read_errors: dayErrors,
      },
    });
  }

  const readableRecordCount = diagnostics.reduce(
    (total, day) => total + Object.values(day.recordCounts).reduce((dayTotal, count) => dayTotal + count, 0),
    0,
  );

  return {
    records,
    diagnostics,
    readableRecordCount,
    readErrors,
    message: records.length
      ? `${records.length} day(s) with Health Connect metrics found.`
      : readErrors.length
        ? 'Health Connect permissions exist, but one or more metric reads failed.'
        : 'Health Connect returned no readable Samsung Health records for the selected window.',
  };
}

async function readHealthRecords(
  health: HealthConnectModule,
  recordType: RecordType,
  startTime: Date,
  endTime: Date,
): Promise<HealthRecordReadResult> {
  try {
    const result = await health.readRecords(recordType, {
      timeRangeFilter: {
        operator: 'between',
        startTime: startTime.toISOString(),
        endTime: endTime.toISOString(),
      },
      ascendingOrder: true,
      pageSize: 250,
    });

    return {
      records: Array.isArray(result.records) ? (result.records as HealthRecord[]) : [],
    };
  } catch (error) {
    return {
      records: [],
      error: error instanceof Error ? error.message : `Could not read ${recordType}.`,
    };
  }
}

function compactMetrics(metrics: NormalizedHealthRecord['metrics']): NormalizedHealthRecord['metrics'] {
  return Object.entries(metrics).reduce<NormalizedHealthRecord['metrics']>((carry, [key, value]) => {
    if (value !== undefined && value !== null && Number.isFinite(value)) {
      const metricKey = key as keyof NormalizedHealthRecord['metrics'];

      carry[metricKey] = integerMetricKeys.has(metricKey) ? Math.round(value) : Math.round(value * 10) / 10;
    }

    return carry;
  }, {});
}

function healthConnectStatusFromPermissions(granted: HealthPermission[]): HealthConnectPermissionStatus {
  const grantedKeys = new Set(
    granted
      .filter(isRecordPermission)
      .map((permission) => `${permission.accessType}:${permission.recordType}`),
  );
  const grantedScopes = healthConnectPermissions
    .filter((permission) => grantedKeys.has(`${permission.accessType}:${permission.recordType}`))
    .map((permission) => permission.recordType);
  const missingScopes = healthConnectPermissions
    .filter((permission) => !grantedKeys.has(`${permission.accessType}:${permission.recordType}`))
    .map((permission) => permission.recordType);
  const canSync = grantedScopes.length > 0;
  const permissionStatus = missingScopes.length === 0 ? 'granted' : canSync ? 'partial' : 'denied';

  return {
    available: true,
    platform: 'android',
    grantedScopes,
    missingScopes,
    permissionStatus,
    canSync,
    message: canSync
      ? `${grantedScopes.length} Health Connect permission(s) enabled.`
      : 'Health Connect permissions are not enabled yet.',
  };
}

function isRecordPermission(permission: HealthPermission): permission is Permission {
  return typeof permission.accessType === 'string' && typeof permission.recordType === 'string';
}

function sumBy(records: HealthRecord[], selector: (record: HealthRecord) => number | undefined) {
  const total = records.reduce((carry, record) => carry + (selector(record) ?? 0), 0);

  return total > 0 ? total : undefined;
}

function averageBy(records: HealthRecord[], selector: (record: HealthRecord) => number | undefined) {
  const values = records
    .map(selector)
    .filter((value): value is number => value !== undefined && Number.isFinite(value));

  if (!values.length) {
    return undefined;
  }

  return values.reduce((total, value) => total + value, 0) / values.length;
}

function estimateTrainingLoad(exerciseSessions: HealthRecord[], heartRateRecords: HealthRecord[]) {
  const activeMinutes = sumBy(exerciseSessions, intervalMinutes) ?? 0;
  const averageHeartRate = averageBy(heartRateRecords.flatMap((record) => record.samples ?? []), (sample) =>
    numberValue(sample.beatsPerMinute),
  );

  if (!activeMinutes && !averageHeartRate) {
    return undefined;
  }

  return Math.round(activeMinutes * ((averageHeartRate ?? 120) / 100));
}

function energyKilocalories(record: HealthRecord) {
  const energy = record.energy;

  if (!energy) {
    return undefined;
  }

  if (typeof energy.inKilocalories === 'number') {
    return energy.inKilocalories;
  }

  if (energy.unit === 'kilocalories' || energy.unit === 'calories') {
    return numberValue(energy.value);
  }

  if (energy.unit === 'joules') {
    return numberValue(energy.value) ? numberValue(energy.value)! / 4184 : undefined;
  }

  if (energy.unit === 'kilojoules') {
    return numberValue(energy.value) ? numberValue(energy.value)! / 4.184 : undefined;
  }

  return undefined;
}

function intervalMinutes(record: HealthRecord) {
  if (!record.startTime || !record.endTime) {
    return undefined;
  }

  const start = new Date(record.startTime).getTime();
  const end = new Date(record.endTime).getTime();
  const minutes = (end - start) / 60000;

  return minutes > 0 ? minutes : undefined;
}

function numberValue(value: unknown) {
  return typeof value === 'number' && Number.isFinite(value) ? value : undefined;
}

function startOfDay(date: Date) {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate());
}

function addDays(date: Date, days: number) {
  const next = new Date(date);
  next.setDate(next.getDate() + days);

  return next;
}

function toDateKey(date: Date) {
  return date.toISOString().slice(0, 10);
}
