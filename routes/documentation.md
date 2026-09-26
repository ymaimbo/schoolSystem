System Summary (Notebook Version)

1. What This System Is

It is a school ERP/admin platform built around role-based access.
There are two major zones:
Super Admin zone for multi-school setup and central billing.
School Admin zone for day-to-day operations inside one school.
2. Entry + Security Model

Public routes:
/ -> platform home.
/school/{school:slug} -> public school page.
Super admin routes:
Protected by auth, verified, role:super_admin.
Prefixed with /super-admin.
School operational routes:
Protected by auth, verified, resolve_school.
Most admin features are under route name prefix admin.*.
3. Core Roles

super_admin
principal
deputy_principal
dean
secretary
accountant
hod
store_keeper
class_teacher
subject_teacher
4. Super Admin Responsibilities

Dashboard: school-level oversight.
School registration lifecycle:
Register school.
Save school.
Store assigned principal.
Update principal details.
Billing/invoicing management:
View bills.
Generate invoice.
Edit invoice.
Update invoice.
5. School Admin Dashboard + Common Routes

/dashboard redirects into admin dashboard.
User profile routes available to authenticated users:
Edit profile.
Update profile.
Delete profile.
6. Module Map (Who Can Do What)

Students

View list: principal, deputy_principal, dean, secretary, accountant.
Create: principal, deputy_principal, dean, secretary.
Update/Delete: principal, deputy_principal, dean.
Programs

Full CRUD: principal, deputy_principal, dean, hod.
Exams

Exam setup CRUD: principal, deputy_principal, dean, hod.
Exam results routes (store/update/delete/missing results):
Available to full roles + class_teacher + subject_teacher.
Actual permission enforcement handled by policy/controller checks.
Timetable

CRUD: principal, deputy_principal, dean.
Finance

Access: principal, accountant.
Finance transactions CRUD.
Voucher create/delete/print.
Fee structures CRUD.
Student finance:
Account upsert.
Payment create/delete.
Receipt print.
Billing submodule:
Invoice list/show.
Store invoice payment.
Print receipt.
M-Pesa receipt view.
Download receipt PDF.
Store (Inventory)

Index/store/update: principal, accountant, store_keeper.
Delete: principal, accountant.
Parents / Guardians

Index + update contacts: principal, secretary, dean.
Sports

CRUD player records: principal, deputy_principal, dean.
Discipline

CRUD discipline cases: principal, deputy_principal, dean.
Communications

Access: principal, deputy_principal, secretary, dean.
Send notice.
Send exam results.
Staff

CRUD: principal, deputy_principal.
Class Teacher Assignments

CRUD: principal only.
Teacher Portals

Class teacher portal (class.teacher.scope, prefix /class-room):
Portal index.
Discipline view.
Submit exam marks.
Subject teacher portal (subject.teacher.scope, prefix /subject-room):
Portal index.
Discipline view.
Submit exam marks.
7. Operational Flow (How Modules Connect)

Student records are the base for many modules.
Exams + results feed communications to parents.
Finance includes both general ledger-like transactions and student fee accounting.
Store supports inventory used by school operations.
Discipline and sports track non-academic student/worker activity.
Class teacher assignments connect staff roles to exam/academic workflows.
8. Route Naming Pattern You Should Know

Route names follow module patterns like:
admin.students.*
admin.finance.*
admin.student-finance.*
admin.class-teacher-assignments.*
This makes frontend navigation and permissions predictable.
9. New System Admin Quick Checklist

Confirm user role matrix is correct.
Confirm resolve_school works (user is in correct school context).
Verify principal + accountant can access all finance and student-finance routes.
Verify teacher scopes (class_teacher, subject_teacher) can submit marks but not admin CRUD.
Test critical workflows end-to-end:
Add student -> create exam -> enter results -> send communications.
Create student finance account -> record payment -> print receipt.
Add inventory item -> update stock -> role-restr