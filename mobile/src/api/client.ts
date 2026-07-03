import Constants from 'expo-constants';

import type { ApiEnvelope, LoginResponse } from '@/types/api';

export class ApiError extends Error {
  status: number;
  payload: unknown;

  constructor(status: number, message: string, payload: unknown) {
    super(message);
    this.status = status;
    this.payload = payload;
  }
}

const extra = Constants.expoConfig?.extra as { apiBaseUrl?: string } | undefined;

export const API_BASE_URL =
  process.env.EXPO_PUBLIC_API_BASE_URL ?? extra?.apiBaseUrl ?? 'https://athlete.ahmaddalao.com';

export async function apiRequest<T>(
  path: string,
  options: RequestInit = {},
  token?: string | null,
): Promise<ApiEnvelope<T>> {
  const headers = new Headers(options.headers);
  headers.set('Accept', 'application/json');

  if (options.body && !(options.body instanceof FormData)) {
    headers.set('Content-Type', 'application/json');
  }

  if (token) {
    headers.set('Authorization', `Bearer ${token}`);
  }

  const response = await fetch(path.startsWith('http') ? path : `${API_BASE_URL}${path}`, {
    ...options,
    headers,
  });
  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    throw new ApiError(response.status, response.statusText || 'API request failed', payload);
  }

  return payload as ApiEnvelope<T>;
}

export async function login(email: string, password: string): Promise<ApiEnvelope<LoginResponse>> {
  return apiRequest<LoginResponse>('/api/v1/auth/tokens', {
    method: 'POST',
    body: JSON.stringify({
      email,
      password,
      token_name: 'Throughline Mobile',
    }),
  });
}
