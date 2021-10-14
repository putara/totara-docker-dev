#!/bin/bash
set -eo pipefail

if [[ -z "$1" ]]; then
  >&2 echo "Usage: $0 <database>"
  exit 1
elif [[ "$1" =~ [^a-z0-9_] ]]; then
  >&2 echo "Invalid database name: $1"
  exit 1
fi

MYSQL_PWD=root mysql -uroot -A "$1"
