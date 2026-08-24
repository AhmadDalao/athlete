import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/data/app_data_providers.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/theme/app_theme.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';
import 'package:throughline_mobile/src/features/auth/auth_controller.dart';
import 'package:throughline_mobile/src/features/coach/coach_program_detail_screen.dart';
import 'package:throughline_mobile/src/features/coach/coach_program_editor_screen.dart';

class CoachProgramsScreen extends ConsumerStatefulWidget {
  const CoachProgramsScreen({super.key});

  @override
  ConsumerState<CoachProgramsScreen> createState() =>
      _CoachProgramsScreenState();
}

class _CoachProgramsScreenState extends ConsumerState<CoachProgramsScreen> {
  String _kind = 'preset';

  @override
  Widget build(BuildContext context) {
    final result = ref.watch(coachProgramsProvider);
    final canManagePrograms =
        ref.watch(authControllerProvider).user?.can('programs.manage') ?? false;
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
        final programs = envelope
            .maps('data')
            .where((program) => program.text('kind') == _kind)
            .toList();
        return RefreshIndicator(
          onRefresh: () => ref.refresh(coachProgramsProvider.future),
          child: ContentColumn(
            children: [
              PageIntro(
                eyebrow: 'Program library',
                title: 'Build once. Personalize safely.',
                body:
                    'Presets remain reusable. Every athlete plan is an isolated copy you edit before publishing.',
                action: canManagePrograms
                    ? FilledButton.icon(
                        onPressed: () => _createProgram(context, ref),
                        icon: const Icon(Icons.add_rounded),
                        label: const Text('New program'),
                      )
                    : null,
              ),
              SegmentedButton<String>(
                segments: const [
                  ButtonSegment(
                    value: 'preset',
                    icon: Icon(Icons.library_books_outlined),
                    label: Text('Presets'),
                  ),
                  ButtonSegment(
                    value: 'athlete_plan',
                    icon: Icon(Icons.person_outline_rounded),
                    label: Text('Athlete plans'),
                  ),
                ],
                selected: {_kind},
                onSelectionChanged: (selection) =>
                    setState(() => _kind = selection.first),
              ),
              if (programs.isEmpty)
                EmptyPanel(
                  title: _kind == 'preset' ? 'No presets' : 'No athlete plans',
                  body: _kind == 'preset'
                      ? 'Create a reusable preset from the New program action.'
                      : 'Open a preset and personalize it for an athlete.',
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
                          _kind == 'preset'
                              ? '${program.text('goal', 'No goal')}\n${program['sessions_count'] ?? 0} sessions · ${program['assignments_count'] ?? 0} athlete plans'
                              : '${program.object('athlete').text('name', 'Athlete')} · ${program.text('assignment_status', 'draft')} · ${program.text('publication_state', 'draft')}\n${program['sessions_count'] ?? 0} sessions · ${program.object('completion').text('percent', '0')}% complete',
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
