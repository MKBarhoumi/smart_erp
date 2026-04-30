# Technical Project Specification

## 1. Project Overview

### Project Purpose
NovERP is a Tunisian B2B ERP platform focused on invoicing, inventory, payments, reporting, and regulatory compliance for electronic invoicing through Tunisia TradeNet (TTN) El Fatoora, TEIF v1.8.8. The system is designed as a tenant-isolated business application with a server-driven Inertia.js interface and a Laravel backend.

### Business Goals
- Provide compliant electronic invoicing for Tunisian SMEs.
- Enforce Tunisian fiscal rules, especially Matricule Fiscale validation, TTN submission, XAdES-BES signing, and TND millime precision.
- Centralize operational data for customers, products, services, stock, payments, and reporting.
- Support multi-tenant SaaS operation with strong data isolation.
- Give role-based access to accountants, sales staff, inventory managers, and auditors without exposing unnecessary controls.

### System Vision
The platform is intended to be the authoritative operational system for a company operating in the Tunisian fiscal environment. It combines ERP-style operational workflows with TEIF-compliant document generation, digital signing, TTN transmission, and printable PDF artifacts containing the CEV QR code when accepted.

### Key Users and Actors
| Actor | Responsibility |
|---|---|
| Super admin | Oversees tenants, plans, and global administration. |
| Admin | Manages users, profiles, settings, and all business modules within a tenant. |
| Accountant | Owns fiscal correctness, validation, signing, submission, payments, and reporting. |
| Sales | Manages customers, drafts, and sales-facing documents within allowed permissions. |
| Inventory manager | Manages stock, products, and movement records. |
| Viewer | Read-only stakeholder, auditor, or consultant. |
| Tenant owner / company admin | Registers and configures a tenant company, certificate, and fiscal identity. |

## 2. Scope

### In-Scope Features
- Tenant registration and provisioning.
- Authentication, password reset, email verification, and role-based authorization.
- Company settings management, including fiscal identity and certificate upload.
- Customer management.
- Product catalog management.
- Service catalog management.
- OldInvoice lifecycle management, including TEIF XML generation, validation, signing, submission, PDF output, and TTN response handling.
- Invoice XML import flow on the invoices page.
- Payment recording and balance tracking.
- Inventory movement tracking and automatic stock updates.
- Dashboard metrics and reporting.
- Admin user management, audit log viewing, and profile manager for custom permission profiles.
- Notifications and flash/toast feedback.

### Out-of-Scope Items
- Multi-currency support beyond TND.
- Native mobile application.
- AI forecasting and automated categorization.
- Direct bank reconciliation integrations.
- Public API platform not explicitly described in the source docs.
- Features not supported by the source files, including unverified extra modules or undocumented automation.

### Assumptions and Constraints
- The blueprint is the primary source of truth for regulatory, architectural, and data-model behavior.
- The frontend UI specification is the source of truth for the current page inventory, layout behavior, and verified runtime permission wiring.
- When documents disagree, this specification prefers the more specific and/or verified implementation detail while preserving regulatory constraints from the blueprint.
- All monetary values are in TND with exactly three decimal places.
- Database isolation is database-per-tenant.
- TTN compliance is mandatory for the TEIF oldinvoice flow.
- Inertia.js is used for server-driven pages rather than a separate JSON-first SPA API.

## 3. System Architecture

### Overall Architecture
NovERP uses a Laravel monolith with an Inertia.js bridge to a React frontend. The backend owns routing, validation, authorization, business logic, persistence, PDF generation, signature generation, and TTN communication. The frontend renders server-provided props and performs local interaction state management.

### Frontend / Backend Separation
- Frontend: React + TypeScript pages, reusable UI primitives, layout shells, local form state, and permission-aware presentation logic.
- Backend: Laravel controllers, middleware, form requests, services, queued jobs, policies, database models, and report/export generation.
- Data is shared via Inertia props rather than a separate public API for most interactions.

### Multi-Tenant Model
The system follows a database-per-tenant model.

