import 'package:flutter/material.dart';

import 'api.dart';

String label(dynamic value) => value.toString().replaceAll('_', ' ');

String manilaTime(dynamic value) {
  final parsed = DateTime.tryParse(value?.toString() ?? '');
  if (parsed == null) return 'Not scheduled';
  final time = parsed.toUtc().add(const Duration(hours: 8));
  String pad(int n) => n.toString().padLeft(2, '0');
  return '${time.year}-${pad(time.month)}-${pad(time.day)} ${pad(time.hour)}:${pad(time.minute)} Manila';
}

// Fixed Manila UTC+8 input, independent of the browser computer's timezone.
DateTime? parseManila(String input) {
  if (!RegExp(r'^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$').hasMatch(input)) return null;
  final year = int.parse(input.substring(0, 4));
  final month = int.parse(input.substring(5, 7));
  final day = int.parse(input.substring(8, 10));
  final hour = int.parse(input.substring(11, 13));
  final minute = int.parse(input.substring(14, 16));
  final wall = DateTime.utc(year, month, day, hour, minute);
  if (wall.year != year ||
      wall.month != month ||
      wall.day != day ||
      wall.hour != hour ||
      wall.minute != minute) {
    return null;
  }
  return wall.subtract(const Duration(hours: 8));
}

String utcValue(DateTime time) => '${time.toIso8601String().substring(0, 19)}Z';

String? textRule(String? text, int min, int max) {
  final length = (text ?? '').trim().length;
  return length < min || length > max ? 'Enter $min–$max characters.' : null;
}

String? futureRule(String? input) {
  final date = parseManila((input ?? '').trim());
  if (date == null) return 'Use a valid Manila date: YYYY-MM-DD HH:mm.';
  if (!date.isAfter(DateTime.now().toUtc())) {
    return 'Choose a future Manila time.';
  }
  return null;
}

class Notice extends StatelessWidget {
  const Notice(this.message, {super.key, this.error = false});
  final String message;
  final bool error;
  @override
  Widget build(BuildContext context) => Semantics(
    liveRegion: true,
    child: Container(
      width: double.infinity,
      margin: const EdgeInsets.symmetric(vertical: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: error
            ? Theme.of(context).colorScheme.errorContainer
            : Theme.of(context).colorScheme.secondaryContainer,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        message,
        style: TextStyle(
          color: error
              ? Theme.of(context).colorScheme.onErrorContainer
              : Theme.of(context).colorScheme.onSecondaryContainer,
        ),
      ),
    ),
  );
}

class Section extends StatelessWidget {
  const Section(this.title, this.children, {super.key});
  final String title;
  final List<Widget> children;
  @override
  Widget build(BuildContext context) => Card(
    margin: const EdgeInsets.only(bottom: 16),
    child: Padding(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(title, style: Theme.of(context).textTheme.titleLarge),
          const SizedBox(height: 16),
          ...children,
        ],
      ),
    ),
  );
}

Widget gap() => const SizedBox(height: 16);

class InputSpec {
  const InputSpec(
    this.key,
    this.title, {
    this.min = 10,
    this.max = 2000,
    this.options,
    this.validator,
    this.lines = 1,
  });
  final String key, title;
  final int min, max, lines;
  final Map<int, String>? options;
  final String? Function(String?)? validator;
}

Future<Object?> editRecord(
  BuildContext context,
  String title,
  List<InputSpec> fields,
  Future<void> Function(Map<String, dynamic>) save,
) => showDialog<Object>(
  context: context,
  barrierDismissible: false,
  builder: (_) => _Editor(title, fields, save),
);

class _Editor extends StatefulWidget {
  const _Editor(this.title, this.fields, this.save);
  final String title;
  final List<InputSpec> fields;
  final Future<void> Function(Map<String, dynamic>) save;
  @override
  State<_Editor> createState() => _EditorState();
}

class _EditorState extends State<_Editor> {
  final form = GlobalKey<FormState>();
  late final controllers = {
    for (final field in widget.fields) field.key: TextEditingController(),
  };
  final Map<String, int> choices = {};
  bool busy = false;
  bool uncertain = false;
  String? error;
  @override
  void dispose() {
    for (final controller in controllers.values) {
      controller.dispose();
    }
    super.dispose();
  }

  Future<void> submit() async {
    if (!form.currentState!.validate()) return;
    setState(() {
      busy = true;
      error = null;
    });
    try {
      await widget.save({
        for (final field in widget.fields)
          field.key: field.options == null
              ? controllers[field.key]!.text.trim()
              : choices[field.key],
      });
      if (mounted) Navigator.pop(context, true);
    } on ApiFailure catch (failure) {
      if (!mounted) return;
      if (failure.status == 401 || failure.status == 419) {
        Navigator.pop(context, failure);
      } else {
        setState(() {
          busy = false;
          error = failure.message;
          // A timeout/server error can hide a committed write. Require a read first.
          uncertain = failure.status == 0 || failure.status >= 500;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) => PopScope(
    canPop: !busy,
    child: AlertDialog(
      title: Text(widget.title),
      content: SizedBox(
        width: 520,
        child: SingleChildScrollView(
          child: Form(
            key: form,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                for (final field in widget.fields) ...[
                  if (field.options != null)
                    DropdownButtonFormField<int>(
                      isExpanded: true,
                      decoration: InputDecoration(labelText: field.title),
                      items: field.options!.entries
                          .map(
                            (e) => DropdownMenuItem(
                              value: e.key,
                              child: Text(
                                e.value,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          )
                          .toList(),
                      onChanged: busy || uncertain
                          ? null
                          : (value) {
                              if (value != null) choices[field.key] = value;
                            },
                      validator: (value) =>
                          value == null ? 'Choose an option.' : null,
                    )
                  else
                    TextFormField(
                      controller: controllers[field.key],
                      enabled: !busy && !uncertain,
                      minLines: field.lines,
                      maxLines: field.lines,
                      maxLength: field.max,
                      decoration: InputDecoration(
                        labelText: field.title,
                        alignLabelWithHint: field.lines > 1,
                      ),
                      validator:
                          field.validator ??
                          (text) => textRule(text, field.min, field.max),
                    ),
                  gap(),
                ],
                if (error != null) Notice(error!, error: true),
                if (busy)
                  const LinearProgressIndicator(
                    semanticsLabel: 'Saving, please wait',
                  ),
              ],
            ),
          ),
        ),
      ),
      actions: [
        TextButton(
          onPressed: busy ? null : () => Navigator.pop(context),
          child: Text(uncertain ? 'Close and refresh' : 'Cancel'),
        ),
        FilledButton(
          onPressed: busy || uncertain ? null : submit,
          child: Text(busy ? 'Saving…' : 'Save'),
        ),
      ],
    ),
  );
}
