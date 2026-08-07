System Documentation


1. Overview
This system is a role-based school operations platform built for Vigurungani Senior School.
It supports administration, academics, exams, finance, store, timetable, parent/guardian records, and parent communication.

Core stack:

Backend: Laravel
Frontend: Vue (Inertia pages)
Database: MySQL
Queue/async tasks: Laravel Queue (recommended with Redis)
Security: Authentication + role middleware + route-level permissions
2. How The System Works
2.1 Authentication and Access
A user logs in with email/password.
Laravel authenticates the user and loads the assigned role.
Route middleware checks role permissions before loading each module.
Inertia shares authenticated user data to Vue layout.
The navbar and dashboard only show links allowed for that role.
2.2 Role Enforcement
Access is enforced in 2 places:

Backend route middleware (EnsureUserHasRole) for true security.
Frontend visibility rules (Vue) for user experience.
Important: backend middleware is the real gatekeeper. Even if someone manipulates UI, unauthorized actions are blocked server-side.

2.3 Data Flow
User performs action in Vue page form.
Request goes to Laravel Controller.
Controller validates input.
Controller writes/reads records from MySQL models.
Response returns via Inertia with flash success/error messages.
2.4 Parent Messaging Flow
Deputy Principal or Secretary selects students.
They send a notice or results message.
System determines parent/guardian based on contact preference.
Message is stored in parent_messages log.
Delivery integration (SMS/email provider) can be connected to queue workers.
3. User Roles
Roles currently used:

Principal
Deputy Principal (deputy_principal)
Head of Department (hod)
Accountant
Store Keeper (store_keeper)
Secretary
Guest (not logged in)
4. Modules and Purpose
Dashboard: role-specific summary cards and quick links.
Students: student records and status management.
Programs: school programs and learning tracks.
Exams: exam setup and result entry/publishing.
Finance: income/expense transactions.
Store: inventory items and stock operations.
Timetable: class/subject scheduling.
Parents: parent and guardian contact records.
Communications: send notices and results to parents/guardians.
5. CRUD Permission Matrix (Who Can Do What)
Legend:

C = Create
R = Read
U = Update
D = Delete
Module	Principal	Deputy Principal	HOD	Accountant	Store Keeper	Secretary
Dashboard	R	R	R	R	R	R
Students	C,R,U,D	C,R,U,D	-	C,R,U,D	-	C,R,U
Programs	C,R,U,D	C,R,U,D	C,R,U,D	-	-	-
Exams	C,R,U,D	C,R,U,D	C,R,U,D	-	-	-
Exam Results	C,R,U,D	C,R,U,D	C,R,U,D	-	-	-
Finance	C,R,U,D	-	-	C,R,U,D	-	-
Store	C,R,U,D	-	-	C,R,U,D	C,R,U	-
Timetable	C,R,U,D	C,R,U,D	-	-	-	-
Parents/Guardians	R,U	-	-	-	-	R,U
Communications (Notice/Results to Parents)	C,R	C,R	-	-	-	C,R
Notes:

Secretary cannot delete student records.
Store Keeper cannot delete inventory records.
Timetable creation and management is handled by Deputy Principal (Principal also has oversight access).
Communications module is for Deputy Principal and Secretary (Principal can also access as administrator).
6. Key Business Rules
admission_no is unique for each student.
Role names are normalized (store keeper -> store_keeper) to prevent permission mismatch.
Parent/guardian contact preference controls who receives messages.
Messaging and result sending actions should be queued in production.
All sensitive actions should be logged (recommended: activity logs).
7. Security Model
Auth required for admin routes.
Email verification required for protected routes (if enabled).
Role middleware blocks unauthorized actions with HTTP 403.
Cloudflare WAF/rate limits protect login and communication endpoints.
HTTPS is mandatory in production.
8. Operational Components
Queue Worker: processes message/result dispatch jobs.
Scheduler: runs periodic tasks every minute.
Managed MySQL backups: daily snapshots.
Offsite backups: recommended to object storage.
Deployment automation: Laravel Forge.
9. Typical End-to-End Scenarios
9.1 Deputy Principal Creates Timetable
Login as Deputy Principal.
Open Timetable module.
Create slot with day, period, class, subject, teacher, time.
Save and publish for school use.
9.2 Secretary Updates Parent/Guardian
Login as Secretary.
Open Parents module.
Search student.
Update parent/guardian phone and relationship.
Save changes.
9.3 Deputy Principal Sends Results to Parents
Open Communications.
Select exam and students.
Click send results.
System prepares message from student result.
Logs each sent message in communications history.
10. Recommended Governance
Principal approves role assignment and access changes.
Secretary manages contact data quality weekly.
Deputy Principal verifies timetable and communication cadence.
Accountant verifies financial integrity and monthly reports.
Admin performs backup restore test at least once per month.






