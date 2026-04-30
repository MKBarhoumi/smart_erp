Project Overview
   - Project name: Smart ERP Lite
   - One-line description: A multi-tenant SaaS ERP for Tunisian SMEs specializing in automated compliance
     with Tunisia TradeNet (TTN) El Fatoora e-invoicing.
   - Business goal: Automate legal compliance (TEIF V2.0 XML, XAdES signing, SOAP submission) while
     providing a full ERP suite for business operations.
   - Target users: Small and Medium Enterprises (SMEs) in Tunisia.
   - User roles:
       - Super Admin: Full system access, company settings, certificate management.
       - Admin: Invoice signing, TTN submission, user management, audit logs.
       - Accountant: Invoice creation, validation requests, payment recording, reports.
       - Sales Representative: Invoice creation, customer management, product catalog.
       - Inventory Manager: Stock management, product catalog, stock movements.
       - Viewer: Read-only access to reports and dashboards.

Product Scope
   - What the application does: Manages CRM, inventory, and finances while ensuring every invoice meets
     Tunisian digital compliance standards.
   - Main workflows: Invoice Lifecycle (Draft -> Validated -> Signed -> Submitted -> Accepted), Customer
     Management with Tax ID validation, Stock Movement tracking.
   - Core user actions: Create Invoice, Request Validation, Sign XML (XAdES), Submit to TTN, Record
     Payment, Generate Revenue Reports.
   - In scope: Multi-tenancy, TTN El Fatoora compliance, Inventory tracking, Payment management, Audit
     logging.
   - Out of scope: AI forecasting (Future), Mobile App (Future), Pricing/Subscription module.

Information Architecture
   - Main pages: Dashboard, Invoices (List/Form/Sign/Submit), CRM, Catalog, Inventory, Payments, Reports,
     Admin Panel, Settings.
   - Navigation structure: 
       - Left Sidebar: Fixed and collapsible; sections grouped by "General", "Finance", "Inventory", and
         "System".
       - Top Navbar: User profile, notification bell, and current Tenant/Company name display.
       - Breadcrumbs: Required for all inner pages (e.g., Invoices > INV-2026-001 > Digital Signature).
   - Route names: Standard Laravel/Inertia resource naming (e.g., invoices.index, invoices.create,
     audit.logs).
   - Page relationships: Invoices depend on Customer and Product data; Payments and Stock Movements depend
     on Invoice status.

