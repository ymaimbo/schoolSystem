#!/usr/bin/env bash
set -euo pipefail

TARGET_SCHOOL="Vigurungani Senior School"

DB_HOST=$(grep '^DB_HOST=' .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")
DB_PORT=$(grep '^DB_PORT=' .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")
DB_DATABASE=$(grep '^DB_DATABASE=' .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")
DB_USERNAME=$(grep '^DB_USERNAME=' .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")
DB_PASSWORD=$(grep '^DB_PASSWORD=' .env | cut -d '=' -f2- | tr -d '"' | tr -d "'")

DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}

echo "Target school: $TARGET_SCHOOL"
echo "Database: $DB_DATABASE @ $DB_HOST:$DB_PORT"
echo

read -r -p "Type YES to continue destructive cleanup: " CONFIRM
if [[ "$CONFIRM" != "YES" ]]; then
  echo "Aborted."
  exit 1
fi

MYSQL="MYSQL_PWD=$DB_PASSWORD mysql --default-character-set=utf8mb4 -h $DB_HOST -P $DB_PORT -u $DB_USERNAME $DB_DATABASE"

eval "$MYSQL" <<SQL
SET @target_name := '${TARGET_SCHOOL}';

SET @keep_school_id := (
  SELECT id
  FROM schools
  WHERE CONVERT(name USING utf8mb4) COLLATE utf8mb4_unicode_ci
      = CONVERT(@target_name USING utf8mb4) COLLATE utf8mb4_unicode_ci
  LIMIT 1
);

SELECT @keep_school_id AS keep_school_id, @target_name AS keep_school_name;

-- Safety: stop if school not found
SELECT CASE WHEN @keep_school_id IS NULL THEN
  (SELECT 1/0) ELSE 1 END AS ensure_school_exists;

-- Preview
SELECT 'schools_before' AS label, COUNT(*) AS total FROM schools;
SELECT 'users_before' AS label, COUNT(*) AS total FROM users;
SELECT id, name, slug FROM schools ORDER BY id;

-- Backup tables
SET @ts := DATE_FORMAT(NOW(), '%Y%m%d_%H%i%S');
SET @schools_bk := CONCAT('schools_backup_before_keep_vigurungani_', @ts);
SET @users_bk := CONCAT('users_backup_before_keep_vigurungani_', @ts);

SET @q1 := CONCAT('CREATE TABLE ', @schools_bk, ' AS SELECT * FROM schools');
PREPARE s1 FROM @q1; EXECUTE s1; DEALLOCATE PREPARE s1;

SET @q2 := CONCAT('CREATE TABLE ', @users_bk, ' AS SELECT * FROM users');
PREPARE s2 FROM @q2; EXECUTE s2; DEALLOCATE PREPARE s2;

SELECT @schools_bk AS schools_backup_table, @users_bk AS users_backup_table;

START TRANSACTION;

-- Keep users in kept school + super admins + global users with no school_id
DELETE FROM users
WHERE NOT (
  role = 'super_admin'
  OR school_id = @keep_school_id
  OR school_id IS NULL
);

-- Remove all schools except target
DELETE FROM schools
WHERE id <> @keep_school_id;

COMMIT;

-- Final verify
SELECT 'schools_after' AS label, COUNT(*) AS total FROM schools;
SELECT 'users_after' AS label, COUNT(*) AS total FROM users;

SELECT id, name, slug FROM schools ORDER BY id;
SELECT id, name, email, role, school_id FROM users ORDER BY id;
SQL

echo
echo "Cleanup complete."
