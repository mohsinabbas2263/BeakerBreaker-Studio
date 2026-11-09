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
- iOS: ARKit through `arkit_plugin`. **Not built or run yet**: an iPhone build needs a
  Mac with Xcode. On the Mac, after `flutter pub get`, add the camera permission
  for `permission_handler` to `ios/Podfile`:

  ```ruby
  post_install do |installer|
    installer.pods_project.targets.each do |target|
      flutter_additional_ios_build_settings(target)
      target.build_configurations.each do |config|
        config.build_settings['GCC_PREPROCESSOR_DEFINITIONS'] ||= ['$(inherited)', 'PERMISSION_CAMERA=1']
      end
    end
  end
  ```

## Checked so far

| Check | State |
|---|---|
| `flutter analyze` | ✅ no issues |
| `flutter test` | ❌ blocked on the development PC: Windows Smart App Control stops `flutter_tester.exe` |
| Android debug build | see the commit that adds this line |
| Run on an emulator or phone | ❌ not yet |
| iOS build | ❌ needs a Mac |
