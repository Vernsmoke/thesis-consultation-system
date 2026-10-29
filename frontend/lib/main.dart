import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

// Public address only: never put private credentials in the web client.
const apiBaseUrl = String.fromEnvironment(
  'API_BASE_URL',
  defaultValue: 'http://127.0.0.1:8000/api',
);

void main() => runApp(const ConsultationApp());

class ConsultationApp extends StatelessWidget {
  const ConsultationApp({super.key, this.client});
  final http.Client? client;

  @override
  Widget build(BuildContext context) => MaterialApp(
    title: 'Thesis Consultation',
    theme: ThemeData(colorSchemeSeed: Colors.indigo, useMaterial3: true),
    home: FoundationScreen(client: client),
  );
}

class FoundationScreen extends StatefulWidget {
  const FoundationScreen({super.key, this.client});
  final http.Client? client;
  @override
  State<FoundationScreen> createState() => _FoundationScreenState();
}

class _FoundationScreenState extends State<FoundationScreen> {
  late final http.Client _client = widget.client ?? http.Client();
  bool _loading = false;
  String _status = 'Connection has not been checked.';

  @override
  void dispose() {
    if (widget.client == null) _client.close();
    super.dispose();
  }

  Future<void> _checkConnection() async {
    setState(() {
      _loading = true;
      _status = 'Checking the local API…';
    });
    String message;
    try {
      final response = await _client
          .get(Uri.parse('$apiBaseUrl/health'))
          .timeout(const Duration(seconds: 5));
      if (response.statusCode != 200) {
        message = 'The API returned an error. Check Laravel and retry.';
      } else {
        final data = jsonDecode(response.body);
        message =
            data is Map &&
                data['status'] == 'ok' &&
                data['project'] == 'Thesis Consultation Reminder System' &&
                data['week'] == 13 &&
                data['milestone'] == 'foundation'
            ? 'Connected to the Week 13 Laravel API.'
            : 'The API response did not match this project.';
      }
    } catch (_) {
      // Transport details belong in protected diagnostics, not user feedback.
      message = 'Could not reach the local API. Check the server and retry.';
    }
    if (!mounted) return;
    setState(() {
      _loading = false;
      _status = message;
    });
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Thesis Consultation')),
    body: SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 760),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                'Week 13 · Project 29',
                style: Theme.of(context).textTheme.labelLarge,
              ),
              const SizedBox(height: 12),
              Text(
                'Thesis Consultation Reminder System',
                style: Theme.of(context).textTheme.headlineMedium,
              ),
              const SizedBox(height: 16),
              const Text(
                'Foundation checkpoint. Consultation requests, schedules, '
                'agendas, meeting status, action items and follow-up '
                'are not implemented yet.',
              ),
              const SizedBox(height: 24),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Text(
                        'Local API connection',
                        style: Theme.of(context).textTheme.titleLarge,
                      ),
                      const SizedBox(height: 12),
                      Semantics(liveRegion: true, child: Text(_status)),
                      const SizedBox(height: 16),
                      FilledButton.icon(
                        onPressed: _loading ? null : _checkConnection,
                        icon: _loading
                            ? const SizedBox(
                                width: 18,
                                height: 18,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                ),
                              )
                            : const Icon(Icons.link),
                        label: Text(
                          _loading ? 'Checking…' : 'Check connection',
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 24),
              Text(
                'Required automations · pending',
                style: Theme.of(context).textTheme.titleMedium,
              ),
              const SizedBox(height: 8),
              const Text(
                'Consultation reminders\nMissed-session handling\n'
                'Action-item deadlines\nProgress summaries',
              ),
              const SizedBox(height: 24),
              const Text(
                'Controlled classroom prototype · fictional data only',
              ),
            ],
          ),
        ),
      ),
    ),
  );
}
