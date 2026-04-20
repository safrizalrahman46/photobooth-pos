import 'package:desktop_flutter/features/auth/presentation/login_page.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter/material.dart';

void main() {
  testWidgets('login page renders desktop fields', (WidgetTester tester) async {
    await tester.pumpWidget(MaterialApp(home: LoginPage(onLoggedIn: (_) {})));

    expect(find.text('Masuk Desktop App'), findsOneWidget);
    expect(find.text('Login'), findsOneWidget);
    expect(find.text('API Base URL'), findsOneWidget);
  });
}
