# Developer Manual: API / Controller Flow (Request → Controller → Model → View)

> Stack: Laravel + Inertia + Vue

This guide explains how an HTTP request travels through the app, and where developers should place logic.

---

## 1) Flow Overview

Standard flow in this project:

1. **Route (`routes/web.php`)**
2. **Middleware stack** (auth, verified, role, resolve_school, class_teacher scope)
3. **FormRequest validation/authorization**
4. **Controller action**
5. **Model/Query layer**
6. **Inertia response (Vue page + props)** OR redirect/json
7. **Frontend rendering and interaction**

---

## 2) Route Layer (`routes/web.php`)

Routes are grouped by:

- authentication (`auth`, `verified`)
- role checks (`role:...`)
- tenant school context (`resolve_school`)
- module prefix (`admin`, `class-room`, etc.)

Example module routes:
- students
- programs
- exams / exam results
- timetable
- finance
- store
- parent/communications
- sports
- discipline
- staff
- class teacher assignments
- billing (with super-admin paths)

### Route naming convention
Use `module.action` style:
- `students.index`
- `finance.voucher.store`
- `class-teacher-assignments.update`

---

## 3) Middleware Layer

Common middleware behavior:

- `auth`: signed-in user required
- `verified`: verified account required
- `role:*`: role-based authorization gates
- `resolve_school`: binds current school to app container
- class teacher scope middleware for class-room routes

### Important
If a controller action accesses tenant data without `resolve_school`, it may leak data.

---

## 4) Request Validation Layer (FormRequest)

For mutating endpoints (`store`, `update`), use FormRequests:

- `authorize()` enforces user eligibility
- `rules()` enforces payload constraints

Example pattern:
- `Rule::exists(...)->where('school_id', $schoolId)`
- `Rule::unique(...)->where('school_id', $schoolId)`

### Why this matters
Validation is first line of data integrity and tenant isolation.

---

## 5) Controller Layer

Controllers should:

1. Read current school context
2. Apply scoped queries
3. Perform domain action (create/update/delete)
4. Return Inertia view or redirect with flash message

### Good controller structure
- `index()` for listing/dashboard data
- `store()` create with validated payload
- `update()` mutate existing model
- `destroy()` remove model
- private helper methods for repeated logic

### Example (ClassTeacherAssignmentController)
- `index`: fetch assignments + teachers + unassigned classes
- `store`: create assignment (scoped by school)
- `update`: mutate assignment with ownership check
- `destroy`: delete assignment with ownership check

---

## 6) Model Layer

Models represent tables and relationships.

Expected model responsibilities:
- `$fillable` fields
- `$casts`
- relationships (`belongsTo`, `hasMany`)
- limited query scopes if useful

Business logic should be split thoughtfully:
- simple behavior in model
- complex orchestration in service classes/controllers

---

## 7) Inertia Response Layer

For page endpoints, controllers return:

- `Inertia::render('Path/To/Page', [...props])`

Props typically contain:
- `activeView`
- list data (assignments, students, etc.)
- summary stats
- dropdown options / form data

Frontend (Vue) receives props and handles:
- table rendering
- modal/forms
- action submit via Inertia POST/PUT/DELETE

---

## 8) Module-by-Module Flow Summary

## 8.1 Students
- Route: `students.*`
- Request: create/update request classes
- Controller: `StudentController`
- Model: `Student`
- View: admin/students Inertia page

## 8.2 Programs
- Route: `programs.*`
- Controller: `ProgramAdminController`
- Model: `Program`
- View: programs page

## 8.3 Exams + Results
- Routes: `exams.*`, `exam-results.*`
- Controller: `ExamController`
- Models: `Exam`, `ExamResult`
- Supports missing-result checks + mark entry

## 8.4 Timetable
- Route: `timetable.*`
- Controller: `TimetableController`
- Model: `Timetable`

## 8.5 Finance
- Route: `finance.*`, `finance.voucher.*`, `finance.fee-structures.*`
- Controllers: `FinanceController`, `FeeStructureController`, `StudentFinanceController`
- Models: `FinanceTransaction`, `PaymentVoucher`, fee tables

## 8.6 Store
- Route: `store.*`
- Controller: `StoreController`
- Model: `InventoryItem`

## 8.7 Parent + Communications
- Routes: `parents.*`, `communications.*`
- Controllers: `ParentGuardianController`, `ParentCommunicationController`
- Models: `Student`, `ParentMessage`

## 8.8 Sports
- Route: `sports.*`
- Controller: `SportsDepartmentController`
- Model: `SportsDepartmentRecord`

## 8.9 Discipline
- Route: `discipline.*`
- Controller: `DisciplineDepartmentController`
- Model: `DisciplineCase`

## 8.10 Staff
- Route: `staff.*`
- Controller: `StaffDirectoryController`
- Models: `StaffRecord` / `StaffMember`

## 8.11 Class Teacher Assignments
- Route: `class-teacher-assignments.*`
- Controller: `ClassTeacherAssignmentController`
- Request classes:
  - `StoreClassTeacherAssignmentRequest`
  - `UpdateClassTeacherAssignmentRequest`