| Layer | Contents |
|---|---|
| Central database | Tenants, domains, plans, and shared SaaS metadata. |
| Tenant database | Company settings, users, customers, products, services/catalog items, oldinvoices, lines, payments, stock movements, audit logs, and TTN submission logs. |

Tenant identification is subdomain-based. A tenant provision creates a dedicated PostgreSQL database, then runs tenant migrations into that database.

### Module Structure
- Authentication and tenant management.
- Company settings.
- Customers.
- Products.
- Services.
- OldInvoices and invoices.
- Payments.
- Inventory.
- Dashboard and reports.
- Administration.
- Shared UI, permissions, and notifications.

### Data Flow and Dependencies
1. User action occurs in the Inertia page.
2. Controller receives the request and validates it with a Form Request.
3. Service classes apply the domain rules.
4. Eloquent models persist data in the tenant database.
5. Middleware shares updated permissions, flashes, and auth context back to the frontend.
6. For TEIF flows, XML is generated, validated, signed, queued, submitted, and logged.
7. Accepted TTN responses store the TTN reference and CEV QR payload, which are rendered in the UI and PDF output.

### Integrations and External Services
- Laravel Sanctum for SPA authentication.
- stancl/tenancy for multi-tenancy.
- Redis and Laravel Horizon for queues and monitoring.
- TTN SOAP or HTTPS submission service.
- OpenSSL for RSA-SHA256 signing.
- DOMDocument, XML canonicalization, and XSD validation.
- DomPDF or Snappy for PDF output.
- QR code generation and QR rendering.
- Email delivery for notifications and document sending.

## 4. Core Functional Modules

### 4.1 Authentication and Tenant Management

**Purpose**: Create tenant companies, authenticate users, and enforce tenant isolation.

**Main Features**
- Tenant registration with company name, Matricule Fiscale, admin email, and password.
- Subdomain-based tenant provisioning.
- Database creation and tenant migration execution on registration.
- Email verification, login, logout, reset password, and remember-me support.
- Account lockout after five failed login attempts with a 15-minute cooldown.

**Inputs / Outputs**
- Inputs: registration form, login credentials, reset requests.
- Outputs: tenant record, database provisioning, session or Sanctum auth state, shared auth props.

**Related Pages or Screens**
- Login, register, forgot password, reset password, verify email.

**Permissions / Roles**
- Public auth pages are available before login.
- Registration may be tenant-scoped or admin-controlled depending on deployment policy.

**Special Rules / Validations**
- Matricule Fiscale must conform to the Tunisian pattern required by the blueprint.
- Tenant creation must provision a fresh tenant database.

### 4.2 Company Settings

**Purpose**: Store the tenant’s fiscal identity and default invoice configuration.

**Main Features**
- Company identity fields.
- Logo upload.
- Bank and postal details.
- Default TVA and Timbre Fiscal configuration.
- OldInvoice numbering format.
- Certificate upload and expiry visibility.
- Default payment terms template.

**Inputs / Outputs**
- Inputs: company form fields, certificate file, logo upload.
- Outputs: persisted settings and certificate metadata shared to the UI.

**Related Pages or Screens**
- Company settings page.
- Profile settings page for user account updates.

**Permissions / Roles**
- Admin and super admin have full access.
- Custom profiles may expose read-only or edit access depending on page permissions.

**Special Rules / Validations**
- Company Matricule Fiscale must be validated strictly.
- Certificate private material must be encrypted at rest.

### 4.3 Customers

**Purpose**: Maintain customer and counterparty records used in invoices and statements.

**Main Features**
- CRUD list, create, edit, show, and delete.
- Search, filter, and pagination.
- CSV import and export.
- Support for identifier types I-01, I-02, I-03, and I-04.

**Inputs / Outputs**
- Inputs: identifier type, identifier value, address, contact, fiscal metadata, bank data.
- Outputs: customer records, show-page usage history, export files.

**Related Pages or Screens**
- Customers index, create, edit, show.

**Permissions / Roles**
- Admin and accountant typically have full access.
- Sales may create and edit customers if profile permissions allow.
- Viewer is read-only.