A) Formal SOP Manual (Policy Format)
Document Title: Vigurungani School Management System - Standard Operating Procedures (SOP)
Version: 1.0
Effective Date: [Insert Date]
Owner: School Administration
Applies To: Principal, Deputy Principal, HOD, Accountant, Store Keeper, Secretary, ICT/Admin Support

1. Purpose
This SOP establishes standardized procedures for secure and consistent use of the Vigurungani School Management System for academic, administrative, financial, communication, and inventory operations.

2. Scope
This policy governs:

User access and role assignment.
Data entry and record maintenance.
Timetable management.
Parent/guardian management.
Parent communications and student result dispatch.
Security, audit, and operational continuity.
3. Definitions
System: Laravel + Vue school management platform.
User Role: Access category assigned to a user account.
CRUD: Create, Read, Update, Delete.
Sensitive Data: Student records, parent contacts, finance records, exam results.
Authorized User: User authenticated and approved for a module by role middleware.
4. Governance and Responsibilities
Principal:
Approves role assignments.
Oversees all modules and compliance.
Deputy Principal:
Manages timetable.
Manages academic communication and result dissemination to parents.
Secretary:
Maintains parent/guardian records.
Supports parent communications and student registry workflows.
Accountant:
Maintains finance records.
Supports financial reporting and compliance.
Store Keeper:
Manages inventory records and stock operations.
ICT/Admin Support:
Maintains uptime, backups, deployment controls, and security baselines.
5. Access Control Policy
All admin access requires authenticated and verified accounts.
Access is role-based and enforced server-side.
Role names shall be normalized in underscore format (example: store_keeper).
Unauthorized operations must return denial (HTTP 403 equivalent).
Shared credentials are prohibited.
6. Module SOP Procedures
6.1 Students Module
Authorized Roles:
Principal, Deputy Principal, Accountant, Secretary (Create/Read/Update).
Delete is limited to Principal, Deputy Principal, Accountant.
Procedure:
Validate admission number uniqueness.
Capture mandatory student fields.
Update status changes with reason.
Save and verify records.
6.2 Programs and Exams
Authorized Roles:
Principal, Deputy Principal, HOD.
Procedure:
Create/update program and exam settings.
Enter or update exam results.
Confirm publish status before communications.
6.3 Finance Module
Authorized Roles:
Principal, Accountant.
Procedure:
Capture transaction type, category, amount, reference.
Validate supporting notes and source.
Reconcile balances weekly.
6.4 Store Module
Authorized Roles:
Principal, Accountant, Store Keeper (Create/Read/Update).
Delete limited to Principal, Accountant.
Procedure:
Record stock-in/stock-out accurately.
Maintain reorder levels.
Review variances weekly.
6.5 Timetable Module
Authorized Roles:
Deputy Principal, Principal.
Procedure:
Create timetable slot with day, period, subject, class, stream, teacher.
Resolve schedule conflicts prior to publication.
Update version history when changes occur.
6.6 Parent/Guardian Module
Authorized Roles:
Secretary, Principal (Read/Update).
Procedure:
Maintain parent and guardian name/phone.
Define contact preference (parent or guardian).
Validate phone format before save.
6.7 Communications Module
Authorized Roles:
Deputy Principal, Secretary, Principal.
Procedure:
Select student(s) and message type (notice/result).
Confirm recipients from contact preference.
Send and log communications.
Review failed deliveries and retry.
7. Data Integrity and Validation Policy
Mandatory validations must run before data persistence.
Unique constraints must be enforced on key identifiers.
Soft-deleted conflicts must be resolved using with-trashed update logic.
All high-impact changes should be traceable.
8. Security and Compliance Policy
Production must use HTTPS and SSL strict mode.
WAF and rate limiting must protect login and communication endpoints.
Least-privilege access must be applied.
Password complexity standards shall be enforced.
Monthly access review is mandatory.
9. Backup and Recovery Policy
Daily automated database backups required.
Secondary offsite backup required.
Weekly restore test in staging is recommended.
Recovery objective and responsible owner must be documented.
10. Incident Handling
User reports issue to ICT/Admin Support.
Incident is classified: Access, Data, Performance, Security.
Immediate containment actions are performed.
Root cause and corrective action are documented.
Principal receives incident summary for major issues.
11. Monitoring and Reporting
Daily:
Failed jobs check.
Error log review.
Message send status review.
Weekly:
Data quality review.
Inventory and finance reconciliation.
Monthly:
Permission audit.
Backup restore drill.
Security posture review.
12. Policy Enforcement
Violation of access or data-handling policy may result in:

