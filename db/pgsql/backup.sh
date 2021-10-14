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

pg_dump -U postgres -OFc "$1" -t "${2}*" > /tmp/backup.dump
