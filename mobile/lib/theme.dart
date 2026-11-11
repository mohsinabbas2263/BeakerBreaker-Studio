import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// The prototype's design tokens (CLAUDE.md, "Design tokens from the prototype"),
/// so the app looks like the web portal rather than a second product.
class C {
  static const blue = Color(0xFF4FC3F7);
  static const blueD = Color(0xFF29B6F6);
  static const green = Color(0xFF4CAF82);
  static const greenD = Color(0xFF2E7D57);
  static const orange = Color(0xFFFF8A50);
  static const orangeD = Color(0xFFFF6D00);
  static const purple = Color(0xFF9575CD);
  static const yellow = Color(0xFFFFD54F);
  static const sky = Color(0xFFE8F4FD);
  static const mint = Color(0xFFC8F7E4);
  static const lemon = Color(0xFFFFF9C4);
  static const coral = Color(0xFFFFD6C8);
  static const ink = Color(0xFF2D3748);
  static const sub = Color(0xFF718096);
  static const muted = Color(0xFFA0AEC0);
  static const page = Color(0xFFF5F7FA);
}

/// The bouncy curve the prototype uses for its transitions.
const kBounce = Cubic(.68, -0.55, .27, 1.55);

const kCardRadius = BorderRadius.all(Radius.circular(18));
const kCardShadow = [BoxShadow(color: Color(0x1A000000), blurRadius: 32, offset: Offset(0, 8))];

TextStyle fredoka(double size, {Color color = C.ink, FontWeight weight = FontWeight.w600}) =>
    GoogleFonts.fredoka(fontSize: size, color: color, fontWeight: weight);

TextStyle nunito(double size, {Color color = C.ink, FontWeight weight = FontWeight.w700}) =>
    GoogleFonts.nunito(fontSize: size, color: color, fontWeight: weight);

ThemeData buildTheme() {
  final base = ThemeData(
    useMaterial3: true,
    colorScheme: ColorScheme.fromSeed(seedColor: C.blue, primary: C.blueD, secondary: C.green, surface: Colors.white),
    scaffoldBackgroundColor: C.page,
  );
  return base.copyWith(
    textTheme: base.textTheme.apply(fontFamily: GoogleFonts.nunito().fontFamily, bodyColor: C.ink, displayColor: C.ink),
    appBarTheme: AppBarTheme(
      backgroundColor: Colors.white,
      foregroundColor: C.ink,
      elevation: 0,
      scrolledUnderElevation: 1,
      titleTextStyle: fredoka(19),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: C.green,
        foregroundColor: Colors.white,
        minimumSize: const Size.fromHeight(52),
        shape: const RoundedRectangleBorder(borderRadius: BorderRadius.all(Radius.circular(16))),
        textStyle: GoogleFonts.fredoka(fontSize: 17, fontWeight: FontWeight.w600),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: C.sky,
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: BorderSide.none),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(16), borderSide: const BorderSide(color: C.blue, width: 2)),
    ),
  );
}

/// A white card with the prototype's radius and shadow.
class BBCard extends StatelessWidget {
  const BBCard({super.key, required this.child, this.padding = const EdgeInsets.all(16), this.color = Colors.white, this.onTap});
  final Widget child;
  final EdgeInsets padding;
  final Color color;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final box = Container(
      padding: padding,
      decoration: BoxDecoration(color: color, borderRadius: kCardRadius, boxShadow: kCardShadow),
      child: child,
    );
    if (onTap == null) return box;
    return Material(
      color: Colors.transparent,
      child: InkWell(borderRadius: kCardRadius, onTap: onTap, child: box),
    );
  }
}

/// A short, friendly message at the bottom of the screen (the web portal's toast).
void toast(BuildContext context, String message, {bool error = false}) {
  ScaffoldMessenger.of(context)
    ..hideCurrentSnackBar()
    ..showSnackBar(SnackBar(
      content: Text(message, style: nunito(14, color: Colors.white, weight: FontWeight.w800)),
      backgroundColor: error ? C.orangeD : C.greenD,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
    ));
}
