#!/bin/bash
cd /var/www/CRM || exit 1
git fetch origin
LOCAL=$(git rev-parse @)
REMOTE=$(git rev-parse @{u})

if [ "$LOCAL" != "$REMOTE" ]; then
  git pull --ff-only
  echo "Repo aggiornata!"
else
  echo "Nessuna modifica sulla repo."
fi