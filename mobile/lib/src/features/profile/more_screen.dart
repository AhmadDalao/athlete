import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/providers.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';
import 'package:throughline_mobile/src/features/auth/auth_controller.dart';

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
          child: Row(
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
                      style: const TextStyle(color: ThroughlineColors.muted),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
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
          onSelectionChanged: (selection) => _setTheme(ref, selection.first),
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
        OutlinedButton.icon(
          onPressed: ref.read(authControllerProvider.notifier).logout,
          icon: const Icon(Icons.logout_rounded),
          label: const Text('Sign out'),
        ),
      ],
    );
  }

  Future<void> _setTheme(WidgetRef ref, ThemeMode mode) async {
    await ref.read(themeControllerProvider.notifier).setMode(mode);
    await ref
        .read(apiClientProvider)
        .put('/profile/theme', data: {'theme': mode.name});
  }
}
