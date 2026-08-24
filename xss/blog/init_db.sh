#!/bin/bash
set -e

# Replace the placeholder 'admin_password' with the value from the environment variable
# and execute the SQL script.
sed "s/admin_password/$ADMIN_PASSWORD/g" /tmp/db.sql | mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"
