Great upgrade idea. You need a school-level billing access control layer that gates specific roles based on payment progress.
Below is the exact implementation path.

1. Define billing-access rules first

Create a clear matrix per payment milestone.
Example:
< 25% paid: only super admin and school accountant can access.
>= 25% and < 50%: block principal and deputy_principal (your rule), allow accountant to continue posting payments.
>= 50% and < 100%: allow principal + deputy_principal.
>= 100%: full school role access.
Decide if secretary/dean/hod/class_teacher are allowed in each stage.
Decide if “grace days” apply after invoice due date.
2. Add required billing status fields in DB

Add to schools table or a dedicated school_subscriptions table:
billing_plan
billing_cycle (monthly/quarterly/annual)
current_invoice_id
amount_due_cycle
amount_paid_cycle
paid_percentage
access_stage (locked, quarter_only, half_only, full)
grace_until (nullable datetime)
is_access_override (boolean, for super admin manual unlock)
access_override_reason (nullable text)
Keep a history table for audit, e.g. school_access_events:
who changed stage
old/new stage
reason
timestamp
3. Compute paid percentage reliably

On every billing payment post, recalculate:
amount_paid_cycle
paid_percentage = amount_paid_cycle / amount_due_cycle * 100
Update access_stage based on your rule matrix.
Also run this recalculation via a scheduled job nightly for consistency.
4. Create a dedicated middleware

Add middleware like EnforceSchoolBillingAccess.
Middleware flow:
resolve current school
skip if user is super_admin
skip if is_access_override = true
read role + school access_stage
check against allowed roles matrix
if blocked: redirect to a “billing restricted” page with message
Register alias in Laravel middleware aliases (both app bootstrap and kernel if your project still has both).
5. Apply middleware to admin route group

Add new middleware to school-scoped route group after auth/resolve_school.
Keep billing/payment routes reachable for roles that must settle payment (usually accountant).
Make sure blocked users cannot bypass by direct URL hits.
6. Add super admin controls UI

In super admin school page add:
current due, paid, paid %, stage
active restrictions
manual override toggle (unlock/lock)
reason input for override
Add a “recalculate access now” action.
7. Add school-side restricted page

Create a clear page for blocked users:
current payment progress
minimum required threshold
contact/accountant action prompt
Do not show stack traces or 403 raw errors.
8. Protect critical workflows

Ensure no blocked role can trigger write endpoints by API calls.
Recheck controller-level authorization for sensitive actions.
Keep payment posting available for authorized billing-settlement roles.
9. Add automated tests (must-have)

Feature tests for each role at each payment stage.
Tests for override on/off behavior.
Test route protection (UI + direct URL + POST/PUT/DELETE).
Test recalculation after payment.
10. Rollout plan

Deploy DB migration first.
Backfill existing schools with initial access_stage=full to avoid accidental lockout.
Deploy middleware and route binding.
Enable enforcement behind a feature flag (env/config).
Turn on school-by-school after verification.
11. Operations and monitoring

Log each blocked access attempt:
school, user, role, route, stage.
Alert if many blocked attempts happen (misconfiguration signal).
Add admin report: schools nearing lock threshold.
If you want, next I can give you the exact code-level implementation pack in order:

migration file,
middleware file,
config rule map,
route updates,
super admin UI fields/actions,
feature test cases.