import 'dart:async';
import 'dart:io' show Platform;
import 'dart:math' as math;

import 'package:arcore_flutter_plus/arcore_flutter_plus.dart';
import 'package:arkit_plugin/arkit_plugin.dart';
import 'package:flutter/material.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:provider/provider.dart';
import 'package:vector_math/vector_math_64.dart' as vm;

import '../api/api.dart';
import '../auth.dart';
import '../game/game_runner.dart';
import '../game/game_step.dart';
import '../game/guide.dart';
import '../screens/quiz_screen.dart';
import '../theme.dart';
import '../widgets/common.dart';
import 'bench.dart';
import 'sim_bench.dart';

/// UC-04, AR Mode: FR-05.1 to FR-05.6.
///
///   starting   → is AR supported here? then the camera permission (FR-05.2)
///   scanning   → ARCore (Android) or ARKit (iOS) looks for the desk, 3 s (FR-05.3)
///   live       → the bench stands on the desk, the controls over the camera
///   simulation → the clearly labelled fallback for all three reasons (FR-05.4)
///
/// FR-05.5: live and simulation show the same bench and the same controls, and both
///          play through the game engine, so the server judges every move (FR-02.5).
/// FR-05.6: leaving disposes the AR view and its controller, which stops the camera
///          and the motion sensors.
class ArScreen extends StatelessWidget {
  const ArScreen({super.key, this.experimentId});
  final int? experimentId;

  @override
  Widget build(BuildContext context) {
    if (experimentId != null) return _ArSession(experimentId: experimentId!);
    return Scaffold(
      appBar: AppBar(title: const Text('AR Mode 🥽')),
      body: Loader(
        load: () => api.get('/experiments'),
        builder: (context, d, _) {
          final ar = (d['experiments'] as List).cast<Json>().where((e) => e['ar_available'] == true).toList();
          return ListView(padding: const EdgeInsets.all(16), children: [
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(gradient: const LinearGradient(colors: [Color(0xFF00BCD4), Color(0xFF0097A7)]), borderRadius: kCardRadius),
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('Put the lab on your desk', style: fredoka(18, color: Colors.white)),
                const SizedBox(height: 4),
                Text('Point your phone at a flat table and the bench appears on it. The app will ask to use the camera; '
                    'it is only used to find the table, and nothing is recorded or uploaded.',
                    style: nunito(13, color: Colors.white)),
                const SizedBox(height: 4),
                Text('No AR on this phone, or camera not allowed? You get the same experiment as a simulation.',
                    style: nunito(12, color: Colors.white70)),
              ]),
            ),
            const SizedBox(height: 14),
            if (ar.isEmpty) Text('None of your experiments has AR yet.', style: nunito(14, color: C.sub)),
            for (final e in ar)
              Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Opacity(
                  opacity: e['locked'] == true ? .55 : 1,
                  child: BBCard(
                    onTap: e['locked'] == true
                        ? null
                        : () => Navigator.push(context, MaterialPageRoute(builder: (_) => ArScreen(experimentId: e['id'] as int))),
                    child: Row(children: [
                      Text('${e['emoji']}', style: const TextStyle(fontSize: 30)),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                          Text('${e['title']}', style: fredoka(15)),
                          Text(e['locked'] == true ? '🔒 ${e['lock_reason'] ?? 'Locked'}' : '${e['topic']} · ${e['step_count']} steps',
                              style: nunito(12, color: C.sub)),
                        ]),
                      ),
                      if (e['locked'] != true) const Text('🥽', style: TextStyle(fontSize: 22)),
                    ]),
                  ),
                ),
              ),
          ]);
        },
      ),
    );
  }
}

enum _Phase { starting, scanning, live, simulation }

enum _Fallback { unsupported, permissionDenied, noSurface }

class _ArSession extends StatefulWidget {
  const _ArSession({required this.experimentId});
  final int experimentId;

  @override
  State<_ArSession> createState() => _ArSessionState();
}

class _ArSessionState extends State<_ArSession> {
  static const _scanTime = Duration(seconds: 3); // FR-05.3, NFR-12

  late final GameRunner _runner = GameRunner(experimentId: widget.experimentId, mode: 'ar')..load();

  _Phase _phase = _Phase.starting;
  _Fallback? _fallback;
  int _turns = 0;
  bool _panelOpen = true;
  bool _askOpen = false;
  Duration? _surfaceTime;

  Timer? _scanTimer;
  final _scanClock = Stopwatch();
  ArCoreController? _arcore;
  ARKitController? _arkit;
  String? _arkitParent;
  vm.Vector3? _anchor;

  Bench get _bench => benchFor(_runner.experiment['slug'] as String?);

  @override
  void initState() {
    super.initState();
    _launch();
  }

  @override
  void dispose() {
    _release();
    _runner.dispose();
    super.dispose();
  }