**Special Rules / Validations**
- I-01 must match the Tunisian Matricule Fiscale pattern.
- I-02 must be 8 digits.
- I-03 must be 9 digits.
- I-04 must be 35 characters or less.
- Country code defaults to TN and must be ISO 3166-1 alpha-2.

### 4.4 Products

**Purpose**: Manage stock-relevant catalog items and invoice line sources.

**Main Features**
- CRUD list, create, edit, show, and delete.
- Search, filter by category, pagination, import/export.
- Multilingual descriptions.
- Inventory tracking, minimum stock threshold, and current stock.

**Inputs / Outputs**
- Inputs: item code, descriptions, unit of measure, unit price, TVA, category, inventory settings.
- Outputs: product catalog data, stock movement linkage, line item selection.

**Related Pages or Screens**
- Products index, create, edit, show.

**Permissions / Roles**
- Inventory manager can fully manage products.
- Sales and viewer may only view, depending on profile permissions.

**Special Rules / Validations**
- Item code must be unique per tenant.
- Monetary values use NUMERIC(20,3).
- Soft delete is used where deletion could affect historical traceability.

### 4.5 Services

**Purpose**: Maintain billable service catalog items distinct from physical inventory items.

**Main Features**
- CRUD list, create, edit, and show.
- Search by name, code, or category.
- Filters by category and status.
- Usage visibility showing invoices where the service was billed.
- Fields include service code, name, description, category, billing unit, unit price, tax rate, and status.

**Inputs / Outputs**
- Inputs: service metadata and pricing.
- Outputs: service catalog records and invoice usage links.

**Related Pages or Screens**
- Services index, create, edit, show.

**Permissions / Roles**
- Admin and accountant can manage services unless restricted by custom profiles.
- Sales and viewer are typically read-only.
- Inventory manager normally has no access.

**Special Rules / Validations**
- Service code must be unique and may be auto-generated or manually entered.
- Status defaults to Active.
- Tax rate must be one of 0%, 7%, 13%, or 19%.

### 4.6 OldInvoices

**Purpose**: Handle the regulatory invoice lifecycle, including TEIF generation, validation, signing, submission, and acceptance.

**Main Features**
- Create, edit, show, duplicate, and delete draft documents.
- Validate to lock the document and generate TEIF XML.
- Apply XAdES-BES signature.
- Submit to TTN asynchronously.
- Download PDF and signed XML.
- Create credit notes from an oldinvoice.
- Email the invoice to the customer.
- Display TTN reference, CEV QR code, payment status, and tax summary.

**Inputs / Outputs**
- Inputs: customer, line items, tax rates, payment terms, conditions, notes, and supporting metadata.
- Outputs: TEIF XML, signed XML, PDF, TTN submission logs, TTN reference, CEV QR code.

**Related Pages or Screens**
- OldInvoices index, create, edit, show.

**Permissions / Roles**
- Accountant and admin can perform the full lifecycle.
- Sales may create drafts and edit own drafts if the profile allows it.
- Viewer is read-only.

**Special Rules / Validations**
- Status flow: DRAFT -> VALIDATED -> SIGNED -> SUBMITTED -> ACCEPTED.
- Rejected documents may return to DRAFT for correction.
- ARCHIVED documents are read-only.
- OldInvoice date and other dates must be stored as date values and formatted for XML as required.
- All totals must use three-decimal TND precision.

### 4.7 Invoices

**Purpose**: Support the current invoices page and XML import flow described in the UI and task specification.

**Main Features**
- List invoices with search, filters, and actions.
- Create invoice records.
- Import TEIF-compliant XML through a step-by-step modal.
- Parse uploaded XML into previewable fields before import.
- Store imported invoices as Draft entries.

**Inputs / Outputs**
- Inputs: XML file upload, invoice fields extracted from XML.
- Outputs: parsed preview, validation errors, imported invoice entry.

**Related Pages or Screens**
- Invoices index, create, edit, show.
- Import XML modal.

**Permissions / Roles**
- Sales can import XML invoices if profile permissions allow.
- Accountant and admin can import and process invoices.
- Viewer cannot use import or action buttons.

