Deployment Documentation
This is a complete, practical deployment guide for your Laravel + Vue school system, optimized for Kenya operations and your current project setup.

1. Architecture

Domain registrar: Cloudflare Registrar or Namecheap.
DNS/CDN/WAF: Cloudflare.
App server: Forge-managed VPS (DigitalOcean or Hetzner).
Database: Managed MySQL (same provider region as app server).
Cache and queues: Redis (managed preferred).
File storage: Cloudflare R2 or S3-compatible bucket.
SMS: Africa’s Talking.
Error monitoring: Sentry (recommended).
2. Recommended Regions

Primary: South Africa region for lower latency to Kenya.
Keep app, DB, and Redis in the same region to reduce latency and cost.
3. Minimum Server Sizing

Start: 2 vCPU, 4 GB RAM, 80 GB SSD.
Increase to 4 vCPU, 8 GB when:
queue delay grows
reporting/finance load increases
many simultaneous users during school hours
4. Required Accounts

Forge account.
Cloudflare account.
VPS provider account.
Managed MySQL account/cluster.
Redis account/instance.
SMS provider account (Africa’s Talking).
GitHub/GitLab repo access.
5. Pre-Deployment Checklist

Production domain is ready.
Cloudflare nameservers set.
SSL strategy decided: Cloudflare Full (strict).
All .env production secrets prepared.
Backups strategy documented.
Queue jobs tested locally.
Super admin routes audited and passing.
Middleware aliases confirmed:
role
resolve.school
resolve_school (backward compatibility)
Route caching decision made.
Your project currently contains closure routes.
Do not run php artisan route:cache until closures are removed.
6. Forge Server Provisioning

Create Ubuntu 22.04 server from Forge.
Attach your SSH key.
Create site: app.yourdomain.com.
Set web directory to Laravel public.
Enable SSL in Forge (Let’s Encrypt).
Add worker and scheduler in Forge.
Add environment variables in Forge .env.
7. DNS and SSL (Cloudflare)

Add A record:
app.yourdomain.com -> server_ip
Proxy status:
Proxied (orange cloud) for web app.
SSL mode:
Full (strict)
Security settings:
Always Use HTTPS: ON
Minimum TLS: 1.2
HSTS: ON (after validation)
8. Production .env Template
Use this baseline and fill real values:

env

APP_NAME="Vigurungani School System"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://app.yourdomain.com
APP_TIMEZONE=Africa/Nairobi
APP_LOCALE=en
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
SESSION_DOMAIN=.yourdomain.com

CACHE_STORE=redis
QUEUE_CONNECTION=redis

REDIS_CLIENT=phpredis
REDIS_HOST=
REDIS_PASSWORD=
REDIS_PORT=6379

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=s3

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=auto
AWS_BUCKET=
AWS_ENDPOINT=
AWS_URL=
AWS_USE_PATH_STYLE_ENDPOINT=true

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@app.yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

AFRICASTALKING_USERNAME=
AFRICASTALKING_API_KEY=
AFRICASTALKING_SENDER_ID=

FORCE_HTTPS=true
SENTRY_LARAVEL_DSN=
9. Deployment Script (Forge Safe Version)
This version is safe with closure routes:

Bash

set -e

cd /home/forge/app.yourdomain.com

php artisan down --render="errors::503" || true

git pull origin main

composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

npm ci
npm run build

php artisan migrate --force

php artisan optimize:clear
php artisan config:cache
php artisan view:cache
# skip route:cache due to closure routes

php artisan storage:link || true
php artisan queue:restart

php artisan up
10. Queue Worker Configuration (Forge)

Command:
Bash

php /home/forge/app.yourdomain.com/artisan queue:work redis --sleep=1 --tries=3 --timeout=120 --max-time=3600
Processes:
start with 2
Stop wait:
3600
Auto-restart:
enabled
11. Scheduler Configuration

Add server cron (Forge usually manages this):
Bash

* * * * * php /home/forge/app.yourdomain.com/artisan schedule:run >/dev/null 2>&1
12. Database Migration Strategy

Take backup before deployment.
Run:
Bash

php artisan migrate --force
Never run destructive migration in production without tested rollback.
13. Storage and File Uploads

Use object storage (R2 or S3), not local disk.
Keep FILESYSTEM_DISK=s3.
Serve files via CDN URL where possible.
14. Cloudflare WAF Baseline
Apply in this order:

Allow trusted office IPs.
Block probe paths (/.env, /wp-login.php, /xmlrpc.php, /wp-admin).
Block invalid HTTP methods.
Challenge scanner user agents.
Optional geo challenge for /admin.
Optional strict challenge for non-office /admin.
15. Rate Limiting Baseline

