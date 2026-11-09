#!/bin/sh
# SR-10: a recoverable copy of all retained data at least every 24 hours, each kept
# for at least 30 days. Runs inside the backup container (deploy/docker-compose.yml).
#
# The dump is compressed and encrypted (AES-256, key from BACKUP_PASSPHRASE), because
# the data inside the encrypted tables (SR-06) comes out of mysqldump as plain text.
# A copy of the MySQL keyring is kept next to the dumps: without it the live database
# cannot be opened again after a disk failure. Copy /backups off the server as well.
set -eu

: "${DB_DATABASE:?}" "${DB_USERNAME:?}" "${DB_PASSWORD?}" "${BACKUP_PASSPHRASE:?}"
DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"
BACKUP_DIR="${BACKUP_DIR:-/backups}"
KEYRING_DIR="${KEYRING_DIR:-/keyring}"
MYSQLDUMP="${MYSQLDUMP:-mysqldump}"

STAMP=$(date -u +%Y-%m-%dT%H%MZ)
OUT="${BACKUP_DIR}/beaker-${STAMP}.sql.gz.enc"

"$MYSQLDUMP" --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" --password="$DB_PASSWORD" \
    --single-transaction --routines --triggers --no-tablespaces "$DB_DATABASE" \
  | gzip -9 \
  | openssl enc -aes-256-cbc -pbkdf2 -iter 200000 -salt -pass env:BACKUP_PASSPHRASE -out "$OUT"

# A dump that did not produce a file, or produced an empty one, is a failed backup.
[ -s "$OUT" ] || { echo "$(date -u) backup FAILED: $OUT is empty"; exit 1; }

[ -d "$KEYRING_DIR" ] && cp -a "$KEYRING_DIR" "${BACKUP_DIR}/keyring-${STAMP}"

# Keep 30 days, plus one so that day 30 is never the only copy.
find "$BACKUP_DIR" -maxdepth 1 -name 'beaker-*.sql.gz.enc' -mtime +31 -delete
find "$BACKUP_DIR" -maxdepth 1 -name 'keyring-*' -type d -mtime +31 -exec rm -rf {} +

echo "$(date -u) backup ok: $OUT ($(wc -c < "$OUT") bytes)"
