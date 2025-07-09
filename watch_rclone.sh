#!/bin/bash
while inotifywait -r -e close_write,create,delete /var/www/CRM/local_drive; do
  bash /var/www/CRM/sync_rclone.sh
done