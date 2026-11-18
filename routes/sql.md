SQL SCRIPT — Preview + Purge + Verify/* ============================================================
   PREVIEW: SEE WHAT EXISTS BEFORE DELETE
   ============================================================ */

SELECT 'SUPER ADMIN USERS TO KEEP' AS section_label;
SELECT id, school_id, name, email, role, created_at, updated_at
FROM users
WHERE role = 'super_admin';

SELECT 'LIVE TABLE COUNTS BEFORE PURGE' AS section_label;

SELECT 'schools' AS table_name, COUNT(*) AS row_count FROM schools
UNION ALL SELECT 'users_non_super_admin', COUNT(*) FROM users WHERE role <> 'super_admin'
UNION ALL SELECT 'students', COUNT(*) FROM students
UNION ALL SELECT 'vote_heads', COUNT(*) FROM vote_heads
UNION ALL SELECT 'fee_profiles', COUNT(*) FROM fee_profiles
UNION ALL SELECT 'fee_profile_votehead_priorities', COUNT(*) FROM fee_profile_votehead_priorities
UNION ALL SELECT 'fee_structures', COUNT(*) FROM fee_structures
UNION ALL SELECT 'fee_structure_lines', COUNT(*) FROM fee_structure_lines
UNION ALL SELECT 'student_fee_accounts', COUNT(*) FROM student_fee_accounts
UNION ALL SELECT 'student_fee_ledgers', COUNT(*) FROM student_fee_ledgers
UNION ALL SELECT 'student_fee_allocations', COUNT(*) FROM student_fee_allocations
UNION ALL SELECT 'student_fee_payments', COUNT(*) FROM student_fee_payments
UNION ALL SELECT 'finance_transactions', COUNT(*) FROM finance_transactions
UNION ALL SELECT 'payment_vouchers', COUNT(*) FROM payment_vouchers
UNION ALL SELECT 'school_invoices', COUNT(*) FROM school_invoices
UNION ALL SELECT 'school_invoice_payments', COUNT(*) FROM school_invoice_payments
UNION ALL SELECT 'programs', COUNT(*) FROM programs
UNION ALL SELECT 'inventory_items', COUNT(*) FROM inventory_items
UNION ALL SELECT 'timetables', COUNT(*) FROM timetables
UNION ALL SELECT 'exams', COUNT(*) FROM exams
UNION ALL SELECT 'exam_results', COUNT(*) FROM exam_results
UNION ALL SELECT 'parent_messages', COUNT(*) FROM parent_messages
UNION ALL SELECT 'sports_department_records', COUNT(*) FROM sports_department_records
UNION ALL SELECT 'discipline_cases', COUNT(*) FROM discipline_cases
UNION ALL SELECT 'staff_members', COUNT(*) FROM staff_members
UNION ALL SELECT 'staff_records', COUNT(*) FROM staff_records
UNION ALL SELECT 'class_teacher_assignments', COUNT(*) FROM class_teacher_assignments
UNION ALL SELECT 'subject_teacher_assignments', COUNT(*) FROM subject_teacher_assignments
UNION ALL SELECT 'school_access_events', COUNT(*) FROM school_access_events
UNION ALL SELECT 'activity_logs', COUNT(*) FROM activity_logs
UNION ALL SELECT 'sessions', COUNT(*) FROM sessions
UNION ALL SELECT 'cache', COUNT(*) FROM cache
UNION ALL SELECT 'cache_locks', COUNT(*) FROM cache_locks
UNION ALL SELECT 'jobs', COUNT(*) FROM jobs
UNION ALL SELECT 'job_batches', COUNT(*) FROM job_batches
UNION ALL SELECT 'failed_jobs', COUNT(*) FROM failed_jobs
UNION ALL SELECT 'password_reset_tokens', COUNT(*) FROM password_reset_tokens
ORDER BY table_name;


/* ============================================================
   PURGE: DELETE ALL APP DATA, KEEP ONLY SUPER ADMIN USERS
   ============================================================ */

SET FOREIGN_KEY_CHECKS = 0;

