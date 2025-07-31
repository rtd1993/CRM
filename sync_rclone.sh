#!/bin/bash
LOGFILE="/var/www/CRM/logs/rclone_sync.log"
rclone sync /var/www/CRM/local_drive gdrive:CRM --log-file="$LOGFILE" --log-level INFO