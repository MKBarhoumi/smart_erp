# FRONTEND_UI_SPEC

## 1) Project Overview And Roles

### Frontend stack (verified)
- Laravel + Inertia + React + TypeScript + Tailwind.
- Entry point: resources/js/app.tsx.
- Inertia page resolver: ./Pages/**/*.tsx.
- Document title suffix: Novation ERP.

### Layout and shell (verified)
- Main authenticated shell: resources/js/Layouts/AuthenticatedLayout.tsx.
- Guest shell: resources/js/Layouts/GuestLayout.tsx.
- Navigation labels in sidebar:
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
- Header search placeholder: Search invoices, customers, products...
- Footer text inside authenticated layout: © 2026 NovERP. All rights reserved. and v2.0.0.

### Roles discovered in uploaded files
- super_admin
- admin
- accountant
- sales
- inventory_manager
- viewer
- Additional custom roles are supported through CustomRole records (database-driven).

### Permission source of truth (verified)
- Runtime permissions are shared by app/Http/Middleware/HandleInertiaRequests.php under auth.user.permissions.
- For admin and super_admin, full page and special permissions are hardcoded to true in middleware share.
- For non-admin roles, permissions are loaded from CustomRole page_permissions and special_permissions.
- Frontend hook resources/js/hooks/usePermissions.ts consumes these shared permissions.

## 2) Frontend-Related File Tree (Exact Paths)

### Application entry and global style
- resources/js/app.tsx
- resources/css/app.css

### Layouts
- resources/js/Layouts/AuthenticatedLayout.tsx
- resources/js/Layouts/GuestLayout.tsx

### Hooks
- resources/js/hooks/usePermissions.ts

### Shared components
- resources/js/Components/ImportXmlModal.tsx
- resources/js/Components/NotificationDropdown.tsx
- resources/js/Components/Dashboard/KpiCard.tsx
- resources/js/Components/Invoices/ConfirmActionDialog.tsx
- resources/js/Components/Invoices/InvoiceLifecycleStepper.tsx
- resources/js/Components/Invoices/TtnLogInspector.tsx

### UI primitives
- resources/js/Components/ui/Badge.tsx
- resources/js/Components/ui/Button.tsx
- resources/js/Components/ui/Input.tsx
- resources/js/Components/ui/Modal.tsx
- resources/js/Components/ui/Pagination.tsx
- resources/js/Components/ui/QRCodeDisplay.tsx
- resources/js/Components/ui/Select.tsx
- resources/js/Components/ui/Table.tsx
- resources/js/Components/ui/Toast.tsx

