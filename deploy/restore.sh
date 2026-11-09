#!/bin/sh
# SR-10: proves a backup is recoverable by loading it into a database.
#   restore.sh /backups/beaker-2026-12-01T2100Z.sql.gz.enc beaker_restore_check
# Restore into a separate database first and compare; only then into the live one.
set -eu

FILE="${1:?usage: restore.sh <backup file> <target database>}"
TARGET="${2:?usage: restore.sh <backup file> <target database>}"
: "${DB_USERNAME:?}" "${DB_PASSWORD?}" "${BACKUP_PASSPHRASE:?}"
DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"
MYSQL="${MYSQL:-mysql}"

"$MYSQL" --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" --password="$DB_PASSWORD" \
    -e "CREATE DATABASE IF NOT EXISTS \`$TARGET\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass env:BACKUP_PASSPHRASE -in "$FILE" \
  | gunzip \
  | "$MYSQL" --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" --password="$DB_PASSWORD" "$TARGET"

echo "restored $FILE into $TARGET"
