import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/data/app_data_providers.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/network/api_client.dart';
import 'package:throughline_mobile/src/core/providers.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';

class CoachInvitationsScreen extends ConsumerWidget {
  const CoachInvitationsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final result = ref.watch(coachInvitationsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Athlete invitations')),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _create(context, ref),
        icon: const Icon(Icons.person_add_alt_1_rounded),
        label: const Text('Invite athlete'),
      ),
      body: result.when(
        loading: () => const LoadingPanel(),
        error: (error, _) => ContentColumn(
          children: [
            ErrorPanel(
              error: error,
              onRetry: () => ref.invalidate(coachInvitationsProvider),
            ),
          ],
        ),
        data: (envelope) {
          final invitations = envelope.maps('data');
          return RefreshIndicator(
            onRefresh: () => ref.refresh(coachInvitationsProvider.future),
            child: ContentColumn(
              children: [
                const PageIntro(
                  eyebrow: 'Roster growth',
                  title: 'Invite athletes.',
                  body:
                      'Track pending, accepted, expired, and cancelled invitations from one place.',
                ),
                if (invitations.isEmpty)
                  const EmptyPanel(
                    title: 'No invitations',
                    body: 'Invite an athlete to start building your roster.',
                  )
                else
                  ...invitations.map(
                    (invitation) => PremiumCard(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      invitation.text(
                                        'name',
                                        invitation.text('email'),
                                      ),
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w900,
                                        fontSize: 17,
                                      ),
                                    ),
                                    Text(
                                      invitation.text('email'),
                                      style: const TextStyle(
                                        color: ThroughlineColors.muted,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              StatusChip(invitation.text('status', 'pending')),
                            ],
                          ),
                          if (invitation.text('expires_at').isNotEmpty) ...[
                            const SizedBox(height: 12),
                            Text(
                              'Expires ${_date(invitation.text('expires_at'))}',
                              style: const TextStyle(
                                color: ThroughlineColors.muted,
                                fontSize: 12,
                              ),
                            ),
                          ],
                          if (invitation.text('status') == 'pending') ...[
                            const SizedBox(height: 10),
                            Row(
                              children: [
                                TextButton.icon(
                                  onPressed: () => _resend(
                                    context,
                                    ref,
                                    invitation['id'] as int,
                                  ),
                                  icon: const Icon(Icons.send_rounded),
                                  label: const Text('Resend'),
                                ),
                                TextButton.icon(
                                  onPressed: () => _cancel(
                                    context,
                                    ref,
                                    invitation['id'] as int,
                                  ),
                                  icon: const Icon(Icons.close_rounded),
                                  label: const Text('Cancel'),
                                ),
                              ],
                            ),
                          ],
                        ],
                      ),
                    ),
                  ),
                const SizedBox(height: 84),
              ],
            ),
          );
        },
      ),
    );
  }

  static String _date(String value) => value.split('T').first;

  Future<void> _create(BuildContext context, WidgetRef ref) async {
    final name = TextEditingController();
    final email = TextEditingController();
    final formKey = GlobalKey<FormState>();
    var saving = false;
    String? error;

    final created = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => StatefulBuilder(
        builder: (context, setState) => AlertDialog(
          title: const Text('Invite athlete'),
          content: Form(
            key: formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextFormField(
                  controller: name,
                  textCapitalization: TextCapitalization.words,
                  decoration: const InputDecoration(labelText: 'Name'),
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: email,
                  keyboardType: TextInputType.emailAddress,
                  autocorrect: false,
                  decoration: const InputDecoration(labelText: 'Email'),
                  validator: (value) {
                    final candidate = value?.trim() ?? '';
                    if (candidate.isEmpty || !candidate.contains('@')) {
                      return 'Enter a valid email address.';
                    }
                    return null;
                  },
                ),
                if (error != null) ...[
                  const SizedBox(height: 12),
                  Text(
                    error!,
                    style: const TextStyle(color: ThroughlineColors.danger),
                  ),
                ],
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: saving ? null : () => Navigator.pop(context, false),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: saving
                  ? null
                  : () async {
                      if (!formKey.currentState!.validate()) return;
                      setState(() {
                        saving = true;
                        error = null;
                      });
                      try {
                        await ref
                            .read(apiClientProvider)
                            .post(
                              '/coach/invitations',
                              data: {
                                'name': name.text.trim().isEmpty
                                    ? null
                                    : name.text.trim(),
                                'email': email.text.trim(),
                              },
                            );
                        if (context.mounted) Navigator.pop(context, true);
                      } on ApiFailure catch (failure) {
                        setState(() {
                          saving = false;
                          error = failure.message;
                        });
                      }
                    },
              child: Text(saving ? 'Sending...' : 'Send invitation'),
            ),
          ],
        ),
      ),
    );
    name.dispose();
    email.dispose();
    if (created == true) ref.invalidate(coachInvitationsProvider);
  }

  Future<void> _resend(
    BuildContext context,
    WidgetRef ref,
    int invitationId,
  ) async {
    try {
      await ref
          .read(apiClientProvider)
          .post('/coach/invitations/$invitationId/resend');
      ref.invalidate(coachInvitationsProvider);
      if (context.mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(const SnackBar(content: Text('Invitation sent again.')));
      }
    } on ApiFailure catch (failure) {
      if (context.mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(failure.message)));
      }
    }
  }

  Future<void> _cancel(
    BuildContext context,
    WidgetRef ref,
    int invitationId,
  ) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cancel invitation?'),
        content: const Text('The existing invitation link will stop working.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Keep'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Cancel invitation'),
          ),
        ],
      ),
    );
    if (confirmed != true) return;
    try {
      await ref
          .read(apiClientProvider)
          .delete('/coach/invitations/$invitationId');
      ref.invalidate(coachInvitationsProvider);
    } on ApiFailure catch (failure) {
      if (context.mounted) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(failure.message)));
      }
    }
  }
}
