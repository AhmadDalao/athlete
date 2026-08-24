import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/data/app_data_providers.dart';
import 'package:throughline_mobile/src/core/providers.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';
import 'package:throughline_mobile/src/features/auth/auth_controller.dart';
import 'package:throughline_mobile/src/features/coach/coach_invitations_screen.dart';

class MoreScreen extends ConsumerWidget {
  const MoreScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);
    final theme = ref.watch(themeControllerProvider);
    return ContentColumn(
      children: [
        const PageIntro(
          eyebrow: 'Account',
          title: 'Profile & settings',
          body: 'Your identity, organization, theme, and session controls.',
        ),
        PremiumCard(
          accent: ThroughlineColors.lime,
          child: Column(
            children: [
              Row(
                children: [
                  CircleAvatar(
                    radius: 28,
                    backgroundColor: ThroughlineColors.lime,
                    foregroundColor: ThroughlineColors.graphite,
                    child: Text(
                      auth.user?.name.substring(0, 1).toUpperCase() ?? 'T',
                      style: const TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          auth.user?.name ?? '',
                          style: const TextStyle(
                            fontWeight: FontWeight.w900,
                            fontSize: 18,
                          ),
                        ),
                        Text(
                          auth.user?.email ?? '',
                          style: const TextStyle(
                            color: ThroughlineColors.muted,
                          ),
                        ),
                      ],
                    ),
                  ),
                  IconButton.filledTonal(
                    tooltip: 'Edit profile',
                    onPressed: auth.user == null
                        ? null
                        : () => _editProfile(context, ref),
                    icon: const Icon(Icons.edit_outlined),
                  ),
                ],
              ),
              if (auth.user?.primaryGoal?.isNotEmpty == true) ...[
                const SizedBox(height: 16),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: ThroughlineColors.lime.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'PRIMARY GOAL',
                        style: TextStyle(
                          color: ThroughlineColors.muted,
                          fontSize: 10,
                          letterSpacing: 1.5,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        auth.user!.primaryGoal!,
                        style: const TextStyle(fontWeight: FontWeight.w800),
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
        if (auth.user?.isCoach == true &&
            auth.user?.can('invitations.manage') == true) ...[
          const SectionTitle('Coach tools'),
          PremiumCard(
            padding: EdgeInsets.zero,
            child: ListTile(
              contentPadding: const EdgeInsets.symmetric(
                horizontal: 18,
                vertical: 8,
              ),
              leading: const Icon(Icons.mark_email_unread_rounded),
              title: const Text(
                'Athlete invitations',
                style: TextStyle(fontWeight: FontWeight.w800),
              ),
              subtitle: const Text('Invite, resend, and cancel access links.'),
              trailing: const Icon(Icons.chevron_right_rounded),
              onTap: () => Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => const CoachInvitationsScreen(),
                ),
              ),
            ),
          ),
        ],
        const SectionTitle('Appearance'),
        SegmentedButton<ThemeMode>(
          segments: const [
            ButtonSegment(
              value: ThemeMode.system,
              icon: Icon(Icons.brightness_auto_rounded),
              label: Text('System'),
            ),
            ButtonSegment(
              value: ThemeMode.dark,
              icon: Icon(Icons.dark_mode_rounded),
              label: Text('Dark'),
            ),
            ButtonSegment(
              value: ThemeMode.light,
              icon: Icon(Icons.light_mode_rounded),
              label: Text('Light'),
            ),
          ],
          selected: {theme},
          onSelectionChanged: (selection) =>
              _setTheme(context, ref, selection.first),
        ),
        if (auth.organizations.length > 1) ...[
          const SectionTitle('Organization'),
          PremiumCard(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: DropdownButtonHideUnderline(
              child: DropdownButton<int>(
                value: auth.activeOrganizationId,
                isExpanded: true,
                items: [
                  for (final organization in auth.organizations)
                    DropdownMenuItem(
                      value: organization.id,
                      child: Text(organization.name),
                    ),
                ],
                onChanged: (value) {
                  if (value != null) {
                    ref
                        .read(authControllerProvider.notifier)
                        .selectOrganization(value);
                  }
                },
              ),
            ),
          ),
        ],
        const SectionTitle('Security'),
        PremiumCard(
          padding: EdgeInsets.zero,
          child: SwitchListTile(
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 18,
              vertical: 8,
            ),
            secondary: const Icon(Icons.fingerprint_rounded),
            title: const Text(
              'Biometric unlock',
              style: TextStyle(fontWeight: FontWeight.w800),
            ),
            subtitle: Text(
              auth.keepSignedIn
                  ? 'Require your fingerprint or face when reopening Throughline.'
                  : 'Sign in again with “Keep me signed in” to enable this.',
            ),
            value: auth.biometricEnabled,
            onChanged: auth.keepSignedIn
                ? (enabled) => _setBiometrics(context, ref, enabled)
                : null,
          ),
        ),
        OutlinedButton.icon(
          onPressed: ref.read(authControllerProvider.notifier).logout,
          icon: const Icon(Icons.logout_rounded),
          label: const Text('Sign out'),
        ),
      ],
    );
  }

  Future<void> _setTheme(
    BuildContext context,
    WidgetRef ref,
    ThemeMode mode,
  ) async {
    await ref.read(themeControllerProvider.notifier).setMode(mode);
    try {
      await ref
          .read(apiClientProvider)
          .put('/profile/theme', data: {'theme': mode.name});
    } catch (error) {
      if (context.mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'Theme changed on this device, but could not sync: $error',
            ),
          ),
        );
      }
    }
  }

  Future<void> _setBiometrics(
    BuildContext context,
    WidgetRef ref,
    bool enabled,
  ) async {
    final changed = await ref
        .read(authControllerProvider.notifier)
        .setBiometricEnabled(enabled);
    if (!changed && context.mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Biometric verification was canceled or is unavailable.',
          ),
        ),
      );
    }
  }

  Future<void> _editProfile(BuildContext context, WidgetRef ref) async {
    final user = ref.read(authControllerProvider).user!;
    final name = TextEditingController(text: user.name);
    final phone = TextEditingController(text: user.phone ?? '');
    final goal = TextEditingController(text: user.primaryGoal ?? '');
    final bio = TextEditingController(text: user.bio ?? '');
    var saving = false;

    final saved = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) => SizedBox(
          height: MediaQuery.sizeOf(context).height * 0.82,
          child: ListView(
            padding: EdgeInsets.fromLTRB(
              20,
              22,
              20,
              MediaQuery.viewInsetsOf(context).bottom + 24,
            ),
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      'Edit profile',
                      style: Theme.of(context).textTheme.headlineSmall
                          ?.copyWith(fontWeight: FontWeight.w900),
                    ),
                  ),
                  IconButton(
                    onPressed: () => Navigator.pop(context),
                    icon: const Icon(Icons.close_rounded),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              TextField(
                controller: name,
                decoration: const InputDecoration(labelText: 'Name'),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: phone,
                keyboardType: TextInputType.phone,
                decoration: const InputDecoration(labelText: 'Phone'),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: goal,
                maxLines: 3,
                decoration: const InputDecoration(labelText: 'Primary goal'),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: bio,
                maxLines: 5,
                decoration: const InputDecoration(labelText: 'Bio'),
              ),
              const SizedBox(height: 18),
              FilledButton(
                onPressed: saving
                    ? null
                    : () async {
                        setModalState(() => saving = true);
                        try {
                          await ref
                              .read(apiClientProvider)
                              .put(
                                '/profile',
                                data: {
                                  'name': name.text.trim(),
                                  'phone': phone.text.trim().isEmpty
                                      ? null
                                      : phone.text.trim(),
                                  'primary_goal': goal.text.trim().isEmpty
                                      ? null
                                      : goal.text.trim(),
                                  'bio': bio.text.trim().isEmpty
                                      ? null
                                      : bio.text.trim(),
                                },
                              );
                          await ref
                              .read(authControllerProvider.notifier)
                              .refreshProfile();
                          if (context.mounted) {
                            Navigator.pop(context, true);
                          }
                        } catch (error) {
                          if (context.mounted) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(content: Text(error.toString())),
                            );
                          }
                        } finally {
                          if (context.mounted) {
                            setModalState(() => saving = false);
                          }
                        }
                      },
                child: saving
                    ? const SizedBox.square(
                        dimension: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Save profile'),
              ),
            ],
          ),
        ),
      ),
    );
    name.dispose();
    phone.dispose();
    goal.dispose();
    bio.dispose();
    if (saved == true) ref.invalidate(profileProvider);
  }
}
