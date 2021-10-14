FROM memcached:alpine
USER root

RUN addgroup -g 1000 totara && \
    adduser -G totara -u 1000 -D totara

COPY entrypoint.sh /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]

CMD ["memcached", "-u", "totara", "-m", "64", "-s", "/run/memcached/memcached.sock"]