/* ---------------------------
   CHILD / TRANSACTION TABLES
   --------------------------- */
DELETE FROM exam_results;
DELETE FROM student_fee_allocations;
DELETE FROM student_fee_ledgers;
DELETE FROM student_fee_payments;
DELETE FROM school_invoice_payments;
DELETE FROM class_teacher_assignments;
DELETE FROM subject_teacher_assignments;
DELETE FROM parent_messages;
DELETE FROM sports_department_records;
DELETE FROM discipline_cases;
DELETE FROM activity_logs;
DELETE FROM school_access_events;
DELETE FROM payment_vouchers;

/* ---------------------------
   MAIN BUSINESS TABLES
   --------------------------- */
DELETE FROM exams;
DELETE FROM student_fee_accounts;
DELETE FROM fee_structure_lines;
DELETE FROM fee_structures;
DELETE FROM fee_profile_votehead_priorities;
DELETE FROM fee_profiles;
DELETE FROM finance_transactions;
DELETE FROM school_invoices;
DELETE FROM timetables;
DELETE FROM inventory_items;
DELETE FROM programs;
DELETE FROM staff_members;
DELETE FROM staff_records;
DELETE FROM vote_heads;
DELETE FROM students;

/* ---------------------------
   PLATFORM / TEMP / SESSION DATA
   --------------------------- */
DELETE FROM sessions;
DELETE FROM cache;
DELETE FROM cache_locks;
DELETE FROM jobs;
DELETE FROM job_batches;
DELETE FROM failed_jobs;
DELETE FROM password_reset_tokens;

/* ---------------------------
   USERS: KEEP ONLY SUPER ADMIN
   --------------------------- */
DELETE FROM users
WHERE role <> 'super_admin';

/* make sure preserved super admins are detached from deleted schools */
UPDATE users
SET school_id = NULL
WHERE role = 'super_admin';

/* ---------------------------
   PARENT TABLES
   --------------------------- */
DELETE FROM schools;

SET FOREIGN_KEY_CHECKS = 1;


/* ============================================================
   OPTIONAL: RESET AUTO INCREMENT ON CLEARED TABLES
   (safe because data is gone; users table is intentionally skipped)
   ============================================================ */

ALTER TABLE schools AUTO_INCREMENT = 1;
ALTER TABLE students AUTO_INCREMENT = 1;
ALTER TABLE vote_heads AUTO_INCREMENT = 1;
ALTER TABLE fee_profiles AUTO_INCREMENT = 1;
ALTER TABLE fee_profile_votehead_priorities AUTO_INCREMENT = 1;
ALTER TABLE fee_structures AUTO_INCREMENT = 1;
ALTER TABLE fee_structure_lines AUTO_INCREMENT = 1;
ALTER TABLE student_fee_accounts AUTO_INCREMENT = 1;
ALTER TABLE student_fee_ledgers AUTO_INCREMENT = 1;
ALTER TABLE student_fee_allocations AUTO_INCREMENT = 1;
ALTER TABLE student_fee_payments AUTO_INCREMENT = 1;
ALTER TABLE finance_transactions AUTO_INCREMENT = 1;
ALTER TABLE payment_vouchers AUTO_INCREMENT = 1;
ALTER TABLE school_invoices AUTO_INCREMENT = 1;
ALTER TABLE school_invoice_payments AUTO_INCREMENT = 1;
ALTER TABLE programs AUTO_INCREMENT = 1;
ALTER TABLE inventory_items AUTO_INCREMENT = 1;
ALTER TABLE timetables AUTO_INCREMENT = 1;
ALTER TABLE exams AUTO_INCREMENT = 1;
ALTER TABLE exam_results AUTO_INCREMENT = 1;
ALTER TABLE parent_messages AUTO_INCREMENT = 1;
ALTER TABLE sports_department_records AUTO_INCREMENT = 1;
ALTER TABLE discipline_cases AUTO_INCREMENT = 1;
ALTER TABLE staff_members AUTO_INCREMENT = 1;
ALTER TABLE staff_records AUTO_INCREMENT = 1;
ALTER TABLE class_teacher_assignments AUTO_INCREMENT = 1;
ALTER TABLE subject_teacher_assignments AUTO_INCREMENT = 1;
ALTER TABLE school_access_events AUTO_INCREMENT = 1;
ALTER TABLE activity_logs AUTO_INCREMENT = 1;


