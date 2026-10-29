import 'package:flutter/material.dart';

import 'api.dart';
import 'details.dart';
import 'ui.dart';

class Workspace extends StatefulWidget {
  const Workspace({super.key, this.api});
  final ConsultationApi? api;
  @override
  State<Workspace> createState() => _WorkspaceState();
}

class _WorkspaceState extends State<Workspace> {
  late final api = widget.api ?? ConsultationApi();
  final email = TextEditingController();
  final password = TextEditingController();
  final recordId = TextEditingController();
  final loginForm = GlobalKey<FormState>();
  Map<String, dynamic>? user, detail;
  List<dynamic> records = [];
  bool busy = true, error = false;
  String? notice;
  String area = 'consultations';
  int page = 1, lastPage = 1, total = 0;

  @override
  void initState() {
    super.initState();
    restore();
  }

  @override
  void dispose() {
    email.dispose();
    password.dispose();
    recordId.dispose();
    if (widget.api == null) api.close();
    super.dispose();
  }

  void showFailure(ApiFailure failure) {
    if (!mounted) return;
    setState(() {
      busy = false;
      error = true;
      notice = failure.message;
      if (failure.status == 401 || failure.status == 419) {
        user = null;
        detail = null;
        records = [];
        password.clear();
        area = 'consultations';
      }
    });
  }

  Future<void> restore() async {
    try {
      final response = await api.call('GET', '/me');
      if (!mounted) return;
      setState(() => user = response['data']);
      await load();
    } on ApiFailure catch (failure) {
      if (failure.status == 401) {
        if (mounted) setState(() => busy = false);
      } else {
        showFailure(failure);
      }
    }
  }

  Future<void> login() async {
    if (!loginForm.currentState!.validate()) return;
    setState(() {
      busy = true;
      notice = null;
    });
    try {
      await api.call('GET', '/sanctum/csrf-cookie');
      final result = await api.call('POST', '/login', {
        'email': email.text.trim(),
        'password': password.text,
      });
      password.clear();
      if (!mounted) return;
      setState(() {
        user = result['data'];
        area = 'consultations';
        page = 1;
      });
      await load(message: 'Signed in. Only your permitted records are shown.');
    } on ApiFailure catch (failure) {
      password.clear();
      showFailure(failure);
    }
  }

  Future<void> logout() async {
    setState(() => busy = true);
    try {
      await api.call('POST', '/logout', {});
      if (!mounted) return;
      setState(() {
        user = null;
        records = [];
        detail = null;
        busy = false;
        notice = 'Signed out.';
        error = false;
      });
    } on ApiFailure catch (failure) {
      showFailure(failure);
    }
  }

  Future<void> load({String? message}) async {
    setState(() {
      busy = true;
      detail = null;
      records = [];
      notice = null;
      error = false;
    });
    try {
      final result = await api.call('GET', '/$area?page=$page');
      if (!mounted) return;
      setState(() {
        records = result['data'] as List;
        lastPage = result['last_page'] as int;
        total = result['total'] as int;
        busy = false;
        notice = message;
      });
    } on ApiFailure catch (failure) {
      showFailure(failure);
    }
  }

  Future<void> open(int id, {String? message}) async {
    setState(() {
      busy = true;
      detail = null;
      notice = null;
      error = false;
    });
    try {
      final result = await api.call(
        'GET',
        '/${area == 'consultations' ? 'consultations' : 'automation'}/$id',
      );
      if (!mounted) return;
      setState(() {
        detail = result;
        busy = false;
        notice = message;
      });
    } on ApiFailure catch (failure) {
      showFailure(failure);
    }
  }

  Future<void> edit(
    String title,
    List<InputSpec> fields,
    String method,
    String path, {
    bool dates = false,
  }) async {
    final result = await editRecord(context, title, fields, (values) async {
      if (dates) {
        for (final key in ['starts_at', 'ends_at', 'due_at']) {
          if (values.containsKey(key)) {
            values[key] = utcValue(parseManila(values[key])!);
          }
        }
      }
      if (values.containsKey('starts_at') &&
          !DateTime.parse(values['ends_at'])
              .isAfter(DateTime.parse(values['starts_at']))) {
        throw ApiFailure('End time must be after start time.', status: 422);
      }
      if (values.containsKey('status')) {
        values['status'] = values['status'] == 1 ? 'completed' : 'missed';
      }
      await api.call(method, path, values);
    });
    if (!mounted) return;
    if (result is ApiFailure) {
      showFailure(result);
      return;
    }
    if (result == true) {
      if (detail != null) {
        await open(
          detail!['data']['id'],
          message: 'Saved. Current record refreshed.',
        );
      } else {
        page = 1;
        await load(
          message: 'Consultation requested. Waiting for adviser confirmation.',
        );
      }
    }
  }