**Special Rules / Validations**
- XML file size limit: 5 MB.
- Only .xml files are accepted.
- ID must be unique in the system.
- IssueDate must be valid.
- InvoiceTypeCode must be one of the supported codes listed in the task spec.
- PayableAmount must be positive.
- At least one InvoiceLine is required.

### 4.8 Payments

**Purpose**: Record receipts against invoices or oldinvoices and show balance status.

**Main Features**
- Create and delete payment records.
- Partial and full payment support.
- Payment method tracking.
- Remaining balance calculation.

**Inputs / Outputs**
- Inputs: amount, date, method, reference, institution, notes.
- Outputs: updated balance, payment history, receipt artifacts.

**Related Pages or Screens**
- Payments index and payment forms embedded in invoice detail pages.

**Permissions / Roles**
- Accountant and admin can create and manage payments.
- Sales and viewer typically have read-only visibility.

**Special Rules / Validations**
- Payment amounts must be numeric and fit TND precision.
- Overdue and partially paid states must be reflected in the UI.

### 4.9 Inventory

**Purpose**: Track stock levels, movements, and valuation.

**Main Features**
- Inventory dashboard and movement history.
- Automatic deduction on invoice validation.
- Stock restoration on credit note issuance.
- Manual stock adjustments with reason notes.
- Low stock alerts and stock valuation.

**Inputs / Outputs**
- Inputs: movement type, quantity, reason, reference, product.
- Outputs: current stock, movement logs, alerts.

**Related Pages or Screens**
- Inventory index and inventory history.

**Permissions / Roles**
- Inventory manager has primary access.
- Admin may access all stock controls.
- Viewer is read-only.

**Special Rules / Validations**
- Stock movements must be auditable.
- Negative inventory should be prevented or explicitly governed by business rules.

### 4.10 Dashboard and Reports

**Purpose**: Surface business status, fiscal summaries, and analytical views.

**Main Features**
- Revenue cards and charts.
- Outstanding receivables and overdue documents.
- Recent invoices or oldinvoices.
- Low stock alerts.
- TTN submission status summaries.
- Sales, TVA, Timbre, aging, and customer statement reports.
- PDF and Excel exports where supported.

**Inputs / Outputs**
- Inputs: date range, customer, product, report type, filters.
- Outputs: metrics, charts, downloadable reports, PDFs.

**Related Pages or Screens**
- Dashboard and reports pages.

**Permissions / Roles**
- Accountant and admin have access to financial reports.
- Sales may only access sales-focused reports if allowed.
- Inventory manager may access inventory-focused reporting.
- Viewer gets read-only report access where granted.

**Special Rules / Validations**
- Financial KPIs may be hidden for roles without permission.
- Reports must respect tenant boundaries.

### 4.11 Administration, Users, and Profile Manager

**Purpose**: Manage users, audit history, and custom access profiles.

**Main Features**
- User list and user form.
- Audit log list with filters and stat cards.
- Profile manager for custom page-level and special permissions.
- Assignment of custom profiles to users.

**Inputs / Outputs**
- Inputs: user data, profile name, base role, page permissions, special permissions.
- Outputs: stored users, profiles, permission matrices, and user assignments.

**Related Pages or Screens**
- Admin users, audit log, and profile manager pages.

**Permissions / Roles**
- Admin and super admin only.

**Special Rules / Validations**
- Custom profiles can clone permissions from a base role.
- No access profile should bypass tenant isolation or security middleware.

## 5. User Roles and Permissions

### Roles
The source files identify the following built-in roles: super_admin, admin, accountant, sales, inventory_manager, and viewer. In addition, custom profiles are supported through profile records stored in the database.

### Access Model
- Runtime permissions are shared through the Inertia auth payload.
- Admin and super_admin receive full page and special permissions in middleware.
- Other roles use stored page_permissions and special_permissions.
- Frontend checks use the shared permission object to hide or disable actions.

