# Deploying Beaker Breakers Studio

The runbook for M9 (`docs/build-order.md`) and Sprint 4 (`docs/sprint-plan.md`). The API,
admin panel, database, cache and backups run on one Linux VPS with Docker Compose; the web
portal runs on Vercel.

```
Students' browsers ──HTTPS──► Vercel (web/, Next.js)
        │
        └──HTTPS, TLS 1.3──► VPS: Caddy ─► nginx ─► Laravel (PHP-FPM) ─► MySQL 8.4 (encrypted)
                                                      ├─ queue worker      Redis 7
                                                      └─ scheduler         backup (daily, 30 days)
```

## What has been checked, and what has not

| Check | Status |
|---|---|
| The backend tests on **MySQL 8.4** (not only SQLite) | ✅ all 184 passed, 9 Nov 2026 (the 2 security header tests came after, and pass on SQLite) |
| `migrate:fresh --seed` on MySQL 8.4 | ✅ |
| Encryption at rest (`SR-06`) with the settings in `deploy/mysql/` | ✅ every tablespace encrypted; the email is not readable in the raw file (`deploy/mysql/README.md`) |
| Backup, then restore into a fresh database (`SR-10`) | ✅ counts match; the backup file holds no plain text; a wrong passphrase is refused |
| Security headers, HSTS in production (`SR-02`) | ✅ `SecurityHeadersTest` |
| The Docker images build and the stack starts | ❌ **not run**: Docker is not installed on the development machine. First thing to check on the server |
| TLS 1.3 only (`SR-02`) | ❌ configured in `Caddyfile`, check on the server (step 7) |
| 300 ms at p95 for 1000 users (`NFR-01`, `NFR-04`) | ❌ load test not run |
| First paint within 3 s (`NFR-02`), browser matrix (`NFR-09`), WCAG 2.1 AA (`NFR-08`) | ❌ not measured |

Nothing in the "not" rows may be claimed in the report until it has been run (CLAUDE.md,
"Measure before claiming").

## Before you start

- A VPS: Ubuntu 24.04, 2 vCPU, 4 GB RAM, 40 GB disk is enough for one school.
- A domain with two names: `api.<domain>` for the VPS and the web address for Vercel.
- The secrets, which go only into `.env` files on the server and into Vercel's settings:
  database passwords, `BACKUP_PASSPHRASE`, `APP_KEY`, `GEMINI_API_KEY`.
  **Write `BACKUP_PASSPHRASE` down somewhere off the server.** Without it no backup can be read.

## 1. Prepare the server

```bash
sudo apt update && sudo apt upgrade -y
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER        # log out and in again
sudo ufw allow OpenSSH && sudo ufw allow 80 && sudo ufw allow 443 && sudo ufw enable
git clone https://github.com/mohsinabbas2263/BeakerBreaker-Studio.git && cd BeakerBreaker-Studio
```

Point `api.<domain>` (an A record) at the server's IP before step 4, so Caddy can get the certificate.

## 2. Settings

```bash
cp deploy/.env.example deploy/.env           # fill in every value
cp backend/.env.example backend/.env         # then change the lines below
```

In `backend/.env`:

| Setting | Production value |
|---|---|
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `APP_URL` | `https://api.<domain>` |
| `FRONTEND_URL` | the Vercel address, e.g. `https://beakerbreakers.<domain>` (CORS and reset links) |
| `SANCTUM_STATEFUL_DOMAINS` | the Vercel host name |
| `DB_HOST` / `DB_PORT` | `mysql` / `3306`; database, user and password the same as in `deploy/.env` |
| `CACHE_STORE` / `QUEUE_CONNECTION` | `redis` / `redis` |
| `REDIS_HOST` | `redis` |
| `LOG_LEVEL` | `warning` |
| `MAIL_MAILER` | `smtp` with the SendGrid settings, or password resets will not arrive |
| `GEMINI_API_KEY` | the key. Leave it empty and everything still works on pre-authored guidance (`NFR-06`) |

## 3. Keyring folder (once)

The MySQL container runs as user `mysql` and must be able to write its keyring (`SR-06`):

```bash
docker compose -f deploy/docker-compose.yml run --rm --user root --entrypoint sh mysql \
  -c "chown -R mysql:mysql /var/lib/mysql-keyring"
```

## 4. Build and start

```bash
docker compose -f deploy/docker-compose.yml --env-file deploy/.env build
# First time only: print a key and paste it into backend/.env as APP_KEY=...
docker compose -f deploy/docker-compose.yml --env-file deploy/.env run --rm --no-deps app php artisan key:generate --show
docker compose -f deploy/docker-compose.yml --env-file deploy/.env up -d
docker compose -f deploy/docker-compose.yml exec app php artisan migrate --force
docker compose -f deploy/docker-compose.yml exec app php artisan db:seed --force          # first time only
docker compose -f deploy/docker-compose.yml exec app php artisan optimize
```

`db:seed` loads the safety questions and the 36 textbook experiments, and also a **demo
school with test accounts** (`DemoSchoolSeeder`). Before real students are added, remove
the demo accounts or change their passwords in the admin panel.

## 5. Check encryption at rest (`SR-06`)

```bash
docker compose -f deploy/docker-compose.yml exec mysql mysql -uroot -p -e \
  "SELECT * FROM performance_schema.keyring_component_status;
   SELECT COUNT(*) AS not_encrypted FROM information_schema.INNODB_TABLESPACES
   WHERE NAME LIKE 'beaker_breakers/%' AND ENCRYPTION='N';"
```

Expect `Component_status Active` and `not_encrypted 0`. If not, stop here: no student
data goes in until this is right.

## 6. The web portal on Vercel

Import the repository in Vercel, set the root directory to `web/`, and add one
environment variable: `NEXT_PUBLIC_API_URL=https://api.<domain>/api`. Then add the web
address as a domain in Vercel.

## 7. Check the live system

- `https://api.<domain>/up` answers 200.
- TLS 1.3 only (`SR-02`): `openssl s_client -connect api.<domain>:443 -tls1_2` must **fail**,
  and `-tls1_3` must connect. Or use the SSL Labs test.
- `curl -sI https://api.<domain>/up` shows `Strict-Transport-Security`.
- Log in on the Vercel address as each role; the admin panel is at `https://api.<domain>/admin`.
- Play one experiment to the quiz with the Gemini key removed (`NFR-06`).

## 8. Backups (`SR-10`)

The `backup` container dumps the database every day at 02:00 Pakistan time into the
`backups` volume, encrypted, and keeps 31 days. Run one now and **restore it** into a
check database; a backup that has never been restored is not known to be recoverable:

```bash
docker compose -f deploy/docker-compose.yml exec backup sh /usr/local/bin/backup.sh
docker compose -f deploy/docker-compose.yml exec backup ls -l /backups
docker compose -f deploy/docker-compose.yml cp backup:/backups ./backups-copy   # keep a copy off the server too
```

To restore: copy `deploy/restore.sh` into the backup container and run
`sh restore.sh /backups/<file> beaker_restore_check`, then compare row counts with the live
database. Repeat this once a month during the pilot.

## 9. Updating

```bash
git pull
docker compose -f deploy/docker-compose.yml --env-file deploy/.env build
docker compose -f deploy/docker-compose.yml --env-file deploy/.env up -d
docker compose -f deploy/docker-compose.yml exec app php artisan migrate --force
docker compose -f deploy/docker-compose.yml exec app php artisan optimize
```

Take a backup (step 8) before every update that has a migration.