/* ============================================================
   VERIFY: CONFIRM ONLY SUPER ADMIN USERS REMAIN
   ============================================================ */

SELECT 'USERS REMAINING AFTER PURGE' AS section_label;
SELECT id, school_id, name, email, role, created_at, updated_at
FROM users
ORDER BY id;

SELECT 'LIVE TABLE COUNTS AFTER PURGE' AS section_label;

SELECT 'schools' AS table_name, COUNT(*) AS row_count FROM schools
UNION ALL SELECT 'users_total', COUNT(*) FROM users
UNION ALL SELECT 'users_super_admin', COUNT(*) FROM users WHERE role = 'super_admin'
UNION ALL SELECT 'students', COUNT(*) FROM students
UNION ALL SELECT 'vote_heads', COUNT(*) FROM vote_heads
UNION ALL SELECT 'fee_profiles', COUNT(*) FROM fee_profiles
UNION ALL SELECT 'fee_profile_votehead_priorities', COUNT(*) FROM fee_profile_votehead_priorities
UNION ALL SELECT 'fee_structures', COUNT(*) FROM fee_structures
UNION ALL SELECT 'fee_structure_lines', COUNT(*) FROM fee_structure_lines
UNION ALL SELECT 'student_fee_accounts', COUNT(*) FROM student_fee_accounts
UNION ALL SELECT 'student_fee_ledgers', COUNT(*) FROM student_fee_ledgers
UNION ALL SELECT 'student_fee_allocations', COUNT(*) FROM student_fee_allocations
UNION ALL SELECT 'student_fee_payments', COUNT(*) FROM student_fee_payments
UNION ALL SELECT 'finance_transactions', COUNT(*) FROM finance_transactions
UNION ALL SELECT 'payment_vouchers', COUNT(*) FROM payment_vouchers
UNION ALL SELECT 'school_invoices', COUNT(*) FROM school_invoices
UNION ALL SELECT 'school_invoice_payments', COUNT(*) FROM school_invoice_payments
UNION ALL SELECT 'programs', COUNT(*) FROM programs
UNION ALL SELECT 'inventory_items', COUNT(*) FROM inventory_items
UNION ALL SELECT 'timetables', COUNT(*) FROM timetables
UNION ALL SELECT 'exams', COUNT(*) FROM exams
UNION ALL SELECT 'exam_results', COUNT(*) FROM exam_results
UNION ALL SELECT 'parent_messages', COUNT(*) FROM parent_messages
UNION ALL SELECT 'sports_department_records', COUNT(*) FROM sports_department_records
UNION ALL SELECT 'discipline_cases', COUNT(*) FROM discipline_cases
UNION ALL SELECT 'staff_members', COUNT(*) FROM staff_members
UNION ALL SELECT 'staff_records', COUNT(*) FROM staff_records
UNION ALL SELECT 'class_teacher_assignments', COUNT(*) FROM class_teacher_assignments
UNION ALL SELECT 'subject_teacher_assignments', COUNT(*) FROM subject_teacher_assignments
UNION ALL SELECT 'school_access_events', COUNT(*) FROM school_access_events
UNION ALL SELECT 'activity_logs', COUNT(*) FROM activity_logs
UNION ALL SELECT 'sessions', COUNT(*) FROM sessions
UNION ALL SELECT 'cache', COUNT(*) FROM cache
UNION ALL SELECT 'cache_locks', COUNT(*) FROM cache_locks
UNION ALL SELECT 'jobs', COUNT(*) FROM jobs
UNION ALL SELECT 'job_batches', COUNT(*) FROM job_batches
UNION ALL SELECT 'failed_jobs', COUNT(*) FROM failed_jobs
UNION ALL SELECT 'password_reset_tokens', COUNT(*) FROM password_reset_tokens
ORDER BY table_name;