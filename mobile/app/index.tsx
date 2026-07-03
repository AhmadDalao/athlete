import { Redirect } from 'expo-router';

import { LoadingState } from '@/components/mobile-ui';
import { useAuth } from '@/auth/auth-context';

export default function IndexRoute() {
  const { token, isLoading } = useAuth();

  if (isLoading) {
    return <LoadingState />;
  }

  if (!token) {
    return <Redirect href="/login" />;
  }

  return <Redirect href="/home" />;
}