  /// FR-05.6: stop the camera, the sensors and the scan timer.
  void _release() {
    _scanTimer?.cancel();
    _arcore?.dispose();
    _arcore = null;
    _arkit?.dispose();
    _arkit = null;
  }

  void _toSimulation(_Fallback reason) {
    _release();
    if (mounted) setState(() { _phase = _Phase.simulation; _fallback = reason; });
  }

  Future<void> _launch() async {
    setState(() { _phase = _Phase.starting; _fallback = null; _surfaceTime = null; });

    bool supported = false;
    try {
      if (Platform.isAndroid) {
        supported = await ArCoreController.checkArCoreAvailability() == true && await ArCoreController.checkIsArCoreInstalled() == true;
      } else if (Platform.isIOS) {
        supported = await ARKitPlugin.checkConfiguration(ARKitConfiguration.worldTracking);
      }
    } catch (_) {
      supported = false;
    }
    if (!supported) return _toSimulation(_Fallback.unsupported);

    // FR-05.2: ask for the camera before any AR starts.
    final status = await Permission.camera.request();
    if (!status.isGranted) return _toSimulation(_Fallback.permissionDenied);

    if (!mounted) return;
    setState(() => _phase = _Phase.scanning);
    _scanClock
      ..reset()
      ..start();
    _scanTimer = Timer(_scanTime, () {
      if (_phase == _Phase.scanning) _toSimulation(_Fallback.noSurface);
    });
  }

  void _surfaceFound() {
    _scanTimer?.cancel();
    _scanClock.stop();
    if (mounted) setState(() { _phase = _Phase.live; _surfaceTime = _scanClock.elapsed; });
  }

  // ── ARCore (Android) ────────────────────────────────────────────────────────
  void _onArCore(ArCoreController c) {
    _arcore = c;
    c.onPlaneDetected = (ArCorePlane plane) {
      if (_anchor != null || plane.type != ArCorePlaneType.HORIZONTAL_UPWARD_FACING) return;
      _anchor = plane.centerPose?.translation;
      _placeArCore();
      _surfaceFound();
    };
  }

  Future<void> _placeArCore() async {
    final c = _arcore, at = _anchor;
    if (c == null || at == null) return;
    try {
      await c.removeNode(nodeName: 'bench');
    } catch (_) {}
    final angle = _turns * math.pi / 2;
    await c.addArCoreNode(ArCoreNode(
      name: 'bench',
      position: at,
      children: [
        for (final p in _bench.prims.map((p) => p.turned(angle)))
          ArCoreNode(
            position: vm.Vector3(p.pos.$1, p.pos.$2, p.pos.$3),
            rotation: p.horizontal ? _yawThenLie(angle) : null,
            shape: switch (p.shape) {
              Shape.box => ArCoreCube(size: vm.Vector3(p.size.$1, p.size.$2, p.size.$3), materials: [ArCoreMaterial(color: p.color.withValues(alpha: p.opacity))]),
              Shape.cylinder => ArCoreCylinder(radius: p.size.$1, height: p.size.$2, materials: [ArCoreMaterial(color: p.color.withValues(alpha: p.opacity))]),
              Shape.sphere => ArCoreSphere(radius: p.size.$1, materials: [ArCoreMaterial(color: p.color.withValues(alpha: p.opacity))]),
            },
          ),
      ],
    ));
  }

  /// A quaternion that lays a cylinder along x, then turns it with the bench.
  vm.Vector4 _yawThenLie(double angle) {
    final q = vm.Quaternion.axisAngle(vm.Vector3(0, 1, 0), angle) * vm.Quaternion.axisAngle(vm.Vector3(0, 0, 1), math.pi / 2);
    return vm.Vector4(q.x, q.y, q.z, q.w);
  }

  // ── ARKit (iOS) ─────────────────────────────────────────────────────────────
  void _onArKit(ARKitController c) {
    _arkit = c;
    c.onAddNodeForAnchor = (ARKitAnchor anchor) {
      if (_arkitParent != null || anchor is! ARKitPlaneAnchor) return;
      _arkitParent = anchor.nodeName;
      _anchor = vm.Vector3(anchor.center.x, 0, anchor.center.z);
      _placeArKit();
      _surfaceFound();
    };
  }

