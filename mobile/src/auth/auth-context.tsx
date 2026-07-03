import * as SecureStore from 'expo-secure-store';
import { createContext, PropsWithChildren, useContext, useEffect, useMemo, useState } from 'react';

import { apiRequest, login } from '@/api/client';
import type { Viewer } from '@/types/api';

type AuthContextValue = {
  token: string | null;
  user: Viewer | null;
  isLoading: boolean;
  signIn: (email: string, password: string) => Promise<void>;
  signOut: () => Promise<void>;
  refreshUser: () => Promise<void>;
};

const TOKEN_KEY = 'throughline.mobile.token';
const USER_KEY = 'throughline.mobile.user';

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: PropsWithChildren) {
  const [token, setToken] = useState<string | null>(null);
  const [user, setUser] = useState<Viewer | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    let mounted = true;

    async function restore() {
      const [storedToken, storedUser] = await Promise.all([
        SecureStore.getItemAsync(TOKEN_KEY),
        SecureStore.getItemAsync(USER_KEY),
      ]);

      if (!mounted) {
        return;
      }

      setToken(storedToken);
      setUser(storedUser ? (JSON.parse(storedUser) as Viewer) : null);
      setIsLoading(false);
    }

    restore().catch(() => {
      if (mounted) {
        setIsLoading(false);
      }
    });

    return () => {
      mounted = false;
    };
  }, []);

  const value = useMemo<AuthContextValue>(
    () => ({
      token,
      user,
      isLoading,
      async signIn(email: string, password: string) {
        const response = await login(email, password);
        await Promise.all([
          SecureStore.setItemAsync(TOKEN_KEY, response.data.token),
          SecureStore.setItemAsync(USER_KEY, JSON.stringify(response.data.user)),
        ]);
        setToken(response.data.token);
        setUser(response.data.user);
      },
      async signOut() {
        if (token) {
          await apiRequest('/api/v1/auth/tokens/current', { method: 'DELETE' }, token).catch(() => null);
        }

        await Promise.all([SecureStore.deleteItemAsync(TOKEN_KEY), SecureStore.deleteItemAsync(USER_KEY)]);
        setToken(null);
        setUser(null);
      },
      async refreshUser() {
        if (!token) {
          return;
        }

        const response = await apiRequest<Viewer>('/api/v1/me', undefined, token);
        await SecureStore.setItemAsync(USER_KEY, JSON.stringify(response.data));
        setUser(response.data);
      },
    }),
    [isLoading, token, user],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);

  if (!context) {
    throw new Error('useAuth must be used inside AuthProvider.');
  }

  return context;
}
