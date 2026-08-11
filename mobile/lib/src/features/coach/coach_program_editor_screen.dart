import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:throughline_mobile/src/core/data/app_data_providers.dart';
import 'package:throughline_mobile/src/core/models/session_models.dart';
import 'package:throughline_mobile/src/core/network/api_client.dart';
import 'package:throughline_mobile/src/core/providers.dart';
import 'package:throughline_mobile/src/core/widgets/throughline_widgets.dart';

class CoachProgramEditorScreen extends ConsumerStatefulWidget {
  const CoachProgramEditorScreen({super.key, this.program});

  final JsonMap? program;

  @override
  ConsumerState<CoachProgramEditorScreen> createState() =>
      _CoachProgramEditorScreenState();
}

class _CoachProgramEditorScreenState
    extends ConsumerState<CoachProgramEditorScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _title;
  late final TextEditingController _goal;
  late final TextEditingController _weeks;
  late final TextEditingController _notes;
  late String _status;
  late String _visibility;
  bool _saving = false;
  String? _error;

  bool get _editing => widget.program != null;

  @override
  void initState() {
    super.initState();
    final program = widget.program ?? <String, dynamic>{};
    _title = TextEditingController(text: program.text('title'));
    _goal = TextEditingController(text: program.text('goal'));
    _weeks = TextEditingController(
      text: program['estimated_weeks']?.toString() ?? '',
    );
    _notes = TextEditingController(text: program.text('notes'));
    _status = program.text('status', 'active');
    _visibility = program.text('visibility', 'private');
  }

  @override
  void dispose() {
    _title.dispose();
    _goal.dispose();
    _weeks.dispose();
    _notes.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: Text(_editing ? 'Edit program' : 'New program')),
    body: Form(
      key: _formKey,
      child: ContentColumn(
        children: [
          PageIntro(
            eyebrow: 'Reusable template',
            title: _editing ? 'Refine the program.' : 'Build the next block.',
            body:
                'Templates stay separate from athletes until you assign them.',
          ),
          PremiumCard(
            child: Column(
              children: [
                TextFormField(
                  controller: _title,
                  decoration: const InputDecoration(labelText: 'Program title'),
                  validator: (value) => value == null || value.trim().isEmpty
                      ? 'Enter a title.'
                      : null,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _goal,
                  decoration: const InputDecoration(labelText: 'Goal'),
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _weeks,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(
                    labelText: 'Estimated weeks',
                  ),
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  initialValue: _status,
                  decoration: const InputDecoration(labelText: 'Status'),
                  items: const [
                    DropdownMenuItem(value: 'draft', child: Text('Draft')),
                    DropdownMenuItem(value: 'active', child: Text('Active')),
                    DropdownMenuItem(
                      value: 'archived',
                      child: Text('Archived'),
                    ),
                  ],
                  onChanged: (value) => _status = value ?? _status,
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  initialValue: _visibility,
                  decoration: const InputDecoration(labelText: 'Visibility'),
                  items: const [
                    DropdownMenuItem(value: 'private', child: Text('Private')),
                    DropdownMenuItem(
                      value: 'organization',
                      child: Text('Organization'),
                    ),
                  ],
                  onChanged: (value) => _visibility = value ?? _visibility,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _notes,
                  minLines: 3,
                  maxLines: 6,
                  decoration: const InputDecoration(labelText: 'Coach notes'),
                ),
              ],
            ),
          ),
          if (_error != null)
            Text(_error!, style: const TextStyle(color: Colors.redAccent)),
          FilledButton.icon(
            onPressed: _saving ? null : _save,
            icon: _saving
                ? const SizedBox.square(
                    dimension: 18,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.check_rounded),
            label: Text(_editing ? 'Save program' : 'Create program'),
          ),
        ],
      ),
    ),
  );

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _saving = true;
      _error = null;
    });
    final payload = {
      'title': _title.text.trim(),
      'goal': _goal.text.trim().isEmpty ? null : _goal.text.trim(),
      'status': _status,
      'visibility': _visibility,
      'estimated_weeks': int.tryParse(_weeks.text),
      'notes': _notes.text.trim().isEmpty ? null : _notes.text.trim(),
    };
    try {
      final api = ref.read(apiClientProvider);
      final envelope = _editing
          ? await api.put(
              '/coach/programs/${widget.program!['id']}',
              data: payload,
            )
          : await api.post('/coach/programs', data: payload);
      ref.invalidate(coachProgramsProvider);
      if (mounted) Navigator.pop(context, envelope.object('data')['id'] as int);
    } on ApiFailure catch (failure) {
      if (mounted) setState(() => _error = failure.message);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }
}
