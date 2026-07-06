import { useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import { Pressable, StyleSheet, Text, TextInput, View } from 'react-native';

import { apiErrorMessage, apiRequest } from '@/api/client';
import { useAuth } from '@/auth/auth-context';
import { AppHeader, Card, EmptyState, ErrorState, LoadingState, Screen, SectionTitle } from '@/components/mobile-ui';
import { colors } from '@/theme';
import type { MessageThread } from '@/types/api';

export default function MessagesScreen() {
  const { token } = useAuth();
  const [threads, setThreads] = useState<MessageThread[]>([]);
  const [activeId, setActiveId] = useState<number | null>(null);
  const [body, setBody] = useState('');
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    if (!token) {
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      const response = await apiRequest<{ threads: MessageThread[] }>('/api/v1/messages', undefined, token);
      setThreads(response.data.threads);
      setActiveId((current) => current ?? response.data.threads[0]?.assignmentId ?? null);
    } catch (loadError) {
      setError(apiErrorMessage(loadError, 'Could not load messages.'));
    } finally {
      setIsLoading(false);
    }
  }, [token]);

  useFocusEffect(
    useCallback(() => {
      void load();
    }, [load]),
  );

  async function send() {
    if (!token || !activeId || !body.trim()) {
      return;
    }

    try {
      const response = await apiRequest<{ thread: MessageThread }>('/api/v1/messages', {
        method: 'POST',
        body: JSON.stringify({
          assignment_id: activeId,
          body: body.trim(),
        }),
      }, token);

      setThreads((current) => current.map((thread) => (thread.assignmentId === activeId ? response.data.thread : thread)));
      setBody('');
      setError(null);
    } catch (sendError) {
      setError(apiErrorMessage(sendError, 'Could not send message.'));
    }
  }

  if (isLoading && !threads.length) {
    return <LoadingState label="Loading messages..." />;
  }

  const activeThread = threads.find((thread) => thread.assignmentId === activeId);

  return (
    <Screen>
      <AppHeader title="Messages" eyebrow="Coach thread" />
      <SectionTitle eyebrow="Messages" title="Coach-athlete thread" />

      {error ? <ErrorState title="Message problem" body={error} onRetry={load} /> : null}

      {threads.length ? (
        <View style={styles.threadTabs}>
          {threads.map((thread) => (
            <Pressable
              key={thread.assignmentId}
              onPress={() => setActiveId(thread.assignmentId)}
              style={[styles.threadTab, activeId === thread.assignmentId && styles.threadTabActive]}
            >
              <Text style={[styles.threadTabText, activeId === thread.assignmentId && styles.threadTabTextActive]}>
                {thread.participant.name}
              </Text>
            </Pressable>
          ))}
        </View>
      ) : null}

      {activeThread ? (
        <Card style={styles.threadCard}>
          {activeThread.messages.length ? (
            activeThread.messages.map((message) => (
              <View key={message.id} style={[styles.bubble, message.isMine ? styles.mine : styles.theirs]}>
                <Text style={styles.sender}>{message.senderName}</Text>
                <Text style={styles.message}>{message.body}</Text>
              </View>
            ))
          ) : (
            <Text style={styles.note}>No messages yet.</Text>
          )}
          <TextInput
            multiline
            onChangeText={setBody}
            placeholder="Write a message..."
            placeholderTextColor="#aaa39a"
            style={styles.input}
            value={body}
          />
          <Pressable onPress={send} style={styles.send}>
            <Text style={styles.sendText}>Send</Text>
          </Pressable>
        </Card>
      ) : (
        <EmptyState title="No message thread" body="A thread appears after a coach-athlete assignment exists." />
      )}
    </Screen>
  );
}

const styles = StyleSheet.create({
  threadTabs: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  threadTab: {
    borderRadius: 999,
    borderColor: colors.border,
    borderWidth: 1,
    backgroundColor: colors.card,
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  threadTabActive: {
    backgroundColor: colors.green,
    borderColor: colors.green,
  },
  threadTabText: {
    color: colors.ink,
    fontWeight: '900',
  },
  threadTabTextActive: {
    color: colors.panelDark,
  },
  threadCard: {
    gap: 12,
  },
  bubble: {
    borderRadius: 18,
    padding: 12,
    maxWidth: '88%',
  },
  mine: {
    alignSelf: 'flex-end',
    backgroundColor: colors.greenSoft,
  },
  theirs: {
    alignSelf: 'flex-start',
    backgroundColor: colors.panel,
  },
  sender: {
    color: colors.muted,
    fontSize: 11,
    fontWeight: '900',
    textTransform: 'uppercase',
  },
  message: {
    color: colors.ink,
    fontSize: 15,
    lineHeight: 22,
  },
  note: {
    color: colors.muted,
    fontSize: 14,
  },
  input: {
    minHeight: 86,
    borderRadius: 18,
    borderColor: colors.border,
    borderWidth: 1,
    color: colors.ink,
    padding: 14,
    textAlignVertical: 'top',
  },
  send: {
    borderRadius: 18,
    backgroundColor: colors.green,
    alignItems: 'center',
    paddingVertical: 14,
  },
  sendText: {
    color: colors.panelDark,
    fontWeight: '900',
  },
});
