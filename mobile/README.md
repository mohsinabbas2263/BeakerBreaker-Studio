# Beaker Breakers Studio: the student app (Flutter)

The Android and iOS app from Chapter 1 ("Nature of the End Product"): Game Mode and
AR Mode for students, over the same Laravel API as the web portal. Teachers and
administrators use the web portal; the app refuses their sign-in.

Every score is still computed on the server (`FR-02.5`): the app sends what the
student did and shows what the server says.

## Run it

```bash
flutter pub get
flutter run --dart-define=API_URL=http://10.0.2.2:8000/api      # Android emulator, backend on this PC
flutter run --dart-define=API_URL=https://api.<domain>/api       # against the live server
```

Without `API_URL` the app uses `http://10.0.2.2:8000/api`, the development machine
as the Android emulator sees it. Plain HTTP is allowed only to `10.0.2.2` and
`localhost` (`android/app/src/main/res/xml/network_security_config.xml`); anything
else must be HTTPS (`SR-02`).

For a phone on USB, forward the port and use localhost:

```bash
adb reverse tcp:8000 tcp:8000
flutter run --dart-define=API_URL=http://localhost:8000/api
```

## What is where

| Folder | |
|---|---|
| `lib/api/` | The API client. The token is kept in the platform's secure storage (`SR-03`). |
| `lib/game/` | The game engine client: `game_runner.dart` (the moves), `game_step.dart` (the 9 step kinds), `lab_items.dart` (generated). |
| `lib/ar/` | AR Mode: ARCore on Android, ARKit on iOS, and the simulation (`FR-05.4`), all drawing the bench in `bench.dart`. |
| `lib/screens/` | Login, home with missions, labs, game runner, quiz, safety, leaderboard, profile, messages, readiness. |
| `tool/gen_lab_items.py` | Regenerates `lib/game/lab_items.dart` from `web/lib/labItems.ts`, so both clients name items the same way. |

```bash
py -3 -I tool/gen_lab_items.py ..
```

## AR

- Android: ARCore through `arcore_flutter_plus`. AR is marked optional in the
  manifest, so the app installs on any phone; one without ARCore, a refused camera,
  or no table found in 3 seconds all land on the labelled simulation (`FR-05.4`).
- The live view shows "Desk found in x s": write it down per phone for `NFR-12`.
- iOS: ARKit through `arkit_plugin`, with the same fallbacks. Built on Codemagic and
  installed with Sideloadly, below.

## iPhone (no Mac, no paid Apple account)

The app is not published for iOS; schools get the Android app. For testing and the
defence it runs on our own iPhones (an iPhone 11 and an iPhone 14 Pro Max):

1. **Build on Codemagic.** `codemagic.yaml` at the repository root has the
   `ios-unsigned` workflow. In Codemagic, add the repository, set the environment
   variable `API_URL` (see 3), and start the workflow. It builds a release app on
   Codemagic's Mac and gives `BeakerBreakers-unsigned.ipa` under Artifacts. The free
   plan's monthly macOS minutes cover this.
2. **Install from Windows with Sideloadly** (download it only from sideloadly.io).
   Turn on Developer Mode on the iPhone (Settings, Privacy & Security), connect it by
   USB, drop the `.ipa` on Sideloadly and sign in with an Apple ID. Use a separate
   Apple ID made for this, not your main one. The free signing lasts **7 days**;
   install again before a demo.
3. **Backend address.** The iPhone cannot use `10.0.2.2`. Before the server exists,
   run the backend on the PC so the Wi-Fi can reach it,
   `php artisan serve --host=0.0.0.0 --port=8000`, allow it through the Windows
   firewall, and set `API_URL` to `http://<PC's Wi-Fi IP>:8000/api` (`ipconfig`
   shows the IP). Info.plist allows plain HTTP only to local addresses
   (`NSAllowsLocalNetworking`); after deployment use `https://api.<domain>/api`.
4. On first start iOS asks for local network access and, in AR Mode, the camera.

Camera permission: under Swift Package Manager `permission_handler` enables it from
`NSCameraUsageDescription` in Info.plist, and the workflow also sets
`PERMISSION_CAMERA=1`. No Podfile is needed; every plugin supports Swift Package Manager.

## Building on Windows

- If Gradle stops with `Unable to establish loopback connection`, the user's TEMP path
  (a space, or a short `NAME~1` form) is the cause. Point Java at a plain folder for the build:
  `set JAVA_TOOL_OPTIONS=-Djdk.net.unixdomain.tmpdir=C:\src	mp` (and `TEMP`/`TMP` to the same folder).
- `compileSdk` is 37 because `permission_handler_android` needs it; `minSdk` 24 and
  `targetSdk` are unchanged.
- `arcore_flutter_plus` is used from `packages/` with three build lines fixed for Gradle 9
  (`packages/arcore_flutter_plus/PATCHED.md`), and `android.uniquePackageNames=false`
  lets its Sceneform libraries share a namespace.

## Checked so far

| Check | State |
|---|---|
| `flutter analyze` | ✅ no issues |
| `flutter test` | ❌ blocked on the development PC: Windows Smart App Control stops `flutter_tester.exe` |
| Android debug build | ✅ 11 Nov 2026, Flutter 3.47.9, AGP 9.1, Gradle 9.3.1, Java 21 (Android Studio's JBR) |
| Run on an emulator (Pixel 5, Android 11) | ✅ 11 Nov 2026 against the local backend: login, home with missions, labs list, experiment detail, game steps (gear with a wrong and right move, measure), Safety tiers, quiz opens, AR Mode falls back to the labelled simulation (no ARCore on the emulator) |
| Run on a real phone, live AR | ❌ not yet: needs an Android phone with ARCore (`NFR-12`) |
| iOS build (Codemagic, unsigned) | ❌ not run yet: needs a Codemagic account linked to the repository |
| Run on an iPhone | ❌ not yet |
