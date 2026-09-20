#!/bin/bash

# Configuration
DB_USER="root"
DB_NAME="automation_db"
BACKUP_DIR="/var/backups/automation"
STORAGE_DIR="/var/www/automation/storage"
DATE=$(date +"%Y%m%d_%H%M%S")

mkdir -p $BACKUP_DIR

# Database Backup
echo "Dumping database..."
sudo mysqldump -u $DB_USER $DB_NAME > $BACKUP_DIR/db_backup_$DATE.sql

# Storage Backup
echo "Archiving storage..."
tar -czf $BACKUP_DIR/storage_backup_$DATE.tar.gz $STORAGE_DIR

echo "Backup complete! Files saved to $BACKUP_DIR"
