import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'auth.dart';
import 'screens/login_screen.dart';
import 'screens/shell.dart';
import 'theme.dart';

/// Beaker Breakers Studio, the student app for Android and iOS (Chapter 1, "Nature
/// of the End Product"): Game Mode and AR Mode over the same Laravel API as the web
/// portal, so every score is still computed on the server (FR-02.5).
void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(ChangeNotifierProvider(create: (_) => Auth()..restore(), child: const BeakerBreakersApp()));
}

class BeakerBreakersApp extends StatelessWidget {
  const BeakerBreakersApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Beaker Breakers Studio',
      debugShowCheckedModeBanner: false,
      theme: buildTheme(),
      home: Consumer<Auth>(
        builder: (context, auth, _) {
          if (auth.loading) return const Splash();
          return auth.signedIn ? const Shell() : const LoginScreen();
        },
      ),
    );
  }
}

class Splash extends StatelessWidget {
  const Splash({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(child: Image.asset('assets/brand/mark.png', width: 72)),
    );
  }
}
