import 'package:http/browser_client.dart';
import 'package:http/http.dart' as http;
import 'package:web/web.dart' as web;

http.Client createBrowserClient() => BrowserClient()..withCredentials = true;

String csrfCookie() {
  // Only the public CSRF cookie is readable; the session remains HttpOnly.
  for (final cookie in web.document.cookie.split(';')) {
    final pair = cookie.trim().split('=');
    if (pair.first == 'XSRF-TOKEN' && pair.length > 1) {
      return Uri.decodeComponent(pair.sublist(1).join('='));
    }
  }
  return '';
}
