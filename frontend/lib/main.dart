import 'package:flutter/material.dart';

import 'api.dart';
import 'workspace.dart';

void main() => runApp(const ConsultationApp());

class ConsultationApp extends StatelessWidget {
  const ConsultationApp({super.key, this.api});
  final ConsultationApi? api;

  @override
  Widget build(BuildContext context) => MaterialApp(
    title: 'Thesis Consultation',
    debugShowCheckedModeBanner: false,
    theme: ThemeData(
      colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xff28574e)),
      scaffoldBackgroundColor: const Color(0xfff5f6f3),
      useMaterial3: true,
      inputDecorationTheme: const InputDecorationTheme(
        border: OutlineInputBorder(),
      ),
    ),
    home: Workspace(api: api),
  );
}
