#!/usr/bin/env bash
# Clinic Booking System — backup script
#
# Dumps the MySQL database (or SQLite file) plus uploaded attachments
# (storage/app/public) into a single timestamped tarball.
#
# Usage:
#   ./scripts/backup.sh                  # writes to backups/
#   BACKUP_DIR=/var/backups ./scripts/backup.sh
#
# Required env (from your Laravel .env, or override here):
#   DB_CONNECTION=mysql|sqlite
#   DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD  (mysql only)
#
# Cron example (daily at 02:30):
#   30 2 * * * cd /var/www/clinic && ./scripts/backup.sh >> /var/log/clinic-backup.log 2>&1

set -euo pipefail

# Resolve repo root from this script's location so it works under any cwd.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

# Load .env if present so we don't require the operator to export everything.
if [[ -f .env ]]; then
  set -a
  # shellcheck disable=SC1091
  source <(grep -E '^[A-Z_]+=' .env | sed 's/\r$//')
  set +a
fi

BACKUP_DIR="${BACKUP_DIR:-$REPO_ROOT/backups}"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"
WORKDIR="$(mktemp -d)"
trap 'rm -rf "$WORKDIR"' EXIT

mkdir -p "$BACKUP_DIR"

DB_CONNECTION="${DB_CONNECTION:-mysql}"

echo "[backup] starting $(date -u +%FT%TZ) — driver=$DB_CONNECTION"

case "$DB_CONNECTION" in
  mysql)
    : "${DB_HOST:?DB_HOST is required for mysql}"
    : "${DB_DATABASE:?DB_DATABASE is required for mysql}"
    : "${DB_USERNAME:?DB_USERNAME is required for mysql}"
    DB_PORT="${DB_PORT:-3306}"
    # --single-transaction keeps the dump consistent without locking writers.
    # --quick + --no-tablespaces keep memory low and avoid privilege issues.
    MYSQL_PWD="${DB_PASSWORD:-}" mysqldump \
      --single-transaction \
      --quick \
      --no-tablespaces \
      --default-character-set=utf8mb4 \
      -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" \
      "$DB_DATABASE" > "$WORKDIR/db.sql"
    ;;
  sqlite)
    SQLITE_FILE="${DB_DATABASE:-database/database.sqlite}"
    if [[ ! -f "$SQLITE_FILE" ]]; then
      echo "[backup] SQLite file not found at $SQLITE_FILE" >&2
      exit 1
    fi
    # Use sqlite3 .backup so it's safe under a running app (not just a copy).
    sqlite3 "$SQLITE_FILE" ".backup '$WORKDIR/db.sqlite'"
    ;;
  *)
    echo "[backup] unsupported DB_CONNECTION=$DB_CONNECTION" >&2
    exit 2
    ;;
esac

# Bundle uploads (avatars, clinic logo/hero, prescription PDFs, attachments).
# The folder may legitimately not exist in a fresh install — don't fail.
if [[ -d storage/app/public ]]; then
  cp -r storage/app/public "$WORKDIR/public"
else
  mkdir -p "$WORKDIR/public"
fi

# Manifest so a restore knows what it's dealing with without unpacking.
cat > "$WORKDIR/manifest.txt" <<EOF
clinic-booking-backup
created_at=$(date -u +%FT%TZ)
db_connection=$DB_CONNECTION
db_database=${DB_DATABASE:-}
host=$(hostname)
EOF

OUT="$BACKUP_DIR/clinic-backup-$TIMESTAMP.tar.gz"
tar -C "$WORKDIR" -czf "$OUT" .

echo "[backup] wrote $OUT ($(du -h "$OUT" | awk '{print $1}'))"

# Optional retention: keep last N backups if BACKUP_RETENTION is set.
if [[ -n "${BACKUP_RETENTION:-}" ]]; then
  ls -1t "$BACKUP_DIR"/clinic-backup-*.tar.gz 2>/dev/null \
    | tail -n +"$((BACKUP_RETENTION + 1))" \
    | xargs -r rm -f
  echo "[backup] retention applied: keeping latest $BACKUP_RETENTION"
fi
