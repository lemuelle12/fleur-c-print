#!/bin/bash
# ── backup.sh ────────────────────────────────────────────────
# Daily backup: MySQL dump + uploads archive + optional rclone
# Add to cron: 30 23 * * * /path/to/fleur-c-print/backup.sh

DATE=$(date +%Y-%m-%d)
APP_DIR="$(cd "$(dirname "$0")" && pwd)"
BACKUP_DIR="$APP_DIR/backups"
LOG="$APP_DIR/logs/backup.log"

mkdir -p "$BACKUP_DIR"

echo "[$DATE] Starting backup..." >> "$LOG"

# 1. MySQL dump
mysqldump -u root fleur_c_print > "$BACKUP_DIR/db_$DATE.sql" 2>>"$LOG"
if [ $? -eq 0 ]; then
  gzip -f "$BACKUP_DIR/db_$DATE.sql"
  echo "[$DATE] DB backup OK → db_$DATE.sql.gz" >> "$LOG"
else
  echo "[$DATE] DB backup FAILED" >> "$LOG"
fi

# 2. Uploads archive
tar -czf "$BACKUP_DIR/uploads_$DATE.tar.gz" -C "$APP_DIR" uploads/ 2>>"$LOG"
echo "[$DATE] Uploads backup OK → uploads_$DATE.tar.gz" >> "$LOG"

# 3. Delete backups older than 30 days
find "$BACKUP_DIR" -mtime +30 -delete
echo "[$DATE] Old backups purged." >> "$LOG"

# 4. Optional: rclone to Google Drive (uncomment when configured)
# rclone copy "$BACKUP_DIR" gdrive:fleur-c-print-backups/ >> "$LOG" 2>&1
# echo "[$DATE] rclone sync done." >> "$LOG"

echo "[$DATE] Backup complete." >> "$LOG"
