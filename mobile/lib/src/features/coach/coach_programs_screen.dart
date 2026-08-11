import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/data/app_data_providers.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';
import 'package:throughline_mobile/src/features/coach/coach_program_detail_screen.dart';
import 'package:throughline_mobile/src/features/coach/coach_program_editor_screen.dart';

class CoachProgramsScreen extends ConsumerWidget {
  const CoachProgramsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final result = ref.watch(coachProgramsProvider);
    return result.when(
      loading: () => const LoadingPanel(),
      error: (error, _) => ContentColumn(
        children: [
          ErrorPanel(
            error: error,
            onRetry: () => ref.invalidate(coachProgramsProvider),
          ),
        ],
      ),
      data: (envelope) {
        final programs = envelope.maps('data');
        return RefreshIndicator(
          onRefresh: () => ref.refresh(coachProgramsProvider.future),
          child: ContentColumn(
            children: [
              PageIntro(
                eyebrow: 'Program library',
                title: 'Build once. Assign well.',
                body:
                    'Create reusable programs, build sessions, and assign them to your athletes.',
                action: FilledButton.icon(
                  onPressed: () => _createProgram(context, ref),
                  icon: const Icon(Icons.add_rounded),
                  label: const Text('New program'),
                ),
              ),
              if (programs.isEmpty)
                const EmptyPanel(
                  title: 'No programs',
                  body:
                      'Create the first reusable program in the web coach workspace.',
                )
              else
                ...programs.map(
                  (program) => PremiumCard(
                    padding: EdgeInsets.zero,
                    accent: ThroughlineColors.cyan,
                    child: ListTile(
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 18,
                        vertical: 14,
                      ),
                      title: Text(
                        program.text('title'),
                        style: const TextStyle(fontWeight: FontWeight.w900),
                      ),
                      subtitle: Padding(
                        padding: const EdgeInsets.only(top: 6),
                        child: Text(
                          '${program.text('goal', 'No goal')}\n${program['sessions_count'] ?? 0} sessions · ${program['assignments_count'] ?? 0} assigned',
                        ),
                      ),
                      isThreeLine: true,
                      trailing: const Icon(Icons.chevron_right_rounded),
                      onTap: () => Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => CoachProgramDetailScreen(
                            programId: program['id'] as int,
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

  Future<void> _createProgram(BuildContext context, WidgetRef ref) async {
    final programId = await Navigator.push<int>(
      context,
      MaterialPageRoute(builder: (_) => const CoachProgramEditorScreen()),
    );
    if (programId == null || !context.mounted) return;
    ref.invalidate(coachProgramsProvider);
    await Navigator.push<void>(
      context,
      MaterialPageRoute(
        builder: (_) => CoachProgramDetailScreen(programId: programId),
      ),
    );
  }
}
