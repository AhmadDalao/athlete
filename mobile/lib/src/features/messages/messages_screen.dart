import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/data/app_data_providers.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/providers.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';

class MessagesScreen extends ConsumerWidget {
  const MessagesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final result = ref.watch(messagesProvider);
    return result.when(
      loading: () => const LoadingPanel(),
      error: (error, _) => ContentColumn(
        children: [
          ErrorPanel(
            error: error,
            onRetry: () => ref.invalidate(messagesProvider),
          ),
        ],
      ),
      data: (envelope) {
        final conversations = envelope.maps('data');
        return RefreshIndicator(
          onRefresh: () => ref.refresh(messagesProvider.future),
          child: ContentColumn(
            children: [
              const PageIntro(
                eyebrow: 'Team communication',
                title: 'Messages',
                body:
                    'Clear context between coach and athlete. No fake realtime promises.',
              ),
              if (conversations.isEmpty)
                const EmptyPanel(
                  title: 'No conversations',
                  body:
                      'A conversation appears after a coach-athlete thread is created.',
                  icon: Icons.forum_outlined,
                )
              else
                ...conversations.map(
                  (conversation) => _ConversationTile(
                    conversation: conversation,
                    onTap: () => _openThread(context, ref, conversation),
                  ),
                ),
            ],
          ),
        );
      },
    );
  }

  Future<void> _openThread(
    BuildContext context,
    WidgetRef ref,
    JsonMap conversation,
  ) async {
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (context) =>
          _ConversationSheet(conversationId: conversation['id'] as int),
    );
    ref.invalidate(messagesProvider);
  }
}

class _ConversationTile extends StatelessWidget {
  const _ConversationTile({required this.conversation, required this.onTap});
  final JsonMap conversation;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final participants = conversation.maps('participants');
    final latest = conversation.object('latest_message');
    return PremiumCard(
      padding: EdgeInsets.zero,
      child: ListTile(
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 18,
          vertical: 10,
        ),
        onTap: onTap,
        leading: const CircleAvatar(
          backgroundColor: ThroughlineColors.cyan,
          foregroundColor: ThroughlineColors.graphite,
          child: Icon(Icons.forum_rounded),
        ),
        title: Text(
          conversation.text(
            'subject',
            participants.map((user) => user.text('name')).join(', '),
          ),
          style: const TextStyle(fontWeight: FontWeight.w900),
        ),
        subtitle: Text(
          latest.text('body', 'Open conversation'),
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
        ),
        trailing: const Icon(Icons.chevron_right_rounded),
      ),
    );
  }
}

class _ConversationSheet extends ConsumerStatefulWidget {
  const _ConversationSheet({required this.conversationId});
  final int conversationId;

  @override
  ConsumerState<_ConversationSheet> createState() => _ConversationSheetState();
}

class _ConversationSheetState extends ConsumerState<_ConversationSheet> {
  final _body = TextEditingController();
  late Future<JsonMap> _thread = _load();
  bool _sending = false;

  Future<JsonMap> _load() => ref
      .read(apiClientProvider)
      .get('/messages/${widget.conversationId}', query: {'per_page': 50});

  @override
  void dispose() {
    _body.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => FractionallySizedBox(
    heightFactor: 0.92,
    child: Padding(
      padding: EdgeInsets.fromLTRB(
        18,
        18,
        18,
        MediaQuery.viewInsetsOf(context).bottom + 14,
      ),
      child: Column(
        children: [
          Row(
            children: [
              Text(
                'Conversation',
                style: Theme.of(
                  context,
                ).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w900),
              ),
              const Spacer(),
              IconButton(
                onPressed: () => Navigator.pop(context),
                icon: const Icon(Icons.close_rounded),
              ),
            ],
          ),
          Expanded(
            child: FutureBuilder<JsonMap>(
              future: _thread,
              builder: (context, snapshot) {
                if (snapshot.connectionState != ConnectionState.done) {
                  return const LoadingPanel();
                }
                if (snapshot.hasError) {
                  return ErrorPanel(
                    error: snapshot.error!,
                    onRetry: () => setState(() => _thread = _load()),
                  );
                }
                final messages = snapshot.data!.object('data').maps('messages');
                return ListView.builder(
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  itemCount: messages.length,
                  itemBuilder: (context, index) {
                    final message = messages[index];
                    return Align(
                      alignment: Alignment.centerLeft,
                      child: Container(
                        constraints: const BoxConstraints(maxWidth: 310),
                        margin: const EdgeInsets.only(bottom: 10),
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: ThroughlineColors.emerald.withValues(
                            alpha: 0.13,
                          ),
                          borderRadius: BorderRadius.circular(18),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              message.object('sender').text('name', 'User'),
                              style: const TextStyle(
                                fontWeight: FontWeight.w800,
                                fontSize: 12,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(message.text('body')),
                          ],
                        ),
                      ),
                    );
                  },
                );
              },
            ),
          ),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _body,
                  minLines: 1,
                  maxLines: 4,
                  decoration: const InputDecoration(
                    hintText: 'Write a message',
                  ),
                ),
              ),
              const SizedBox(width: 8),
              IconButton.filled(
                onPressed: _sending ? null : _send,
                icon: _sending
                    ? const SizedBox.square(
                        dimension: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.send_rounded),
              ),
            ],
          ),
        ],
      ),
    ),
  );

  Future<void> _send() async {
    final body = _body.text.trim();
    if (body.isEmpty) return;
    setState(() => _sending = true);
    try {
      await ref
          .read(apiClientProvider)
          .post('/messages/${widget.conversationId}', data: {'body': body});
      _body.clear();
      setState(() => _thread = _load());
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }
}
