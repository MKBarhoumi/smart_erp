# NovERP — Feature Development Task Specification

> **Project:** NovERP (Tunisia-focused ERP system)
> **Stack Context:** React frontend, dark navy sidebar nav, purple/blue primary color scheme
> **Existing Pages:** Dashboard, Invoices, OldInvoices, Customers, Products, Payments, Inventory, Reports, My Account, Settings, Admin (Users, Audit Log)

---

## TASK 1 — New "Services" Page

### Overview
Add a new top-level navigation item called **"Services"** in the sidebar, between **Products** and **Payments**. This page manages the company's service catalog (distinct from physical products in inventory).

### UI Requirements

#### Sidebar
- Add a "Services" menu item with an appropriate icon (e.g., wrench/toolbox/briefcase icon) in the left sidebar
- Position it between **Products** and **Payments**
- Follows the same active/hover style as existing nav items (purple highlight, white text)

#### Services List Page (`/services`)
- Page title: **"Services"** with subtitle: *"Manage your service catalog"*
- Top-right button: **`+ New Service`** (same purple button style as `+ New Invoice`)
- Search bar: filter by name, code, or category
- Filter dropdowns: **Category**, **Status** (Active / Inactive)
- Data table with columns:
  | Column | Description |
  |--------|-------------|
  | SERVICE CODE | Unique identifier (e.g., SRV-001) |
  | NAME | Service name |
  | CATEGORY | Service category |
  | UNIT | Billing unit (hour, day, unit, forfait) |
  | UNIT PRICE (TND) | Price excluding tax |
  | TAX RATE | VAT % (0%, 7%, 13%, 19%) |
  | STATUS | Active / Inactive badge |
  | ACTIONS | View (eye icon), Edit (pencil icon), Delete (trash icon) |

#### New Service Form (modal or dedicated page `/services/new`)
Fields:
- **Service Code** *(required, auto-generated or manual)*
- **Service Name** *(required)*
- **Description** *(textarea, optional)*
- **Category** *(dropdown — create/select: IT, Consulting, Maintenance, Training, Other)*
- **Billing Unit** *(dropdown: Hour, Day, Unit, Forfait)*
- **Unit Price (TND)** *(required, numeric)*
- **Tax Rate** *(dropdown: 0%, 7%, 13%, 19%)*
- **Status** *(toggle: Active / Inactive, default Active)*

#### Edit Service
- Pre-fills the form with existing data
- Same validation rules

#### Service Detail View
- Read-only view of all service fields
- Shows usage: list of invoices where this service was billed

### Data Model (suggested)
```json
{
  "id": "uuid",
  "code": "SRV-001",
  "name": "IT Support",
  "description": "On-site IT support per hour",
  "category": "IT",
  "unit": "Hour",
  "unitPrice": 150.000,
  "taxRate": 19,
  "status": "active",
  "createdAt": "2026-03-12T00:00:00Z",
  "updatedAt": "2026-03-12T00:00:00Z"
}
```

---

## TASK 2 — Import Invoice XML Button (Invoices Page)

### Overview
Add an **"Import XML"** button on the existing **Invoices page** (`/invoices`) that allows users to upload a TEIF-compliant XML invoice file and parse it into the system.

### UI Requirements

#### Button Placement
- Add **`↑ Import XML`** button next to the existing **`+ New Invoice`** button (top-right of the Invoices page)
- Style: outlined/secondary button variant (purple border, white text) to visually differentiate from the primary `+ New Invoice` button

#### Import Flow (Step-by-step modal)

**Step 1 — File Upload**
- Drag-and-drop zone OR click-to-browse
- Accepts: `.xml` files only
- Max file size: 5MB
- Shows filename once selected

**Step 2 — Validation & Preview**
- Parse the XML and display a preview table with extracted fields:
  | Field | Extracted Value |
  |-------|----------------|
  | Invoice ID | TEIF/2026/000027 |
  | Invoice Type | CO380 / I-11 |
  | Sender | NovERP Solutions SARL |
  | Receiver | [Company Name] |
  | Date | 2026-03-12 |
  | Total HT | xxx.xxx TND |
  | Total TVA | xxx.xxx TND |
  | Total TTC | xxx.xxx TND |
- Show validation errors in red if required fields are missing or malformed
- Only allow proceeding to Step 3 if validation passes (green checkmark)

