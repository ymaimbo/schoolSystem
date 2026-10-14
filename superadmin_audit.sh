#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"

GREEN="\033[0;32m"
RED="\033[0;31m"
YELLOW="\033[1;33m"
NC="\033[0m"

ok()   { echo -e "${GREEN}[OK]${NC} $1"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
err()  { echo -e "${RED}[FAIL]${NC} $1"; }

echo "=== Super Admin Audit ==="

# 1) Basic app checks
[[ -f artisan ]] && ok "artisan found" || { err "artisan not found"; exit 1; }
[[ -f .env ]] && ok ".env found" || { err ".env not found"; exit 1; }

php -v >/dev/null 2>&1 && ok "PHP available" || { err "PHP missing"; exit 1; }
php artisan --version >/dev/null 2>&1 && ok "Laravel booted" || { err "Laravel failed to boot"; exit 1; }

# 2) Config sanity
APP_ENV=$(grep '^APP_ENV=' .env | cut -d '=' -f2- || true)
APP_DEBUG=$(grep '^APP_DEBUG=' .env | cut -d '=' -f2- || true)
APP_URL=$(grep '^APP_URL=' .env | cut -d '=' -f2- || true)

echo "APP_ENV=$APP_ENV"
echo "APP_DEBUG=$APP_DEBUG"
echo "APP_URL=$APP_URL"

# 3) Required classes
classes=(
  "app/Http/Middleware/EnsureUserHasRole.php"
  "app/Http/Middleware/ResolveSchool.php"
  "app/Http/Controllers/SchoolRegistrationController.php"
  "app/Http/Controllers/SuperAdmin/InvoiceManagementController.php"
)
for f in "${classes[@]}"; do
  [[ -f "$f" ]] && ok "File exists: $f" || err "Missing file: $f"
done

# 4) Route checks
ROUTES=$(php artisan route:list --path=super-admin 2>/dev/null || true)

echo "$ROUTES" | grep -q "superadmin.dashboard" && ok "Route exists: superadmin.dashboard" || err "Missing route: superadmin.dashboard"
echo "$ROUTES" | grep -q "superadmin.schools.register" && ok "Route exists: superadmin.schools.register" || err "Missing route: superadmin.schools.register"
echo "$ROUTES" | grep -q "superadmin.schools.store" && ok "Route exists: superadmin.schools.store" || err "Missing route: superadmin.schools.store"
echo "$ROUTES" | grep -q "superadmin.schools.principal.store" && ok "Route exists: superadmin.schools.principal.store" || err "Missing route: superadmin.schools.principal.store"
echo "$ROUTES" | grep -q "superadmin.schools.update" && ok "Route exists: superadmin.schools.update" || err "Missing route: superadmin.schools.update"
echo "$ROUTES" | grep -q "superadmin.principals.update" && ok "Route exists: superadmin.principals.update" || err "Missing route: superadmin.principals.update"
echo "$ROUTES" | grep -q "superadmin.billing.index" && ok "Route exists: superadmin.billing.index" || err "Missing route: superadmin.billing.index"
echo "$ROUTES" | grep -q "superadmin.billing.store" && ok "Route exists: superadmin.billing.store" || err "Missing route: superadmin.billing.store"
echo "$ROUTES" | grep -q "superadmin.billing.edit" && ok "Route exists: superadmin.billing.edit" || err "Missing route: superadmin.billing.edit"
echo "$ROUTES" | grep -q "superadmin.billing.update" && ok "Route exists: superadmin.billing.update" || err "Missing route: superadmin.billing.update"

# 5) Middleware alias checks in source
if grep -R "resolve.school" bootstrap/app.php app/Http/Kernel.php >/dev/null 2>&1; then
  ok "Middleware alias resolve.school found"
else
  err "Middleware alias resolve.school not found"
fi

if grep -R "role" bootstrap/app.php app/Http/Kernel.php >/dev/null 2>&1; then
  ok "Role middleware alias found"
else
  err "Role middleware alias not found"
fi

# 6) Migration status
php artisan migrate:status >/tmp/migrate_status.txt 2>/dev/null && ok "Migration status readable" || err "Cannot read migration status"

# 7) Super admin users check (users.role pattern)
DB_HOST=$(grep '^DB_HOST=' .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")
DB_PORT=$(grep '^DB_PORT=' .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")
DB_DATABASE=$(grep '^DB_DATABASE=' .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")
DB_USERNAME=$(grep '^DB_USERNAME=' .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")
DB_PASSWORD=$(grep '^DB_PASSWORD=' .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}

if command -v mysql >/dev/null 2>&1; then
  echo "=== Super Admin Accounts ==="
  MYSQL_PWD="$DB_PASSWORD" mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" "$DB_DATABASE" -e "
    SELECT id,name,email,role,created_at
    FROM users
    WHERE role='super_admin'
    ORDER BY id;
  " || warn "Could not query users table (check DB creds or schema)"
else
  warn "mysql client not installed, skipped DB checks"
fi

echo
echo "=== Audit finished ==="
