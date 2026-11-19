import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../api/api.dart';
import '../auth.dart';
import '../theme.dart';

/// wf_S01. FR-01.6: no "create account" here; the school's administrator makes
/// accounts. A forgotten password is reset from the web portal's link.
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _busy = false;
  bool _hide = true;
  String? _error;

  Future<void> _submit() async {
    if (_email.text.trim().isEmpty || _password.text.isEmpty) {
      setState(() => _error = 'Enter your email and password.');
      return;
    }
    setState(() { _busy = true; _error = null; });
    try {
      await context.read<Auth>().login(_email.text.trim(), _password.text);
    } on ApiError catch (e) {
      // FR-01.4 / FR-01.5: the server's own words for a wrong password or a lockout.
      setState(() => _error = e.message);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: [C.sky, C.mint, C.lemon]),
        ),
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 400),
                child: Column(children: [
                  Semantics(label: 'Beaker Breakers Studio', child: Image.asset('assets/brand/logo.png', width: 150)),
                  const SizedBox(height: 6),
                  Text('Virtual Chemistry Lab', style: nunito(14, color: C.sub)),
                  const SizedBox(height: 24),
                  BBCard(
                    padding: const EdgeInsets.all(22),
                    child: AutofillGroup(
                      child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
                        Text('Sign In to Your Lab', textAlign: TextAlign.center, style: fredoka(20)),
                        const SizedBox(height: 18),
                        TextField(
                          controller: _email,
                          keyboardType: TextInputType.emailAddress,
                          autofillHints: const [AutofillHints.email],
                          textInputAction: TextInputAction.next,
                          decoration: const InputDecoration(labelText: 'Email address', hintText: 'student@school.edu.pk'),
                        ),
                        const SizedBox(height: 12),
                        TextField(
                          controller: _password,
                          obscureText: _hide,
                          autofillHints: const [AutofillHints.password],
                          onSubmitted: (_) => _submit(),
                          decoration: InputDecoration(
                            labelText: 'Password',
                            suffixIcon: IconButton(
                              tooltip: _hide ? 'Show password' : 'Hide password',
                              icon: Icon(_hide ? Icons.visibility : Icons.visibility_off),
                              onPressed: () => setState(() => _hide = !_hide),
                            ),
                          ),
                        ),
                        if (_error != null) ...[
                          const SizedBox(height: 12),
                          Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(color: C.coral, borderRadius: BorderRadius.circular(14)),
                            child: Text(_error!, style: nunito(13)),
                          ),
                        ],
                        const SizedBox(height: 18),
                        FilledButton(
                          style: FilledButton.styleFrom(backgroundColor: C.blueD),
                          onPressed: _busy ? null : _submit,
                          child: Text(_busy ? 'Signing in…' : 'Enter the Lab 🔬'),
                        ),
                        const SizedBox(height: 14),
                        Text('Accounts are created by your school administrator.\nForgot your password? Reset it on the web portal.',
                            textAlign: TextAlign.center, style: nunito(12, color: C.sub)),
                      ]),
                    ),
                  ),
                ]),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
