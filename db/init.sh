#!/bin/bash
set -eou pipefail

file=/scripts/init.sh
if [ -x "$file" ]; then
  "$file"
else
  . "$file"
fi