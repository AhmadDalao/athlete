import Constants from 'expo-constants';

import type { ApiEnvelope, LoginResponse } from '@/types/api';

export class ApiError extends Error {
  status: number;
  payload: unknown;
  method: string;
  url: string;

  constructor(status: number, message: string, payload: unknown, method: string, url: string) {
    super(message);
    this.status = status;
    this.payload = payload;
    this.method = method;
    this.url = url;
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
  const url = path.startsWith('http') ? path : `${API_BASE_URL}${path}`;
  const method = options.method ?? 'GET';
  const headers = new Headers(options.headers);
  headers.set('Accept', 'application/json');

  if (options.body && !(options.body instanceof FormData)) {
    headers.set('Content-Type', 'application/json');
  }

  if (token) {
    headers.set('Authorization', `Bearer ${token}`);
  }

  let response: Response;

  try {
    response = await fetch(url, {
      ...options,
      headers,
    });
  } catch (error) {
    throw new ApiError(0, error instanceof Error ? error.message : 'Network request failed', null, method, url);
  }

  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    throw new ApiError(
      response.status,
      extractApiMessage(payload, response.statusText || 'API request failed'),
      payload,
      method,
      url,
    );
  }

  return payload as ApiEnvelope<T>;
}

export function apiErrorMessage(error: unknown, fallback = 'API request failed.'): string {
  if (error instanceof ApiError) {
    const endpoint = error.url.replace(API_BASE_URL, '');

    if (error.status === 0) {
      return `${error.method} ${endpoint}: ${error.message}`;
    }

    return `${error.method} ${endpoint} failed (${error.status}): ${error.message}`;
  }

  return error instanceof Error ? error.message : fallback;
}

function extractApiMessage(payload: unknown, fallback: string): string {
  if (!payload || typeof payload !== 'object') {
    return fallback;
  }

  const body = payload as { message?: unknown; errors?: unknown };
  const message = typeof body.message === 'string' ? body.message : fallback;
  const validationMessages = firstValidationMessages(body.errors);

  return validationMessages.length ? `${message}: ${validationMessages.join(' ')}` : message;
}

function firstValidationMessages(errors: unknown): string[] {
  if (!errors || typeof errors !== 'object') {
    return [];
  }

  return Object.values(errors as Record<string, unknown>)
    .flatMap((value) => {
      if (Array.isArray(value)) {
        return value.filter((message): message is string => typeof message === 'string');
      }

      return typeof value === 'string' ? [value] : [];
    })
    .slice(0, 3);
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