  Future<void> _placeArKit() async {
    final c = _arkit, at = _anchor, parent = _arkitParent;
    if (c == null || at == null || parent == null) return;
    try {
      await c.remove('bench');
    } catch (_) {}
    await c.add(ARKitNode(name: 'bench', position: at), parentNodeName: parent);
    final angle = _turns * math.pi / 2;
    for (final p in _bench.prims.map((p) => p.turned(angle))) {
      final material = ARKitMaterial(diffuse: ARKitMaterialProperty.color(p.color), transparency: p.opacity);
      await c.add(
        ARKitNode(
          position: vm.Vector3(p.pos.$1, p.pos.$2, p.pos.$3),
          rotation: p.horizontal ? _yawThenLie(angle) : null,
          geometry: switch (p.shape) {
            Shape.box => ARKitBox(width: p.size.$1, height: p.size.$2, length: p.size.$3, materials: [material]),
            Shape.cylinder => ARKitCylinder(radius: p.size.$1, height: p.size.$2, materials: [material]),
            Shape.sphere => ARKitSphere(radius: p.size.$1, materials: [material]),
          },
        ),
        parentNodeName: 'bench',
      );
    }
  }

  void _rotate() {
    setState(() => _turns++);
    if (_phase == _Phase.live) {
      if (_arcore != null) _placeArCore();
      if (_arkit != null) _placeArKit();
    }
  }

  Future<void> _act(Json action) async {
    final err = await _runner.act(action);
    if (!mounted) return;
    if (err != null) toast(context, err, error: true);
    announceBadges(context, _runner.newBadges);
    _runner.newBadges = [];
    if (_runner.finished) _release(); // the quiz needs no camera (FR-05.6)
  }

  @override
  Widget build(BuildContext context) {
    final avatar = context.watch<Auth>().user?['avatar_key'] as String?;
    final cameraOn = _phase == _Phase.scanning || _phase == _Phase.live;
    return ListenableBuilder(
      listenable: _runner,
      builder: (context, _) {
        final r = _runner;
        final exp = r.experiment;
        return Scaffold(
          backgroundColor: const Color(0xFF0D2137),
          body: Stack(children: [
            // The camera view, only while AR is running.
            if (cameraOn && Platform.isAndroid)
              Positioned.fill(child: ArCoreView(onArCoreViewCreated: _onArCore, enableUpdateListener: true, enablePlaneRenderer: true)),
            if (cameraOn && Platform.isIOS)
              Positioned.fill(child: ARKitSceneView(onARKitViewCreated: _onArKit, planeDetection: ARPlaneDetection.horizontal)),
            SafeArea(
              child: Column(children: [
                _topBar(exp),
                if (_phase == _Phase.simulation && _fallback != null) _banner(),
                Expanded(child: _middle()),
                if (r.error != null)
                  Container(color: Colors.white, padding: const EdgeInsets.all(16), child: Text(r.error!, style: fredoka(16)))
                else if (r.finished)
                  _finished(r)
                else if (r.current != null && (_phase == _Phase.live || _phase == _Phase.simulation))
                  _panel(r, avatar),
              ]),
            ),
          ]),
        );
      },
    );
  }

  Widget _topBar(Json exp) => Padding(
        padding: const EdgeInsets.fromLTRB(8, 8, 8, 6),
        child: Row(children: [
          IconButton.filled(
            tooltip: 'Exit AR',
            style: IconButton.styleFrom(backgroundColor: Colors.black38),
            onPressed: () => Navigator.pop(context),
            icon: const Icon(Icons.close, color: Colors.white),
          ),
          Expanded(
            child: Text(exp.isEmpty ? 'AR Mode' : '${exp['emoji']} ${exp['title']}',
                textAlign: TextAlign.center, overflow: TextOverflow.ellipsis, style: fredoka(16, color: Colors.white, weight: FontWeight.w500)),
          ),
          _phase == _Phase.simulation
              ? const Pill('◈ SIMULATION', bg: C.lemon, fg: C.orangeD)
              : _phase == _Phase.live
                  ? const Pill('● LIVE AR', bg: C.mint, fg: C.greenD)
                  : const Pill('AR'),
        ]),
      );

