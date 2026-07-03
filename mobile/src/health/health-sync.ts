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

type HealthRecord = Record<string, any>;
type HealthConnectModule = typeof import('react-native-health-connect');

const HEALTH_CONNECT_DAYS = 14;

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

export async function syncMobileHealthRecords({
  token,
  provider,
  records,
  deviceName,
}: {
  token: string;
  provider: MobileHealthProvider;
  records: NormalizedHealthRecord[];
  deviceName?: string;
}) {
  return apiRequest('/api/v1/wearables/mobile-sync', {
    method: 'POST',
    body: JSON.stringify({
      provider,
      device_name: deviceName,
      platform: provider === 'apple_health' ? 'ios' : 'android',
      records,
    }),
  }, token);
}

export async function collectNativeHealthRecords(): Promise<NormalizedHealthRecord[]> {
  if (Platform.OS !== 'android') {
    throw new Error('Apple Health is not wired yet. This build currently syncs Android Health Connect records.');
  }

  const health = await import('react-native-health-connect');
  const initialized = await health.initialize();

  if (!initialized) {
    throw new Error('Health Connect is not available on this Android device.');
  }

  const granted = await health.requestPermission(healthConnectPermissions);
  const grantedKeys = new Set(granted.map((permission) => `${permission.accessType}:${permission.recordType}`));
  const hasRequiredPermission = healthConnectPermissions.some((permission) =>
    grantedKeys.has(`${permission.accessType}:${permission.recordType}`),
  );

  if (!hasRequiredPermission) {
    throw new Error('Health Connect permissions were not granted.');
  }

  return collectHealthConnectDailyRecords(health);
}

async function collectHealthConnectDailyRecords(health: HealthConnectModule): Promise<NormalizedHealthRecord[]> {
  const today = startOfDay(new Date());
  const records: NormalizedHealthRecord[] = [];

  for (let offset = HEALTH_CONNECT_DAYS - 1; offset >= 0; offset -= 1) {
    const day = addDays(today, -offset);
    const nextDay = addDays(day, 1);
    const metricDate = toDateKey(day);

    const [
      steps,
      activeCalories,
      totalCalories,
      sleepSessions,
      heartRate,
      restingHeartRate,
      heartRateVariability,
      exerciseSessions,
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
        record_counts: {
          steps: steps.length,
          active_calories: activeCalories.length,
          total_calories: totalCalories.length,
          sleep: sleepSessions.length,
          heart_rate: heartRate.length,
          resting_heart_rate: restingHeartRate.length,
          heart_rate_variability: heartRateVariability.length,
          exercise: exerciseSessions.length,
        },
      },
    });
  }

  return records;
}

async function readHealthRecords(
  health: HealthConnectModule,
  recordType: RecordType,
  startTime: Date,
  endTime: Date,
): Promise<HealthRecord[]> {
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

    return Array.isArray(result.records) ? (result.records as HealthRecord[]) : [];
  } catch {
    return [];
  }
}

function compactMetrics(metrics: NormalizedHealthRecord['metrics']): NormalizedHealthRecord['metrics'] {
  return Object.entries(metrics).reduce<NormalizedHealthRecord['metrics']>((carry, [key, value]) => {
    if (value !== undefined && value !== null && Number.isFinite(value)) {
      carry[key as keyof NormalizedHealthRecord['metrics']] = Math.round(value * 10) / 10;
    }

    return carry;
  }, {});
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
