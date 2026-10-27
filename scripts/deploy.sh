#!/bin/sh
# First deployment from the committed, sanitized release. Never import into a populated database.
set -eu
cd "$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"
test -f .env || { echo 'Generate .env first: python3 scripts/prepare-env.py --site-url https://your-domain.example' >&2; exit 1; }
node scripts/verify-release.cjs --files
docker compose build php
docker compose up -d --wait mysql redis
table_count=$(docker compose exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" mysql -N -u "$MYSQL_USER" "$MYSQL_DATABASE" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE()"')
test "$table_count" = 0 || { echo 'Database contains tables. Import stopped; no existing data changed.' >&2; exit 1; }
cat database/schema.sql database/data.sql | docker compose exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_PASSWORD" mysql -u "$MYSQL_USER" "$MYSQL_DATABASE"'
docker compose up -d php
docker compose exec -T php php /opt/flipbook/scripts/bootstrap-release.php
docker compose exec -T --user www-data php php /opt/flipbook/scripts/rebuild-release-cache.php
docker compose up -d nginx cron
echo 'Deployment initialized. Configure HTTPS for SITE_URL and complete private WeChat/storage/payment settings in administration.'
