# RMCP User Guide

This guide explains how end users use the Risk Management and Compliance Platform (RMCP).

## 1. What RMCP Does

RMCP helps teams manage:
- Client onboarding and profile management
- KYC/AML document collection and versioning
- Risk assessments and screening checks
- Compliance incidents, tasks, and communications
- Case lifecycle (draft, review, approval, closure)

## 2. Accessing the Application

- Web application: http://localhost:4200/
- API gateway (backend): http://localhost:8080/

If you open `http://localhost:4200/login`, it redirects to the main app URL.

## 3. Sign In

1. Open the web app.
2. Enter your email and password.
3. Select Sign In.

Your menu items depend on your role and permissions.

## 4. Default Test Accounts

These are typical seeded accounts in local environments:

- Super Admin: admin@rmcp.local / Admin@12345
- Compliance Officer: officer@rmcp.local / Officer@12345

Additional accounts may exist depending on environment seeding:
- Company Admin
- Employee
- Individual User

## 5. Navigation (Left Sidebar)

After login, the main menu appears on the left side. Typical sections:
- Dashboard
- Clients
- Cases
- Governance
- Incidents
- Tasks
- Comms
- Companies
- Audit
- Roles
- Compliance

If a section is missing, your role does not have access to that feature.

## 6. Core Workflows

### 6.1 Client Onboarding

1. Go to Clients.
2. Create a new client (individual or company).
3. Fill required profile details.
4. Save and review risk status.

### 6.2 Document Management

1. Open a client profile.
2. Upload required documents.
3. Replace outdated files when needed.
4. Review document version history.

Notes:
- Missing/expired required documents can block certain case actions.
- Some users can only view documents, not edit.

### 6.3 Risk and Screening

1. Open a client.
2. Run a screening check (sanctions/PEP/adverse media).
3. Review latest risk assessment and score explanation.
4. Reassess when profile or screening outcomes change.

### 6.4 Case Management

1. Create a case.
2. Add supporting details and required documents.
3. Submit for review.
4. Approver can approve/reject.
5. Close the case when complete.

### 6.5 Incidents, Tasks, and Communications

- Use Incidents to track compliance issues.
- Use Tasks to assign follow-up actions.
- Use Comms to track communication records.

## 7. Role-Based Access

The platform enforces permissions per role.
Examples:
- `clients.view` allows viewing clients
- `cases.submit` allows submitting cases for review
- `roles.view` allows viewing role and compliance admin features

You may see 403 access errors when attempting actions outside your role.

## 8. Troubleshooting for Users

### Cannot Access a Page

- Confirm you are logged in.
- Confirm your role has permission for that feature.
- Contact an administrator to request access.

### Data Not Updating

- Refresh the page once.
- Sign out and sign in again.
- If issue continues, report the exact page and action.

### Login Fails

- Verify email and password.
- Ensure Caps Lock is off.
- Ask an admin to confirm your account is active.

## 9. Security and Good Practices

- Do not share credentials.
- Log out when done.
- Upload only valid, current compliance documentation.
- Record incident and case notes clearly for audit traceability.
