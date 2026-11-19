import 'package:flutter/material.dart';

import '../api/api.dart';
import '../theme.dart';

/// The four profile avatars; the keys match User::AVATARS on the server.
const kAvatars = ['avatar-girl1', 'avatar-girl2', 'avatar-boy1', 'avatar-boy2'];

String avatarAsset(String? key) => 'assets/avatars/${kAvatars.contains(key) ? key : kAvatars.first}.png';

class Avatar extends StatelessWidget {
  const Avatar(this.avatarKey, {super.key, this.size = 48, this.border = true});
  final String? avatarKey;
  final double size;
  final bool border;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        border: border ? Border.all(color: Colors.white, width: size > 56 ? 4 : 3) : null,
        boxShadow: border ? kCardShadow : null,
      ),
      child: ClipOval(child: Image.asset(avatarAsset(avatarKey), fit: BoxFit.cover)),
    );
  }
}

/// Loads a JSON endpoint and shows a spinner, an error with Retry, or the content.
class Loader extends StatefulWidget {
  const Loader({super.key, required this.load, required this.builder});
  final Future<Json> Function() load;
  final Widget Function(BuildContext context, Json data, Future<void> Function() reload) builder;

  @override
  State<Loader> createState() => _LoaderState();
}

class _LoaderState extends State<Loader> {
  Json? _data;
  ApiError? _error;

  @override
  void initState() {
    super.initState();
    _reload();
  }

  Future<void> _reload() async {
    try {
      final d = await widget.load();
      if (mounted) setState(() { _data = d; _error = null; });
    } on ApiError catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_error != null && _data == null) {
      return ErrorView(message: _error!.message, onRetry: () { setState(() => _error = null); _reload(); });
    }
    if (_data == null) return const Spinner();
    return widget.builder(context, _data!, _reload);
  }
}

class Spinner extends StatelessWidget {
  const Spinner({super.key});
  @override
  Widget build(BuildContext context) => const Center(child: Padding(padding: EdgeInsets.all(32), child: CircularProgressIndicator(color: C.blueD)));
}

class ErrorView extends StatelessWidget {
  const ErrorView({super.key, required this.message, this.onRetry});
  final String message;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          const Text('⚠️', style: TextStyle(fontSize: 40)),
          const SizedBox(height: 8),
          Text(message, textAlign: TextAlign.center, style: fredoka(17)),
          if (onRetry != null) ...[
            const SizedBox(height: 12),
            TextButton(onPressed: onRetry, child: Text('Try again', style: nunito(14, color: C.blueD, weight: FontWeight.w900))),
          ],
        ]),
      ),
    );
  }
}

class Pill extends StatelessWidget {
  const Pill(this.text, {super.key, this.bg = C.sky, this.fg = C.blueD});
  final String text;
  final Color bg;
  final Color fg;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(20)),
        child: Text(text, style: nunito(11, color: fg, weight: FontWeight.w900)),
      );
}

class ProgressBar extends StatelessWidget {
  const ProgressBar(this.value, {super.key, this.height = 10, this.color});
  final double value;
  final double height;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(height),
      child: Container(
        height: height,
        color: C.sky,
        alignment: Alignment.centerLeft,
        child: FractionallySizedBox(
          widthFactor: value.clamp(0, 1),
          child: Container(
            decoration: BoxDecoration(
              color: color,
              gradient: color == null ? const LinearGradient(colors: [C.blue, C.green]) : null,
            ),
          ),
        ),
      ),
    );
  }
}

/// FR-06.3: say so when the server reports new badges.
void announceBadges(BuildContext context, dynamic badges) {
  if (badges is! List || badges.isEmpty) return;
  final names = badges.map((b) => '${b['icon']} ${b['name']}').join(', ');
  toast(context, 'New badge! $names');
}
