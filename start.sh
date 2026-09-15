#!/bin/bash
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "=== Nexus Platform Startup ==="

# ─── Select environment defaults ────────────────────────────────────────────
# Local development keeps the previous convenient defaults, while the
# published artifact supplies APP_ENV=production and APP_DEBUG=false.
APP_ENV_VALUE="${APP_ENV:-local}"
if [ -n "${APP_DEBUG+x}" ]; then
  APP_DEBUG_VALUE="$APP_DEBUG"
elif [ "$APP_ENV_VALUE" = "production" ]; then
  APP_DEBUG_VALUE="false"
else
  APP_DEBUG_VALUE="true"
fi

# ─── Parse DATABASE_URL for PostgreSQL credentials ──────────────────────────
if [ -n "$DATABASE_URL" ]; then
  _URL="${DATABASE_URL#postgresql://}"
  _URL="${_URL#postgres://}"
  _USERINFO="${_URL%%@*}"
  _HOSTPATH="${_URL#*@}"
  export DB_USERNAME="${_USERINFO%%:*}"
  export DB_PASSWORD="${_USERINFO#*:}"
  _HOSTPORT="${_HOSTPATH%%/*}"
  _DBPATH="${_HOSTPATH#*/}"
  export DB_HOST="${_HOSTPORT%%:*}"
  _PORT_PART="${_HOSTPORT#*:}"
  if [ "$_PORT_PART" = "$_HOSTPORT" ]; then
    export DB_PORT="5432"
  else
    export DB_PORT="$_PORT_PART"
  fi
  export DB_DATABASE="${_DBPATH%%\?*}"
fi

# ─── Generate APP_KEY if not set ────────────────────────────────────────────
if [ -z "$APP_KEY" ]; then
  echo "  Generating APP_KEY..."
  # Temporarily write a minimal env so key:generate can run
  cat > .env <<_TMPENV
APP_NAME="Nexus"
APP_ENV=${APP_ENV_VALUE}
APP_KEY=
APP_DEBUG=${APP_DEBUG_VALUE}
APP_URL=http://localhost
DB_CONNECTION=pgsql
DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}
_TMPENV
  export APP_KEY=$(php artisan key:generate --show --no-ansi 2>/dev/null)
fi

# ─── Write .env from environment variables ──────────────────────────────────
echo "  Writing .env..."
cat > .env <<ENV
APP_NAME="${APP_NAME:-Nexus Business Platform}"
APP_ENV=${APP_ENV_VALUE}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG_VALUE}
APP_URL=http://localhost:${PORT:-8000}
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=debug

DB_CONNECTION=pgsql
DB_HOST=${DB_HOST:-helium}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE:-heliumdb}
DB_USERNAME=${DB_USERNAME:-postgres}
DB_PASSWORD=${DB_PASSWORD}
DB_SSLMODE=disable

SESSION_DRIVER=${SESSION_DRIVER:-redis}
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=${QUEUE_CONNECTION:-redis}

CACHE_STORE=${CACHE_STORE:-redis}
CACHE_PREFIX=nexus_

REDIS_CLIENT=predis
REDIS_HOST=${REDIS_HOST:-127.0.0.1}
REDIS_PASSWORD=${REDIS_PASSWORD:-null}
REDIS_PORT=${REDIS_PORT:-6379}

MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@nexus.co.ke"
MAIL_FROM_NAME="Nexus Business Platform"

VITE_APP_NAME="Nexus Business Platform"
CENTRAL_DOMAINS="127.0.0.1,localhost"

TENANCY_DATABASE_AUTO_CREATE=false

ANTHROPIC_API_KEY=${ANTHROPIC_API_KEY:-}
ENV
echo "  .env written."

# ─── Start Redis in background ───────────────────────────────────────────────
if ! redis-cli ping > /dev/null 2>&1; then
  echo "  Starting Redis..."
  redis-server --daemonize yes --loglevel warning
  sleep 1
fi
echo "  Redis: $(redis-cli ping 2>/dev/null || echo 'unavailable')"

# ─── Clear caches before starting ──────────────────────────────────────────
php artisan config:clear --quiet 2>/dev/null || true
php artisan cache:clear --quiet 2>/dev/null || true

# ─── Run pending migrations ─────────────────────────────────────────────────
echo "  Running migrations..."
php artisan migrate --force --no-interaction 2>&1 | grep -E "DONE|ERROR|INFO|error" || true

# ─── Build frontend assets ──────────────────────────────────────────────────
echo "  Building frontend assets..."
npm run build 2>&1 | tail -8 || echo "  WARNING: Vite build had errors (check manually)"

# ─── Kill any stale artisan serve process holding the port ───────────────────
pkill -f "artisan serve" 2>/dev/null || true
sleep 1

# ─── Start server ────────────────────────────────────────────────────────────
PORT="${PORT:-8000}"
echo "  Starting Laravel on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port="$PORT"
