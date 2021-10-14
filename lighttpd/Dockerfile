FROM alpine

RUN apk add --update --no-cache lighttpd
COPY lighttpd.conf /etc/lighttpd/lighttpd.conf
COPY incl-php-fpm.conf /etc/lighttpd/incl-php-fpm.conf

RUN addgroup -g 1000 totara && \
    adduser -G totara -u 1000 -D totara

# RUN mkfifo -m 600 /tmp/logpipe
# RUN cat <> /tmp/logpipe 1>&2 &
# RUN chown testing /tmp/logpipe

EXPOSE 80
CMD ["lighttpd", "-D", "-f", "/etc/lighttpd/lighttpd.conf", "2>&1"]
