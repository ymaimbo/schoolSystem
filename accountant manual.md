Accountant User Manual v1
Student Finance Module (School ERP)
Version: 1.0
Audience: School Accountant
Prepared for: Admin Finance → Student Finance workflows

1) Purpose of This Manual
This manual helps a new accountant use the Student Finance module correctly from day one.
By the end, you should be able to:

Create and update student fee accounts
Record and reverse payments safely
Print receipts and statements
Understand votehead collections
Perform daily and period-end checks
2) Your Role as an Accountant (System Scope)
As an Accountant, your finance routes are under the admin namespace:

admin.student-finance.*
admin.finance.*
admin.finance.fee-structures.*
admin.billing.*
What you can do
Access Student Finance dashboard
Upsert student account details
Record payments
Delete/reverse payment entries (if permitted by policy)
Print receipts
View votehead report
Access finance/billing pages as role allows
What you should not do without authorization
Change role access controls
Modify system configuration or schema
Bulk delete transactional history
3) Key Terms You Must Know
Student Fee Account: Main finance profile for one student.
Fee Structure: Planned charges template (e.g., class-based fees).
Vote Head: Charge category (Tuition, Meals, Transport, etc.).
Ledger: Student-level breakdown by vote head/term.
Expected Amount: What the student is supposed to pay.
Paid/Allocated Amount: What has been posted and assigned.
Outstanding Balance: Expected minus allocated.
Credit/Overpayment: Extra amount paid beyond current outstanding.
4) Navigation Map (Screens)
Student Finance Main Page
Admin / Finance / StudentFinance / Index

Summary cards
Account upsert form
Payment form
Votehead collection report panel
Accounts table
Recent payments table
Print controls (all/class/student)
Votehead Report View (if opened separately)

Votehead totals and outstandings
Receipt Print View

Payment receipt per transaction
5) Daily Workflow (Standard Operating Procedure)
Step A — Open Student Finance
Login.
Go to Admin → Student Finance.
Click Refresh Data once after load.
Check: summary cards visible, no red error banners.

Step B — Create/Update a Student Fee Account
In Create / Update Student Fee Account:
Search/select student (name/admission no)
Select fee structure
Enter opening balance (if required)
Confirm expected amount
Click Save Account.
Expected result: success message appears; account shows in accounts table.

Important: If account does not exist, payment cannot be posted correctly.

Step C — Record a Payment
In Record Fee Payment:
Select student
Confirm displayed current balance/credit
Enter amount
Choose payment method (cash/bank/mpesa/cheque/transfer)
Fill reference/org details if applicable
Set paid date
(Optional) reorder votehead priority for allocation
Click Record Payment.
Expected result: payment appears in Recent Payments; balances update.

Step D — Print Receipt
In Recent Payments, click Print Receipt for a row.
Verify:
Student details
Amount
Date
Method/reference
Step E — Review Votehead Collections (Daily)
In Votehead Collections Report:
Set period (day/week/month/custom)
Optional votehead filter
Optional fee profile filter
Click Generate.
Confirm grand total and row totals.
Print if needed.
Step F — End-of-Day Reconciliation
Compare:
Total collected in system (day)
Cashbook/bank/mpesa totals
Verify no unposted receipts.
Verify no unexplained deleted payments.
Export/print day summaries.
Report discrepancies immediately.
6) Screen-by-Screen Instructions
Screen 1: Top Summary Cards
Cards show:

Students
Accounts
Total Billed
Total Paid
Outstanding
Credit
Use these as a quick health check:

High outstanding trend → follow-up needed
High credit trend → review overpayment handling
Screen 2: Create/Update Account Form
Fields:

Student (required)
Fee structure (recommended)
Opening balance (optional)
Expected amount
Sponsor/org details (optional)
Notes (optional)
Rules
Always select from suggestion list (avoid free-typing only).
If fee structure changes, recheck expected amount.
Save before recording payment.
Screen 3: Record Payment Form
Fields:

