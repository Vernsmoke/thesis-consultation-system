import 'package:flutter/material.dart';

import 'ui.dart';

typedef EditAction = Future<void> Function(
  String title,
  List<InputSpec> fields,
  String method,
  String path, {
  bool dates,
});

class ConsultationDetails extends StatelessWidget {
  const ConsultationDetails({
    super.key,
    required this.data,
    required this.user,
    required this.busy,
    required this.edit,
    required this.complete,
  });
  final Map<String, dynamic> data, user;
  final bool busy;
  final EditAction edit;
  final Future<void> Function(int) complete;

  @override
  Widget build(BuildContext context) {
    final id = data['id'];
    final assignedAdviser =
        user['role'] == 'adviser' && user['id'] == data['adviser_id'];
    final owningStudent =
        user['role'] == 'student' && user['id'] == data['student_id'];
    final terminal = ['completed', 'missed'].contains(data['status']);
    final end = DateTime.tryParse(data['ends_at'] ?? '');
    final mayClose =
        assignedAdviser &&
        data['status'] == 'confirmed' &&
        end != null &&
        !end.isAfter(DateTime.now().toUtc());
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Section('Consultation #$id · ${label(data['status'])}', [
          SelectableText('Reference: ${data['reference']}'),
          gap(),
          Text(
            'Student: ${data['student']?['name']}\nAdviser: ${data['adviser']?['name']}',
          ),
          if ((data['panel_members'] as List? ?? []).isNotEmpty)
            Text(
              'Panel: ${(data['panel_members'] as List).map((p) => p['name']).join(', ')}',
            ),
          gap(),
          const Text('Agenda', style: TextStyle(fontWeight: FontWeight.bold)),
          Text(data['agenda'] ?? ''),
          gap(),
          Text(
            'Start: ${manilaTime(data['starts_at'])}\nEnd: ${manilaTime(data['ends_at'])}\nSchedule version: ${data['schedule_version']}',
          ),
          if (data['meeting_notes'] != null) ...[
            gap(),
            const Text(
              'Meeting notes',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
            Text(data['meeting_notes']),
          ],
          if (assignedAdviser && data['status'] == 'requested') ...[
            gap(),
            FilledButton(
              onPressed: busy
                  ? null
                  : () => edit(
                      'Confirm schedule',
                      [
                        InputSpec(
                          'starts_at',
                          'Start · YYYY-MM-DD HH:mm Manila',
                          min: 16,
                          max: 16,
                          validator: futureRule,
                        ),
                        InputSpec(
                          'ends_at',
                          'End · YYYY-MM-DD HH:mm Manila',
                          min: 16,
                          max: 16,
                          validator: futureRule,
                        ),
                      ],
                      'PATCH',
                      '/consultations/$id/schedule',
                      dates: true,
                    ),
              child: const Text('Confirm schedule'),
            ),
          ],
          if (mayClose) ...[
            gap(),
            FilledButton(
              onPressed: busy
                  ? null
                  : () => edit(
                      'Record meeting outcome',
                      [
                        const InputSpec(
                          'status',
                          'Outcome',
                          options: {1: 'Completed', 2: 'Missed'},
                        ),
                        const InputSpec(
                          'meeting_notes',
                          'Meeting notes',
                          lines: 4,
                        ),
                      ],
                      'PATCH',
                      '/consultations/$id/status',
                    ),
              child: const Text('Record meeting outcome'),
            ),
          ],
          if (assignedAdviser &&
              data['status'] == 'confirmed' &&
              !mayClose) ...[
            gap(),
            const Text(
              'Record an outcome after the scheduled end. Refresh when the meeting has ended.',
            ),
          ],
          if (data['status'] == 'missed') ...[
            gap(),
            const Notice(
              'A missed status may be inferred from an elapsed schedule without a recorded outcome. It is not attendance proof. Ask the coordinator to review; no correction endpoint exists in this prototype.',
            ),
          ],
          if (!assignedAdviser && !owningStudent) ...[
            gap(),
            const Text('Read-only consultation access.'),
          ],
        ]),
        Section('Action items', [
          if ((data['action_items'] as List).isEmpty)
            const Text('No action items yet.'),
          for (final item in data['action_items']) ...[
            Text(
              '#${item['id']} · ${item['description']}',
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            Text('Due: ${manilaTime(item['due_at'])}'),
            Text(
              item['completed_at'] == null
                  ? 'Open${DateTime.parse(item['due_at']).isBefore(DateTime.now().toUtc()) ? ' · overdue' : ''}'
                  : 'Completed: ${manilaTime(item['completed_at'])}',
            ),
            if (owningStudent &&
                data['status'] == 'completed' &&
                item['completed_at'] == null)
              Align(
                alignment: Alignment.centerLeft,
                child: OutlinedButton(
                  onPressed: busy ? null : () => complete(item['id']),
                  child: Text('Complete action #${item['id']}'),
                ),
              ),
            gap(),
          ],
          if (assignedAdviser && data['status'] == 'completed')
            FilledButton(
              onPressed: busy
                  ? null
                  : () => edit(
                      'Assign action item',
                      [
                        const InputSpec(
                          'description',
                          'Action item',
                          max: 500,
                          lines: 3,
                        ),
                        InputSpec(
                          'due_at',
                          'Due · YYYY-MM-DD HH:mm Manila',
                          min: 16,
                          max: 16,
                          validator: futureRule,
                        ),
                      ],
                      'POST',
                      '/consultations/$id/action-items',
                      dates: true,
                    ),
              child: const Text('Assign action item'),
            ),
        ]),
        Section('Follow-up', [
          if ((data['follow_ups'] as List).isEmpty)
            const Text('No follow-up notes yet.'),
          for (final note in data['follow_ups']) ...[
            Text(
              '${note['author']?['name']} · ${manilaTime(note['created_at'])}',
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            Text(note['message']),
            gap(),
          ],
          if (terminal && (assignedAdviser || owningStudent))
            FilledButton(
              onPressed: busy
                  ? null
                  : () => edit(
                      'Add follow-up',
                      [
                        const InputSpec(
                          'message',
                          'Follow-up message',
                          lines: 4,
                        ),
                      ],
                      'POST',
                      '/consultations/$id/follow-ups',
                    ),
              child: const Text('Add follow-up'),
            ),
          if (!terminal)
            const Text(
              'Follow-up is available after a completed or missed consultation.',
            ),
        ]),
      ],
    );
  }
}

class AutomationDetails extends StatelessWidget {
  const AutomationDetails({
    super.key,
    required this.response,
    required this.coordinator,
    required this.busy,
    required this.edit,
  });
  final Map<String, dynamic> response;
  final bool coordinator, busy;
  final EditAction edit;
  @override
  Widget build(BuildContext context) {
    final data = response['data'];
    final payload = data['payload'];
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Section('Work #${data['id']} · ${label(data['kind'])}', [
          Text(
            'Status: ${label(data['status'])}',
            style: Theme.of(context).textTheme.titleMedium,
          ),
          Text(
            'Recipient #${data['recipient_id']} · Review owner #${data['review_owner_id']}',
          ),
          gap(),
          Text(
            'Scheduled: ${manilaTime(data['scheduled_for'])}\nNext eligible: ${manilaTime(data['available_at'])}\nCycle: ${data['cycle']} of 3 · Attempts this cycle: ${data['attempts']} of 3\nTotal attempts: ${data['total_attempts']}',
          ),
          if (data['failure_code'] != null)
            Text('Failure: ${label(data['failure_code'])}'),
          if (data['receipt'] != null) ...[
            gap(),
            SelectableText('Receiver receipt: ${data['receipt']}'),
          ],
          gap(),
          SelectableText('Correlation: ${data['correlation_id']}'),
          gap(),
          if (data['kind'] == 'progress_summary') ...[
            Text(
              'Adviser #${payload['adviser_id']} · Manila snapshot ${payload['local_date']}',
            ),
            for (final entry in (payload['counts'] as Map).entries)
              Text('${label(entry.key)}: ${entry.value}'),
            const Text(
              'First-scan daily snapshot, not an end-of-day or academic evaluation.',
            ),
          ] else ...[
            if (data['consultation_id'] != null)
              Text('Consultation #${data['consultation_id']}'),
            if (data['action_item_id'] != null)
              Text('Action item #${data['action_item_id']}'),
            if (data['kind'] == 'missed_session')
              const Notice(
                'Elapsed schedule without recorded outcome: human review required. This is an inference, not attendance proof.',
              ),
          ],
          if (coordinator && data['status'] == 'needs_review') ...[
            gap(),
            const Notice(
              'Inspect the source and dependency first. Recovery queues the same work identity and retains history. It does not start delivery or guarantee success.',
            ),
            if (data['cycle'] < 3)
              FilledButton(
                onPressed: busy
                    ? null
                    : () => edit(
                        'Authorize recovery',
                        [
                          const InputSpec(
                            'reason',
                            'Diagnosis and recovery reason',
                            max: 500,
                            lines: 4,
                          ),
                        ],
                        'POST',
                        '/automation/${data['id']}/recover',
                      ),
                child: const Text('Authorize recovery'),
              )
            else
              const Text(
                'Recovery cycle limit reached. Maintainer review is required.',
              ),
          ],
        ]),
        Section('Attempt history', [
          if ((response['attempts'] as List).isEmpty)
            const Text('No service attempt has been made.'),
          for (final attempt in response['attempts']) ...[
            Text(
              'Cycle ${attempt['cycle']} · attempt ${attempt['number']} · ${label(attempt['outcome'])}',
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            Text(
              '${label(attempt['code'] ?? 'in progress')} · HTTP ${attempt['http_status'] ?? 'no response'} · ${attempt['duration_ms'] ?? '—'} ms',
            ),
            Text(
              'Started: ${manilaTime(attempt['started_at'] == null ? null : '${attempt['started_at']}Z')}',
            ),
            gap(),
          ],
        ]),
        if (coordinator)
          Section('Recovery history', [
            if ((response['recoveries'] as List).isEmpty)
              const Text('No recovery has been authorized.'),
            for (final recovery in response['recoveries']) ...[
              Text(
                'Actor #${recovery['actor_id']} · cycle ${recovery['cycle']}',
              ),
              Text(recovery['reason']),
              gap(),
            ],
          ]),
      ],
    );
  }
}
