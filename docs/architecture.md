# Architecture

Three tiers. The application tier is the only tier that may reach the data tier, and the
only tier that holds credentials for any external service. No provider key ever reaches a
client (`SR-06`, `SR-07`).

## Stack

| Layer | Technology |
|---|---|
| Web | React 18, Next.js 14 |
| Mobile | Flutter 3, Dart (Android and iOS) |
| AR | ARCore, ARKit |
| API | Laravel 11 on PHP 8.2 |
| Admin panel | Filament |
| Database | MySQL 8.0 |
| Cache, queues, leaderboard | Redis 7 |
| Assets | AWS S3 behind Cloudflare CDN |

## External services

| Service | Used for | Requirement |
|---|---|---|
| Gemini API | Step guidance, readiness classification | `FR-03.7`, `FR-08.3` |
| ElevenLabs | Avatar voice | |
| Pusher | Live class events | `FR-09.3` |
| Firebase Cloud Messaging | Push notifications | `FR-09.6` |
| SendGrid | Transactional email | `FR-01.3` |

Only the notification component holds FCM and SendGrid credentials. Other components raise
notifications through it rather than calling those services directly.

## Application tier components

`api-gateway`, `auth`, `safety`, `experiments`, `assessment`, `gamification`, `readiness`,
`classes`, `admin-audit`, `notifications`.

Every request enters through the gateway, which delegates to `auth` before any other
component is reached (`SR-03`).

## Two deliberate shortcuts

- `gamification` reaches Redis directly, because `FR-07.2` requires a points change on the
  leaderboard within 2 seconds and a relational read will not hold that reliably.
- Rewards are computed by `gamification` on an event emitted by `experiments`, not inside
  the experiment engine. `FR-04.3` requires the award to happen exactly once, and isolating
  it lets the reward rules change without touching the laboratory.

## Performance targets

| Target | Requirement |
|---|---|
| Server response within 300 ms at p95 (excluding AI calls) | `NFR-01` |
| First contentful paint within 3 s on 4 Mbps | `NFR-02` |
| 99.5 percent monthly availability | `NFR-03` |
| 1000 concurrent authenticated users | `NFR-04` |
| AI guidance within 2 s, or 200 ms cached | `FR-03.7` |
| Surface detection within 3 s | `FR-05.3` |
| Test coverage 70 percent overall, 100 percent on auth, scoring, rewards, certification | `NFR-10` |