### Role Behavior Summary
| Role | Typical Access Pattern |
|---|---|
| super_admin | Full system access, including tenant and admin capabilities. |
| admin | Full tenant access, including users, profiles, settings, and business modules. |
| accountant | Finance-first access: validation, signing, submission, payments, reports, and document control. |
| sales | Customer-facing and draft-oriented access; may create and edit only permitted records. |
| inventory_manager | Product and stock access; no invoicing or finance access. |
| viewer | Read-only access where explicitly granted; no state-changing actions. |

### Special Permission Flags
- Can validate or submit TEIF invoices.
- Can import XML invoices.
- Can export data.
- Can view financial KPIs on the dashboard.
- Can manage own profile and password.

### Role-Based UI Behavior
- Action buttons are hidden when permissions do not allow the action.
- Sidebar items are hidden when a page is not accessible.
- Direct URL access to disallowed pages returns 403.

## 6. Business Processes and Workflows

### Tenant Registration and Provisioning
1. User submits tenant registration details.
2. System validates Matricule Fiscale and account inputs.
3. Central tenant record and domain record are created.
4. A new tenant database is provisioned.
5. Tenant migrations are executed.
6. First user and default settings are created.
7. Email verification is sent.

### Authentication Workflow
1. User logs in with email and password.
2. Sanctum issues a session-based SPA auth context.
3. The app loads shared props, including permissions and flash messages.
4. Failed login attempts trigger the lockout policy.

### OldInvoice Lifecycle Workflow
1. Create a draft with customer, document data, and line items.
2. Calculate totals and store them in TND precision.
3. Validate the document and lock editing.
4. Generate TEIF XML.
5. Apply XAdES-BES signature.
6. Submit the signed XML to TTN asynchronously.
7. On success, store TTN reference and CEV QR data.
8. Render accepted documents as PDF with QR code and reference.
9. If rejected, store the error and allow correction back to DRAFT.

### Invoice XML Import Workflow
1. User uploads a TEIF-compliant XML file.
2. System parses and validates the file.
3. A preview shows key extracted fields.
4. Validation errors block import.
5. On confirmation, the invoice is imported and refreshed in Draft status.

### Payment and Stock Workflow
1. Payment is recorded against a document.
2. Remaining balance updates immediately.
3. When an oldinvoice is validated, stock is decremented for tracked items.
4. Credit notes restore stock where applicable.

### Status Transitions
| Entity | Status Flow |
|---|---|
| OldInvoice | DRAFT -> VALIDATED -> SIGNED -> SUBMITTED -> ACCEPTED -> ARCHIVED |
| OldInvoice error path | DRAFT -> VALIDATED -> SIGNED -> SUBMITTED -> REJECTED -> DRAFT |
| Tenant | trial -> active -> suspended -> cancelled |
| Payment | unpaid -> partially paid -> fully paid; overdue is derived from aging rules |
| Service / Product | active / inactive |

### Edge Cases and Exceptions
- Invalid Matricule Fiscale inputs must show specific validation errors.
- TTN timeouts must queue retries with exponential backoff.
- Certificate expiry blocks signing and should surface administrative alerts.
- Duplicate invoice IDs from XML import must be rejected.
- Soft-deleted linked records must not break historical display.
- Permission denials should hide controls and return 403 on direct access.

## 7. UI / Frontend Specification

### Global Layout
- Main authenticated shell: AuthenticatedLayout.
- Guest shell: GuestLayout.
- Document title suffix: Novation ERP.
- Sidebar uses a dark navy background with a purple active state.
- Header search placeholder: Search invoices, customers, products...
- Authenticated footer: copyright text and version text.

### Sidebar Navigation
Visible labels from the UI spec:
- Dashboard
- Invoices
- OldInvoices
- Customers
- Products
- Services
- Payments
- Inventory
- Reports
- My Account
- Settings
- Users
- Audit Log
- Profile Manager

### Page Structure and Behavior
| Area | Specification |
|---|---|
| Lists | Search, filters, pagination, and action buttons. |
| Forms | Inertia useForm-based submissions with inline errors. |
| Tables | Responsive tables with hover states and badges. |
| Modals | Used for destructive actions, XML import, and confirmations. |
| Navigation | Sidebar-based module navigation with active state and role gating. |
| State | Shared props from Inertia, local React state for interactive widgets, and loading/processing states on async actions. |