Account suspension.
Incident review.
Administrative action per school governance policy.
B) Staff Training Guide (Simple, Non-Technical)
Title: How To Use the Vigurungani School System

1. What this system is for
This system helps the school manage:

Students
Exams and results
Finance
Store/inventory
Timetable
Parent and guardian contacts
Messages to parents
2. First steps for every user
Go to the school system link.
Log in with your school account.
Check your name and role at the top.
Open only the modules you are allowed to use.
3. Role guides
Principal
Can view and manage all major modules.
Oversees records, reports, and approvals.
Deputy Principal
Manages timetable.
Works with exams and results.
Sends notices/results to parents.
Secretary
Updates parent and guardian contact details.
Works with student registry actions.
Sends notices/results to parents.
HOD
Works on programs and exams.
Supports departmental academic records.
Accountant
Manages income/expense records.
Reviews financial balances.
Store Keeper
Manages stock records.
Adds and updates inventory items.
4. Everyday tasks
Updating student or contact records
Search student.
Open record.
Edit details carefully.
Click save.
Confirm success message.
Creating timetable entries
Open Timetable module.
Enter day, period, subject, class, and teacher.
Save.
Check for clashes.
Sending parent messages
Open Communications.
Select students.
Type notice or choose result send.
Send.
Check message history/log.
5. Good practices
Double-check names and phone numbers before saving.
Do not share your password.
Log out when done.
Report errors immediately.
Only do tasks allowed for your role.
6. If something goes wrong
Take screenshot.
Note what you clicked.
Report to ICT/Admin Support.
Do not keep retrying if system is failing.
C) One-Page Role & Permission Handbook (Printable)
Vigurungani School System - Role & Permission Handbook

CRUD Legend

C = Create
R = Read/View
U = Update
D = Delete
Module	Principal	Deputy Principal	HOD	Accountant	Store Keeper	Secretary
Dashboard	R	R	R	R	R	R
Students	C,R,U,D	C,R,U,D	-	C,R,U,D	-	C,R,U
Programs	C,R,U,D	C,R,U,D	C,R,U,D	-	-	-
Exams	C,R,U,D	C,R,U,D	C,R,U,D	-	-	-
Exam Results	C,R,U,D	C,R,U,D	C,R,U,D	-	-	-
Finance	C,R,U,D	-	-	C,R,U,D	-	-
Store	C,R,U,D	-	-	C,R,U,D	C,R,U	-
Timetable	C,R,U,D	C,R,U,D	-	-	-	-
Parent/Guardian Details	R,U	-	-	-	-	R,U
Parent Messages/Results	C,R	C,R	-	-	-	C,R
Special Rules

Secretary cannot delete students.
Store Keeper cannot delete inventory.
Timetable ownership is Deputy Principal-led.
Parent contact updates are Secretary-led.
Parent communication is Deputy Principal + Secretary-led.
Data Responsibility

Secretary: Contact quality and completeness.
Deputy Principal: Timetable quality and academic communications.
Principal: Governance and compliance.
Security Rules

Never share passwords.
Always log out after use.
Report suspicious activity immediately.
Only use modules assigned to your role.
Support Contact

ICT/Admin Support: [Insert Name/Phone/Email]
Escalation: Principal’s office [Insert Contact]