**Step 3 — Confirm & Import**
- Summary of what will be imported
- **`Import Invoice`** confirm button (purple)
- **`Cancel`** button
- On success: show success toast, close modal, refresh invoice list with new entry in **Draft** status

### TEIF XML Structure (expected schema to parse)

```xml
<Invoice>
  <ID>TEIF/2026/000027</ID>
  <IssueDate>2026-03-12</IssueDate>
  <InvoiceTypeCode>CO380</InvoiceTypeCode>
  <AccountingSupplierParty>
    <Party>
      <PartyName><Name>NovERP Solutions SARL</Name></PartyName>
      <PartyTaxScheme><CompanyID>1234567A/P/M000</CompanyID></PartyTaxScheme>
    </Party>
  </AccountingSupplierParty>
  <AccountingCustomerParty>
    <Party>
      <PartyName><Name>Customer Name</Name></PartyName>
      <PartyTaxScheme><CompanyID>9876543B/P/M000</CompanyID></PartyTaxScheme>
    </Party>
  </AccountingCustomerParty>
  <LegalMonetaryTotal>
    <LineExtensionAmount currencyID="TND">1000.000</LineExtensionAmount>
    <TaxExclusiveAmount currencyID="TND">1000.000</TaxExclusiveAmount>
    <TaxInclusiveAmount currencyID="TND">1190.000</TaxInclusiveAmount>
    <PayableAmount currencyID="TND">1190.000</PayableAmount>
  </LegalMonetaryTotal>
  <TaxTotal>
    <TaxAmount currencyID="TND">190.000</TaxAmount>
  </TaxTotal>
  <InvoiceLine>
    <ID>1</ID>
    <InvoicedQuantity unitCode="C62">2</InvoicedQuantity>
    <LineExtensionAmount currencyID="TND">500.000</LineExtensionAmount>
    <Item><Description>Service Description</Description></Item>
    <Price><PriceAmount currencyID="TND">250.000</PriceAmount></Price>
  </InvoiceLine>
</Invoice>
```

### Validation Rules
- `<ID>` must be present and unique (not already in the system)
- `<IssueDate>` must be a valid date
- `<InvoiceTypeCode>` must be one of: `CO380`, `I-11`, `T-09`, `FA`
- `<PayableAmount>` must be a positive number
- At least one `<InvoiceLine>` must exist

---

## TASK 3 — Role Descriptions & Permissions

### Context
When creating a new user in the Admin panel, the **Role** dropdown currently shows: Admin, Accountant, Sales, Inventory_manager, Viewer.

Below are the detailed permission definitions for the three roles needing specification:

---

### Role: Sales

**Description:**
> The Sales role is designed for sales representatives and commercial team members. They manage customer relationships, create and track invoices, and monitor sales activity. They have no access to financial reports, inventory management, or administrative settings.

**Permissions Matrix:**

| Page / Feature | Access | View | Create | Edit | Delete |
|----------------|--------|------|--------|------|--------|
| Dashboard | ✅ | ✅ | ❌ | ❌ | ❌ |
| Invoices | ✅ | ✅ | ✅ | ✅ (own drafts only) | ❌ |
| OldInvoices | ✅ | ✅ | ❌ | ❌ | ❌ |
| Customers | ✅ | ✅ | ✅ | ✅ | ❌ |
| Products | ✅ | ✅ | ❌ | ❌ | ❌ |
| Services | ✅ | ✅ | ❌ | ❌ | ❌ |
| Payments | ✅ | ✅ | ❌ | ❌ | ❌ |
| Inventory | ❌ | ❌ | ❌ | ❌ | ❌ |
| Reports | ✅ (sales reports only) | ✅ | ❌ | ❌ | ❌ |
| My Account | ✅ | ✅ | ❌ | ✅ (own profile) | ❌ |
| Settings | ❌ | ❌ | ❌ | ❌ | ❌ |
| Admin Panel | ❌ | ❌ | ❌ | ❌ | ❌ |

**Special Rules:**
- Can only edit invoices in **Draft** status
- Cannot validate/submit invoices to TEIF (accountant action)
- Can import XML invoices
- Cannot see financial totals on the Dashboard (revenue/profit KPIs hidden)

---

### Role: Inventory_manager

**Description:**
> The Inventory Manager role is for warehouse and stock management personnel. They control product stock levels, manage inventory movements, and access product data. They have no access to invoicing, financial data, or customer information beyond what is necessary for fulfillment.

**Permissions Matrix:**

