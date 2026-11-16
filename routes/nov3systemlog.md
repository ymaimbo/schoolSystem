

What is already present
Routes and an Inertia/Vue page for student accounts, payments, reports, and statements.
Account, ledger, payment, allocation, structure, profile, and votehead models.
A service with manual → profile → structure priority-ordering logic.
A signed total_fee_due representation and account accessors where a negative value means credit.
A votehead allocation smoke test.
A school resolver and role middleware. The role middleware checks a regular user’s school against the resolved school, which helps protect school-staff routes.
An existing receipt template at resources/views/admin/finance/print_receipt.blade.php.


Important confirmed gaps
Fee structures are out of sync across controller, model, and database.
FeeStructureController uses a feeProfile relationship and fee_profile_id, but the model does not define that relationship and the migrations/live schema do not show that column. Its model’s fillable list also omits fields the controller writes, including required structure fields such as year and category. FeeStructureLine likewise omits several fields the controller writes, including parent amounts and sort order. Structure creation or editing can therefore fail or save incomplete line data.

Structure edits can erase financial history.
The controller deletes and recreates structure lines on update. The live foreign keys cascade from fee-structure lines to ledgers and from ledgers to allocations. Editing a structure that has been used can consequently delete payment allocations and ledger history. Changing an account’s structure also does not remove or reconcile its old ledgers.

Ledger synchronization has schema and balance problems.
The service identifies existing ledgers by votehead, while the live unique key identifies them by account, structure line, source, and term. It does not remove stale ledgers. It omits required source when creating a ledger, even though the live column is non-null with no default. Its enum-reading logic chooses the first term, T1, rather than the database default, ANNUAL. The ledger’s expected_amount accessor also treats the remaining parent_balance as expected amount, then subtracts allocations again for outstanding amount.

Account updates can discard the opening balance.
The UI sends an expected amount plus opening balance, but the controller then recalculates due from ledger totals and payments. Without a fee structure, there may be no ledger rows to represent the amount, and the recalculation can overwrite it. A clear, persisted representation for opening balances is needed.

The default UI priority may always override profile and structure priority.
The payment form submits the active voteheads as a manual priority list by default. The service treats a nonempty list as a manual override, so the normal UI can bypass fee-profile and structure ordering even though the service-level smoke test checks those cases with an empty list.

Payment reversal is a hard delete, not an auditable reversal.
The controller reverses allocations, deletes the allocation rows, and deletes the payment. That removes the original payment and allocation history. The current schema has no payment reversal/status fields, so this needs a forward migration and an audit-preserving approach.

The receipt route does not use the receipt template you supplied.
printReceipt() checks for admin.student-finance.receipt, which you confirmed does not exist, and otherwise returns JSON. It does not render admin.finance.print_receipt.blade.php or pass that template its required student, breakdown, and totals.

The votehead report does not meet its UI filters.
It filters by allocation creation time rather than payment paid_at; the fee-profile filter is ignored; the response profile fields are always null; and the index action sends an empty feeProfiles list.

Locking happens too late for concurrent payment allocation.
Allocation decisions are planned before the service locks individual ledger rows. Concurrent payments can therefore plan against the same outstanding balance. The account/ledger rows need to be locked before calculating the plan.

The migrations show multiple environment paths.
The October profile migrations are guarded duplicates, and the October 11 priority-table definition differs from the earlier one. The live schema matches the earlier layout. The duplicate October 12 ledger-column migrations have rollback methods that drop the same columns, and the later added balance fields are not visibly backfilled. Existing migrations should not be rewritten for production; fixes should be forward-only and data-aware.

Recommended files to update
Backend: StudentFinanceController.php, FeeAllocationService.php, and FeeStructureController.php.
Models: FeeStructure.php, FeeStructureLine.php, and StudentFeeLedger.php; payment/allocation models if adding audited reversals.
Frontend: StudentFinance/Index.vue and StudentFinance/VoteheadReport.vue; possibly the fee-structure Vue pages once the fee-structure/profile design is settled.
Receipt: connect the controller to the existing print_receipt.blade.php, or create the view path the controller currently expects.
Database: add a new forward migration for the chosen reversal/audit design and any required data repair. First decide whether fee profiles belong only to students—as the schema indicates—or also to fee structures—as the controller assumes.
Tests: extend the finance tests to cover controller flows, signed-balance reconciliation, structure edits, reversal history, school isolation, report filters/date semantics, receipts, and concurrent allocation.
Update status