### Pages
- resources/js/Pages/Dashboard.tsx
- resources/js/Pages/Welcome.tsx
- resources/js/Pages/Admin/AuditLog/Index.tsx
- resources/js/Pages/Admin/Profiles/Index.tsx
- resources/js/Pages/Admin/Users/Form.tsx
- resources/js/Pages/Admin/Users/Index.tsx
- resources/js/Pages/Auth/ForgotPassword.tsx
- resources/js/Pages/Auth/Login.tsx
- resources/js/Pages/Auth/Register.tsx
- resources/js/Pages/Auth/ResetPassword.tsx
- resources/js/Pages/Auth/VerifyEmail.tsx
- resources/js/Pages/Customers/Create.tsx
- resources/js/Pages/Customers/Edit.tsx
- resources/js/Pages/Customers/Form.tsx
- resources/js/Pages/Customers/Index.tsx
- resources/js/Pages/Customers/Show.tsx
- resources/js/Pages/Errors/Error403.tsx
- resources/js/Pages/Errors/Error404.tsx
- resources/js/Pages/Errors/Error419.tsx
- resources/js/Pages/Errors/Error500.tsx
- resources/js/Pages/Errors/Error503.tsx
- resources/js/Pages/Inventory/History.tsx
- resources/js/Pages/Inventory/Index.tsx
- resources/js/Pages/Invoices/Create.tsx
- resources/js/Pages/Invoices/Edit.tsx
- resources/js/Pages/Invoices/Form.tsx
- resources/js/Pages/Invoices/Index.tsx
- resources/js/Pages/Invoices/Show.tsx
- resources/js/Pages/OldInvoices/Create.tsx
- resources/js/Pages/OldInvoices/Edit.tsx
- resources/js/Pages/OldInvoices/Form.tsx
- resources/js/Pages/OldInvoices/Index.tsx
- resources/js/Pages/OldInvoices/Show.tsx
- resources/js/Pages/Payments/Index.tsx
- resources/js/Pages/Products/Create.tsx
- resources/js/Pages/Products/Edit.tsx
- resources/js/Pages/Products/Form.tsx
- resources/js/Pages/Products/Index.tsx
- resources/js/Pages/Products/Show.tsx
- resources/js/Pages/Reports/CustomerAging.tsx
- resources/js/Pages/Reports/CustomerStatement.tsx
- resources/js/Pages/Reports/CustomerStatementSelect.tsx
- resources/js/Pages/Reports/Index.tsx
- resources/js/Pages/Reports/Revenue.tsx
- resources/js/Pages/Reports/TaxSummary.tsx
- resources/js/Pages/Reports/Timbre.tsx
- resources/js/Pages/Services/Create.tsx
- resources/js/Pages/Services/Edit.tsx
- resources/js/Pages/Services/Form.tsx
- resources/js/Pages/Services/Index.tsx
- resources/js/Pages/Services/Show.tsx
- resources/js/Pages/Settings/Edit.tsx
- resources/js/Pages/Settings/Profile.tsx

### Types and helpers
- resources/js/types/index.ts
- resources/js/utils/format.ts
- resources/js/utils/invoiceStatus.ts
- resources/js/utils/permissions.ts

### Related server-rendered templates used by frontend flows
- resources/views/app.blade.php
- resources/views/welcome.blade.php
- resources/views/pdf/invoice.blade.php
- resources/views/pdf/oldinvoice.blade.php
- resources/views/pdf/reports/customer-aging.blade.php
- resources/views/pdf/reports/customer-statement.blade.php
- resources/views/pdf/reports/revenue.blade.php
- resources/views/pdf/reports/tax-summary.blade.php
- resources/views/pdf/reports/timbre.blade.php

## 3) Per-File Inventory (Purpose And Scope)

### Core shell and wiring
- resources/js/app.tsx: bootstraps Inertia app, resolves page modules, sets page title format.
- resources/css/app.css: Tailwind layers, Inter font import, utility/component classes for glass cards, page header, tables, sidebars, animations.
- resources/js/Layouts/AuthenticatedLayout.tsx: authenticated app shell (sidebar, topbar, role badge, search routing, notification dropdown, flash toasts).
- resources/js/Layouts/GuestLayout.tsx: guest auth wrapper. Not found in the uploaded files for full behavior details.

### Permission and typing layer
- resources/js/hooks/usePermissions.ts: centralized permission checks from auth.user.permissions with canAccess/canView/canCreate/canEdit/canDelete and special flags.
- resources/js/types/index.ts: frontend domain contracts for users, invoices, old invoices, payments, customers, products, services, company settings, pagination, shared page props.

### Helpers
- resources/js/utils/format.ts: number/currency/percent/date/datetime display formatting (TND suffix and locale formatting).
- resources/js/utils/invoiceStatus.ts: status labels, lifecycle list, status tabs, and role helper for create CTA visibility.
- resources/js/utils/permissions.ts: static role permission matrix helper utilities. Runtime source remains middleware-shared permissions.