  Future<void> request() async {
    setState(() => busy = true);
    try {
      final response = await api.call('GET', '/advisers');
      if (!mounted) return;
      setState(() => busy = false);
      final advisers = {
        for (final adviser in response['data'])
          adviser['id'] as int: adviser['name'] as String,
      };
      if (advisers.isEmpty) {
        setState(() {
          notice = 'No advisers are available in the directory. Ask the coordinator.';
          error = true;
        });
        return;
      }
      await edit(
        'Request consultation',
        [
          InputSpec('adviser_id', 'Adviser', options: advisers),
          const InputSpec('agenda', 'Agenda', lines: 4),
        ],
        'POST',
        '/consultations',
      );
    } on ApiFailure catch (failure) {
      showFailure(failure);
    }
  }

  Future<void> complete(int id) async {
    setState(() {
      busy = true;
      notice = null;
    });
    try {
      await api.call('PATCH', '/action-items/$id/complete', {});
      if (mounted) {
        await open(detail!['data']['id'], message: 'Action item completed.');
      }
    } on ApiFailure catch (failure) {
      showFailure(failure);
    }
  }

  Widget loginView() => Center(
    child: ConstrainedBox(
      constraints: const BoxConstraints(maxWidth: 480),
      child: Section('Sign in', [
        const Text(
          'Use your assigned account. Your role is determined by the server.',
        ),
        gap(),
        Form(
          key: loginForm,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              TextFormField(
                controller: email,
                enabled: !busy,
                keyboardType: TextInputType.emailAddress,
                autofillHints: const [AutofillHints.username],
                decoration: const InputDecoration(labelText: 'Email'),
                validator: (value) =>
                    RegExp(r'^[^\s@]+@[^\s@]+\.[^\s@]+$')
                        .hasMatch(value?.trim() ?? '')
                    ? null
                    : 'Enter a valid email address.',
              ),
              gap(),
              TextFormField(
                controller: password,
                enabled: !busy,
                obscureText: true,
                enableSuggestions: false,
                autocorrect: false,
                decoration: const InputDecoration(labelText: 'Password'),
                validator: (value) =>
                    (value ?? '').isEmpty ? 'Enter your password.' : null,
                onFieldSubmitted: (_) {
                  if (!busy) login();
                },
              ),
              gap(),
              FilledButton(
                onPressed: busy ? null : login,
                child: const Text('Sign in'),
              ),
            ],
          ),
        ),
      ]),
    ),
  );

  Widget navigation() => Wrap(
    spacing: 8,
    runSpacing: 8,
    children: [
      for (final target in [
        'consultations',
        if (user!['role'] != 'panel') 'notifications',
        if (user!['role'] == 'coordinator') 'automation',
      ])
        ChoiceChip(
          label: Text(
            target == 'automation'
                ? 'Automation review'
                : target == 'notifications'
                ? 'Notifications'
                : 'Consultations',
          ),
          selected: area == target,
          onSelected: busy
              ? null
              : (_) {
                  area = target;
                  page = 1;
                  load();
                },
        ),
    ],
  );

  Widget recordList() => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      Wrap(
        spacing: 12,
        runSpacing: 8,
        crossAxisAlignment: WrapCrossAlignment.center,
        children: [
          Text(
            area == 'automation'
                ? 'Automation review'
                : area == 'notifications'
                ? 'Accepted notifications'
                : 'Consultations',
            style: Theme.of(context).textTheme.headlineSmall,
          ),
          OutlinedButton.icon(
            onPressed: busy ? null : () => load(),
            icon: const Icon(Icons.refresh),
            label: const Text('Refresh'),
          ),
          if (area == 'consultations' && user!['role'] == 'student')
            FilledButton.icon(
              onPressed: busy ? null : request,
              icon: const Icon(Icons.add),
              label: const Text('Request consultation'),
            ),
        ],
      ),
      gap(),
      if (area != 'consultations')
        const Text(
          'Delivered means accepted by the controlled receiver. It does not mean email delivery or that a person read the message.',
        ),
      if (area == 'automation')
        const Text(
          'Review needs review items before recovery. Resolve the cause, record your reason, then wait for bounded delivery. This screen does not start a worker.',
        ),
      gap(),
      Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: TextField(
              controller: recordId,
              enabled: !busy,
              keyboardType: TextInputType.number,
              decoration: InputDecoration(
                labelText: area == 'consultations'
                    ? 'Consultation ID'
                    : 'Notification / work ID',
              ),
            ),
          ),
          const SizedBox(width: 12),
          OutlinedButton(
            onPressed: busy
                ? null
                : () {
                    final id = int.tryParse(recordId.text.trim());
                    if (id == null || id < 1) {
                      setState(() {
                        notice = 'Enter a positive record ID.';
                        error = true;
                      });
                    } else {
                      open(id);
                    }
                  },
            child: const Text('Open ID'),
          ),
        ],
      ),
      gap(),
      if (!busy && !error && records.isEmpty)
        const Notice('No records to show in this area.'),
      for (final row in records)
        Card(
          child: ListTile(
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 16,
              vertical: 8,
            ),
            title: Text(
              area == 'consultations'
                  ? 'Consultation #${row['id']} · ${label(row['status'])}'
                  : '#${row['id']} · ${label(row['kind'])} · ${label(row['status'])}',
            ),
            subtitle: Text(
              area == 'consultations'
                  ? '${row['student']?['name'] ?? 'Student'} / ${row['adviser']?['name'] ?? 'Adviser'}\n${manilaTime(row['starts_at'])}'
                  : 'Recipient #${row['recipient_id']} · ${manilaTime(row['scheduled_for'])}',
            ),
            trailing: const Icon(Icons.chevron_right),
            onTap: busy ? null : () => open(row['id']),
          ),
        ),
      gap(),
      Wrap(
        spacing: 12,
        crossAxisAlignment: WrapCrossAlignment.center,
        children: [
          OutlinedButton(
            onPressed: busy || page <= 1
                ? null
                : () {
                    page--;
                    load();
                  },
            child: const Text('Previous'),
          ),
          Text('Page $page of $lastPage · $total records'),
          OutlinedButton(
            onPressed: busy || page >= lastPage
                ? null
                : () {
                    page++;
                    load();
                  },
            child: const Text('Next'),
          ),
        ],
      ),
    ],
  );

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(
      title: const Text('Thesis Consultation'),
      actions: [
        if (user != null)
          TextButton(
            onPressed: busy ? null : logout,
            child: const Text('Sign out'),
          ),
      ],
    ),
    body: SingleChildScrollView(
      padding: EdgeInsets.all(MediaQuery.sizeOf(context).width < 600 ? 16 : 28),
      child: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 1040),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                'Week 13 · Project 29',
                style: Theme.of(context).textTheme.labelLarge,
              ),
              const Text(
                'Controlled classroom prototype · fictional data only',
              ),
              gap(),
              if (user != null) ...[
                Text(
                  '${user!['name']} · ${label(user!['role'])}',
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                gap(),
                navigation(),
                gap(),
              ],
              if (busy)
                const LinearProgressIndicator(
                  semanticsLabel: 'Loading, please wait',
                ),
              if (notice != null) Notice(notice!, error: error),
              if (user == null)
                loginView()
              else if (detail == null)
                recordList()
              else ...[
                Align(
                  alignment: Alignment.centerLeft,
                  child: TextButton.icon(
                    onPressed: busy ? null : () => load(),
                    icon: const Icon(Icons.arrow_back),
                    label: const Text('Back to list'),
                  ),
                ),
                if (area == 'consultations')
                  ConsultationDetails(
                    data: detail!['data'],
                    user: user!,
                    busy: busy,
                    edit: edit,
                    complete: complete,
                  )
                else
                  AutomationDetails(
                    response: detail!,
                    coordinator: user!['role'] == 'coordinator',
                    busy: busy,
                    edit: edit,
                  ),
                OutlinedButton.icon(
                  onPressed: busy ? null : () => open(detail!['data']['id']),
                  icon: const Icon(Icons.refresh),
                  label: const Text('Refresh record'),
                ),
              ],
              gap(),
              const Text(
                'All displayed dates use Asia/Manila (UTC+8). Server permissions and validation apply.',
              ),
            ],
          ),
        ),
      ),
    ),
  );
}
