import 'dart:async';
import 'dart:convert';

import 'package:http/http.dart' as http;

import 'browser_transport.dart';

const apiBaseUrl = String.fromEnvironment(
  'API_BASE_URL',
  defaultValue: 'http://127.0.0.1:8000/api',
);

class ApiFailure implements Exception {
  ApiFailure(this.message, {this.status = 0});
  final String message;
  final int status;
}

class ConsultationApi {
  ConsultationApi({http.Client? client, String Function()? readCsrf})
    : _client = client ?? createBrowserClient(),
      _readCsrf = readCsrf ?? csrfCookie;

  final http.Client _client;
  final String Function() _readCsrf;
  void close() => _client.close();

  Future<Map<String, dynamic>> call(
    String method,
    String path, [
    Map<String, dynamic>? data,
  ]) async {
    final headers = {'Accept': 'application/json'};
    if (method != 'GET') {
      headers['Content-Type'] = 'application/json';
      headers['X-XSRF-TOKEN'] = _readCsrf();
    }
    final uri = path == '/sanctum/csrf-cookie'
        ? Uri.parse(apiBaseUrl).replace(path: path, query: null)
        : Uri.parse('$apiBaseUrl$path');
    try {
      final request = http.Request(method, uri)..headers.addAll(headers);
      if (data != null) request.body = jsonEncode(data);
      final response = await _client
          .send(request)
          .then(http.Response.fromStream)
          .timeout(const Duration(seconds: 10));
      if (response.statusCode == 204) return {};
      Map<String, dynamic> body = {};
      try {
        final decoded = jsonDecode(response.body);
        if (decoded is Map<String, dynamic>) body = decoded;
      } catch (_) {
        // Never display a proxy error page, raw trace, or request contents.
      }
      final status = response.statusCode;
      if (status >= 200 && status < 300) {
        if (body.isEmpty) {
          throw ApiFailure('The server returned an unexpected response.');
        }
        return body;
      }
      final message = switch (status) {
        401 =>
          'Sign in again. Your session ended or the credentials are invalid.',
        403 => 'You do not have permission to access this record or action.',
        404 => 'That record was not found. Refresh the list.',
        409 => 'This action conflicts with the current record. Refresh and review the schedule, status or recovery limit.',
        419 => 'Your session security check expired. Reload the page and sign in again.',
        422 => _validationMessage(body),
        429 => 'Too many sign-in attempts. Wait one minute before retrying.',
        _ => 'The service is unavailable. Retry reads later; check the current record before repeating a write.',
      };
      throw ApiFailure(message, status: status);
    } on TimeoutException {
      throw ApiFailure(
        'The request timed out. Its result may be uncertain. Refresh the record before repeating a write.',
      );
    } on http.ClientException {
      throw ApiFailure(
        'Could not reach the service. Check the connection, then refresh before repeating a write.',
      );
    }
  }

  String _validationMessage(Map<String, dynamic> body) {
    final errors = body['errors'];
    if (errors is Map) {
      final messages = errors.values
          .whereType<List>()
          .expand((e) => e)
          .whereType<String>()
          .take(4);
      if (messages.isNotEmpty) return messages.join('\n');
    }
    return 'Please check the form values.';
  }
}