| Page / Feature | Access | View | Create | Edit | Delete |
|----------------|--------|------|--------|------|--------|
| Dashboard | ✅ (inventory widgets only) | ✅ | ❌ | ❌ | ❌ |
| Invoices | ❌ | ❌ | ❌ | ❌ | ❌ |
| OldInvoices | ❌ | ❌ | ❌ | ❌ | ❌ |
| Customers | ❌ | ❌ | ❌ | ❌ | ❌ |
| Products | ✅ | ✅ | ✅ | ✅ | ✅ |
| Services | ❌ | ❌ | ❌ | ❌ | ❌ |
| Payments | ❌ | ❌ | ❌ | ❌ | ❌ |
| Inventory | ✅ | ✅ | ✅ | ✅ | ✅ |
| Reports | ✅ (inventory reports only) | ✅ | ❌ | ❌ | ❌ |
| My Account | ✅ | ✅ | ❌ | ✅ (own profile) | ❌ |
| Settings | ❌ | ❌ | ❌ | ❌ | ❌ |
| Admin Panel | ❌ | ❌ | ❌ | ❌ | ❌ |

**Special Rules:**
- Can adjust stock quantities (increase/decrease with reason/note)
- Can create purchase orders or stock transfer records
- Cannot view invoice amounts or customer financial data
- Dashboard shows only: Low Stock Alerts, Stock Value, Pending Stock Movements

---

### Role: Viewer

**Description:**
> The Viewer role is a read-only role for stakeholders, auditors, or external consultants who need visibility into the system without the ability to modify any data. They can see all pages they are granted access to but cannot create, edit, or delete anything.

**Permissions Matrix:**

| Page / Feature | Access | View | Create | Edit | Delete |
|----------------|--------|------|--------|------|--------|
| Dashboard | ✅ | ✅ | ❌ | ❌ | ❌ |
| Invoices | ✅ | ✅ | ❌ | ❌ | ❌ |
| OldInvoices | ✅ | ✅ | ❌ | ❌ | ❌ |
| Customers | ✅ | ✅ | ❌ | ❌ | ❌ |
| Products | ✅ | ✅ | ❌ | ❌ | ❌ |
| Services | ✅ | ✅ | ❌ | ❌ | ❌ |
| Payments | ✅ | ✅ | ❌ | ❌ | ❌ |
| Inventory | ✅ | ✅ | ❌ | ❌ | ❌ |
| Reports | ✅ | ✅ | ❌ | ❌ | ❌ |
| My Account | ✅ | ✅ | ❌ | ✅ (own profile only) | ❌ |
| Settings | ❌ | ❌ | ❌ | ❌ | ❌ |
| Admin Panel | ❌ | ❌ | ❌ | ❌ | ❌ |

**Special Rules:**
- All action buttons (New, Edit, Delete, Import) are hidden from the UI
- No access to Settings or Admin panel
- Can export/download data if export buttons are present (read-only export)
- Cannot perform any state changes (e.g., cannot validate invoices)

---

## TASK 4 — New Admin Page: "Profile Manager"

### Overview
Add a new page **"Profile Manager"** inside the Admin panel (alongside Users and Audit Log). This page allows admins to create custom access profiles/roles with granular page-level permissions, and assign those profiles to users.

### Navigation
- In the Admin sidebar section, add **"Profile Manager"** with an `ADMIN` badge (same as Users and Audit Log)
- Icon: shield or person-with-settings icon
- Position: between **Users** and **Audit Log**

---

### Profile Manager — List Page (`/admin/profiles`)

- Page title: **"Profile Manager"**
- Subtitle: *"Create and manage access profiles for users"*
- Top-right button: **`+ New Profile`** (purple)
- Table columns:

| Column | Description |
|--------|-------------|
| PROFILE NAME | Custom name (e.g., "Sales Manager", "Junior Accountant") |
| DESCRIPTION | Short description of this profile |
| PAGES ACCESSIBLE | Count or pill badges of pages this profile can access |
| PERMISSIONS LEVEL | Summary (e.g., "View only", "View + Edit", "Full") |
| ASSIGNED USERS | Number of users currently using this profile |
| ACTIONS | Edit (pencil), Duplicate, Delete |

---

### New Profile Form (`/admin/profiles/new`)

#### Section 1 — Profile Info
- **Profile Name** *(required)*
- **Description** *(textarea, optional)*
- **Base Role** *(optional — clone permissions from an existing role: Admin, Accountant, Sales, Inventory_manager, Viewer)*

