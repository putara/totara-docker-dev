#!/bin/sh
set -e

chown -R totara:totara /run/memcached/
chmod 777 /run/memcached/
rm -f /var/run/memcached/memcached.sock

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
	set -- memcached "$@"
fi

exec "$@"