- Model: `ClassTeacherAssignment`
- View: `Admin/ClassTeacherAssignments/Index`

## 8.12 Billing (Platform)
- Super admin billing routes + admin billing routes
- Controllers: `InvoiceManagementController`, `BillingController`
- Models: `SchoolInvoice`, `SchoolInvoicePayment`

---

## 9) Coding Standards for Controllers

1. Keep actions short and explicit.
2. Validate with FormRequest only.
3. Scope every query by `school_id` where applicable.
4. Use route-model binding where possible.
5. Add ownership checks before update/delete:
   - `abort_if((int)$model->school_id !== (int)$school->id, 404);`
6. Return clear success/error flash messages.
7. Avoid heavy logic in views.

---

## 10) Error Handling Playbook

### Case: `Class ... not found`
- Missing model file or namespace mismatch
- Fix import and run autoload clear commands

### Case: `SQLSTATE table not found`
- Missing migration or not migrated

### Case: `403 Unauthorized`
- Check route role middleware + FormRequest `authorize()`

### Case: wrong school data shown
- Missing school scope condition in query

---

## 11) Recommended Service Layer Pattern (Next Improvement)

For growing complexity, add services:

- `app/Services/StudentService.php`
- `app/Services/FinanceService.php`
- `app/Services/ClassTeacherAssignmentService.php`

Controller delegates business rules to service.
Benefits:
- reusable domain logic
- cleaner controllers
- easier testing

---

## 12) Testing Strategy for Flow

Minimum tests per module:

1. Feature test for role access
2. Feature test for tenant isolation
3. Validation test for bad payloads
4. Happy path create/update/delete test
5. Inertia response props assertion

---

## 13) New Developer Fast Path

1. Start from `routes/web.php` to identify module entrypoint.
2. Open mapped controller action.
3. Check FormRequest used by action.
4. Check corresponding model relationships.
5. Inspect Inertia view path in `Inertia::render(...)`.
6. Track frontend page under `resources/js/Pages/...`.

---

## 14) Documentation Update Rule

Any PR that changes:
- routes
- request validation
- model ownership rules
- controller flow

must update this manual file:
`DEVELOPER_MANUAL_API_CONTROLLER_FLOW.md`






DEVELOPER_MANUAL_DEPLOYMENT_AND_DEBUG.md
Markdown

# Developer Manual: Deployment & Debug Operations

> Stack: Laravel + Inertia + Vue (+ Vite)  
> Focus: Production deployment, queue, cache, logs, and emergency troubleshooting

This guide is for developers and maintainers responsible for deploying and operating this system reliably.

---

## 1) Deployment Philosophy

1. **Predictable releases**: every deployment should be repeatable.
2. **Safe migrations**: avoid breaking schema changes.
3. **Observability first**: logs/health checks should clearly show app status.
4. **Fast rollback path**: keep ability to revert quickly.

---

## 2) Production Environment Requirements

- PHP (version matching your local/dev)
- Composer
- Node.js + npm (for Vite build)
- MySQL/MariaDB (or your chosen DB)
- Nginx/Apache
- Supervisor (for queue workers)
- Cron (for scheduler)
- SSL/TLS certificates
- Proper file permissions for `storage/` and `bootstrap/cache/`

---

## 3) Required Environment Variables (`.env`)