### Key Pages
- Dashboard: KPI cards, charts, recent documents, top customers, and low stock alerts.
- Invoices: list, create, edit, show, import XML modal.
- OldInvoices: list, create, edit, show, lifecycle controls, TTN logs, QR display.
- Customers: list, create, edit, show.
- Products: list, create, edit, show.
- Services: list, create, edit, show.
- Payments: payment ledger and filters.
- Inventory: overview and movement history.
- Reports: index hub plus individual report pages.
- Settings: company settings and profile settings.
- Admin: users, audit log, and profile manager.

### Forms and Controls
- Primary button style is purple and rounded.
- Secondary action buttons use outlined styling.
- Inputs, selects, badges, tables, modals, pagination, QR code display, and toast notifications are centralized in reusable UI primitives.
- Complex invoice actions use confirmation dialogs, lifecycle steppers, and TTN log inspectors.

### Import XML Modal
- Step 1: XML upload.
- Step 2: validation and preview of extracted fields.
- Step 3: confirm import.
- The modal enforces file type and size limits and blocks submission on validation errors.

### Empty and Error States
- Empty lists should render clear no-data states.
- Validation errors appear inline on fields.
- Permission denials render 403 pages.
- Flash messages appear as toast notifications.
- Network or TTN errors should expose retry or corrective guidance where possible.

### Responsive Behavior
- The layout must remain usable on desktop and mobile.
- Tables should remain legible with horizontal scrolling where necessary.
- Sidebar and page chrome must not obscure core actions on smaller screens.

## 8. Data Model / Domain Concepts

### Main Entities
| Entity | Core Fields |
|---|---|
| Tenant | name, slug, matricule_fiscal, database_name, status, plan_id, trial ends. |
| Domain | tenant_id, domain, is_primary. |
| Plan | name, limits, TTN integration flag, monthly price. |
| User | name, email, password, role, active flag. |
| CompanySetting | company identity, address, bank, fiscal defaults, certificate data, numbering format. |
| Customer | identifier type/value, name, address, fiscal references, contact, bank, status. |
| Product | item code, descriptions, unit, price, tax rate, category, stock tracking. |
| Service | code, name, description, category, billing unit, unit price, tax rate, status. |
| OldInvoice | document identifier, document type, dates, parties, totals, status, TTN data, XML, signature data. |
| OldInvoiceLine | line number, product reference, item data, quantity, unit, pricing, tax, discount, nesting. |
| OldInvoiceTaxLine | tax type, rate, taxable amount, tax amount. |
| OldInvoiceAllowance | allowance code, description, amount. |
| Payment | invoice reference, date, amount, method, reference, institution, notes. |
| StockMovement | product, type, quantity, reference, reason, performer. |
| AuditLog | actor, action, auditable entity, old and new values, IP, user agent. |
| TTNSubmissionLog | oldinvoice, attempt, request, response, status, error data, timestamps. |

### Relations
- Tenant has many domains and belongs to one plan.
- Customer has many oldinvoices.
- OldInvoice has many lines, tax lines, allowances, payments, and TTN submission logs.
- Product has many stock movements and can be referenced by oldinvoice lines.
- OldInvoiceLine can contain nested sub-lines.
- Users create or update business records and audit logs.

### Statuses and Rules
- OldInvoice statuses: draft, validated, signed, submitted, accepted, rejected, archived.
- Payment states are derived from balances and aging.
- Tenant states: trial, active, suspended, cancelled.
- Service and product status: active or inactive.
- All TND amounts must be stored and rendered with three decimal places.
- Monetary types must use NUMERIC(20,3); floats are not allowed.

### Domain Rules
- Matricule Fiscale validation is mandatory for sellers and relevant partner identifiers.
- Customer and company identifier types must follow the allowed Tunisian identifier formats.
- TEIF XML and signed XML must be retained for auditability and fiscal traceability.
- TTN reference and CEV QR code are stored only after successful submission and acceptance.

