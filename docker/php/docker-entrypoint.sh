#!/bin/bash
set -e

# Enable/disable Xdebug based on environment variable
if [ "${XDEBUG_ENABLED}" = "1" ] || [ "${XDEBUG_ENABLED}" = "true" ]; then
    docker-php-ext-enable xdebug
    echo "Xdebug enabled"
else
    docker-php-ext-disable xdebug 2>/dev/null || true
    echo "Xdebug disabled"
fi

# Execute command
exec "$@"

