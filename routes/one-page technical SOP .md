SOP: School Billing Access & Finance Operations
System: Laravel + Inertia + Vue (Multi-school)
Scope: Super Admin, School Admin, Accountant
Goal: Ensure billing, payment posting, and role access control run safely in production.

1) Roles & Responsibilities
Super Admin
Create and manage school invoices.
Monitor school billing stages and payment progress.
Trigger manual recalculation when needed.
Use emergency override only when justified.
Review audit trails (school_access_events).
School Admin / Principal
View school billing dashboard and invoice status.
Ensure school accountant posts payments promptly.
Escalate billing lock issues to Super Admin.
Accountant
Post payments against school invoices.
Confirm receipt details and references are accurate.
Reconcile outstanding balances and invoice statuses.
2) Billing Access Stage Policy (authoritative)
locked: < 25% paid
quarter_only: >= 25% and < 50% paid
half_only: >= 50% and < 100% paid
full: >= 100% paid
Operational rule:

Principal/deputy unlock from half_only (>= 50%).
Accountant remains able to post payments in restricted stages.
Super Admin always bypasses.
Override/grace are controlled by policy fields on schools.
3) Daily Operations Workflow
A. Invoice Lifecycle (Super Admin)
Create invoice (SuperAdmin/Billing/Index.vue).
Verify invoice appears with correct:
school
amount
due date
status = unpaid
On edits, ensure balance is not invalid (cannot exceed invoiced amount).
B. Payment Posting (Accountant/Admin)
Open invoice detail (Admin/Billing/Show.vue).
Post payment with method, reference, paid date, notes.
Confirm invoice updates:
balance reduces
status moves unpaid → partial → paid
Confirm school billing fields update immediately:
due, paid, paid%, stage
C. Access Verification
If stage crosses 50%, principal/deputy must regain access immediately.
If stage drops below thresholds, restrictions apply by middleware.
4) Monitoring & Health Checks (daily/weekly)
Run SQL checks:

School billing snapshot
SQL

SELECT id, name, billing_cycle_amount_due, billing_cycle_amount_paid, billing_paid_percent, billing_access_stage
FROM schools ORDER BY id;
Invoice math consistency
SQL

SELECT s.id, s.name,
ROUND(COALESCE(SUM(i.invoiced_amount),0),2) total_invoiced,
ROUND(COALESCE(SUM(i.balance_amount),0),2) total_balance,
ROUND(COALESCE(SUM(i.invoiced_amount),0)-COALESCE(SUM(i.balance_amount),0),2) computed_paid,
ROUND(s.billing_cycle_amount_paid,2) school_paid
FROM schools s
LEFT JOIN school_invoices i ON i.school_id=s.id
GROUP BY s.id,s.name,s.billing_cycle_amount_paid;
Audit/event trail
SQL

SELECT id, school_id, old_stage, new_stage, amount_due, amount_paid, paid_percent, reason, created_at
FROM school_access_events
ORDER BY id DESC LIMIT 20;
5) Incident Runbook (when access is wrongly blocked)
Identify school and current stage (schools.billing_access_stage).
Validate paid% from invoice math query.
If mismatch: run recalculation/sync process.
If urgent: apply temporary billing_access_override = 1 with reason.
After reconciliation: disable override, verify stage correctness.
Log incident outcome in ops notes.
6) Change Control & Safety
Before any billing logic deploy:

Backup database.
Apply code changes + migration in maintenance window.
Run smoke tests:
invoice create
payment post
stage transition
principal unlock at >=50%
Clear and rebuild Laravel caches.
After deploy:

Monitor first 24 hours for stage or access anomalies.
7) Minimum Go-Live Quality Gate
System is “GO” only if:

Payment posting automatically recalculates stage.
Stage thresholds map correctly to role access.
Audit events are being written.
Super Admin can recover with override/recalculate.
No unresolved data mismatches in health checks.
