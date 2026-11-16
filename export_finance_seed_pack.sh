#!/usr/bin/env bash
set -euo pipefail

DB_NAME="vigurunganiSenior"
DB_USER="root"
DB_PASS=""   # set if needed, e.g. DB_PASS="yourpass"
DB_HOST="127.0.0.1"
DB_PORT="3306"

OUT_DIR="finance_seed_pack_$(date +%Y%m%d_%H%M%S)"
mkdir -p "$OUT_DIR"

MYSQL_BASE=(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER")
DUMP_BASE=(mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER")

if [[ -n "${DB_PASS}" ]]; then
  MYSQL_BASE+=(-p"$DB_PASS")
  DUMP_BASE+=(-p"$DB_PASS")
fi

echo "==> Resolving school id..."
SCHOOL_ID="$("${MYSQL_BASE[@]}" -Nse "SELECT id FROM ${DB_NAME}.schools WHERE name LIKE '%Vigurungani%' ORDER BY id LIMIT 1;")"
SCHOOL_ID="${SCHOOL_ID:-1}"
echo "Using SCHOOL_ID=$SCHOOL_ID"

echo "==> Exporting schema (structure only)..."
"${DUMP_BASE[@]}" \
  --no-data \
  --routines --events --triggers \
  "$DB_NAME" \
  > "$OUT_DIR/00_schema.sql"

echo "==> Exporting reference tables..."
"${DUMP_BASE[@]}" \
  --no-create-info \
  --skip-triggers \
  --complete-insert \
  --extended-insert=FALSE \
  "$DB_NAME" \
  schools vote_heads fee_profiles fee_profile_votehead_priorities \
  > "$OUT_DIR/01_reference_data.sql"

echo "==> Exporting students scoped to school..."
"${DUMP_BASE[@]}" \
  --no-create-info \
  --skip-triggers \
  --complete-insert \
  --extended-insert=FALSE \
  --where="school_id=${SCHOOL_ID}" \
  "$DB_NAME" \
  students \
  > "$OUT_DIR/02_students_data.sql"

echo "==> Exporting fee structures scoped to school..."
"${DUMP_BASE[@]}" \
  --no-create-info \
  --skip-triggers \
  --complete-insert \
  --extended-insert=FALSE \
  --where="school_id=${SCHOOL_ID}" \
  "$DB_NAME" \
  fee_structures \
  > "$OUT_DIR/03_fee_structures_data.sql"

echo "==> Exporting fee structure lines linked to scoped structures..."
"${DUMP_BASE[@]}" \
  --no-create-info \
  --skip-triggers \
  --complete-insert \
  --extended-insert=FALSE \
  --where="fee_structure_id IN (SELECT id FROM fee_structures WHERE school_id=${SCHOOL_ID})" \
  "$DB_NAME" \
  fee_structure_lines \
  > "$OUT_DIR/04_fee_structure_lines_data.sql"

echo "==> Exporting student fee accounts scoped to school..."
"${DUMP_BASE[@]}" \
  --no-create-info \
  --skip-triggers \
  --complete-insert \
  --extended-insert=FALSE \
  --where="school_id=${SCHOOL_ID}" \
  "$DB_NAME" \
  student_fee_accounts \
  > "$OUT_DIR/05_student_fee_accounts_data.sql"

echo "==> Exporting student fee ledgers scoped to school..."
"${DUMP_BASE[@]}" \
  --no-create-info \
  --skip-triggers \
  --complete-insert \
  --extended-insert=FALSE \
  --where="school_id=${SCHOOL_ID}" \
  "$DB_NAME" \
  student_fee_ledgers \
  > "$OUT_DIR/06_student_fee_ledgers_data.sql"

echo "==> Exporting student fee payments scoped to school..."
"${DUMP_BASE[@]}" \
  --no-create-info \
  --skip-triggers \
  --complete-insert \
  --extended-insert=FALSE \
  --where="school_id=${SCHOOL_ID}" \
  "$DB_NAME" \
  student_fee_payments \
  > "$OUT_DIR/07_student_fee_payments_data.sql"

echo "==> Exporting student fee allocations via payment linkage..."
"${DUMP_BASE[@]}" \
  --no-create-info \
  --skip-triggers \
  --complete-insert \
  --extended-insert=FALSE \
  --where="student_fee_payment_id IN (SELECT id FROM student_fee_payments WHERE school_id=${SCHOOL_ID})" \
  "$DB_NAME" \
  student_fee_allocations \
  > "$OUT_DIR/08_student_fee_allocations_data.sql"

echo "==> Exporting diagnostics..."
"${MYSQL_BASE[@]}" "$DB_NAME" <<SQL > "$OUT_DIR/09_diagnostics.txt"
SELECT 'students' AS table_name, COUNT(*) AS total FROM students WHERE school_id=${SCHOOL_ID}
UNION ALL SELECT 'fee_structures', COUNT(*) FROM fee_structures WHERE school_id=${SCHOOL_ID}
UNION ALL SELECT 'fee_structure_lines', COUNT(*) FROM fee_structure_lines WHERE fee_structure_id IN (SELECT id FROM fee_structures WHERE school_id=${SCHOOL_ID})
UNION ALL SELECT 'student_fee_accounts', COUNT(*) FROM student_fee_accounts WHERE school_id=${SCHOOL_ID}
UNION ALL SELECT 'student_fee_ledgers', COUNT(*) FROM student_fee_ledgers WHERE school_id=${SCHOOL_ID}
UNION ALL SELECT 'student_fee_payments', COUNT(*) FROM student_fee_payments WHERE school_id=${SCHOOL_ID}
UNION ALL SELECT 'student_fee_allocations', COUNT(*) FROM student_fee_allocations WHERE student_fee_payment_id IN (SELECT id FROM student_fee_payments WHERE school_id=${SCHOOL_ID});

SHOW CREATE TABLE student_fee_accounts;
SHOW CREATE TABLE student_fee_ledgers;
SHOW CREATE TABLE student_fee_payments;
SHOW CREATE TABLE student_fee_allocations;
SHOW CREATE TABLE fee_structures;
SHOW CREATE TABLE fee_structure_lines;
SHOW CREATE TABLE students;
SQL

echo "==> Building combined SQL payload..."
cat \
  "$OUT_DIR/01_reference_data.sql" \
  "$OUT_DIR/02_students_data.sql" \
  "$OUT_DIR/03_fee_structures_data.sql" \
  "$OUT_DIR/04_fee_structure_lines_data.sql" \
  "$OUT_DIR/05_student_fee_accounts_data.sql" \
  "$OUT_DIR/06_student_fee_ledgers_data.sql" \
  "$OUT_DIR/07_student_fee_payments_data.sql" \
  "$OUT_DIR/08_student_fee_allocations_data.sql" \
  > "$OUT_DIR/10_finance_seed_data_combined.sql"

echo "==> Done."
echo "Output folder: $OUT_DIR"
ls -1 "$OUT_DIR"