Student (required)
Amount > 0 (required)
Payment method
Reference/organization details
Paid date
Notes
Votehead priority list (optional allocation control)
Rules
Never post zero/negative amounts.
Confirm the selected student has an account.
Use consistent references for bank/mpesa traceability.
Screen 4: Votehead Priority Panel
Purpose: controls allocation order if your backend allocation supports priority.

Example:

Tuition
Meals
Transport
If payment is not enough to clear all lines, top priority gets paid first.

Screen 5: Accounts Table
Shows:

Admission no
Student
Class
Expected
Paid
Balance
Credit
Use search and class filter to isolate records quickly.

Screen 6: Recent Payments Table
Shows:

Date
Student
Method
Ref/org
Amount
Actions (Print / Delete)
Delete payment only with authorization and document reason.

Screen 7: Print Tools
Options:

Print all accounts
Print class accounts
Print single student statement
Always validate filters before printing.

7) Data Accuracy Rules (Critical)
Student must be resolved correctly from picker.
Payment must map to a valid student fee account.
Ledger rows must carry student_id (system-level integrity).
Never bypass workflow by posting payments without account context.
Confirm date and reference before submit.
8) Common Errors & Quick Fixes
Error: Field 'student_id' doesn't have a default value
Cause: ledger insert missing student_id.
Action:

Ensure student fee account is valid
Ensure backend fills student_id from account
Ensure model fillable includes student_id
Error: Unknown column admission_number
Cause: DB uses a different student admission field name.
Action:

Use controller fallback selection (admission_no / admission_number / adm_no)
Avoid hardcoded single column assumption
Error: Page not found: ./Pages/Admin/StudentFinance/Index.vue
Cause: Inertia page path mismatch/case mismatch.
Action:

Ensure page file path matches controller Inertia::render(...) exactly
Linux is case-sensitive
Error: Unknown column parent_amount
Cause: code expects ledger columns missing in DB.
Action:

Run safe migration adding missing columns:
parent_amount
parent_balance
govt_capitation_amount
notes
9) Internal Control Checklist (Daily)
 Logged in with accountant role
 Refreshed data before posting
 Every payment has method + reference (where applicable)
 No payment posted without student account
 Receipt printed/confirmed for each posted payment
 Votehead report generated for day
 System total reconciled to cash/bank/mpesa
 Any deleted payment has documented reason + approval
10) Week-End / Month-End Checklist
 Run votehead report for period
 Review top outstanding students/classes
 Review total credits (overpayments)
 Confirm billing and invoice-related entries are consistent
 Archive printed/exported reports
 Share reconciliation summary with Principal/Finance lead
11) Escalation Guide
Escalate to System Admin/Developer when:

Payments save but balances don’t update
Receipts fail to open
Repeated SQL “unknown column” or constraint errors
Route/page mismatch errors in browser console
Include in escalation:

exact error text
screenshot
student/account/payment ID
timestamp of action
12) Quick “Do / Don’t” for Accountants
Do
Do post against correct student account.
Do verify balances before and after payment.
Do keep reference numbers accurate.
Do reconcile daily.
Don’t
Don’t post test transactions in production.
Don’t delete payments without approval.
Don’t ignore warning/error banners.
Don’t rely on memory—use this SOP each day.
Appendix A — Example Allocation (KES 5,000)
Expected:

Tuition 12,000
Meals 4,000
Transport 3,000
Payment: 5,000 with priority Tuition → Meals → Transport

After allocation:

Tuition paid 5,000; outstanding 7,000
Meals outstanding 4,000
Transport outstanding 3,000
Totals:

Expected 19,000
Paid 5,000
Outstanding 14,000
Appendix B — Suggested Training Plan (New Accountant)
Day 1: Navigation + account creation
Day 2: Payment posting + receipt flow
Day 3: Votehead report + reconciliation
Day 4: Exception handling + reversals
Day 5: Supervised live posting + checklist audit

