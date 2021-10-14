#!/bin/bash
set -eo pipefail

if [[ -z "$1" ]]; then
  >&2 echo "Usage: $0 <database>"
  exit 1
elif [[ "$1" =~ [^a-z0-9_] ]]; then
  >&2 echo "Invalid database name: $1"
  exit 1
elif [[ "$2" =~ [^a-z0-9_] ]]; then
  >&2 echo "Invalid prefix: $2"
  exit 1
fi

TABLES=$(MYSQL_PWD=root mysql -uroot -N information_schema -e "SELECT table_name FROM tables WHERE table_schema = '${1}' AND table_name LIKE '${2}%'")
MYSQL_PWD=root mysqldump -uroot --default-character-set=utf8 --add-drop-database --compact "$1" ${TABLES} | gzip -c > /tmp/backup.dump