At minimum verify:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain`
- `APP_KEY=base64:...`
- `DB_CONNECTION=...`
- `DB_HOST=...`
- `DB_PORT=...`
- `DB_DATABASE=...`
- `DB_USERNAME=...`
- `DB_PASSWORD=...`
- `CACHE_DRIVER=redis` (recommended)
- `QUEUE_CONNECTION=redis` (recommended)
- `SESSION_DRIVER=redis` (recommended for scale)
- `LOG_CHANNEL=stack` or `daily`
- Mail/SMS/payment provider keys as needed

> Never commit secrets. Use server secret manager or deployment platform env variables.

---

## 4) One-Time Server Setup Checklist

- [ ] Clone project on server
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `npm ci && npm run build`
- [ ] Configure web root to `public/`
- [ ] Set write permissions:
  - `storage/`
  - `bootstrap/cache/`
- [ ] `php artisan key:generate` (only once for new environment)
- [ ] `php artisan migrate --force`
- [ ] `php artisan storage:link` (if needed)
- [ ] Configure Supervisor queue worker
- [ ] Configure cron for scheduler
- [ ] Enable HTTPS and security headers

---

## 5) Standard Deployment Procedure (Release Runbook)

## 5.1 Pre-deploy checks
1. Ensure CI/tests pass.
2. Confirm migration impact (especially large tables).
3. Verify rollback strategy for this release.
4. Put team on deployment window if needed.

## 5.2 Deploy commands (typical)
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan queue:restart
If you use Horizon: restart Horizon instead of plain queue workers.

5.3 Post-deploy verification
 Login works
 Main dashboard loads
 Critical modules open (students/finance/exams/store)
 Queue worker processing jobs
 Scheduler tasks running
 No 500 errors in logs
 Health endpoint passes (if implemented)
6) Queue Operations
6.1 Worker command (generic)
Bash

php artisan queue:work --sleep=3 --tries=3 --timeout=90
6.2 Supervisor example
/etc/supervisor/conf.d/laravel-worker.conf

ini

[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/your-app/artisan queue:work --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/your-app/storage/logs/worker.log
stopwaitsecs=3600
Then:

Bash

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
6.3 Queue debug commands
Bash

php artisan queue:failed
php artisan queue:retry all
php artisan queue:flush
php artisan queue:restart
7) Scheduler Operations
7.1 Cron entry
cron

* * * * * cd /var/www/your-app && php artisan schedule:run >> /dev/null 2>&1
7.2 Verify scheduler
Bash

php artisan schedule:list
If scheduled tasks aren’t executing:

confirm cron service is running
confirm correct app path
check user permissions
inspect app logs around expected run time
8) Caching Strategy & Commands
8.1 What to cache in production
config
routes
views
events (if used)
8.2 Common cache commands
Bash

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
8.3 When to clear cache
Clear/rebuild cache after:

.env changes
route changes
config changes
provider/service binding changes
9) Logs & Observability
9.1 Laravel logs
Primary logs:

storage/logs/laravel.log
or daily rotated files (storage/logs/laravel-YYYY-MM-DD.log)
Tail logs:

Bash

tail -f storage/logs/laravel.log
9.2 Web server logs
Check Nginx/Apache logs for upstream/connectivity issues:

Nginx error/access logs
Apache error/access logs
9.3 Queue logs
If using Supervisor custom log:

storage/logs/worker.log
9.4 What to log for incidents
timestamp/timezone
user id/role/school id (if available)
route name
request id / correlation id (if implemented)
stack trace
SQL error code
10) Database Migration Safety
10.1 Rules
Always run migrations with --force in production.
Avoid destructive changes in peak hours.
Use additive-first migration strategy:
add new column/table
backfill data
switch code
remove old column later
Backup DB before risky schema changes.
10.2 Pre-migration checklist
 confirmed DB backup exists
 tested migration on staging
 estimated lock time for altered table
 rollback command/plan prepared
11) Emergency Troubleshooting Playbooks
11.1 Site returns HTTP 500
Check Laravel log:
Bash

tail -n 200 storage/logs/laravel.log
Check web server error log.
Clear and rebuild caches:
Bash

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
Validate .env values and DB connectivity.
11.2 Class not found after deploy
Run:

Bash

composer install --no-dev --optimize-autoloader
php artisan optimize:clear
If class still missing:

verify file exists in repo
verify namespace/path/case sensitivity (Linux is case-sensitive)
11.3 Queue jobs not running
check Supervisor status
restart workers:
Bash

sudo supervisorctl restart laravel-worker:*
php artisan queue:restart
inspect queue:failed and worker log
11.4 Migration broke production
restore DB backup if needed
rollback if migration is reversible:
Bash

php artisan migrate:rollback --step=1 --force
redeploy last stable commit
11.5 App shows stale behavior after release
Likely cache issue:

Bash

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
12) Health Check Recommendations
Implement lightweight health endpoints:

/health basic app alive
/health/db DB connectivity
/health/queue queue backend check (optional)
Use these in uptime monitors and post-deploy checks.

13) Backup & Restore Strategy
13.1 Backups
Nightly DB backups
Retention policy (e.g., 7 daily + 4 weekly + 3 monthly)
Encrypt backups
Store off-server (S3/object storage)
13.2 Restore drill
Perform periodic restore drills in staging to ensure backups are valid.

14) Performance & Stability Tips
Use Redis for cache/session/queue.
Add DB indexes for frequent filters (especially school_id).
Keep heavy tasks async (queues).
Monitor slow queries.
Rotate logs and prevent disk exhaustion.
Use CDN for static assets where possible.
15) Security Baseline
APP_DEBUG=false in production
enforce HTTPS
set secure cookies
keep dependencies patched
restrict server access (SSH keys, firewall)
principle of least privilege for DB user
never expose .env, storage internals, or debug pages
16) Incident Response Template
When incident occurs, capture:

Incident start time
Affected module/routes
Error signature/exception
Last deployment hash/time
Immediate mitigation applied
Root cause
Permanent fix
Postmortem action items
17) Handy Command Reference
Bash

# App state
php artisan about

# Clear and rebuild caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# DB
php artisan migrate --force
php artisan migrate:status

# Queue
php artisan queue:work
php artisan queue:failed
php artisan queue:retry all
php artisan queue:restart

# Scheduler
php artisan schedule:list
php artisan schedule:run

# Logs
tail -f storage/logs/laravel.log
18) Release Checklist (Printable)
 CI/test suite green
 DB backup confirmed
 Deploy performed
 Migrations applied
 Caches rebuilt
 Queue restarted
 Health checks passed
 Smoke test completed
 Monitoring dashboards normal
 Team notified release complete
19) Documentation Rule
Every change affecting deployment/runtime behavior must update this file:

DEVELOPER_MANUAL_DEPLOYMENT_AND_DEBUG.md

text


---

If you want, next I can generate a **4th short file**:  
`RUNBOOK_QUICK_COMMANDS.md` (just one-page emergency commands for on-call engineers).