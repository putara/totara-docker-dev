#!/bin/bash
set -eou pipefail

for ((i = 0; i < 10; i++)); do
  /scripts/create.sh "totara$i"
done
