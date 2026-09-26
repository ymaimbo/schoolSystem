# Developer Manual: Database Schema & Data Model

> Stack: Laravel + Inertia + Vue  
> Architecture: Multi-school (single DB, school-scoped tenancy)

This guide helps new developers understand how the database is structured and how modules relate.

---

## 1) System Architecture

### 1.1 Tenancy model
The app uses **single-database multi-tenancy**:

- Every school is a tenant.
- Tenant-owned tables include `school_id`.
- Current school context is resolved via middleware (`ResolveSchool`).
- Most queries must be scoped with `where('school_id', app('currentSchool')->id)`.

### 1.2 Authorization model
Roles are enforced through route middleware + controller logic.

Typical roles:

- `super_admin`
- `principal`
- `deputy_principal`
- `dean`
- `accountant`
- `secretary`
- `store_keeper`
- `class_teacher`

---

## 2) Core Tables

## 2.1 `schools`
Tenant root entity.

Common columns:
- `id`
- `name`
- `slug` (unique)
- `code` (unique)
- metadata fields
- timestamps

Used by almost all tenant-owned tables.

---

## 2.2 `users`
Authentication/identity table.

Important:
- role support via `role` column
- unique email index
- usually includes `school_id` for tenant users

---

## 2.3 `activity_logs`
Audit trail table for system/user actions.

Typical fields:
- actor (`user_id`)
- event/action
- target table/model/id
- payload/meta
- timestamps

---

## 3) Academics Domain

## 3.1 `students`
Student profile and enrollment-related data.

Migration history suggests:
- core profile fields
- guardian/parent fields
- CBC/senior school fields
- soft deletes enabled

---

## 3.2 `programs`
Academic programs or class structures per school.

---

## 3.3 `exams`
Exam definitions per school/term/year.

---

## 3.4 `exam_results`
Stores student marks/results per exam.

Common links:
- `student_id`
- `exam_id`
- `school_id`

---

## 3.5 `timetables`
Class schedule entries per school.

---

## 3.6 `class_teacher_assignments`
Maps class teachers to class-level/stream for each school.

Recommended columns:
- `id`
- `school_id`
- `user_id` (teacher user)
- `class_level`
- `stream` nullable
- `is_active`
- timestamps

Recommended constraints:
- unique (`school_id`, `user_id`)
- index on (`school_id`, `class_level`, `stream`)

---

## 4) Finance Domain (School Internal)

## 4.1 `vote_heads`
Fee/account categories.

## 4.2 `fee_structures`
Fee templates.

## 4.3 `fee_structure_lines`
Line items under fee structures.

## 4.4 `student_fee_accounts`
Per-student fee account summary.

## 4.5 `student_fee_ledgers`
Debit/credit ledger entries.

## 4.6 `student_fee_allocations`
Fee allocation rows by category/term.

## 4.7 `student_fee_payments`
Payment records and receipt references.

## 4.8 `finance_transactions`
General finance transactions (soft deletes enabled).

## 4.9 `payment_vouchers`
Voucher workflow records.

---

## 5) Billing Domain (Platform/SaaS)

Separate from student fees.

## 5.1 `school_invoices`
Invoices issued to schools by platform.

## 5.2 `school_invoice_payments`
Payments against platform invoices.

---

## 6) Operations Domain

## 6.1 `inventory_items`
Store/stock records.

Migration history indicates normalization and schema fixes over time.

---

## 7) Welfare & Communication Domain

## 7.1 `discipline_cases`
Student discipline case tracking.

## 7.2 `sports_department_records`
Sports activity and records.

## 7.3 `parent_messages`
Parent/school communications log.

---

## 8) Staff Domain

## 8.1 `staff_members`
Core staff identity/registry.

## 8.2 `staff_records`
Operational staff module records.

Recent migration indicates `login_user_id` linkage to `users`.

---

## 9) Conceptual Relationships (ERD-level)

- School 1---* Users
- School 1---* Students
- School 1---* Programs
- School 1---* Exams
- Exam 1---* ExamResults
- Student 1---* ExamResults
- School 1---* Timetables
- School 1---* FinanceTransactions
- School 1---* InventoryItems
- School 1---* DisciplineCases
- School 1---* SportsDepartmentRecords
- School 1---* ParentMessages
- School 1---* ClassTeacherAssignments
- User(class_teacher) 1---* ClassTeacherAssignments
- School 1---* FeeStructures
- FeeStructure 1---* FeeStructureLines
- Student 1---1 / 1---* StudentFeeAccount(s) (depends on implementation)
- StudentFeeAccount 1---* StudentFeeLedger
- StudentFeeAccount 1---* StudentFeePayments
- School 1---* SchoolInvoices
- SchoolInvoice 1---* SchoolInvoicePayments

---

## 10) Route Module → Table Mapping (based on `web.php`)

- `students.*` → `students`
- `programs.*` → `programs`
- `exams.*`, `exam-results.*` → `exams`, `exam_results`
- `timetable.*` → `timetables`
- `finance.*`, `finance.voucher.*` → `finance_transactions`, `payment_vouchers`
- `finance.fee-structures.*` → `fee_structures`, `fee_structure_lines`, `vote_heads`
- `student-finance.*` → student fee tables
- `store.*` → `inventory_items`
- `parents.*`, `communications.*` → `students` + `parent_messages`
- `sports.*` → `sports_department_records`
- `discipline.*` → `discipline_cases`
- `staff.*` → `staff_records` (and/or `staff_members`)
- `class-teacher-assignments.*` → `class_teacher_assignments`
- `billing.*` → `school_invoices`, `school_invoice_payments`

---

## 11) Developer Rules (Must Follow)

1. Always enforce school scope on tenant queries.
2. Use FormRequest classes for validation.
3. Scope unique rules with `school_id`.
4. Keep `$fillable` explicit in models.
5. Add foreign keys + indexes in migrations.
6. Don’t bypass middleware role controls.
7. Update docs whenever schema changes.

---

## 12) Integrity Checklist for New Tables

When adding school-owned tables:

- [ ] add `school_id` FK
- [ ] add `school_id` index
- [ ] add tenant-scoped unique constraints where needed
- [ ] add model relationships
- [ ] add factory/seeder/test updates

---

## 13) Common Issues

### 13.1 Class not found (e.g. `App\Models\ClassTeacherAssignment`)
Fix:
1. Ensure model file exists.
2. Namespace must match file location.
3. Ensure controller import is correct.
4. Run:
   - `composer dump-autoload`
   - `php artisan optimize:clear`

### 13.2 Table not found
Fix:
- Create migration
- `php artisan migrate`

### 13.3 Data leakage across schools
Fix:
- Add missing `school_id` filters
- Scope uniqueness per school

---

## 14) New Developer Quickstart

1. Clone repo and install dependencies.
2. Configure `.env`.
3. Run `php artisan migrate --seed`.
4. Create/use super admin.
5. Register school + principal.
6. Test modules in sequence:
   - Students → Programs → Exams → Finance → Store → Staff → Class Teacher Assignment.

---

## 15) Maintenance Policy

Every DB change PR should include:
- migration
- model updates
- validation updates
- relationship updates
- manual update (`DEVELOPER_MANUAL_DATABASE_SCHEMA.md`)