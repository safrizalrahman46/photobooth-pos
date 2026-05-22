import 'package:desktop_flutter/features/auth/presentation/login_page.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  testWidgets('login page renders desktop fields', (WidgetTester tester) async {
    await tester.pumpWidget(MaterialApp(home: LoginPage(onLoggedIn: (_) {})));

    expect(find.text('Masuk'), findsWidgets);
    expect(find.text('Masuk Admin'), findsOneWidget);
    expect(find.text('Ready To Pict'), findsWidgets);
    expect(find.text('Email'), findsOneWidget);
    expect(find.text('Password'), findsOneWidget);
  });
}
