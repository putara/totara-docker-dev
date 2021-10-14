#!/bin/bash
set -eo pipefail

if [[ -z "$1" ]]; then
  >&2 echo "Usage: $0 <database>"
  exit 1
fi

delete_backup=1
if [[ -n "$2" ]]; then
  delete_backup=0
fi

backup=/tmp/backup.dump
if [[ ! -f "$backup" ]]; then
  >&2 echo "Backup file not found"
  exit 1
fi

/scripts/recreate.sh "$1"
zcat "$backup" | MYSQL_PWD=root mysql -uroot --init-command 'SET SESSION FOREIGN_KEY_CHECKS=0;' --default-character-set=utf8 "$1"
if [[ $delete_backup -eq 1 ]]; then
  rm "$backup"
fi
