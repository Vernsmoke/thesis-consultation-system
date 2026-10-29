import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:thesis_consultation/api.dart';
import 'package:thesis_consultation/main.dart';
import 'package:thesis_consultation/ui.dart';

void main() {
  test('Manila conversion is independent of browser timezone and calendar is strict', () {
    expect(utcValue(parseManila('2026-10-30 08:15')!), '2026-10-30T00:15:00Z');
    expect(parseManila('2026-02-30 08:00'), isNull);
    expect(parseManila('2026-10-30 24:00'), isNull);
    expect(parseManila('2026-10-30T08:00'), isNull);
    expect(manilaTime('2026-10-29T23:30:00Z'), '2026-10-30 07:30 Manila');
  });

  test('write sends CSRF but 419 is never automatically retried', () async {
    var calls = 0;
    final api = ConsultationApi(
      readCsrf: () => 'synthetic-csrf',
      client: MockClient((request) async {
        calls++;
        expect(request.headers['X-XSRF-TOKEN'], 'synthetic-csrf');
        expect(jsonDecode(request.body)['agenda'], 'Fictional agenda');
        return http.Response('{}', 419);
      }),
    );
    addTearDown(api.close);
    await expectLater(
      api.call('POST', '/consultations', {'agenda': 'Fictional agenda'}),
      throwsA(isA<ApiFailure>().having((e) => e.status, 'status', 419)),
    );
    expect(calls, 1);
  });

  test('server diagnostics never appear in 503 feedback', () async {
    final api = ConsultationApi(
      client: MockClient((_) async => http.Response('secret SQL trace', 503)),
    );
    addTearDown(api.close);
    await expectLater(
      api.call('GET', '/consultations'),
      throwsA(
        isA<ApiFailure>().having(
          (e) => e.message,
          'message',
          isNot(contains('secret')),
        ),
      ),
    );
  });

  testWidgets('invalid login stays local and has readable field errors', (
    tester,
  ) async {
    var calls = 0;
    final api = ConsultationApi(
      client: MockClient((_) async {
        calls++;
        return http.Response('{}', 401);
      }),
    );
    addTearDown(api.close);
    await tester.pumpWidget(ConsultationApp(api: api));
    await tester.pumpAndSettle();
    await tester.tap(find.widgetWithText(FilledButton, 'Sign in'));
    await tester.pumpAndSettle();
    expect(find.text('Enter a valid email address.'), findsOneWidget);
    expect(find.text('Enter your password.'), findsOneWidget);
    expect(calls, 1); // Initial restore only, no login request.
  });

  testWidgets('cookie bootstrap then login reveals empty authorized list', (
    tester,
  ) async {
    final paths = <String>[];
    final api = ConsultationApi(
      readCsrf: () => 'synthetic-csrf',
      client: MockClient((request) async {
        paths.add(request.url.path);
        return switch (request.url.path) {
          '/api/me' => http.Response('{}', 401),
          '/sanctum/csrf-cookie' => http.Response('', 204),
          '/api/login' => http.Response(
            '{"data":{"id":1,"name":"Student Demo","role":"student"}}',
            200,
          ),
          '/api/consultations' => http.Response(
            '{"data":[],"last_page":1,"total":0}',
            200,
          ),
          _ => http.Response('{}', 404),
        };
      }),
    );
    addTearDown(api.close);
    await tester.pumpWidget(ConsultationApp(api: api));
    await tester.pumpAndSettle();
    await tester.enterText(
      find.byType(TextFormField).at(0),
      'student@example.test',
    );
    await tester.enterText(
      find.byType(TextFormField).at(1),
      'synthetic-password',
    );
    await tester.tap(find.widgetWithText(FilledButton, 'Sign in'));
    await tester.pumpAndSettle();
    expect(paths, [
      '/api/me',
      '/sanctum/csrf-cookie',
      '/api/login',
      '/api/consultations',
    ]);
    expect(find.text('No records to show in this area.'), findsOneWidget);
    expect(find.text('Request consultation'), findsOneWidget);
    expect(find.text('Automation review'), findsNothing);
  });
}
