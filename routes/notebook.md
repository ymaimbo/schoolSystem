📓 Notebook Summary — Student Finance Module (for New DB Schema Architect)
1) System objective
Build a reliable school student-finance subsystem that supports:

student fee account creation/update
fee structure to ledger sync
payment posting
votehead-priority allocation
receipt printing
votehead collections reporting
safe reversal/deletion of payments
2) Core business flow (what happens in order)
StudentFeeAccount created/updated for a student.
Account linked to a FeeStructure.
Fee structure lines generate/update StudentFeeLedger rows.
When payment is posted:
payment saved in StudentFeePayment
allocation computed via FeeAllocationService
allocation rows saved in StudentFeeAllocation
ledger balances updated
account total due recalculated
Reports aggregate by VoteHead.
3) Route architecture (finance domain)
Inside /admin + admin. namespace + billing/access + role guards:

admin.finance.* (core transactions)
admin.finance.voucher.*
admin.finance.fee-structures.*
admin.student-finance.*
admin.billing.*
Most critical for this module:

admin.student-finance.index
admin.student-finance.account.upsert
admin.student-finance.payment.store
admin.student-finance.payment.destroy
admin.student-finance.receipt.print
admin.student-finance.votehead.report
4) Canonical data model (understood schema)
A) students
Key fields used:

id
school_id
first_name, last_name (sometimes name)
admission identifier (varies by env):
admission_no OR admission_number OR adm_no
optional: class_level, stream
optional: fee_profile_id
B) fee_profiles
Used for profile-level votehead priority strategy.

C) vote_heads
Votehead master (e.g., Tuition, Boarding, Exam, Activity).

D) fee_structures
School-level fee templates.
Linked to fee profile in some environments.

E) fee_structure_lines
Rows per fee structure with votehead and amounts:

fee_structure_id
vote_head_id
amount fields vary (parent_total_amount, total_amount, term amounts)
optional sort_order
F) student_fee_accounts
One account per student/school context:

id, school_id, student_id
fee_structure_id
total_fee_due (net due; can be negative = credit)
optional sponsor fields + notes
G) student_fee_ledgers (critical integrity table)
Must include:

school_id
student_id (NOT NULL in your live DB)
student_fee_account_id
fee_structure_line_id
vote_head_id
term (enum-like)
optional source
amount fields (parent_amount, parent_balance, govt_capitation_amount)
optional notes
This table is where outstanding per votehead/term is tracked.

H) student_fee_payments
Payment transactions:

school_id
student_fee_account_id
optional student_id
amount
method/reference fields vary by schema:
method or payment_method
reference or organization_id
optional organization_name
paid_at
optional audit fields (received_by, recorded_by)
I) student_fee_allocations
Allocation rows from payment to specific ledger/votehead:

student_fee_payment_id
student_fee_ledger_id
vote_head_id
allocated_amount (or legacy amount)
allocation_order
optional school/account/student references
5) Allocation precedence logic (validated by test)
manual > profile > structure > fallback

manual: request payload votehead order (priority_vote_head_ids)
profile: fee_profile_votehead_priorities
structure: fee_structure_lines.sort_order
fallback: ledger natural order
Smoke test confirms this precedence works and allocation totals balance.

6) Major implementation constraints discovered
Schema differs per environment (column variability).
student_fee_ledgers.student_id is required in live DB.
Student admission field naming varies.
Payment method/reference fields vary.
Inertia page paths are case-sensitive on Linux.
Therefore, controller/service/model code must be schema-tolerant and explicit.

7) Important hardening decisions applied
Dynamic column guards using Schema::hasColumn(...)
Always set student_id on ledger writes
Backward-compatible payment payload handling
Redirect-to-index after mutations to force fresh UI hydration
Local UI optimistic update + server reload in Index.vue
Consistent route naming contract maintained
8) Testing posture
Passing:
VoteheadAllocationSmokeTest
verifies priority precedence
verifies allocation consistency (allocated + unallocated = payment)
catches ledger integrity defects early
Operational checks:
syntax checks
route existence checks
schema existence checks
feature test pass
9) Known practical UI/backend contract
Frontend Index.vue sends:

account upsert:
student_id, fee_structure_id, total_fee_due, sponsor fields, notes

payment post (dual contract):

preferred: student_fee_account_id, amount, method, reference, paid_at, notes
legacy: student_id, payment_method, organization_name, organization_id, priority_vote_head_ids
Backend accepts both and normalizes.

10) Architecture notes for future schema architect
Standardize naming eventually:
admissions: choose one column name
payments: choose method/reference or legacy equivalents
Keep ledger uniqueness clear:
likely unique by (account, fee_structure_line, source, term)
Ensure FK coverage and ON DELETE strategies are intentional.
Consider materialized summary table for heavy reporting later.
Keep allocation table immutable where possible (auditability).
Add reconciliation status flags for payment posting lifecycle.
11) Current status snapshot
Finance routing structure: ✅
Controller/UI contract alignment: ✅
Votehead allocation service integrity: ✅
Smoke test: ✅ passing
Schema compatibility handling: ✅ in place
Remaining work: ongoing UX refinements + optional seeder snapshot tooling