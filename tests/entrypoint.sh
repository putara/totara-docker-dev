#!/bin/sh
cp -pR /code/* /tmp/run/ && \
cp -pR /tmp/vendor /tmp/run/tests/ && \
cd /tmp/run/tests && \
vendor/bin/phpunit