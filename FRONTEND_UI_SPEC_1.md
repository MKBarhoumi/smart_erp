# Frontend UI Specification - SaaS NovERP

This document provides a comprehensive specification of the frontend for the SaaS NovERP project. It is based strictly on the analyzed codebase and is designed to allow for a full rebuild or redesign of the existing UI.

## 1. PROJECT FRONTEND OVERVIEW
- **App Name:** NovERP
- **Frontend Stack:** React 19, Inertia.js, TypeScript, Tailwind CSS 4, Lucide React (icons).
- **UI Style Description:** Modern, clean, and highly professional "SaaS-style" interface. Uses a sidebar-driven navigation, soft shadows, rounded corners (2xl), and strong role-based color differentiation.
- **Main Screens:** Dashboard, Invoices, Old Invoices, Customers, Products, Services, Payments, Inventory, Reports, Company Settings, Profile Manager, User Management.
- **Supported Roles/Actors:** 
    - `super_admin` / `admin`: Full system access, orange/red theme in admin sections.
    - `accountant`: Financial focus, can validate invoices.
    - `sales`: Customer and invoice creation focus.
    - `inventory_manager`: Product and stock management focus.
    - `viewer`: Read-only access to specific modules.

---

## 2. FULL FRONTEND FILE TREE (Partial - Key Frontend Files)

```text
resources/
├── js/
│   ├── app.tsx                 # Inertia entry point
│   ├── Layouts/
│   │   ├── AuthenticatedLayout.tsx # Main dashboard layout (Layout)
│   │   └── GuestLayout.tsx         # Auth pages layout (Layout)
│   ├── Pages/
│   │   ├── Dashboard.tsx       # Main metrics (Frontend page)
│   │   ├── Invoices/           # TEIF Invoicing (Frontend pages)
│   │   ├── OldInvoices/        # Standard Invoicing (Frontend pages)
│   │   ├── Customers/          # CRM (Frontend pages)
│   │   ├── Products/           # Catalog (Frontend pages)
│   │   ├── Admin/              # Role/User management (Frontend pages)
│   │   └── Settings/           # Configuration (Frontend page)
│   ├── Components/
│   │   ├── ui/                 # Reusable atomic components (Frontend component)
│   │   ├── NotificationDropdown.tsx # Header notification UI (Frontend component)
│   │   └── ...
│   ├── hooks/
│   │   └── usePermissions.ts   # UI Logic for RBAC (UI helper)
│   ├── utils/
│   │   └── format.ts           # TND Currency formatting (UI helper)
│   └── types/
│       └── index.ts            # TypeScript definitions (Other)
├── css/
│   └── app.css                 # Tailwind 4 global styles (Styling)
└── views/
    └── app.blade.php           # Root HTML template (Entry file)
```

---

## 3. FILE-BY-FILE FRONTEND INVENTORY (Key Files)

| File Path | Type | Purpose | Role(s) |
| :--- | :--- | :--- | :--- |
| `resources/js/Layouts/AuthenticatedLayout.tsx` | Layout | Main navigation wrapper, sidebar, header, and role-based theme logic. | All |
| `resources/js/Pages/Dashboard.tsx` | Page | Displays financial KPIs, recent activity, and stock alerts. | All (View-limited) |
| `resources/js/Pages/OldInvoices/Index.tsx` | Page | Searchable list of invoices with status badges. | All |
| `resources/js/Pages/Admin/Profiles/Index.tsx` | Page | Advanced matrix for managing granular permissions per role. | Admin Only |
| `resources/js/hooks/usePermissions.ts` | Helper | Hook to check `page_permissions` and `special_permissions`. | All |
| `resources/js/utils/format.ts` | Helper | Formats numbers to Tunisian Dinar (3 decimals). | All |

---

## 4. PAGE-BY-PAGE UI SPECIFICATION

### Dashboard (`resources/js/Pages/Dashboard.tsx`)
- **Route:** `/dashboard`
- **Layout:** `AuthenticatedLayout`
- **Visible Sections:**
    - **Header:** Title "Dashboard", welcome message, "New Invoice" button.
    - **Stats Grid:** 4 columns (Revenue, Outstanding Balance, Monthly Invoices, Pending).
    - **Recent Invoices:** Card with list of invoices (Number, Customer, Amount, Status Badge).
    - **Top Customers:** List of customers by revenue with ranking badges (Gold/Silver/Bronze).
    - **Low Stock Alerts:** Amber-colored alert box with product tags.
    - **Revenue Overview:** Modern bar chart showing 12-month performance.
- **Conditional UI:**
    - Financial stats only visible if `special_permissions.canViewFinancialKPIs` is true.
- **Actions:** Click invoice to view, click customer to view, click product to view.

