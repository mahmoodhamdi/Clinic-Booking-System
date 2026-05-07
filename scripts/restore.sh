#!/usr/bin/env bash
# Clinic Booking System — restore script
#
# Restores a backup produced by scripts/backup.sh into the running install.
# This is destructive: it overwrites the current DB schema/data and the
# storage/app/public folder. Always take a fresh backup first.
#
# Usage:
#   ./scripts/restore.sh backups/clinic-backup-20260507-023000.tar.gz

set -euo pipefail

if [[ $# -ne 1 ]]; then
  echo "Usage: $0 <backup.tar.gz>" >&2
  exit 1
fi

ARCHIVE="$1"
if [[ ! -f "$ARCHIVE" ]]; then
  echo "[restore] file not found: $ARCHIVE" >&2
  exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

if [[ -f .env ]]; then
  set -a
  # shellcheck disable=SC1091
  source <(grep -E '^[A-Z_]+=' .env | sed 's/\r$//')
  set +a
fi

WORKDIR="$(mktemp -d)"
trap 'rm -rf "$WORKDIR"' EXIT

echo "[restore] unpacking $ARCHIVE"
tar -xzf "$ARCHIVE" -C "$WORKDIR"

if [[ ! -f "$WORKDIR/manifest.txt" ]]; then
  echo "[restore] archive missing manifest.txt — refusing to proceed" >&2
  exit 2
fi

cat "$WORKDIR/manifest.txt"

# Refuse to restore across drivers — the dump format is incompatible.
ARCHIVE_DRIVER="$(grep '^db_connection=' "$WORKDIR/manifest.txt" | cut -d= -f2)"
DB_CONNECTION="${DB_CONNECTION:-mysql}"
if [[ "$ARCHIVE_DRIVER" != "$DB_CONNECTION" ]]; then
  echo "[restore] archive is from $ARCHIVE_DRIVER but current install uses $DB_CONNECTION — aborting" >&2
  exit 3
fi

case "$DB_CONNECTION" in
  mysql)
    : "${DB_HOST:?DB_HOST required}"
    : "${DB_DATABASE:?DB_DATABASE required}"
    : "${DB_USERNAME:?DB_USERNAME required}"
    DB_PORT="${DB_PORT:-3306}"
    if [[ ! -f "$WORKDIR/db.sql" ]]; then
      echo "[restore] db.sql missing from archive" >&2
      exit 4
    fi
    echo "[restore] importing db.sql into $DB_DATABASE on $DB_HOST"
    MYSQL_PWD="${DB_PASSWORD:-}" mysql \
      --default-character-set=utf8mb4 \
      -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" \
      "$DB_DATABASE" < "$WORKDIR/db.sql"
    ;;
  sqlite)
    SQLITE_FILE="${DB_DATABASE:-database/database.sqlite}"
    if [[ ! -f "$WORKDIR/db.sqlite" ]]; then
      echo "[restore] db.sqlite missing from archive" >&2
      exit 4
    fi
    echo "[restore] copying db.sqlite to $SQLITE_FILE"
    cp "$WORKDIR/db.sqlite" "$SQLITE_FILE"
    ;;
esac

# Restore uploads. Existing files outside the archive are kept (we only add
# back what the backup had); to fully mirror, set RESTORE_UPLOADS_PURGE=1.
if [[ -d "$WORKDIR/public" ]]; then
  if [[ "${RESTORE_UPLOADS_PURGE:-0}" == "1" ]]; then
    echo "[restore] purging storage/app/public before restore"
    rm -rf storage/app/public
    mkdir -p storage/app/public
  fi
  cp -r "$WORKDIR/public/." storage/app/public/
  echo "[restore] uploads restored to storage/app/public"
fi

# Cache may reference rows that no longer exist after restore.
echo "[restore] clearing application cache"
php artisan cache:clear || true

echo "[restore] done"
