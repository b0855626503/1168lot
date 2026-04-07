#!/bin/bash
# OpenClaw and 1168lot Project Monitor

LOG_FILE="/tmp/openclaw_monitor.log"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

echo "[$TIMESTAMP] Starting monitoring check..." >> "$LOG_FILE"

# 1. Check OpenClaw Gateway
if openclaw gateway status >/dev/null 2>&1; then
    echo "[$TIMESTAMP] ✅ OpenClaw Gateway: RUNNING" >> "$LOG_FILE"
else
    echo "[$TIMESTAMP] ❌ OpenClaw Gateway: STOPPED" >> "$LOG_FILE"
    # Try to restart
    openclaw gateway restart >> "$LOG_FILE" 2>&1
fi

# 2. Check log file size
LOG_SIZE=$(du -h /tmp/openclaw.log 2>/dev/null | cut -f1 || echo "0")
echo "[$TIMESTAMP] 📊 OpenClaw Log Size: $LOG_SIZE" >> "$LOG_FILE"

# 3. Check 1168lot project
if [ -f "/home/boat/Projects/1168lot/.env" ]; then
    echo "[$TIMESTAMP] ✅ 1168lot Project: EXISTS" >> "$LOG_FILE"
else
    echo "[$TIMESTAMP] ⚠️  1168lot Project: .env missing" >> "$LOG_FILE"
fi

# 4. Check memory files
MEMORY_COUNT=$(find /home/boat/Projects/1168lot/memory -name "*.md" 2>/dev/null | wc -l)
echo "[$TIMESTAMP] 🧠 Memory Files: $MEMORY_COUNT files" >> "$LOG_FILE"

# 5. Check backup directory
BACKUP_COUNT=$(find /home/boat/backups -name "*.tar.gz" -mtime -7 2>/dev/null | wc -l)
echo "[$TIMESTAMP] 💾 Recent Backups (7 days): $BACKUP_COUNT" >> "$LOG_FILE"

echo "[$TIMESTAMP] Monitoring check completed." >> "$LOG_FILE"
echo "====" >> "$LOG_FILE"