### OldInvoices Index (`resources/js/Pages/OldInvoices/Index.tsx`)
- **Route:** `/oldinvoices`
- **Visible Sections:**
    - **Header:** "OldInvoices" title, "New OldInvoice" button (role-limited).
    - **Filters Bar:** Search input, Status select, Date Range inputs, Filter button.
    - **Data Table:** Columns: #, Customer, Date, Total TTC, Status, Actions.
- **Data Source:** `oldinvoices` prop (paginated).
- **Role Differences:** `canModify` check determines if "New OldInvoice" button appears.

### Profile Manager (`resources/js/Pages/Admin/Profiles/Index.tsx`)
- **Route:** `/admin/profiles`
- **UI Elements:**
    - **Role Cards:** Grid of roles with color-coded dots, user counts, and edit/delete actions.
    - **Permissions Matrix:** Large table mapping every Page vs (Access, View, Create, Edit, Delete). Uses Emerald Check/Gray X icons.
    - **Special Permissions:** Separate table for non-page actions (XML Import, Financials).
    - **Modals:** Slide-over or centered modals for Creating/Editing roles with granular checkboxes.

---

## 5. COMPONENT CATALOG

### `AuthenticatedLayout`
- **Props:** `children`
- **Behavior:**
    - Checks `isAdmin` and `isAdminPage`.
    - **Theme Switcher:** 
        - If Admin Section: `admin-950` (Orange/Dark) gradient.
        - If User Section: `slate-950` (Blue/Dark) gradient.
    - **Sidebar:** Dynamic filtering of `navigation` array based on `canAccessPage`.
    - **Header:** Global search bar, Notification bell, User profile menu.

### `OldInvoiceStatusBadge`
- **Colors:**
    - `draft`: Gray
    - `pending_validation`: Orange
    - `validated`: Blue
    - `signed`: Indigo
    - `submitted`: Amber
    - `accepted`: Emerald
    - `rejected`: Red

---

## 6. ROLE-BASED UI MAP

### Admin UI (`admin`, `super_admin`)
- **Theme:** Strong Orange/Red accents in `/admin` routes. "Admin Mode" badge in sidebar.
- **Navigation:** All items visible including "Users", "Audit Log", "Profile Manager", "Settings".
- **Actions:** Can bypass all client-side restriction checks.

### Accountant UI (`accountant`)
- **Navigation:** "Dashboard", "Invoices", "Reports", "Payments".
- **Special UI:** Can see validation action buttons on Invoices. Can see financial charts.
- **Theme:** Standard Blue/Dark theme.

### Sales UI (`sales`)
- **Navigation:** "Customers", "Invoices", "Products".
- **Action Visibility:** Can create/edit invoices but cannot "Validate" or "Sign" if permissions restricted.
- **Financials:** Restricted view of total company revenue charts unless specifically granted.

### Viewer UI (`viewer`)
- **Navigation:** Dashboard (Read-only), Invoices (View only).
- **Visibility:** No "New", "Edit", or "Delete" buttons visible. Data tables have no edit actions.

---

## 7. FRONTEND REDESIGN CONSTRAINTS
- **Routes:** Preserve existing routes defined in `AuthenticatedLayout`'s `navigation` object.
- **Labels:** Use "NovERP" branding and existing field labels (e.g., "Total incl. tax" instead of "Gross Total").
- **Precision:** All currency displays MUST use `formatTND` (3 decimal places).
- **Architecture:** Maintain the `resources/js/Pages` structure.
- **Interactions:** Maintain Inertia's `preserveState` and `replace` behavior in filters.

---

## 8. FILES TO RECREATE OR REDESIGN

### Pages
- `resources/js/Pages/Dashboard.tsx`
- `resources/js/Pages/OldInvoices/Index.tsx`
- `resources/js/Pages/OldInvoices/Show.tsx`
- `resources/js/Pages/Admin/Profiles/Index.tsx`
- `resources/js/Settings/Edit.tsx`

### Components
- `resources/js/Layouts/AuthenticatedLayout.tsx`
- `resources/js/Components/NotificationDropdown.tsx`
- `resources/js/Components/ui/Badge.tsx`
- `resources/js/Components/ui/Button.tsx`
- `resources/js/Components/ui/Input.tsx`

---

## 9. FINAL FRONTEND REBUILD NOTES
- **State Management:** Fully handled by Inertia props and local React hooks. No global state (Redux/Zustand) is present in the analyzed files.
- **Form Logic:** Uses Inertia's `useForm` hook exclusively for validation and submission.
- **Responsive Design:** Sidebar is hidden on mobile with a hamburger menu toggle. Stats grid collapses from 4 columns to 1 on mobile.
- **Missing Info:** Authentication page UI (Login/Register) was not fully analyzed in this session but follows the `GuestLayout.tsx`.
