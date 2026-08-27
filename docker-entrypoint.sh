#!/bin/bash
set -e

echo "MariaDB is ready (verified by healthcheck)!"

sleep 2

bash /var/www/html/docker/init-db.sh

exec apache2-foreground