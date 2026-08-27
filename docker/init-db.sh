#!/bin/bash
set -e

MARIADB_HOST="${MARIADB_HOST:-mariadb}"
MARIADB_ROOT_USER="${MARIADB_ROOT_USER:-root}"
MARIADB_ROOT_PASSWORD="${MARIADB_ROOT_PASSWORD:?MARIADB_ROOT_PASSWORD is required}"
DB_NAME="${DB_NAME:-tailormate}"

echo "MariaDB is ready (verified by healthcheck)!"

# ------------------------------------------------------------
# Ensure users table exists.
# The original schema is only imported automatically by the
# MariaDB container on first database initialization.
# ------------------------------------------------------------
TABLE_EXISTS=$(mysql \
    -h"$MARIADB_HOST" \
    -u"$MARIADB_ROOT_USER" \
    -p"$MARIADB_ROOT_PASSWORD" \
    -N \
    -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name='users';" \
    2>/dev/null)

if [ "$TABLE_EXISTS" -eq 0 ]; then
    echo "Users table not found."

    if [ -f /var/www/html/docker/tailormate_original.sql ]; then
        echo "Importing original schema..."
        mysql \
            -h"$MARIADB_HOST" \
            -u"$MARIADB_ROOT_USER" \
            -p"$MARIADB_ROOT_PASSWORD" \
            "$DB_NAME" \
            < /var/www/html/docker/tailormate_original.sql
    else
        echo "ERROR: Original schema file not found."
        exit 1
    fi
fi

# ------------------------------------------------------------
# Ensure migration tracking table exists.
# ------------------------------------------------------------
mysql \
    -h"$MARIADB_HOST" \
    -u"$MARIADB_ROOT_USER" \
    -p"$MARIADB_ROOT_PASSWORD" \
    "$DB_NAME" <<'SQL'
CREATE TABLE IF NOT EXISTS schema_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
SQL

# ------------------------------------------------------------
# Apply migration 001 only if it has not already been applied.
# ------------------------------------------------------------
MIGRATION_NAME="001_safe_enhancement"

MIGRATION_APPLIED=$(mysql \
    -h"$MARIADB_HOST" \
    -u"$MARIADB_ROOT_USER" \
    -p"$MARIADB_ROOT_PASSWORD" \
    -N \
    -e "SELECT COUNT(*) FROM $DB_NAME.schema_migrations WHERE migration='$MIGRATION_NAME';")

if [ "$MIGRATION_APPLIED" -eq 0 ]; then
    echo "Applying migration: $MIGRATION_NAME"

    if [ -f /var/www/html/tailormate_enhanced_safe.sql ]; then
        mysql \
            -h"$MARIADB_HOST" \
            -u"$MARIADB_ROOT_USER" \
            -p"$MARIADB_ROOT_PASSWORD" \
            "$DB_NAME" \
            < /var/www/html/tailormate_enhanced_safe.sql

        mysql \
            -h"$MARIADB_HOST" \
            -u"$MARIADB_ROOT_USER" \
            -p"$MARIADB_ROOT_PASSWORD" \
            "$DB_NAME" \
            -e "INSERT INTO schema_migrations (migration) VALUES ('$MIGRATION_NAME');"

        echo "Migration $MIGRATION_NAME applied successfully."
    else
        echo "ERROR: Migration file not found."
        exit 1
    fi
else
    echo "Migration $MIGRATION_NAME already applied. Skipping."
fi

# ------------------------------------------------------------
# Ensure the admin account exists.
# Do NOT overwrite the password on every container restart.
# ------------------------------------------------------------
USER_EXISTS=$(mysql \
    -h"$MARIADB_HOST" \
    -u"$MARIADB_ROOT_USER" \
    -p"$MARIADB_ROOT_PASSWORD" \
    -N \
    -e "SELECT COUNT(*) FROM $DB_NAME.users WHERE username='admin';")

if [ "$USER_EXISTS" -eq 0 ]; then
    echo "Creating admin user..."

    ADMIN_PASSWORD="${ADMIN_PASSWORD:?ADMIN_PASSWORD is required}"
    export ADMIN_PASSWORD
    ADMIN_HASH=$(php -r 'echo password_hash(getenv("ADMIN_PASSWORD"), PASSWORD_BCRYPT, ["cost" => 12]);')

    mysql \
        -h"$MARIADB_HOST" \
        -u"$MARIADB_ROOT_USER" \
        -p"$MARIADB_ROOT_PASSWORD" \
        "$DB_NAME" \
        -e "
        INSERT INTO users (username, password, email, created_at)
        VALUES ('admin', '$ADMIN_HASH', 'admin@tailormate.local', NOW());
        "

    echo "Admin user created."
else
    echo "Admin user already exists. Password unchanged."
fi

echo "============================================"
echo " TailorMate is ready!"
echo "============================================"
echo " App URL:  http://localhost:8080"
echo " Login:    admin / configured ADMIN_PASSWORD"
echo " phpMyAdmin: http://localhost:8081"
echo "============================================"
