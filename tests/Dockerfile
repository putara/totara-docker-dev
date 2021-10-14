FROM php:7.4-alpine3.14 AS builder

COPY composer.json /tmp/
WORKDIR /tmp

RUN curl -o composer-setup.php https://getcomposer.org/installer && \
    php composer-setup.php --quiet --filename=composer --install-dir=/usr/local/bin && \
    rm composer-setup.php && \
    composer install

FROM php:7.4-alpine3.14
COPY --from=builder /tmp/vendor/ /tmp/vendor/
COPY entrypoint.sh /tmp/entrypoint.sh

WORKDIR /tmp/run
CMD ["/tmp/entrypoint.sh"]