### Shared UI components
- resources/js/Components/ImportXmlModal.tsx: XML invoice import wizard with upload, parse preview, and import confirmation.
- resources/js/Components/NotificationDropdown.tsx: notification panel with mark-read, mark-all-read, clear-all, and optional deep-link navigation.
- resources/js/Components/Dashboard/KpiCard.tsx: KPI card rendering. Not found in the uploaded files for detailed internal markup.
- resources/js/Components/Invoices/ConfirmActionDialog.tsx: confirm dialog used for invoice actions. Not found in the uploaded files for detailed internal markup.
- resources/js/Components/Invoices/InvoiceLifecycleStepper.tsx: invoice lifecycle visualization. Not found in the uploaded files for detailed internal markup.
- resources/js/Components/Invoices/TtnLogInspector.tsx: TTN log viewer. Not found in the uploaded files for detailed internal markup.
- resources/js/Components/ui/*: primitive controls (buttons, fields, modal, table, pagination, badges, select, toast, QR display).

### Functional page groups
- Dashboard and landing: Dashboard.tsx, Welcome.tsx.
- Auth: Login, Register, ForgotPassword, ResetPassword, VerifyEmail.
- Errors: Error403, Error404, Error419, Error500, Error503.
- Customers: CRUD list/form/show set.
- Products: CRUD list/form/show set.
- Services: CRUD list/form/show set.
- Invoices: Create/Edit/Form/Index/Show + import flow integration.
- OldInvoices: Create/Edit/Form/Index/Show.
- Payments: index/list and filters.
- Inventory: overview and history pages.
- Reports: hub + revenue/tax/customer-aging/customer-statement/timbre.
- Settings: company settings and profile settings.
- Admin: users, audit log, profiles/permissions manager.

## 4) Page-By-Page UI Specification

### Public and auth pages
- / (Welcome): hero marketing page for Novation ERP with Sign In and Get Started actions.
- /login: login form.
- /register: registration form.
- /forgot-password: password reset request form.
- /reset-password/{token}: Reset Password form with Email address, New password, Confirm password, submit to /reset-password.
- /verify-email: Email Verification panel with Resend Verification Email and Sign Out.

### Main application IA (authenticated)
- /dashboard: KPI/stat widgets, recent invoices, chart(s), top customers, low stock alerts, financial visibility conditioned by permissions.
- /invoices: invoice list with filters/search/status/date, import XML button (permission-based), create button (permission-based), table actions.
- /invoices/create and /invoices/{invoice}/edit: invoice form workflow.
- /invoices/{invoice}: detailed invoice view with lifecycle actions (request validation, validate, reject, sign, submit, download xml/pdf, duplicate, payment add/remove) based on status and role permissions.
- /oldinvoices: old invoice list with filters/search/status/date.
- /oldinvoices/create and /oldinvoices/{oldinvoice}/edit: old invoice form workflow.
- /oldinvoices/{oldinvoice}: old invoice details with validation/approval/rejection/sign/submit/pdf/xml/duplicate/payment behavior.
- /customers: list and filters; /customers/create, /customers/{customer}/edit, /customers/{customer}.
- /products: list and filters; /products/create, /products/{product}/edit, /products/{product}.
- /services: list and filters; /services/create, /services/{service}/edit, /services/{service}.
- /payments: payment ledger and filters.
- /inventory: stock overview and adjustment entry.
- /inventory/history: stock movement history with filters.
- /reports: report index hub.
- /reports/revenue: revenue report with filters and pdf download endpoint.
- /reports/tax-summary: tax summary with pdf download endpoint.
- /reports/customer-aging: aging report with pdf download endpoint.
- /reports/customer-statement: customer picker.
- /reports/customer-statement/{customer}: statement detail and pdf export.
- /reports/timbre: timbre report with pdf export.

### Settings and profile
- /settings is redirected to /settings/profile by routes/settings.php.
- /settings/profile: user profile settings page (name/email, password update, account info, delete account modal).
- /company-settings: company settings page served by SettingsController edit action.

### Admin IA
- /admin/users: users list.
- /admin/users/create and /admin/users/{user}/edit: user form page.
- /admin/audit-log: audit log list and filters/stat cards.
- /admin/profiles: role profile manager (system and custom roles, page/special permissions).

### Error pages (development test routes)
- /test-errors/403 -> Error403 page.
- /test-errors/404 -> Error404 page.
- /test-errors/419 -> Error419 page.
- /test-errors/500 -> Error500 page.
- /test-errors/503 -> Error503 page.

### Explicit route-page mismatch found
- Frontend settings page resources/js/Pages/Settings/Edit.tsx submits to:
  - PUT /settings
  - POST /settings/certificate
  - POST /settings/logo
- Available web routes define:
  - PUT /company-settings
  - POST /company-settings/certificate
  - POST /company-settings/logo

## 5) Component Catalog

### Layout components
- AuthenticatedLayout: full app chrome, permission-filtered navigation, admin vs user visual theme split.
- GuestLayout: auth guest wrapper. Not found in the uploaded files for full details.

### Domain components
- ImportXmlModal:
  - Steps: upload -> preview -> confirm.
  - Parse endpoint: POST /invoices-import/parse.
  - Import endpoint: POST /invoices-import/store.
- NotificationDropdown:
  - Reads /notifications.
  - Marks single read at /notifications/{id}/read.
  - Marks all at /notifications/mark-all-read.
  - Clears all via DELETE /notifications.
- Invoice lifecycle and confirmation components:
  - ConfirmActionDialog
  - InvoiceLifecycleStepper
  - TtnLogInspector
  - Not found in the uploaded files for exact prop contracts and UI internals.

### Primitive UI components
- Button, Input, Select, Badge, Modal, Table, Pagination, Toast, QRCodeDisplay.
- Used across forms, tables, page actions, and feedback messages.

## 6) Data Flow Map (Route -> Controller -> Props -> Render)

### Inertia shared props
- Middleware: app/Http/Middleware/HandleInertiaRequests.php
- Shared payload includes:
  - auth.user identity, role, flags, unread notification count, permissions object.
  - flash success/error/warning.
  - company partial data (company_name, matricule_fiscal, logo_path, default_timbre_fiscal).

### Major page flows
- Dashboard
  - Route: GET /dashboard and GET /app
  - Controller: DashboardController index
  - Inertia page: Dashboard
  - Props: stats, recentOldInvoices, revenueChart, statusDistribution, topCustomers, lowStockProducts.

- Customers
  - Route: resource customers
  - Controller: CustomerController
  - Pages: Customers/Index, Create, Edit, Show
  - Props include paginated customers, filters, customer detail with recent old invoices.

- Products
  - Route: resource products
  - Controller: ProductController
  - Pages: Products/Index, Create, Edit, Show
  - Props include paginated products, filters, stock movements on show.

- Services
  - Route: resource services
  - Controller: ServiceController
  - Pages: Services/Index, Create, Edit, Show
  - Props include paginated services, filters, categories, billingUnits.

- Invoices (new model)
  - Route: resource invoices + action routes under /invoices/{invoice} + import routes.
  - Controller: InvoiceController.
  - Pages: Invoices/Index, Create, Edit, Show.
  - Props from show include detailed invoice object, sender/receiver/lines/taxes/payments, can* flags, validationInfo.
  - XML import actions: parseXml and importXml.

- OldInvoices
  - Route: resource oldinvoices + action routes under /oldinvoices/{oldinvoice}.
  - Controller: OldInvoiceController.
  - Pages: OldInvoices/Index, Create, Edit, Show.
  - Props include oldinvoice aggregate with customer, lines, tax lines, payments, and status transitions.

- Payments
  - Route: GET /payments, POST /oldinvoices/{oldinvoice}/payments, DELETE /payments/{payment}
  - Controller: PaymentController
  - Page: Payments/Index
  - Props include paginated payments, filters, totalCollected.

- Inventory
  - Route: GET /inventory, GET /inventory/history, POST /inventory/adjustment
  - Controller: InventoryController
  - Pages: Inventory/Index, Inventory/History
  - Props include product stock list, lowStockCount, recent movements, movement history, filters.

- Reports
  - Route prefix: /reports/*
  - Controller: ReportController
  - Pages: Reports/Index, Revenue, TaxSummary, CustomerAging, CustomerStatementSelect, CustomerStatement, Timbre
  - PDF endpoints map to Blade templates under resources/views/pdf/reports.

- Settings
  - Route path for company settings: /company-settings*
  - Controller: SettingsController
  - Inertia page: Settings/Edit
  - Props: settings, certificateStatus.

- Admin users/audit/profiles
  - Route prefix: /admin/* under role:admin middleware
  - Controllers: Admin/UserController, Admin/AuditLogController, ProfileController
  - Pages: Admin/Users/Index, Admin/Users/Form, Admin/AuditLog/Index, Admin/Profiles/Index.

## 7) Role-Based UI Map

### super_admin
- Effective behavior: full access to all page_permissions and all special_permissions in middleware share.
- Admin navigation items visible: Users, Audit Log, Profile Manager.
- Can validate invoices and access company settings/admin spaces.

### admin
- Effective behavior: same full access matrix as super_admin in middleware share.
- Admin visual mode appears when route starts with /admin.
- All CRUD and special actions enabled in frontend permission checks.

### accountant
- Source: database role permissions from CustomRole.
- Expected pages/actions depend on CustomRole record values at runtime.
- Invoice validation-request behavior exists in invoice pages and controllers.

### sales
- Source: database role permissions from CustomRole.
- Expected pages/actions depend on CustomRole record values at runtime.

### inventory_manager
- Source: database role permissions from CustomRole.
- Expected pages/actions depend on CustomRole record values at runtime.

### viewer
- Source: database role permissions from CustomRole.
- usePermissions marks viewer-like users via lack of modify permissions and non-admin role.
- Read-only behavior depends on page_permissions + special_permissions provided in auth.user.permissions.

### custom roles beyond the six system roles
- Supported in backend via CustomRole and exposed in Admin/Profiles.
- Exact custom role list and permission values: Not found in the uploaded files.

## 8) Redesign Constraints (Strict Rebuild Rules)

- Preserve route paths exactly as implemented in uploaded route files.
- Preserve sidebar IA labels exactly:
  - Dashboard, Invoices, OldInvoices, Customers, Products, Services, Payments, Inventory, Reports, My Account, Settings, Users, Audit Log, Profile Manager.
- Preserve permission gating model:
  - Frontend checks via auth.user.permissions and usePermissions.
  - Backend enforcement remains in policies/controllers/middleware.
- Preserve status-driven action visibility and transitions for invoice and old invoice flows.
- Preserve data contracts in resources/js/types/index.ts unless backend contracts are changed first.
- Preserve Inertia shared props schema from HandleInertiaRequests middleware.
- Preserve admin/user theme split behavior in authenticated layout.
- Preserve notification endpoints and interaction patterns in NotificationDropdown.
- Preserve XML import two-step behavior (parse preview then import commit).

## 9) File Output Map (Files To Recreate Or Redesign)

### Global shell and setup
- resources/js/app.tsx
- resources/css/app.css
- resources/js/Layouts/AuthenticatedLayout.tsx
- resources/js/Layouts/GuestLayout.tsx

### Permission and state contracts
- resources/js/hooks/usePermissions.ts
- resources/js/types/index.ts
- resources/js/utils/format.ts
- resources/js/utils/invoiceStatus.ts
- resources/js/utils/permissions.ts

### Reusable components
- resources/js/Components/ImportXmlModal.tsx
- resources/js/Components/NotificationDropdown.tsx
- resources/js/Components/Dashboard/KpiCard.tsx
- resources/js/Components/Invoices/ConfirmActionDialog.tsx
- resources/js/Components/Invoices/InvoiceLifecycleStepper.tsx
- resources/js/Components/Invoices/TtnLogInspector.tsx
- resources/js/Components/ui/Badge.tsx
- resources/js/Components/ui/Button.tsx
- resources/js/Components/ui/Input.tsx
- resources/js/Components/ui/Modal.tsx
- resources/js/Components/ui/Pagination.tsx
- resources/js/Components/ui/QRCodeDisplay.tsx
- resources/js/Components/ui/Select.tsx
- resources/js/Components/ui/Table.tsx
- resources/js/Components/ui/Toast.tsx

### Page modules
- resources/js/Pages/Dashboard.tsx
- resources/js/Pages/Welcome.tsx
- resources/js/Pages/Auth/ForgotPassword.tsx
- resources/js/Pages/Auth/Login.tsx
- resources/js/Pages/Auth/Register.tsx
- resources/js/Pages/Auth/ResetPassword.tsx
- resources/js/Pages/Auth/VerifyEmail.tsx
- resources/js/Pages/Errors/Error403.tsx
- resources/js/Pages/Errors/Error404.tsx
- resources/js/Pages/Errors/Error419.tsx
- resources/js/Pages/Errors/Error500.tsx
- resources/js/Pages/Errors/Error503.tsx
- resources/js/Pages/Customers/Create.tsx
- resources/js/Pages/Customers/Edit.tsx
- resources/js/Pages/Customers/Form.tsx
- resources/js/Pages/Customers/Index.tsx
- resources/js/Pages/Customers/Show.tsx
- resources/js/Pages/Products/Create.tsx
- resources/js/Pages/Products/Edit.tsx
- resources/js/Pages/Products/Form.tsx
- resources/js/Pages/Products/Index.tsx
- resources/js/Pages/Products/Show.tsx
- resources/js/Pages/Services/Create.tsx
- resources/js/Pages/Services/Edit.tsx
- resources/js/Pages/Services/Form.tsx
- resources/js/Pages/Services/Index.tsx
- resources/js/Pages/Services/Show.tsx
- resources/js/Pages/Invoices/Create.tsx
- resources/js/Pages/Invoices/Edit.tsx
- resources/js/Pages/Invoices/Form.tsx
- resources/js/Pages/Invoices/Index.tsx
- resources/js/Pages/Invoices/Show.tsx
- resources/js/Pages/OldInvoices/Create.tsx
- resources/js/Pages/OldInvoices/Edit.tsx
- resources/js/Pages/OldInvoices/Form.tsx
- resources/js/Pages/OldInvoices/Index.tsx
- resources/js/Pages/OldInvoices/Show.tsx
- resources/js/Pages/Payments/Index.tsx
- resources/js/Pages/Inventory/Index.tsx
- resources/js/Pages/Inventory/History.tsx
- resources/js/Pages/Reports/Index.tsx
- resources/js/Pages/Reports/Revenue.tsx
- resources/js/Pages/Reports/TaxSummary.tsx
- resources/js/Pages/Reports/CustomerAging.tsx
- resources/js/Pages/Reports/CustomerStatementSelect.tsx
- resources/js/Pages/Reports/CustomerStatement.tsx
- resources/js/Pages/Reports/Timbre.tsx
- resources/js/Pages/Settings/Edit.tsx
- resources/js/Pages/Settings/Profile.tsx
- resources/js/Pages/Admin/Users/Index.tsx
- resources/js/Pages/Admin/Users/Form.tsx
- resources/js/Pages/Admin/AuditLog/Index.tsx
- resources/js/Pages/Admin/Profiles/Index.tsx

## 10) Final Rebuild Notes (Verified, Missing, Risks)

### Verified implementation behaviors
- Inertia resolver is dynamic and file-path based under resources/js/Pages.
- Authenticated layout filters navigation using permissionKey mapped against auth.user.permissions.page_permissions.
- Admin item visibility uses both permission and adminOnly logic.
- Notification dropdown fetches, marks, clears through dedicated notification endpoints.
- Invoice import supports TEIF and UBL parsing in backend and imports into Invoice model with auto-created related entities where needed.
- Report pages have dedicated PDF endpoints and corresponding Blade templates.

### Missing or uncertain details that are not explicitly present
- Detailed internal UI/props for some reusable components (KpiCard, ConfirmActionDialog, InvoiceLifecycleStepper, TtnLogInspector, GuestLayout internals): Not found in the uploaded files.
- Runtime custom role definitions and final permission matrix values from database content: Not found in the uploaded files.

### Rebuild caution points
- Keep runtime permission model DB-driven for non-admin users.
- Do not replace route names/paths without coordinating controller and frontend form actions.
- Resolve and align the discovered settings path inconsistency before deployment.
- Keep invoice and oldinvoice workflows separate where current code keeps distinct models and transitions.
