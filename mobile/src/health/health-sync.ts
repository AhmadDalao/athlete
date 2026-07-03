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
  // Development builds can wire HealthKit/Health Connect native reads here.
  // Expo Go cannot access those native modules, so the contract stays isolated.
  return [];
}
