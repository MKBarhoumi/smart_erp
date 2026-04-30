# SaaS NovERP Lite - Project Roadmap & Documentation

## 📌 1. PROJECT OVERVIEW
**SaaS NovERP Lite** is a comprehensive, multi-tenant Enterprise Resource Planning (ERP) platform specifically designed for the Tunisian B2B market. It enables Small and Medium Enterprises (SMEs) to manage their business operations while strictly adhering to Tunisian fiscal regulations.

- **Main Purpose:** Automate invoicing, inventory, and financial reporting while ensuring compliance with the **Tunisia TradeNet (TTN) El Fatoora (TEIF v1.8.8)** standard.
- **Key Goals:**
    - Regulatory-compliant electronic invoicing with XAdES-BES digital signatures.
    - Automated TTN submission and CEV (Cachet Électronique Visible) QR code integration.
    - Multi-tenant data isolation (Database-per-Tenant).
    - Robust inventory and payment tracking.
- **Tech Stack:**
    - **Backend:** Laravel 11 (PHP 8.3)
    - **Frontend:** React 19 + Inertia.js + Tailwind CSS 4
    - **Database:** MySQL/PostgreSQL (NUMERIC(20,3) for TND precision)
    - **Tools:** Vite, PHPUnit/Pest, ESLint, Prettier

---

## 📌 2. FULL PROJECT STRUCTURE TREE

```text
smart_erp/
├── app/
│   ├── Enums/               # Domain-specific enums (InvoiceStatus, TaxCodes, etc.)
│   ├── Exceptions/          # Custom domain exceptions (SignatureException, TeifValidationException)
│   ├── Http/
│   │   ├── Controllers/     # Main logic controllers (Invoices, Customers, Products, etc.)
│   │   ├── Middleware/      # Auth, Tenant, and Role-based access control
│   │   └── Requests/        # Form validation logic
│   ├── Models/              # Eloquent models (Invoice, Customer, Product, StockMovement, etc.)
│   ├── Services/            # Core business logic (XML Building, Digital Signatures, TTN Client)
│   └── Providers/           # App and Service providers
├── config/                  # Framework and package configurations
├── database/
│   ├── migrations/          # Schema definitions
│   ├── factories/           # Model factories for testing
│   └── seeders/             # Database population scripts
├── docs/                    # Technical reference and API documentation
├── public/                  # Static assets and entry point
├── resources/
│   ├── js/
│   │   ├── Components/      # Reusable UI components (shadcn/ui style)
│   │   ├── Layouts/         # Dashboard and Guest layouts
│   │   ├── Pages/           # Inertia React page components
│   │   ├── types/           # TypeScript definitions
│   │   └── utils/           # Helper functions
│   ├── css/                 # Global styles and Tailwind imports
│   └── views/               # Blade templates (root entry for Inertia)
├── routes/
│   ├── web.php              # Main web routes
│   ├── auth.php             # Authentication routes
│   ├── invoices.php         # TEIF Invoicing routes
│   ├── oldInvoices.php      # Legacy/Standard Invoicing routes
│   └── settings.php         # Company configuration routes
├── tests/                   # Feature and Unit tests (Pest)
└── xsd/                     # XML Schema Definitions for TEIF validation
```

---

## 📌 3. FILE CLASSIFICATION

### Frontend (UI & Logic)
- **Key Folders:** `resources/js/Pages`, `resources/js/Components`
- **Role:** Handles the SPA interface using React. Pages like `OldInvoices/Show.tsx` manage complex UI states for invoice creation and actions.
- **Styling:** Tailwind CSS 4 with Lucide icons.

### Backend (Business Logic)
- **Controllers:** `app/Http/Controllers` - Bridges the UI and Services.
- **Services:** `app/Services` - The "brain" of the app.
    - `TeifXmlBuilder`: Generates compliant XML.
    - `XadesSignatureService`: Handles cryptographic signing.
    - `TTNApiClient`: Manages SOAP communication with TTN.
    - `OldInvoiceCalculationService`: Ensures TND 3-decimal precision.

### Database (Data Layer)
- **Models:** `app/Models` - Defines data structures.
- **Migrations:** `database/migrations` - Manages database schema updates.
- **Seeders:** `database/seeders` - Populates development/demo data.

### Configuration
- `.env`: Environment variables.
- `vite.config.ts`: Frontend build configuration.
- `tailwind.config.js`: Styling configuration.

---

