import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/data/app_data_providers.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';
import 'package:throughline_mobile/src/features/coach/coach_athlete_detail_screen.dart';

class CoachRosterScreen extends ConsumerWidget {
  const CoachRosterScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final result = ref.watch(coachRosterProvider);
    return result.when(
      loading: () => const LoadingPanel(),
      error: (error, _) => ContentColumn(
        children: [
          ErrorPanel(
            error: error,
            onRetry: () => ref.invalidate(coachRosterProvider),
          ),
        ],
      ),
      data: (envelope) {
        final athletes = envelope.maps('data');
        return RefreshIndicator(
          onRefresh: () => ref.refresh(coachRosterProvider.future),
          child: ContentColumn(
            children: [
              const PageIntro(
                eyebrow: 'Coach roster',
                title: 'Your athletes',
                body:
                    'Only active assignments in the current organization are shown.',
              ),
              if (athletes.isEmpty)
                const EmptyPanel(
                  title: 'Roster is empty',
                  body:
                      'Use the web invitation workflow to add the first athlete.',
                )
              else
                ...athletes.map(
                  (athlete) => PremiumCard(
                    padding: EdgeInsets.zero,
                    child: ListTile(
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 18,
                        vertical: 10,
                      ),
                      leading: CircleAvatar(
                        backgroundColor: ThroughlineColors.emerald,
                        foregroundColor: Colors.white,
                        child: Text(athlete.text('name', 'A').substring(0, 1)),
                      ),
                      title: Text(
                        athlete.text('name'),
                        style: const TextStyle(fontWeight: FontWeight.w900),
                      ),
                      subtitle: Text(athlete.text('email')),
                      trailing: const Icon(Icons.chevron_right_rounded),
                      onTap: () => Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => CoachAthleteDetailScreen(
                            athleteId: athlete['id'] as int,
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
            ],
          ),
        );
      },
    );
  }
}