UI / Design System
   - Visual style: Modern SaaS / Enterprise Clean (Notion/Linear aesthetic). Heavy use of whitespace,
     subtle shadows, and clear typographic hierarchy.
   - Color palette:
       - Primary: Deep Blue (#1E3A5F) for headers and primary CTAs.
       - Accent: Indigo/Blue (#2E75B6) for highlights and charts.
       - Sidebar: Dark Slate (#0F172A) with white text/icons.
   - Status Badge Colors:
       - DRAFT: Gray
       - PENDING_VALIDATION: Amber
       - VALIDATED: Blue
       - SIGNED: Purple
       - SUBMITTED: Orange
       - ACCEPTED: Green
       - ARCHIVED: Dark Slate
   - Typography rules: Inter or Plus Jakarta Sans. 14px base body text. 13px for dense tables. 20-24px for
     page titles.
   - Spacing rules: 8px base unit. Consistent 16px or 24px container padding.
   - Layout rules: Sidebar-driven layout. Cards for grouping data.
   - Reusable UI patterns: Shadcn UI components.
   - Icons: Lucide React.
   - Dark mode: Not required (Light mode only with professional "Dark Sidebar").

Page-by-Page Requirements
  Dashboard (Full Redesign)
   - Purpose: Central Hub for business KPIs.
   - Main sections: Large KPI cards with sparkline charts, Revenue trend line chart, Invoice status pie
     chart, Recent activity list.
   - Primary actions: Quick-create invoice, Quick-add customer.

  Invoice List (Full Redesign)
   - Purpose: Monitor invoice pipeline.
   - Main sections: Tabbed view by status, advanced filtering (date range, customer, amount), action menu
     per row.
   - Primary actions: Export PDF, Sign (if validated), Submit (if signed).

  Invoice Creation Form (Polish)
   - Purpose: Rapid data entry.
   - Main sections: Customer lookup (type-ahead), line items table, auto-calculating totals footer (HT,
     TVA, Timbre, TTC).
   - Components: Dynamic row addition/removal.

  XML Signing & TTN Submission (Full Redesign)
   - Purpose: Guided compliance workflow.
   - Main sections: Step-by-step horizontal Stepper (1-7), XML Code Preview vs formatted data view, Signing
     status timeline, Certificate selector.

  CRM / Customer Detail
   - Purpose: 360 view of client.
   - Main sections: Profile header (Tax ID validation status), Outstanding balance widget, Transaction
     history table.

  Reports (Full Redesign)
   - Purpose: Financial analysis.
   - Main sections: Date range picker, dynamic data table with "Export to Excel/PDF" persistent buttons,
     Summary total cards at the top.

  Public Verification Portal (Full Redesign)
   - Purpose: External trust.
   - Main sections: Clean search box for UUID, Large "Valid/Invalid" hero state, minimal invoice details
     display.

Components Library
   - Header: Simple, showing Tenant name and Breadcrumbs.
   - Sidebar: Grouped navigation with collapsible categories.
   - Tables: Shadcn DataTable with sorting, filtering, and row selection.
   - Forms: Label-top layout, clear validation error styling.
   - Alerts/Toasts: Success (Green), Error (Red), and Info (Blue) for background task updates.

Content Rules
   - Tone of text: Academic, professional, and precise.
   - Labels: Must use specific Legal identifiers (e.g., "Matricule Fiscal" instead of "Tax ID").
   - Currency: Always TND with 3 decimal places (Millimes).
   - Date Format: DD/MM/YYYY.

Data / Backend Context
   - Framework: Laravel 11 + Inertia.js (React). No separate REST API; data passed via Inertia Props.
   - Monetary Logic: Calculations performed in Millimes (0.000).
   - RBAC: Prop auth.user.role determines visibility of "Sign" and "Submit" buttons.
   - Status Machine: DRAFT -> PENDING_VALIDATION -> VALIDATED -> SIGNED -> SUBMITTED -> ACCEPTED.
   - Key Fields: Matricule Fiscal (Tunisian format), DocumentIdentifier (INV-YYYY-XXX), TotalTTC.

Responsive Behavior
   - Desktop (1280px+): Full multi-column dashboard and wide tables.
   - Tablet (768px - 1024px): Sidebar collapses to icons; forms stack into a single column.
   - Mobile (<768px): Navigation moves to hamburger menu; tables transform into expandable card lists.

States and Edge Cases
   - Loading: Skeleton screens for Dashboard cards and Report tables.
   - Empty: "No invoices found" illustrations with "Create" button.
   - Error: Modal with "TTN Technical Error Code" and plain-English explanation.
   - Processing: Polling state for TTN submission with animated progress bar.

Constraints
   - Legal Compliance: The invoice status progression is fixed; shortcuts are prohibited.
   - Cryptographic Security: The "Sign Invoice" action must trigger a Confirmation Dialog.
   - Immutability: Audit logs and accepted invoices can never be edited or deleted.
   - Data Integrity: Matricule Fiscal must be validated before allowing "Validated" status.

Acceptance Criteria
   - UI Consistency: All cards, buttons, and tables must match the Shadcn / Notion aesthetic.
   - Status Logic: Action buttons must only appear if the status enum allows it for that role.
   - Financial Precision: All totals must match the backend's 3-decimal calculation exactly.
   - Responsive Navigation: Sidebar must be fully usable on tablets.

Unknowns / TBD
   - Production TTN WSDL endpoints (pending partner onboarding).
   - CA-specific certificate format variations (PFX vs PEM).
   - Multi-tenant operator/billing dashboard UI.
