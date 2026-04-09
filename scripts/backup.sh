#!/bin/bash
# Backup script for 1168lot project and OpenClaw config

BACKUP_DIR="/home/boat/backups"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory if not exists
mkdir -p "$BACKUP_DIR"

echo "=== Starting Backup at $(date) ==="

# 1. Backup 1168lot workspace
echo "Backing up 1168lot workspace..."
tar -czf "$BACKUP_DIR/1168lot_workspace_$DATE.tar.gz" \
  -C /home/boat/Projects/1168lot \
  --exclude="node_modules" \
  --exclude="vendor" \
  --exclude=".git" \
  .

# 2. Backup OpenClaw config
echo "Backing up OpenClaw config..."
cp ~/.openclaw/openclaw.json "$BACKUP_DIR/openclaw_config_$DATE.json"
cp ~/.openclaw/openclaw.json.backup.* "$BACKUP_DIR/" 2>/dev/null || true

# 3. Backup memory files
echo "Backing up memory files..."
tar -czf "$BACKUP_DIR/openclaw_memory_$DATE.tar.gz" \
  -C /home/boat/Projects/1168lot \
  memory/ MEMORY.md \
  workspace/SOUL.md workspace/USER.md workspace/TOOLS.md workspace/HEARTBEAT.md workspace/IDENTITY.md

# 4. Create backup report
cat > "$BACKUP_DIR/backup_report_$DATE.txt" << EOF
Backup Report - $DATE
=====================

1. 1168lot Workspace: $BACKUP_DIR/1168lot_workspace_$DATE.tar.gz
   Size: $(du -h "$BACKUP_DIR/1168lot_workspace_$DATE.tar.gz" | cut -f1)

2. OpenClaw Config: $BACKUP_DIR/openclaw_config_$DATE.json
   Size: $(du -h "$BACKUP_DIR/openclaw_config_$DATE.json" | cut -f1)

3. Memory Files: $BACKUP_DIR/openclaw_memory_$DATE.tar.gz
   Size: $(du -h "$BACKUP_DIR/openclaw_memory_$DATE.tar.gz" | cut -f1)

Total Backup Size: $(du -ch "$BACKUP_DIR/"*_"$DATE".* | grep total | cut -f1)

=== Backup Completed Successfully ===
EOF

echo "=== Backup Completed ==="
echo "Backup files:"
ls -lh "$BACKUP_DIR/"*_"$DATE".*
echo ""
echo "Report: $BACKUP_DIR/backup_report_$DATE.txt"
