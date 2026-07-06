import { router } from 'expo-router';
import { useState } from 'react';
import { KeyboardAvoidingView, Platform, Pressable, StyleSheet, Text, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { useAuth } from '@/auth/auth-context';
import { colors, radius, shadow } from '@/theme';

export default function LoginScreen() {
  const { signIn } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function submit() {
    setError(null);
    setIsSubmitting(true);

    try {
      await signIn(email.trim(), password);
      router.replace('/home');
    } catch {
      setError('Login failed. Check the email and password.');
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={styles.container}>
        <View style={styles.hero}>
          <Text style={styles.eyebrow}>Throughline Mobile</Text>
          <Text style={styles.title}>Train, track, report.</Text>
          <Text style={styles.body}>
            Athletes get their schedule and workouts. Coaches get their roster and training queues.
          </Text>
        </View>

        <View style={styles.form}>
          <Text style={styles.label}>Email</Text>
          <TextInput
            autoCapitalize="none"
            autoComplete="email"
            keyboardType="email-address"
            onChangeText={setEmail}
            placeholder="name@example.com"
            placeholderTextColor="#aaa39a"
            style={styles.input}
            value={email}
          />

          <Text style={styles.label}>Password</Text>
          <TextInput
            onChangeText={setPassword}
            placeholder="Password"
            placeholderTextColor="#aaa39a"
            secureTextEntry
            style={styles.input}
            value={password}
          />

          {error ? <Text style={styles.error}>{error}</Text> : null}

          <Pressable disabled={isSubmitting} onPress={submit} style={[styles.button, isSubmitting && styles.buttonDisabled]}>
            <Text style={styles.buttonText}>{isSubmitting ? 'Signing in...' : 'Sign in'}</Text>
          </Pressable>
        </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: colors.background,
  },
  container: {
    flex: 1,
    justifyContent: 'space-between',
    padding: 24,
  },
  hero: {
    paddingTop: 36,
    gap: 14,
    borderRadius: radius.xl,
    borderColor: 'rgba(168, 255, 47, 0.24)',
    borderWidth: 1,
    backgroundColor: colors.greenDark,
    padding: 22,
  },
  eyebrow: {
    color: colors.green,
    fontSize: 13,
    fontWeight: '900',
    letterSpacing: 2,
    textTransform: 'uppercase',
  },
  title: {
    color: colors.ink,
    fontSize: 48,
    fontWeight: '900',
    letterSpacing: -1.8,
  },
  body: {
    color: colors.panelMuted,
    fontSize: 18,
    lineHeight: 28,
  },
  form: {
    backgroundColor: colors.card,
    borderColor: colors.border,
    borderWidth: 1,
    borderRadius: radius.xl,
    padding: 22,
    gap: 12,
    ...shadow,
  },
  label: {
    color: colors.ink,
    fontSize: 13,
    fontWeight: '900',
  },
  input: {
    borderColor: colors.border,
    borderWidth: 1,
    borderRadius: 16,
    color: colors.ink,
    fontSize: 16,
    paddingHorizontal: 16,
    paddingVertical: 14,
  },
  button: {
    marginTop: 8,
    borderRadius: 18,
    backgroundColor: colors.green,
    paddingVertical: 16,
    alignItems: 'center',
  },
  buttonDisabled: {
    opacity: 0.6,
  },
  buttonText: {
    color: colors.panelDark,
    fontSize: 17,
    fontWeight: '900',
  },
  error: {
    color: colors.danger,
    fontWeight: '700',
  },
});