  /// FR-05.4: say plainly why this is a simulation.
  Widget _banner() {
    final text = switch (_fallback!) {
      _Fallback.unsupported => 'This phone cannot run AR, so here is the simulation. Same experiment, same controls.',
      _Fallback.permissionDenied => 'The camera was not allowed, so here is the simulation. You can allow it in Settings and try again.',
      _Fallback.noSurface => 'No flat table was found in 3 seconds, so here is the simulation. Try again in better light.',
    };
    return Container(
      margin: const EdgeInsets.fromLTRB(12, 0, 12, 6),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: C.lemon, borderRadius: BorderRadius.circular(16)),
      child: Row(children: [
        Expanded(child: Text(text, style: nunito(12, weight: FontWeight.w800))),
        if (_fallback != _Fallback.unsupported)
          TextButton(onPressed: _launch, child: Text('Try AR again', style: nunito(12, color: C.blueD, weight: FontWeight.w900))),
      ]),
    );
  }

  Widget _middle() {
    switch (_phase) {
      case _Phase.starting:
      case _Phase.scanning:
        return Center(
          child: Column(mainAxisSize: MainAxisSize.min, children: [
            const SizedBox(width: 56, height: 56, child: CircularProgressIndicator(color: Colors.white)),
            const SizedBox(height: 14),
            Text(_phase == _Phase.starting ? 'Starting AR…' : 'Point your camera at a flat table…',
                style: fredoka(16, color: Colors.white)),
          ]),
        );
      case _Phase.live:
        return Align(
          alignment: Alignment.topCenter,
          child: Text(
            'Desk found${_surfaceTime == null ? '' : ' in ${(_surfaceTime!.inMilliseconds / 1000).toStringAsFixed(1)} s'}.',
            style: nunito(12, color: Colors.white, weight: FontWeight.w800).copyWith(shadows: const [Shadow(blurRadius: 4)]),
          ),
        );
      case _Phase.simulation:
        return Stack(children: [
          Positioned.fill(child: SimBench(bench: _bench, quarterTurns: _turns)),
          Positioned(
            left: 16,
            right: 16,
            top: 4,
            child: Text(_bench.caption, textAlign: TextAlign.center, style: nunito(12, color: Colors.white70, weight: FontWeight.w800)),
          ),
        ]);
    }
  }

  /// The controls: the same in live and simulation (FR-05.5). UC-04 step 5:
  /// rotate, guidance and exit.
  Widget _panel(GameRunner r, String? avatar) {
    final maxH = MediaQuery.of(context).size.height * .55;
    return Container(
      constraints: BoxConstraints(maxHeight: maxH),
      decoration: const BoxDecoration(color: Colors.white, borderRadius: BorderRadius.vertical(top: Radius.circular(22))),
      child: Column(mainAxisSize: MainAxisSize.min, children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 8, 0),
          child: Row(children: [
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('Step ${(r.done + 1).clamp(1, r.total)} of ${r.total}', style: nunito(11, color: C.sub, weight: FontWeight.w900)),
                const SizedBox(height: 4),
                ProgressBar(r.pct, height: 8),
              ]),
            ),
            TextButton(
              onPressed: () => setState(() => _panelOpen = !_panelOpen),
              child: Text(_panelOpen ? 'Hide ▾' : 'Show ▴', style: nunito(12, color: C.blueD, weight: FontWeight.w900)),
            ),
          ]),
        ),
        if (_panelOpen)
          Flexible(
            child: ListView(shrinkWrap: true, padding: const EdgeInsets.fromLTRB(16, 10, 16, 8), children: [
              GuideBubble(runner: r, avatarKey: avatar, size: 48),
              const SizedBox(height: 10),
              Text('${r.current!['step_order']}. ${r.current!['title']}', style: fredoka(15)),
              Text('${r.current!['instruction_text']}', style: nunito(12, color: C.sub)),
              const SizedBox(height: 12),
              GameStep(
                key: ValueKey(r.current!['id']),
                step: r.current!,
                progress: r.progress,
                reveal: r.reveal,
                lastMistake: r.lastMistake,
                busy: r.busy,
                onAct: _act,
              ),
              if (_askOpen) ...[const SizedBox(height: 12), AskGuide(runner: r)],
            ]),
          ),
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 6, 16, 12),
          child: Row(children: [
            Expanded(child: _control('⟳ Rotate', C.sky, C.blueD, _rotate)),
            const SizedBox(width: 8),
            Expanded(child: _control('💬 Ask', C.purple, Colors.white, () => setState(() { _askOpen = !_askOpen; _panelOpen = true; }))),
            const SizedBox(width: 8),
            Expanded(child: _control('Exit', C.orange, Colors.white, () => Navigator.pop(context))),
          ]),
        ),
      ]),
    );
  }

  Widget _control(String label, Color bg, Color fg, VoidCallback onTap) => FilledButton(
        style: FilledButton.styleFrom(backgroundColor: bg, foregroundColor: fg, minimumSize: const Size.fromHeight(46)),
        onPressed: onTap,
        child: Text(label, style: fredoka(14, color: fg)),
      );

  Widget _finished(GameRunner r) => Container(
        width: double.infinity,
        padding: const EdgeInsets.all(20),
        decoration: const BoxDecoration(color: Colors.white, borderRadius: BorderRadius.vertical(top: Radius.circular(22))),
        child: Column(mainAxisSize: MainAxisSize.min, children: [
          Text('Experiment complete! 🎉', style: fredoka(21)),
          const SizedBox(height: 4),
          Text('Pass the quiz at 80% to earn ${r.experiment['base_points']} XP.', style: nunito(13, color: C.sub)),
          const SizedBox(height: 12),
          FilledButton(
            style: FilledButton.styleFrom(backgroundColor: C.purple),
            onPressed: () => Navigator.pushReplacement(
                context, MaterialPageRoute(builder: (_) => QuizScreen(experimentId: r.experimentId, sessionId: r.sessionId))),
            child: const Text('📝 Take the Quiz'),
          ),
        ]),
      );
}