#### Section 2 — Page Access Matrix

A visual permissions grid with toggle switches:

| Page | No Access | View Only | View + Edit | Full Access |
|------|-----------|-----------|-------------|-------------|
| Dashboard | ⭕ | ⭕ | ⭕ | ⭕ |
| Invoices | ⭕ | ⭕ | ⭕ | ⭕ |
| OldInvoices | ⭕ | ⭕ | — | — |
| Customers | ⭕ | ⭕ | ⭕ | ⭕ |
| Products | ⭕ | ⭕ | ⭕ | ⭕ |
| Services | ⭕ | ⭕ | ⭕ | ⭕ |
| Payments | ⭕ | ⭕ | ⭕ | ⭕ |
| Inventory | ⭕ | ⭕ | ⭕ | ⭕ |
| Reports | ⭕ | ⭕ | — | — |
| Settings | ⭕ | ⭕ | ⭕ | ⭕ |
| Admin — Users | ⭕ | ⭕ | ⭕ | ⭕ |
| Admin — Audit Log | ⭕ | ⭕ | — | — |
| Admin — Profile Manager | ⭕ | ⭕ | ⭕ | ⭕ |

**Permission Level Definitions:**
- **No Access:** Page is hidden from sidebar; direct URL access returns 403
- **View Only:** Page is visible; all create/edit/delete buttons hidden; read-only
- **View + Edit:** Can view and edit existing records; cannot create or delete
- **Full Access:** Can view, create, edit, and delete all records on this page

#### Section 3 — Special Permissions (checkboxes)
- [ ] Can validate/submit TEIF invoices
- [ ] Can import XML invoices
- [ ] Can export data (CSV/PDF)
- [ ] Can view financial KPIs on Dashboard
- [ ] Can manage own profile/password

#### Action Buttons
- **`Create Profile`** (purple, right-aligned)
- **`Cancel`** (text button)

---

### Profile Detail / Edit Page (`/admin/profiles/:id`)
- Same form as New Profile, pre-filled
- Shows **"Assigned Users"** section at the bottom: list of users using this profile with a button to reassign

### Assigning Profile to a User
- In the **New User** and **Edit User** forms, the **Role** dropdown should include both:
  1. Default system roles: Admin, Accountant, Sales, Inventory_manager, Viewer
  2. Custom profiles created via Profile Manager (shown with a "Custom" badge)

### Profile Data Model (suggested)
```json
{
  "id": "uuid",
  "name": "Junior Accountant",
  "description": "Read-only access to invoices and reports",
  "baseRole": "viewer",
  "pagePermissions": {
    "dashboard": "view",
    "invoices": "view",
    "oldInvoices": "view",
    "customers": "none",
    "products": "none",
    "services": "none",
    "payments": "view",
    "inventory": "none",
    "reports": "view",
    "settings": "none",
    "admin": "none"
  },
  "specialPermissions": {
    "canValidateInvoices": false,
    "canImportXML": false,
    "canExportData": true,
    "canViewFinancialKPIs": false,
    "canManageOwnProfile": true
  },
  "assignedUsers": ["user-uuid-1", "user-uuid-2"],
  "createdAt": "2026-03-12T00:00:00Z",
  "updatedAt": "2026-03-12T00:00:00Z"
}
```

---

## General Design System Reference

- **Primary color:** `#6B5CE7` (purple)
- **Sidebar background:** `#0F1123` (dark navy)
- **Active nav item:** Solid purple pill, white text
- **Admin badge items:** Orange background `#E8650A`
- **Card/panel background:** White (`#FFFFFF`) on light page background
- **Status badges:**
  - Validated → teal/green outline pill
  - Draft → gray outline pill
- **Typography:** Clean sans-serif (Inter or similar)
- **Button style:** Rounded corners (`border-radius: 8px`), filled purple for primary, outlined for secondary
- **Table rows:** Hover highlight, alternating white/light-gray is optional
- **Invoice amounts:** Right-aligned, formatted with 3 decimal places + "TND"

---

## Summary of All Tasks

| # | Task | Scope |
|---|------|-------|
| 1 | New **Services** page with CRUD | New page + sidebar nav item |
| 2 | **Import XML** button on Invoices page | Feature addition to existing page |
| 3 | Role definitions: **Sales, Inventory_manager, Viewer** | Permission configuration reference |
| 4 | New Admin page: **Profile Manager** | New admin section with permission builder UI |