## 📌 4. ARCHITECTURE EXPLANATION
The system follows a **Monolithic SPA** architecture using the **Inertia.js** bridge.

1.  **Frontend Flow:** React components render the UI. Actions (like "Submit to TTN") trigger Inertia visits or POST requests.
2.  **Backend Flow:** Laravel receives requests, validates data via FormRequests, and executes business logic through dedicated Service classes.
3.  **Data Flow:**
    - **Drafting:** Data stored in `oldinvoices` / `oldinvoice_lines`.
    - **Validation:** Logic checks for compliance.
    - **Signing:** `XadesSignatureService` creates a signed XML block.
    - **Submission:** `TTNApiClient` sends the XML to TTN via SOAP; on success, CEV QR codes and TTN references are stored.

---

## 📌 5. KEY MODULES BREAKDOWN

### 1. Invoicing Engine (TEIF & Standard)
- **Purpose:** core functionality to create and transmit invoices.
- **Main Files:** `OldInvoiceController.php`, `TeifXmlBuilder.php`, `Invoice.php`, `OldInvoice.tsx`.
- **Interaction:** Consumes Customer and Product data; triggers StockMovements.

### 2. Regulatory Compliance (TTN Integration)
- **Purpose:** ensure legal compliance in Tunisia.
- **Main Files:** `XadesSignatureService.php`, `TTNApiClient.php`, `TeifXsdValidator.php`.
- **Interaction:** Encapsulates the complexity of digital signatures and SOAP APIs.

### 3. Inventory & Catalog
- **Purpose:** Track stock levels and product pricing.
- **Main Files:** `Product.php`, `StockMovement.php`, `InventoryController.php`.
- **Interaction:** Auto-decrements stock upon invoice validation.

---

## 📌 6. ROUTING & API STRUCTURE
- `/dashboard`: Main metrics and recent activity overview.
- `/oldinvoices`: CRUD for standard Tunisian invoices.
- `/invoices`: Management of TEIF-compliant electronic invoices.
- `/customers`: CRM module for buyer/supplier data.
- `/products`: Catalog management.
- `/company-settings`: Management of fiscal identity (Matricule Fiscal) and digital certificates.
- `/reports`: Exportable financial and fiscal reports (PDF/Excel).

---

## 📌 7. STATE MANAGEMENT / DATA HANDLING
- **Server State:** Managed by Laravel and shared with the frontend via **Inertia Props**.
- **Client State:** React `useState` and `useForm` for local UI interactions (e.g., dynamic line item additions).
- **Persistence:** High-precision data handling using `bcmath` in PHP and `NUMERIC(20,3)` in the database to prevent rounding errors in Tunisian Dinars.

---

## 📌 8. DEPENDENCIES
- **Inertia.js:** Eliminates the need for a separate Client-side API (SPA experience with backend routing).
- **Fortify:** Robust authentication and profile management.
- **Barryvdh/Laravel-DomPDF:** For generating regulation-compliant PDF invoices.
- **Sabre/XML:** Used for complex XML serialization.
- **Lucide React:** Iconography.

---

## 📌 9. SETUP & RUN INSTRUCTIONS

### Prerequisites
- PHP 8.3+, Composer
- Node.js 20+, NPM
- MySQL or PostgreSQL

### Installation
1.  **Clone the repository.**
2.  **Install PHP dependencies:** `composer install`
3.  **Install JS dependencies:** `npm install`
4.  **Configure environment:** `cp .env.example .env` (Set DB credentials).
5.  **Generate app key:** `php artisan key:generate`
6.  **Migrate and Seed:** `php artisan migrate:fresh --seed`

### Running the App
- **Backend:** `php artisan serve`
- **Frontend:** `npm run dev`
- **Worker (for TTN):** `php artisan queue:listen`

---

## 📌 10. ROADMAP / FUTURE IMPROVEMENTS
- [ ] **Multi-Currency Support:** Extend beyond TND for export invoices.
- [ ] **Mobile App:** Develop a React Native companion for inventory scanning.
- [ ] **AI Insights:** Automated cash flow forecasting and expense categorization.
- [ ] **Full Multi-Tenancy:** Implement `stancl/tenancy` for true SaaS horizontal scaling.
- [ ] **Direct Bank Integration:** Connect with Tunisian banks for automated reconciliation.

---

## 📌 11. CLEAN FORMATTING RULES
- Professional and technical tone.
- Use of code blocks for technical details.
- Clear separation of concerns (Frontend vs Backend).
- Direct reference to project-specific services and models.