## 9. API / Backend / Logic Specification

### Route and Action Model
The application is server-routed through Inertia. It does not depend on a separate public JSON API for normal UI operation.

### Primary Route Groups
| Area | Representative Routes |
|---|---|
| Auth | /login, /logout, /register, password reset, email verification. |
| Dashboard | /dashboard. |
| Settings | /company-settings or /settings for company configuration, plus /settings/profile for account settings. |
| Customers | /customers and CRUD/import/export routes. |
| Products | /products and CRUD routes. |
| Services | /services and CRUD routes. |
| Inventory | /inventory and /inventory/history plus adjustment action. |
| Invoices | /invoices and XML import parse/store routes. |
| OldInvoices | /oldinvoices and lifecycle actions: validate, sign, submit, duplicate, credit-note, pdf, xml, email. |
| Payments | /payments or /oldinvoices/{id}/payments depending on context. |
| Reports | /reports and report-specific routes. |
| Admin | /admin/users, /admin/audit-log, /admin/profiles. |

### Backend Rules
- Controllers remain thin and delegate to service classes.
- Validation is implemented with Form Request classes.
- Custom exceptions handle domain errors such as TEIF validation, signature failures, TTN submission failures, and invalid state transitions.
- Policies and gates enforce role-based access.
- Middleware shares auth, permissions, flash data, and selected company context to the frontend.

### Processing Logic
#### OldInvoice Calculation
```
line_net = quantity * unit_price - discount
line_tax = line_net * tva_rate / 100
total_ht = sum(line_net)
total_net_before_disc = sum(line_net before discounts)
total_tva = sum(line_tax)
total_gross = total_ht + total_allowances
timbre_fiscal = configured flat amount when enabled
total_ttc = total_ht + total_tva + timbre_fiscal
```

#### TEIF XML Generation
- Build the TEIF root, header, body, line sections, monetary sections, and tax summary.
- Format dates in the specified TEIF date formats.
- Include amount descriptions in French for total TTC.
- Validate against the TEIF XSD before signing or submission.

#### XAdES-BES Signing
- Apply Exclusive XML Canonicalization.
- Exclude ds:Signature and RefTtnVal nodes in digest computation.
- Compute SHA-256 digests for document and SignedProperties references.
- Sign SignedInfo with RSA-SHA256.
- Attach the X.509 chain and XAdES properties.

#### TTN Submission
- Submission runs in a queued job.
- Retry logic uses exponential backoff.
- Submission logs persist request and response payloads.
- Accepted responses extract TTN reference and CEV QR payload.

#### Inventory Side Effects
- Validated invoices deduct stock for tracked items.
- Credit notes restore stock where applicable.

### Events, Triggers, and Side Effects
- Validation locks an oldinvoice and stores generated XML.
- Signing stores the signed XML and signature timestamp.
- TTN acceptance stores reference, validation time, and QR code data.
- Rejection stores the error and returns the document to correction mode.
- Payment creation updates the balance and payment status.
- Stock movement creation updates product availability and history.
- Audit logs capture significant create/update/delete/sign/submit actions.

## 10. Task and Implementation Breakdown

### Core Roadmap Phases
1. Foundation and scaffolding: Laravel, Inertia, React, TypeScript, Tailwind, Sanctum, tenancy.
2. Core business data: company settings, customers, products.
3. Invoicing engine: oldinvoices, calculations, TEIF XML, status machine.
4. Digital signature and TTN integration: certificate management, XAdES-BES, TTN API, CEV handling.
5. Payments and inventory: payment tracking, stock movement automation.
6. Dashboard and reporting: metrics, exports, PDF/report generation.
7. PDF generation and UI polish: document templates, notifications, accessibility, performance.
8. Testing and deployment: backend, frontend, E2E coverage, production hardening.

### Current UI / Product Extensions
- Services module and sidebar item.
- Invoice XML import modal and parsing workflow.
- Role descriptions for sales, inventory_manager, and viewer.
- Profile Manager for custom profiles and permission matrices.

