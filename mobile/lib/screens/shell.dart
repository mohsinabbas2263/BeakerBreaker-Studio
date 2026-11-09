import 'package:flutter/material.dart';

import '../theme.dart';
import 'experiments_screen.dart';
import 'home_screen.dart';
import 'leaderboard_screen.dart';
import 'profile_screen.dart';
import 'safety_screen.dart';

/// The bottom navigation of the prototype on phones: Home, Labs, Safety, Ranks, Me.
class Shell extends StatefulWidget {
  const Shell({super.key});

  static void goTo(BuildContext context, int tab) => context.findAncestorStateOfType<_ShellState>()?.go(tab);

  @override
  State<Shell> createState() => _ShellState();
}

class _ShellState extends State<Shell> {
  int _tab = 0;
  final _keys = List.generate(5, (_) => GlobalKey());

  void go(int tab) => setState(() => _tab = tab);

  @override
  Widget build(BuildContext context) {
    final pages = [
      HomeScreen(key: _keys[0]),
      ExperimentsScreen(key: _keys[1]),
      SafetyScreen(key: _keys[2]),
      LeaderboardScreen(key: _keys[3]),
      ProfileScreen(key: _keys[4]),
    ];
    return Scaffold(
      body: IndexedStack(index: _tab, children: [
        for (var i = 0; i < pages.length; i++) i == _tab ? pages[i] : const SizedBox.shrink(),
      ]),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: go,
        backgroundColor: Colors.white,
        indicatorColor: C.sky,
        destinations: const [
          NavigationDestination(icon: Text('🏠', style: TextStyle(fontSize: 22)), label: 'Home'),
          NavigationDestination(icon: Text('🔬', style: TextStyle(fontSize: 22)), label: 'Labs'),
          NavigationDestination(icon: Text('🛡️', style: TextStyle(fontSize: 22)), label: 'Safety'),
          NavigationDestination(icon: Text('🏆', style: TextStyle(fontSize: 22)), label: 'Ranks'),
          NavigationDestination(icon: Text('👤', style: TextStyle(fontSize: 22)), label: 'Me'),
        ],
      ),
    );
  }
}