/login POST:
10 requests per 60 sec per IP
password reset routes:
5 requests per 60 sec per IP
/admin/communications/notice POST:
30 requests per 300 sec per IP
/admin/communications/results POST:
30 requests per 300 sec per IP
/api/* general:
120 requests per 60 sec per IP
16. Laravel Security Baseline

APP_DEBUG=false
HTTPS only
Strong admin passwords
Role middleware on admin routes
Session secure cookie enabled
Keep dependencies updated monthly
17. Backups

Daily managed DB snapshots (7 to 30 day retention).
Nightly SQL dump to offsite storage.
Weekly restore test to staging.
Keep encrypted copy of critical env and key docs.
18. Monitoring and Alerts

Uptime checks:
app URL
login page
queue health endpoint (if added)
Error tracking:
Sentry
Resource alerts:
CPU, RAM, disk, DB connections
Log review:
failed jobs
auth errors
SMS delivery failures
19. Go-Live Test Plan
Run these tests before opening to users:

Super admin login and dashboard.
Register school and principal.
Student create/edit/delete.
Finance:
account create/update
payment record
overpayment credit reflects correctly
Print workflows:
student accounts by class
single student statement
Communications:
send notice
send results
Queue and scheduler activity.
Backup creation.
20. Rollback Plan

Put app in maintenance mode.
Restore previous release code.
Restore DB from last known good backup if schema/data issue.
Restart queue workers.
Verify key routes and login.
21. Current Project-Specific Notes

You are using Laravel 11 style bootstrap middleware aliases and also Kernel.php.
Keep both aliases available to avoid route breakage:
resolve.school
resolve_school
Do not enable route:cache until all route closures are replaced.
Your school system relies heavily on role routing. After each deploy, test at least one account per role.
22. Post-Go-Live Operations
Daily:

Check queue failures.
Check SMS delivery logs.
Confirm backups ran.
Check disk and DB health.
Weekly:

Apply security updates.
Review role assignments.
Test a partial restore on staging.
Monthly:

Full disaster recovery drill.
Performance review and scaling decisions.






One-Page Runbook (Printable)

System: Vigurungani School System
Environment: Production
Timezone: Africa/Nairobi
Primary URL: https://app.yourdomain.com

1. Daily Start Checks (8:00 AM)

Confirm app opens and login works.
Confirm dashboard loads for at least one admin.
Confirm queue workers are running.
Confirm scheduler is running.
Confirm previous night DB backup completed.
Check failed jobs count is zero or reviewed.
2. Essential Commands

Check queue failures:
Bash

php artisan queue:failed
Retry failed jobs:
Bash

php artisan queue:retry all
Restart workers after deployment/config change:
Bash

php artisan queue:restart
Run scheduler manually (test):
Bash

php artisan schedule:run
Clear and rebuild cache safely:
Bash

php artisan optimize:clear
php artisan config:cache
php artisan view:cache
3. Incident Quick Response

Users cannot log in:
Check Cloudflare WAF/rate-limit events.
Check app logs in storage/logs/laravel.log.
Confirm DB and Redis connectivity.
Messages/results not sending:
Check queue worker status.
Check queue:failed.
Check SMS provider dashboard (Africa’s Talking).
Data not appearing after save:
Hard refresh UI.
Check browser network response.
Check DB row inserted/updated.
500 error:
Review laravel.log.
Roll back latest deployment if needed.
4. Deployment Safety Steps

Announce maintenance window.
Backup DB first.
Deploy through Forge script.
Run smoke tests immediately:
login
students create
fee payment record
message send
If broken, rollback release and restore DB only if needed.
5. Backup and Recovery

Daily managed snapshot must exist.
Nightly offsite SQL dump must exist.
Weekly restore test on staging.
Keep emergency restore instructions available to leads.
6. Security Rules

Never share .env or DB credentials.
Enforce strong passwords.
Remove inactive users quickly.
Keep roles least-privilege.
Review super admin actions weekly.
7. Escalation Path

L1 Admin: check app status, queue, user issue.
L2 Technical Admin: logs, DB/Redis, deployment rollback.
L3 Developer: code fix, hot patch, schema repair.
Role-by-Role UAT Checklist

UAT Setup

Use one test account per role.
Use one test school with realistic data.
Record: pass/fail, screenshot, error message, timestamp.
Principal

Login and access dashboard.
View students list and filters.
Create student and verify it appears.
Update student and verify save.
Access exams, finance, student finance, store, staff.
Create fee account and record payment.
Record overpayment and verify credit shown.
Print class fee accounts and single student statement.
Send parent notice and exam results.
Access class teacher assignments CRUD.
Deputy Principal

Login and access dashboard.
Students view/create/update/delete as permitted.
Access exams/programs/timetable/sports/discipline.
Send communications notice/results.
Verify no access to principal-only areas (if restricted).
Secretary

Login and access students view/create.
Update parent/guardian records.
Send parent communications.
Verify denied access to restricted finance/staff functions.
Accountant

Login and access finance dashboard.
Create/update/delete finance transactions.
Create and print vouchers.
Manage fee structures.
Access student finance:
account upsert
payment record
receipt print
overpayment credit handling
Billing:
view invoice
post payment
print/download receipt
Verify no access to student delete if restricted.
Store Keeper

Login and access store module.
Create inventory item.
Update inventory item.
Verify delete access matches policy (usually blocked unless granted).
Confirm no access to restricted modules.
HOD

Login and access programs/exams scope.
Create or update exam records if allowed.
Submit/update results where permitted.
Access discipline view where allowed.
Verify denied modules outside HOD scope.
Class Teacher

Login and access class-room portal only.
View assigned class context.
Enter exam marks for class students.
Access class discipline view.
Verify cannot access admin modules outside scope.
Subject Teacher

Login and access subject-room portal only.
View subject class context.
Enter exam marks for subject students.
Access discipline view as allowed.
Verify cannot access admin modules outside scope.
Cross-Role Security Tests

Try opening unauthorized URL directly for each role.
Confirm system blocks with 403/redirect.
Confirm middleware aliases work (role, resolve.school).
Data Integrity Tests

Student create -> appears in list and stats.
Fee payment -> updates paid/balance.
Overpayment -> credit visible after refresh.
Delete payment -> account due restored correctly.
Message/result send -> logged in recent messages/history.
Exit Criteria

All critical tests pass for all roles.
No blocker severity defects open.
Backups and restore tested.
Security checks passed.
Go-live signoff by admin + technical lead.