### Dependency Order
- Tenant and auth foundation must exist before any business module.
- Company settings must exist before certificate, numbering, and TEIF output.
- Customer and product catalogs must exist before invoice line authoring.
- Calculation engine must exist before XML generation.
- XML generation must exist before signing.
- Signing must exist before TTN submission.
- Payments and inventory side effects depend on invoice lifecycle state changes.
- Reports depend on reliable persisted financial and movement data.
- Profile Manager depends on shared permission infrastructure and user assignment flows.

### Priority Order
1. Tenant/auth foundation.
2. Company settings and master data.
3. Oldinvoice creation and calculation.
4. TEIF XML and validation.
5. Signature and TTN submission.
6. Payments and inventory.
7. Dashboard and reports.
8. PDF, polish, and broad testing.

## 11. Non-Functional Requirements

### Performance
- List pages should paginate, defaulting to 25 items per page.
- Invoice lifecycle actions should avoid blocking HTTP requests when TTN is involved.
- Dashboard and report queries should be optimized with eager loading and caching.
- PDF generation should remain asynchronous where volume is high.

### Security
- Sanctum-based authentication.
- CSRF protection.
- Role and permission enforcement at middleware, policy, and UI layers.
- Certificate files and passphrases encrypted at rest.
- HTTPS forced in production.
- Rate limiting on login and TTN submission.
- Tenant data must be isolated by database.

### Maintainability
- Strict types in PHP and TypeScript.
- Service classes for business logic.
- Form Requests for validation.
- Clear module boundaries and reusable UI primitives.
- Documented code paths for TEIF and signature logic.

### Scalability
- Database-per-tenant architecture supports horizontal growth.
- Redis and queue-based TTN jobs reduce request blocking.
- Caching is used for settings, tax rates, and catalog data where appropriate.

### Usability
- Clean, role-aware sidebar navigation.
- Consistent purple primary actions and dark navy app shell.
- Readable tables, badges, dialogs, and toast feedback.
- Responsive behavior for smaller screens.

### Reliability
- Retry and circuit-breaker behavior for TTN failures.
- Audit logs for traceability.
- Backups and monitoring are required in production.
- Validation should prevent invalid fiscal documents from progressing.

## 12. Testing and Quality Considerations

### Functional Testing
- Authentication and tenant provisioning.
- CRUD for customers, products, services, and settings.
- OldInvoice calculations.
- TEIF XML generation and XSD validation.
- XAdES signature construction and verification.
- TTN submission success and error flows.
- Payment and stock side effects.
- Report generation and export behavior.

### UI Testing
- Form validation and submission.
- Page rendering and component interaction.
- Import XML modal flow.
- Permission-gated navigation and action visibility.
- Toasts, dialogs, and loading states.

### Edge Cases
- Invalid Matricule Fiscale values.
- Duplicate invoice identifiers.
- Zero, discount-heavy, and multi-rate tax calculations.
- Certificate expiry and invalid chain states.
- TTN timeout, 4xx, and 5xx responses.
- Cross-tenant access attempts.
- Soft-deleted linked records.

### Regression Risks
- Monetary rounding regressions.
- Signature canonicalization changes.
- Permission-sharing mismatches between middleware and frontend.
- Route discrepancies between company settings and settings pages.
- XML import parsing drift versus TEIF expectations.

### Acceptance Criteria
- Oldinvoice can be created, calculated, validated, signed, submitted, accepted, and printed with QR code.
- XML import can parse and import a compliant invoice into Draft status.
- Custom profiles can be created and assigned.
- Roles respect page-level and action-level permission gates.
- All TND amounts render at three decimal places.
- Tenant data remains isolated.

## 13. Open Questions / Missing Information
- The exact persistence model for services is defined in the UI task spec but not fully detailed in the blueprint database schema.
- The final canonical route for company settings is split between /settings and /company-settings across documents; current UI files use /company-settings for company configuration and /settings/profile for account settings.
- The documents differ on some stack versions, especially React and Tailwind. This specification follows the blueprint and verified UI behavior rather than the older roadmap summary.
- The exact default permission matrix for accountant is not fully enumerated in the source files.
