#!/bin/bash
set -eo pipefail

if [[ -z "$1" ]]; then
  >&2 echo "Usage: $0 <database>"
  exit 1
elif [[ "$1" =~ [^a-z0-9_] ]]; then
  >&2 echo "Invalid database name: $1"
  exit 1
fi

psql -U postgres -d postgres -c "SELECT pg_terminate_backend(pg_stat_activity.pid) FROM pg_stat_activity where pg_stat_activity.datname = '$1'" >/dev/null
dropdb -U postgres --if-exists "$1"
