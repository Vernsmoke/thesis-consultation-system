import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:thesis_consultation/main.dart';

void main() {
  testWidgets('checks project identity and shows success', (tester) async {
    final client = MockClient((request) async {
      expect(request.url.path, '/api/health');
      return http.Response(
        '{"status":"ok","project":"Thesis Consultation Reminder System",'
        '"week":13,"milestone":"foundation"}',
        200,
      );
    });
    addTearDown(client.close);
    await tester.pumpWidget(ConsultationApp(client: client));
    await tester.tap(find.text('Check connection'));
    await tester.pumpAndSettle();
    expect(find.text('Connected to the Week 13 Laravel API.'), findsOneWidget);
  });

  testWidgets('a service error leaves an enabled retry button', (tester) async {
    final client = MockClient((_) async => http.Response('unavailable', 503));
    addTearDown(client.close);
    await tester.pumpWidget(ConsultationApp(client: client));
    await tester.tap(find.text('Check connection'));
    await tester.pumpAndSettle();
    expect(find.textContaining('The API returned an error.'), findsOneWidget);
    expect(
      tester.widget<FilledButton>(find.byType(FilledButton)).onPressed,
      isNotNull,
    );
  });
}
