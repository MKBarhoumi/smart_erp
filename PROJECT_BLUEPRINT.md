# PROJECT_BLUEPRINT.md — SaaS Smart ERP Lite (Tunisia)

> **Version:** 1.0.0  
> **Last Updated:** 2026-02-13  
> **Status:** Source of Truth for Autonomous AI Developer Agent  
> **Regulatory Standard:** Tunisia TradeNet (TTN) El Fatoora — TEIF v1.8.8

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Technical Stack (Strict)](#2-technical-stack-strict)
3. [Regulatory Compliance Engine](#3-regulatory-compliance-engine)
4. [Functional Module Breakdown](#4-functional-module-breakdown)
5. [Database Schema](#5-database-schema)
6. [API Architecture](#6-api-architecture)
7. [Implementation Roadmap](#7-implementation-roadmap)
8. [Coding Standards](#8-coding-standards)
9. [Appendix — TEIF Code Reference Tables](#9-appendix--teif-code-reference-tables)

---

## 1. Executive Summary

**SaaS Smart ERP Lite** is a cloud-based, multi-tenant Enterprise Resource Planning platform purpose-built for **Tunisian B2B companies**. Its primary mission is to provide small and medium enterprises (SMEs) with an affordable, regulation-compliant tool to:

- **Create, manage, and transmit electronic oldinvoices** conforming to the Tunisia TradeNet (TTN) El Fatoora standard (TEIF XML v1.8.8).
- **Digitally sign oldinvoices** using XAdES-BES signatures with RSA-SHA256, as mandated by the Tunisian National Digital Certification Agency (ANCE).
- **Generate Cachet Électronique Visible (CEV)** QR codes for physical/digital oldinvoice verification.
- **Handle Tunisian fiscal requirements**: TVA (VAT) at multiple rates, Timbre Fiscal (stamp duty), Matricule Fiscale (MF) validation, and TND 3-decimal monetary precision.
- **Manage inventory, customers, products, and payments** through a clean, modern single-page application (SPA) interface.

The platform is designed as a **database-per-tenant** SaaS architecture, ensuring data isolation, regulatory compliance, and horizontal scalability.

---

## 2. Technical Stack (Strict)

| Layer | Technology | Version | Notes |
|-------|-----------|---------|-------|
| **Backend Framework** | Laravel | 11.x | PHP 8.3 minimum |
| **Frontend Framework** | React | 18.x | With TypeScript |
| **CSS Framework** | Tailwind CSS | 3.x | With `@tailwindcss/forms` plugin |
| **SPA Bridge** | Inertia.js | 2.x | Server-side routing, client-side rendering |
| **Database** | PostgreSQL | 16.x | NUMERIC(20,3) for TND precision |
| **Auth** | Laravel Sanctum | — | API token + SPA cookie auth |
| **Multi-Tenancy** | stancl/tenancy | 3.x | Database-per-tenant isolation |
| **XML Processing** | PHP DOMDocument + ext-dom | — | For TEIF XML generation |
| **Digital Signature** | PHP ext-openssl | — | RSA-SHA256, XAdES-BES envelope |
| **QR Code** | `simplesoftwareio/simple-qrcode` | — | CEV QR code generation |
| **PDF Generation** | DomPDF or Snappy | — | OldInvoice PDF export |
| **Queue** | Laravel Horizon + Redis | — | Async TTN submission jobs |
| **Testing** | PHPUnit + Pest (backend), Vitest (frontend) | — | Mandatory test coverage |
| **CI/CD** | GitHub Actions | — | Lint, test, deploy pipeline |

### 2.1 Development Environment

- [ ] PHP 8.3 with extensions: `dom`, `openssl`, `mbstring`, `pgsql`, `redis`, `xml`, `xmlwriter`
- [ ] Node.js 20 LTS with npm
- [ ] PostgreSQL 16
- [ ] Redis 7
- [ ] Composer 2.x

### 2.2 Multi-Tenant Architecture Rules

```
ARCHITECTURE: Database-per-Tenant
├── Central Database (landlord): tenants, domains, plans, billing
├── Tenant Database (per client): oldinvoices, products, customers, inventory
└── Each tenant gets: {tenant_id}_erp_db
```

- **Tenant Identification:** Subdomain-based (e.g., `company-x.smarterp.tn`)
- **Tenant Creation:** Creates a new PostgreSQL database, runs tenant-specific migrations
- **Shared Resources:** Plans, system configuration stored in central DB
- **Isolated Resources:** All business data (oldinvoices, products, customers) in tenant DB

---

## 3. Regulatory Compliance Engine

### 3.1 TEIF XML Structure (v1.8.8)

The **Tunisian Electronic OldInvoice Format (TEIF)** is the mandatory XML structure for all electronic oldinvoices transmitted through the TTN El Fatoora platform. The root element is `<TEIF>`.

#### 3.1.1 Root Element

```xml
<TEIF controlingAgency="TTN" version="1.8.8">
  <OldInvoiceHeader>...</OldInvoiceHeader>
  <OldInvoiceBody>...</OldInvoiceBody>
  <AdditionnalDocuments>...</AdditionnalDocuments>   <!-- optional -->
  <ds:Signature>...</ds:Signature>                    <!-- Supplier signature (SigFrs) -->
  <RefTtnVal>...</RefTtnVal>                          <!-- Added by TTN after validation -->
  <ds:Signature>...</ds:Signature>                    <!-- TTN signature (SigTTN) -->
</TEIF>
```

**Attributes:**
| Attribute | Required | Values |
|-----------|----------|--------|
| `version` | YES | `1.8.1` through `1.8.8` (use `1.8.8`) |
| `controlingAgency` | YES | `TTN` or `Tunisie TradeNet` |

#### 3.1.2 OldInvoiceHeader

```xml
<OldInvoiceHeader>
  <MessageSenderIdentifier type="I-01">0736202XAM000</MessageSenderIdentifier>
  <MessageRecieverIdentifier type="I-01">0914089JAM000</MessageRecieverIdentifier>
</OldInvoiceHeader>
```

**Partner Identifier Types:**
| Code | Description | Pattern | Length |
|------|-------------|---------|--------|
| `I-01` | Matricule Fiscale (MF) | `[0-9]{7}[ABCDEFGHJKLMNPQRSTVWXYZ][ABDNP][CMNP][0]{3}` | 13 |
| `I-02` | Carte d'Identité Nationale (CIN) | `[0-9]{8}` | 8 |
| `I-03` | Carte de Séjour | `[0-9]{9}` | 9 |
| `I-04` | Other (free format) | Any string ≤ 35 chars | ≤ 35 |

> **CRITICAL VALIDATION:** When `type="I-01"`, the system MUST validate the MF pattern:  
> `[0-9]{7}[ABCDEFGHJKLMNPQRSTVWXYZ][ABDNP][CMNP][0]{3}`  
> Example valid MF: `0736202XAM000`

#### 3.1.3 OldInvoiceBody Structure

```xml
<OldInvoiceBody>
  <Bgm>                        <!-- Document identification -->
  <Dtm>                        <!-- Dates -->
  <PartnerSection>             <!-- Seller & Buyer details -->
  <LocSection>                 <!-- Locations (optional) -->
  <PytSection>                 <!-- Payment terms (optional) -->
  <Ftx>                        <!-- Free text (optional) -->
  <SpecialConditions>          <!-- Special conditions (optional) -->
  <LinSection>                 <!-- Line items (REQUIRED) -->
  <OldInvoiceMoa>                 <!-- OldInvoice-level amounts (REQUIRED) -->
  <OldInvoiceTax>                 <!-- OldInvoice-level taxes (REQUIRED) -->
  <OldInvoiceAlc>                 <!-- Allowances/charges (optional) -->
</OldInvoiceBody>
```

#### 3.1.4 Bgm (Document Identification)

```xml
<Bgm>
  <DocumentIdentifier>12016_2012</DocumentIdentifier>
  <DocumentType code="I-11">Facture</DocumentType>
  <DocumentReferences>...</DocumentReferences>  <!-- optional -->
</Bgm>
```

**Document Type Codes:**
| Code | Description (French) | Description (English) |
|------|---------------------|----------------------|
| `I-11` | Facture | OldInvoice |
| `I-12` | Facture rectificative | Corrective oldinvoice |
| `I-13` | Avoir | Credit note |
| `I-14` | Note de débit | Debit note |
| `I-15` | Facture proforma | Proforma oldinvoice |
| `I-16` | Facture d'acompte | Advance oldinvoice |

#### 3.1.5 Dtm (Dates)

```xml
<Dtm>
  <DateText format="ddMMyy" functionCode="I-31">070612</DateText>
  <DateText format="ddMMyy-ddMMyy" functionCode="I-36">010512-310512</DateText>
  <DateText format="ddMMyy" functionCode="I-32">070712</DateText>
</Dtm>
```

**Date Function Codes:**
| Code | Description |
|------|-------------|
| `I-31` | OldInvoice date |
| `I-32` | Due date |
| `I-33` | Delivery date |
| `I-34` | Order date |
| `I-35` | Dispatch date |
| `I-36` | Billing period (format: `ddMMyy-ddMMyy`) |
| `I-37` | TTN validation date (format: `ddMMyyHHmm`) |
| `I-38` | Other date |

**Date Formats:** `ddMMyy`, `ddMMyyHHmm`, `ddMMyy-ddMMyy`

#### 3.1.6 PartnerSection

Each partner has a `functionCode`:
| Code | Role |
|------|------|
| `I-61` | Buyer (Acheteur) |
| `I-62` | Seller/Supplier (Vendeur) — **subject to MF validation** |
| `I-63` | OldInvoicee |
| `I-64` | OldInvoiced party |
| `I-65` | Delivery party |
| `I-66` | Payer |
| `I-67` | Payee |
| `I-68` | Other |

Each partner contains:
- `Nad` → `PartnerIdentifier`, `PartnerName`, `PartnerAdresses`
- `RffSection` → References (MF, category, activity, etc.)
- `CtaSection` → Contact details with communication means

**Reference Codes (RffSection):**
| Code | Description |
|------|-------------|
| `I-81` | Matricule Fiscale |
| `I-811` | Category type (SMTP, etc.) |
| `I-812` | Person type (P=Physical, M=Moral) |
| `I-813` | Tax office (Bureau fiscal) |
| `I-814` | Secondary identifier |
| `I-815` | RC (Registre de Commerce) |
| `I-816` | Legal form (SA, SARL, etc.) |

**Communication Means:**
| Code | Description |
|------|-------------|
| `I-101` | Phone |
| `I-102` | Fax |
| `I-103` | Email |
| `I-104` | Website |

#### 3.1.7 LinSection (Line Items)

```xml
<LinSection>
  <Lin>
    <ItemIdentifier>1</ItemIdentifier>
    <LinImd lang="fr">
      <ItemCode>DDM</ItemCode>
      <ItemDescription>Dossier DDM</ItemDescription>
    </LinImd>
    <LinQty>
      <Quantity measurementUnit="UNIT">1.0</Quantity>
    </LinQty>
    <LinTax>
      <TaxTypeName code="I-1602">TVA</TaxTypeName>
      <TaxDetails>
        <TaxRate>12</TaxRate>
      </TaxDetails>
    </LinTax>
    <LinMoa>
      <MoaDetails>
        <Moa amountTypeCode="I-183" currencyCodeList="ISO_4217">
          <Amount currencyIdentifier="TND">2.000</Amount>
        </Moa>
      </MoaDetails>
      <MoaDetails>
        <Moa amountTypeCode="I-171" currencyCodeList="ISO_4217">
          <Amount currencyIdentifier="TND">2.000</Amount>
        </Moa>
      </MoaDetails>
    </LinMoa>
  </Lin>
</LinSection>
```

Each `<Lin>` contains:
- `ItemIdentifier` (required, max 35 chars)
- `LinImd` (item description, with `lang` attribute: `fr`, `en`, `ar`, `or`)
- `LinApi` (additional product info, optional)
- `LinQty` (quantity with `measurementUnit` attribute)
- `LinDtm` (line-level dates, optional)
- `LinTax` (tax applicable to this line)
- `LinAlc` (line-level allowances/charges, optional)
- `LinMoa` (line-level amounts)
- `LinFtx` (line-level free text, optional)
- `SubLin` (sub-lines, optional, recursive)

#### 3.1.8 Tax Codes

| Code | Description |
|------|-------------|
| `I-1601` | Droit de timbre (Stamp duty / Timbre Fiscal) |
| `I-1602` | TVA (Value Added Tax) |
| `I-1603` | Other tax/duty |
| `I-161` to `I-169`, `I-160` | Additional tax category codes |

**Tunisian TVA Rates (as of 2026):**
| Rate | Description |
|------|-------------|
| 0% | Exempt |
| 7% | Reduced rate |
| 13% | Intermediate rate |
| 19% | Standard rate |

#### 3.1.9 Amount Type Codes (Moa)

| Code | Description | Level |
|------|-------------|-------|
| `I-171` | Line item net amount | Line |
| `I-172` | Line item gross amount | Line |
| `I-173` | Unit price | Line |
| `I-174` | Discount amount | Line |
| `I-175` | Charge amount | Line |
| `I-176` | Total net amount (HT) | OldInvoice |
| `I-177` | Taxable amount per tax rate | OldInvoice Tax |
| `I-178` | Tax amount per tax rate | OldInvoice Tax |
| `I-179` | Total gross amount | OldInvoice |
| `I-180` | Total amount due (TTC) | OldInvoice |
| `I-181` | Total TVA amount | OldInvoice |
| `I-182` | Total net amount before discount | OldInvoice |
| `I-183` | Line unit price amount | Line |
| `I-184` | Advance payment amount | OldInvoice |
| `I-185` | Remaining amount due | OldInvoice |
| `I-186` | Allowance total | OldInvoice |
| `I-187` | Charge total | OldInvoice |
| `I-188` | Rounding adjustment | OldInvoice |

**Monetary Format:**
```
Pattern: -?[0-9]{1,15}([.][0-9]{1,5})?
Currency: TND (Tunisian Dinar)
Precision: 3 decimal places (millimes)
Currency Code List: ISO_4217
Examples: "2.000", "0.240", "2.540"
```

> **CRITICAL:** All monetary amounts in TND MUST use exactly 3 decimal places. The PostgreSQL column type MUST be `NUMERIC(20,3)`.

#### 3.1.10 OldInvoiceMoa (OldInvoice-Level Amounts)

```xml
<OldInvoiceMoa>
  <AmountDetails>
    <Moa amountTypeCode="I-179" currencyCodeList="ISO_4217">
      <Amount currencyIdentifier="TND">2000000</Amount>
    </Moa>
  </AmountDetails>
  <AmountDetails>
    <Moa amountTypeCode="I-180" currencyCodeList="ISO_4217">
      <Amount currencyIdentifier="TND">2.540</Amount>
      <AmountDescription lang="fr">DEUX DINARS ET CINQ CENT QUARANTE MILLIMES</AmountDescription>
    </Moa>
  </AmountDetails>
  <!-- I-176: Total HT, I-182: Total net before discount, I-181: Total TVA -->
</OldInvoiceMoa>
```

> **NOTE:** `I-180` (Total TTC) SHOULD include `<AmountDescription>` — the amount spelled out in words in French.

#### 3.1.11 OldInvoiceTax (OldInvoice-Level Taxes)

```xml
<OldInvoiceTax>
  <OldInvoiceTaxDetails>
    <Tax>
      <TaxTypeName code="I-1601">droit de timbre</TaxTypeName>
      <TaxDetails><TaxRate>0</TaxRate></TaxDetails>
    </Tax>
    <AmountDetails>
      <Moa amountTypeCode="I-178" currencyCodeList="ISO_4217">
        <Amount currencyIdentifier="TND">0.300</Amount>
      </Moa>
    </AmountDetails>
  </OldInvoiceTaxDetails>
  <OldInvoiceTaxDetails>
    <Tax>
      <TaxTypeName code="I-1602">TVA</TaxTypeName>
      <TaxDetails><TaxRate>12.0</TaxRate></TaxDetails>
    </Tax>
    <AmountDetails>
      <Moa amountTypeCode="I-177" currencyCodeList="ISO_4217">
        <Amount currencyIdentifier="TND">2.000</Amount>
      </Moa>
    </AmountDetails>
    <AmountDetails>
      <Moa amountTypeCode="I-178" currencyCodeList="ISO_4217">
        <Amount currencyIdentifier="TND">0.240</Amount>
      </Moa>
    </AmountDetails>
  </OldInvoiceTaxDetails>
</OldInvoiceTax>
```

### 3.2 XAdES-BES Digital Signature Workflow

The TEIF document requires **two digital signatures**: one from the **supplier** (`SigFrs`) and one from **TTN** (`SigTTN`).

#### 3.2.1 Signature Architecture

```
TEIF Document
├── OldInvoiceHeader
├── OldInvoiceBody
├── ds:Signature [Id="SigFrs"]       ← Supplier signs FIRST
│   ├── ds:SignedInfo
│   │   ├── CanonicalizationMethod: Exclusive C14N
│   │   ├── SignatureMethod: RSA-SHA256
│   │   ├── Reference [Id="r-id-frs", URI=""]
│   │   │   ├── Transform: XPath (exclude ds:Signature nodes)
│   │   │   ├── Transform: XPath (exclude RefTtnVal node)
│   │   │   ├── Transform: Exclusive C14N
│   │   │   └── DigestMethod: SHA-256
│   │   └── Reference [URI="#xades-SigFrs"]
│   │       ├── Transform: Exclusive C14N
│   │       └── DigestMethod: SHA-256
│   ├── ds:SignatureValue [Id="value-SigFrs"]
│   ├── ds:KeyInfo → ds:X509Data → X509Certificate chain
│   └── ds:Object → xades:QualifyingProperties
│       └── xades:SignedProperties [Id="xades-SigFrs"]
│           ├── SigningTime
│           ├── SigningCertificateV2
│           ├── SignaturePolicyIdentifier
│           ├── SignerRoleV2
│           └── DataObjectFormat
├── RefTtnVal                          ← Added by TTN
│   ├── ReferenceTTN [refID="I-88"]
│   ├── ReferenceDate
│   └── ReferenceCEV (Base64 PNG QR)
└── ds:Signature [Id="SigTTN"]        ← TTN signs SECOND
```

#### 3.2.2 Supplier Signature Generation Steps

The system MUST implement this exact sequence:

- [ ] **Step 1:** Build the complete TEIF XML document (without any `<ds:Signature>` or `<RefTtnVal>` elements)
- [ ] **Step 2:** Canonicalize the document content using **Exclusive XML Canonicalization** (`http://www.w3.org/2001/10/xml-exc-c14n#`)
- [ ] **Step 3:** Apply XPath transforms to exclude `ds:Signature` and `RefTtnVal` nodes:
  ```
  XPath 1: not(ancestor-or-self::ds:Signature)
  XPath 2: not(ancestor-or-self::RefTtnVal)
  ```
- [ ] **Step 4:** Compute SHA-256 digest of the transformed content → `DigestValue` for reference `r-id-frs`
- [ ] **Step 5:** Build the `xades:SignedProperties` block:
  - `SigningTime`: ISO 8601 UTC timestamp (e.g., `2020-09-16T11:37:16Z`)
  - `SigningCertificateV2`: SHA-1 digest of the signing certificate + IssuerSerialV2
  - `SignaturePolicyIdentifier`:
    - OID: `urn:2.16.788.1.2.1`
    - Description: `Politique de signature de la facture electronique`
    - Policy URL: `http://www.tradenet.com.tn/portal/telechargerTelechargement?lien=Politique_de_Signature_de_la_facture_electronique.pdf`
    - Policy Hash: SHA-256 digest of the policy document
  - `SignerRoleV2`: Claimed role (e.g., `CEO`, `CFO`, `Accountant`)
  - `DataObjectFormat`: `application/octet-stream` referencing `#r-id-frs`
- [ ] **Step 6:** Canonicalize the `xades:SignedProperties` block → compute SHA-256 digest → `DigestValue` for reference `#xades-SigFrs`
- [ ] **Step 7:** Build `ds:SignedInfo` containing both references
- [ ] **Step 8:** Canonicalize `ds:SignedInfo` → sign with **RSA-SHA256** using the supplier's private key
- [ ] **Step 9:** Encode the signature as Base64 → `ds:SignatureValue`
- [ ] **Step 10:** Attach the full X.509 certificate chain in `ds:KeyInfo/ds:X509Data`:
  - End-entity certificate (supplier's certificate)
  - Intermediate CA: TnTrust Qualified Gov CA
  - Issuing CA: Tunisia Gov CA
  - Root CA: Tunisia National Root CA
- [ ] **Step 11:** Assemble the complete `<ds:Signature Id="SigFrs">` block and insert it into the TEIF document

#### 3.2.3 Cryptographic Parameters (Strict)

| Parameter | Value |
|-----------|-------|
| Canonicalization | `http://www.w3.org/2001/10/xml-exc-c14n#` (Exclusive C14N) |
| Signature Algorithm | `http://www.w3.org/2001/04/xmldsig-more#rsa-sha256` |
| Digest Algorithm (content) | `http://www.w3.org/2001/04/xmlenc#sha256` |
| Digest Algorithm (cert) | `http://www.w3.org/2000/09/xmldsig#sha1` |
| XAdES Version | `http://uri.etsi.org/01903/v1.3.2#` |
| Signature Policy OID | `urn:2.16.788.1.2.1` |
| Key Size | RSA 2048-bit minimum |

#### 3.2.4 Certificate Management

- [ ] Store supplier's `.p12` / `.pfx` private key file securely (encrypted at rest)
- [ ] Provide admin UI to upload/rotate certificates
- [ ] Validate certificate chain up to Tunisia National Root CA
- [ ] Check certificate expiry and alert 30 days before expiration
- [ ] Support certificate revocation checking via CRL URLs:
  - `http://crl.tuntrust.tn/tntrustqualifiedgovca.crl`
  - `http://crl.certification.tn/tunisiagovca.crl`
  - `http://crl.certification.tn/tunrootca.crl`

### 3.3 CEV QR Code (Cachet Électronique Visible)

The CEV is a QR code returned by TTN after oldinvoice validation. It is stored in the `<RefTtnVal>` block.

#### 3.3.1 RefTtnVal Structure

```xml
<RefTtnVal>
  <ReferenceTTN refID="I-88">073620200053562920196810312</ReferenceTTN>
  <ReferenceDate>
    <DateText format="ddMMyyHHmm" functionCode="I-37">1609201239</DateText>
  </ReferenceDate>
  <ReferenceCEV>iVBORw0KGgoAAAANSUhEUgAAASwAAAEs...</ReferenceCEV>
</RefTtnVal>
```

| Field | Description | Max Length |
|-------|-------------|-----------|
| `ReferenceTTN` | Unique TTN reference number | 70 chars |
| `ReferenceDate` | TTN validation timestamp | Format: `ddMMyyHHmm` |
| `ReferenceCEV` | Base64-encoded PNG image of QR code | 4000 chars |

#### 3.3.2 CEV Implementation Tasks

- [ ] Decode `ReferenceCEV` from Base64 to PNG binary
- [ ] Store the QR code image associated with the oldinvoice
- [ ] Display the QR code on the PDF oldinvoice printout
- [ ] Display the QR code in the oldinvoice detail view in the UI
- [ ] The QR code links to a TTN verification URL containing the `ReferenceTTN`

### 3.4 TTN Submission Workflow

```
┌──────────────┐    ┌───────────────┐    ┌──────────────┐    ┌───────────────┐
│  1. Create   │───▶│ 2. Validate   │───▶│ 3. Sign XML  │───▶│ 4. Submit to  │
│   OldInvoice    │    │   TEIF XML    │    │  (XAdES-BES) │    │   TTN API     │
└──────────────┘    └───────────────┘    └──────────────┘    └───────┬───────┘
                                                                     │
┌──────────────┐    ┌───────────────┐    ┌──────────────┐            │
│  7. Store &  │◀───│ 6. Decode CEV │◀───│ 5. Receive   │◀───────────┘
│   Display    │    │   QR Code     │    │   Response   │
└──────────────┘    └───────────────┘    └──────────────┘
```

**OldInvoice Status State Machine:**
```
DRAFT → VALIDATED → SIGNED → SUBMITTED → ACCEPTED → ARCHIVED
                                      └→ REJECTED → DRAFT (correction cycle)
                                      └→ ERROR → RETRY
```

---

## 4. Functional Module Breakdown

### 4.1 Module: Authentication & Tenant Management

#### 4.1.1 Tenant Registration
- [ ] Create registration page: company name, MF, admin email, password
- [ ] Validate Matricule Fiscale format: `[0-9]{7}[A-Z][ABDNP][CMNPE][0-9]{3}`
- [ ] On registration: create tenant record in central DB
- [ ] On registration: provision new PostgreSQL database for tenant
- [ ] On registration: run tenant-specific migrations on new database
- [ ] On registration: assign subdomain `{slug}.smarterp.tn`
- [ ] Send email verification link

#### 4.1.2 Authentication
- [ ] Login page with email + password
- [ ] Laravel Sanctum SPA cookie authentication
- [ ] "Remember me" functionality (30-day token)
- [ ] Password reset via email
- [ ] Account lockout after 5 failed attempts (15-minute cooldown)

#### 4.1.3 User Roles & Permissions
- [ ] Define roles: `super_admin`, `admin`, `accountant`, `sales`, `inventory_manager`, `viewer`
- [ ] Implement Laravel policies + gates for each resource
- [ ] Role-based sidebar menu visibility
- [ ] Role management UI (admin only)

### 4.2 Module: Company Settings

- [ ] Company profile form: name, MF, RC, legal form, address, phone, fax, email, website
- [ ] Upload company logo (stored per tenant)
- [ ] Bank account details (RIB, bank name, branch code)
- [ ] Postal account details (CCP number)
- [ ] Default TVA rate setting
- [ ] Timbre Fiscal configuration (enable/disable, fixed amount)
- [ ] OldInvoice numbering format configuration (prefix, counter, format pattern)
- [ ] Digital certificate management (upload `.p12`, set passphrase, view expiry)
- [ ] Default payment terms template

### 4.3 Module: Customer Management (Clients)

#### 4.3.1 Customer CRUD
- [ ] List customers with search, filter by type (Physical/Moral), pagination
- [ ] Create customer form:
  - Identifier type (`I-01` MF, `I-02` CIN, `I-03` Carte de Séjour, `I-04` Other)
  - Identifier value (validated per type)
  - Name (`nameType`: Physical or Qualification)
  - Address (description, street, city, postal code, country=TN)
  - Contact: phone, fax, email, website
  - Category type, person type, tax office, legal form
  - Bank details (optional)
- [ ] Edit customer
- [ ] Delete customer (soft delete, check for linked oldinvoices)
- [ ] Import customers from CSV
- [ ] Export customers to CSV/Excel

#### 4.3.2 Customer Validation Rules
- [ ] If `identifier_type = I-01`: validate `[0-9]{7}[ABCDEFGHJKLMNPQRSTVWXYZ][ABDNP][CMNP][0]{3}` (13 chars)
- [ ] If `identifier_type = I-02`: validate `[0-9]{8}` (8 digits)
- [ ] If `identifier_type = I-03`: validate `[0-9]{9}` (9 digits)
- [ ] If `identifier_type = I-04`: any string ≤ 35 chars
- [ ] Country code must be ISO 3166-1 alpha-2 (default: `TN`)

### 4.4 Module: Product/Service Catalog

#### 4.4.1 Product CRUD
- [ ] List products with search, filter by category, pagination
- [ ] Create product form:
  - Item code (unique per tenant, max 35 chars)
  - Description (max 500 chars, multilingual: `fr`, `en`, `ar`)
  - Unit of measure (UNIT, KG, L, M, M2, M3, H, etc.)
  - Unit price (NUMERIC 20,3 in TND)
  - Default TVA rate (0%, 7%, 13%, 19%)
  - Category (for internal organization)
  - Track inventory (boolean)
  - Minimum stock alert threshold
- [ ] Edit product
- [ ] Delete product (soft delete, check for linked oldinvoice lines)
- [ ] Import products from CSV
- [ ] Export products to CSV/Excel

### 4.5 Module: Inventory Management

- [ ] Dashboard showing current stock levels per product
- [ ] Stock movement log (IN, OUT, ADJUSTMENT)
- [ ] Automatic stock deduction when oldinvoice is validated
- [ ] Automatic stock restoration when credit note is issued
- [ ] Low stock alert notifications (when qty < threshold)
- [ ] Stock valuation report (quantity × unit cost)
- [ ] Manual stock adjustment with reason field
- [ ] Stock movement history per product

### 4.6 Module: Invoicing (Core)

#### 4.6.1 OldInvoice Creation
- [ ] OldInvoice creation form with:
  - Auto-generated document identifier (configurable format)
  - Document type selector (Facture, Avoir, Note de débit, Proforma, Acompte)
  - OldInvoice date (default: today, format stored as `ddMMyy`)
  - Due date
  - Billing period (optional, `ddMMyy-ddMMyy`)
  - Customer selector (from customer module)
  - Payment terms (from templates or custom)
  - Special conditions (optional free text, max 200 chars each)
  - Free text notes (optional, max 500 chars)

#### 4.6.2 OldInvoice Line Items
- [ ] Add line items:
  - Product/service selector (from catalog) or manual entry
  - Quantity + unit of measure
  - Unit price (auto-filled from catalog, editable)
  - TVA rate per line (auto-filled from product, editable)
  - Line-level discount (percentage-based, optional)
  - Auto-calculate: line net amount = qty × unit_price
  - Auto-calculate: line tax amount = line_net × tax_rate / 100
- [ ] Reorder line items (drag & drop)
- [ ] Remove line items
- [ ] Sub-line support (nested items)

#### 4.6.3 OldInvoice Calculations Engine

```
FOR EACH line_item:
  line_net = quantity × unit_price
  IF line_discount:
    line_net = line_net - (line_net × discount_pct / 100)
  line_tax = line_net × tva_rate / 100

INVOICE TOTALS:
  total_ht      (I-176) = SUM(all line_net)
  total_net_pre (I-182) = SUM(all line_net before discounts)
  total_gross   (I-179) = total_ht + total_allowances
  total_tva     (I-181) = SUM(all line_tax)
  timbre_fiscal (I-1601)= configured flat amount (e.g., 0.600 TND) if applicable
  total_ttc     (I-180) = total_ht + total_tva + timbre_fiscal

ALL amounts stored as NUMERIC(20,3) — 3 decimal places for TND millimes.
```

- [ ] Implement calculation engine as a reusable service class
- [ ] Round all amounts to 3 decimal places (TND millimes)
- [ ] Generate amount in words (French): e.g., "DEUX DINARS ET CINQ CENT QUARANTE MILLIMES"
- [ ] Tax summary grouping: group taxes by rate, show taxable base + tax amount per rate

#### 4.6.4 OldInvoice Status Management
- [ ] **DRAFT**: Editable, not yet validated
- [ ] **VALIDATED**: Locked for editing, XML generated, ready for signing
- [ ] **SIGNED**: XAdES-BES signature applied
- [ ] **SUBMITTED**: Sent to TTN El Fatoora
- [ ] **ACCEPTED**: TTN returned RefTtnVal + CEV QR code
- [ ] **REJECTED**: TTN returned error, allow correction → back to DRAFT
- [ ] **ARCHIVED**: Finalized, read-only, retained per fiscal requirements

#### 4.6.5 OldInvoice Actions
- [ ] Save as draft
- [ ] Validate (lock + generate TEIF XML)
- [ ] Sign (apply XAdES-BES digital signature)
- [ ] Submit to TTN (async via queue job)
- [ ] Download PDF (with CEV QR code if accepted)
- [ ] Download signed XML
- [ ] Duplicate oldinvoice (create new draft from existing)
- [ ] Create credit note from oldinvoice
- [ ] Print oldinvoice
- [ ] Email oldinvoice to customer (PDF attachment)

### 4.7 Module: Payment Tracking

- [ ] Record payments against oldinvoices
- [ ] Payment methods: bank transfer (RIB), postal payment (CCP), cash, check
- [ ] Partial payment support
- [ ] Payment status: unpaid, partially paid, fully paid, overdue
- [ ] Auto-calculate remaining balance
- [ ] Overdue oldinvoice alerts
- [ ] Payment receipt generation

### 4.8 Module: Reporting & Dashboard

#### 4.8.1 Dashboard
- [ ] Total revenue (current month, current year)
- [ ] Outstanding receivables
- [ ] Overdue oldinvoices count + total
- [ ] Recent oldinvoices list
- [ ] Low stock alerts
- [ ] TTN submission status summary (pending, accepted, rejected)
- [ ] Revenue chart (monthly bar chart, last 12 months)

#### 4.8.2 Reports
- [ ] Sales report by period (filterable by date range, customer, product)
- [ ] TVA declaration report (grouped by TVA rate, shows taxable base + tax)
- [ ] Timbre Fiscal report
- [ ] Customer statement (all oldinvoices + payments for a customer)
- [ ] Product sales report
- [ ] Aging report (receivables by age: 0-30, 31-60, 61-90, 90+ days)
- [ ] Export all reports to PDF and Excel

### 4.9 Module: System Administration (Super Admin)

- [ ] Tenant list with status (active, suspended, trial)
- [ ] Plan management (Free, Basic, Pro with feature limits)
- [ ] System health monitoring
- [ ] Global audit log viewer

---

## 5. Database Schema

### 5.1 Central Database (`landlord`)

```sql
-- ============================================================
-- CENTRAL DATABASE: Tenant & Billing Management
-- ============================================================

CREATE TABLE tenants (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name            VARCHAR(200) NOT NULL,
    slug            VARCHAR(100) NOT NULL UNIQUE,      -- subdomain identifier
    matricule_fiscal VARCHAR(13),                       -- MF for the company
    database_name   VARCHAR(100) NOT NULL UNIQUE,
    status          VARCHAR(20) NOT NULL DEFAULT 'trial', -- trial, active, suspended, cancelled
    plan_id         UUID REFERENCES plans(id),
    trial_ends_at   TIMESTAMP,
    created_at      TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE domains (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id       UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    domain          VARCHAR(255) NOT NULL UNIQUE,       -- e.g., company-x.smarterp.tn
    is_primary      BOOLEAN NOT NULL DEFAULT true,
    created_at      TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE plans (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name            VARCHAR(50) NOT NULL,               -- Free, Basic, Pro
    max_oldinvoices_per_month INTEGER,
    max_users       INTEGER,
    max_products    INTEGER,
    has_ttn_integration BOOLEAN NOT NULL DEFAULT false,
    price_monthly   NUMERIC(10,3) NOT NULL DEFAULT 0,   -- TND
    created_at      TIMESTAMP NOT NULL DEFAULT NOW()
);
```

### 5.2 Tenant Database (per-tenant)

```sql
-- ============================================================
-- TENANT DATABASE: Business Data
-- ============================================================

-- ===================== USERS & AUTH =====================

CREATE TABLE users (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name            VARCHAR(200) NOT NULL,
    email           VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP,
    password        VARCHAR(255) NOT NULL,
    role            VARCHAR(30) NOT NULL DEFAULT 'viewer',
        -- Values: super_admin, admin, accountant, sales, inventory_manager, viewer
    is_active       BOOLEAN NOT NULL DEFAULT true,
    remember_token  VARCHAR(100),
    created_at      TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ===================== COMPANY SETTINGS =====================

CREATE TABLE company_settings (
    id                      UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    company_name            VARCHAR(200) NOT NULL,
    matricule_fiscal        VARCHAR(13) NOT NULL,        -- Pattern: [0-9]{7}[A-Z][ABDNP][CMNPE][0-9]{3}
    registre_commerce       VARCHAR(200),                -- RC number (ref I-815)
    legal_form              VARCHAR(10),                  -- SA, SARL, etc. (ref I-816)
    category_type           VARCHAR(20),                  -- ref I-811
    person_type             VARCHAR(1),                   -- P=Physical, M=Moral (ref I-812)
    tax_office              VARCHAR(200),                 -- Bureau fiscal (ref I-813)
    address_description     VARCHAR(500),
    street                  VARCHAR(35),
    city                    VARCHAR(35),
    postal_code             VARCHAR(17),
    country_code            VARCHAR(2) NOT NULL DEFAULT 'TN',
    phone                   VARCHAR(500),                 -- I-101
    fax                     VARCHAR(500),                 -- I-102
    email                   VARCHAR(500),                 -- I-103
    website                 VARCHAR(500),                 -- I-104
    logo_path               VARCHAR(500),
    -- Bank details
    bank_rib                VARCHAR(20),                  -- RIB (Relevé d'Identité Bancaire)
    bank_name               VARCHAR(70),
    bank_branch_code        VARCHAR(17),
    postal_account          VARCHAR(20),                  -- CCP number
    -- OldInvoice defaults
    default_tva_rate        NUMERIC(5,2) NOT NULL DEFAULT 19.00,
    timbre_fiscal_enabled   BOOLEAN NOT NULL DEFAULT true,
    timbre_fiscal_amount    NUMERIC(20,3) NOT NULL DEFAULT 0.600, -- TND
    oldinvoice_prefix          VARCHAR(20) NOT NULL DEFAULT 'INV',
    oldinvoice_counter         INTEGER NOT NULL DEFAULT 1,
    oldinvoice_format          VARCHAR(100) NOT NULL DEFAULT '{prefix}-{YYYY}-{counter}',
    -- Certificate
    certificate_path        VARCHAR(500),                 -- Path to .p12 file (encrypted)
    certificate_passphrase  VARCHAR(500),                 -- Encrypted passphrase
    certificate_expires_at  TIMESTAMP,
    certificate_subject_cn  VARCHAR(200),
    -- Timestamps
    created_at              TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ===================== CUSTOMERS =====================

CREATE TABLE customers (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    identifier_type     VARCHAR(4) NOT NULL,              -- I-01, I-02, I-03, I-04
    identifier_value    VARCHAR(35) NOT NULL,
    name                VARCHAR(200) NOT NULL,
    name_type           VARCHAR(20) NOT NULL DEFAULT 'Qualification', -- Physical | Qualification
    -- Address
    address_description VARCHAR(500),
    street              VARCHAR(35),
    city                VARCHAR(35),
    postal_code         VARCHAR(17),
    country_code        VARCHAR(2) NOT NULL DEFAULT 'TN',
    -- Fiscal info
    matricule_fiscal    VARCHAR(13),                      -- Only if identifier_type = I-01
    category_type       VARCHAR(20),                      -- ref I-811
    person_type         VARCHAR(1),                       -- P | M (ref I-812)
    tax_office          VARCHAR(200),                     -- ref I-813
    secondary_id        VARCHAR(200),                     -- ref I-814
    registre_commerce   VARCHAR(200),                     -- ref I-815
    legal_form          VARCHAR(10),                      -- ref I-816
    -- Contact
    phone               VARCHAR(500),
    fax                 VARCHAR(500),
    email               VARCHAR(500),
    website             VARCHAR(500),
    -- Bank
    bank_rib            VARCHAR(20),
    bank_name           VARCHAR(70),
    bank_branch_code    VARCHAR(17),
    -- Metadata
    notes               TEXT,
    is_active           BOOLEAN NOT NULL DEFAULT true,
    created_at          TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMP NOT NULL DEFAULT NOW(),
    deleted_at          TIMESTAMP                         -- Soft delete
);

CREATE UNIQUE INDEX idx_customers_identifier
    ON customers(identifier_type, identifier_value)
    WHERE deleted_at IS NULL;

-- ===================== PRODUCTS / SERVICES =====================

CREATE TABLE products (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    item_code           VARCHAR(35) NOT NULL UNIQUE,
    description_fr      VARCHAR(500) NOT NULL,
    description_en      VARCHAR(500),
    description_ar      VARCHAR(500),
    unit_of_measure     VARCHAR(8) NOT NULL DEFAULT 'UNIT',  -- UNIT, KG, L, M, H, etc.
    unit_price          NUMERIC(20,3) NOT NULL,               -- TND with 3 decimals
    default_tva_rate    NUMERIC(5,2) NOT NULL DEFAULT 19.00,  -- 0, 7, 13, 19
    category            VARCHAR(100),
    track_inventory     BOOLEAN NOT NULL DEFAULT false,
    min_stock_threshold NUMERIC(20,3) DEFAULT 0,
    current_stock       NUMERIC(20,3) NOT NULL DEFAULT 0,
    is_active           BOOLEAN NOT NULL DEFAULT true,
    created_at          TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMP NOT NULL DEFAULT NOW(),
    deleted_at          TIMESTAMP
);

-- ===================== INVENTORY =====================

CREATE TABLE stock_movements (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    product_id      UUID NOT NULL REFERENCES products(id),
    movement_type   VARCHAR(20) NOT NULL,                 -- IN, OUT, ADJUSTMENT
    quantity         NUMERIC(20,3) NOT NULL,
    reference_type  VARCHAR(50),                          -- oldinvoice, credit_note, manual
    reference_id    UUID,                                 -- FK to oldinvoices if applicable
    reason          VARCHAR(500),
    performed_by    UUID REFERENCES users(id),
    created_at      TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_stock_movements_product ON stock_movements(product_id);
CREATE INDEX idx_stock_movements_created ON stock_movements(created_at);

-- ===================== INVOICES =====================

CREATE TABLE oldinvoices (
    id                      UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    -- Document identification (Bgm)
    document_identifier     VARCHAR(70) NOT NULL UNIQUE,    -- OldInvoice number
    document_type_code      VARCHAR(4) NOT NULL DEFAULT 'I-11',
        -- I-11=Facture, I-12=Rectificative, I-13=Avoir, I-14=Note débit,
        -- I-15=Proforma, I-16=Acompte
    document_type_label     VARCHAR(35) NOT NULL DEFAULT 'Facture',
    -- References
    parent_oldinvoice_id       UUID REFERENCES oldinvoices(id),   -- For credit notes / corrections
    -- Dates (Dtm)
    oldinvoice_date            DATE NOT NULL,                   -- I-31 (stored as date, formatted ddMMyy for XML)
    due_date                DATE,                            -- I-32
    delivery_date           DATE,                            -- I-33
    billing_period_start    DATE,                            -- I-36 start
    billing_period_end      DATE,                            -- I-36 end
    -- Parties
    customer_id             UUID NOT NULL REFERENCES customers(id),
    -- Amounts (OldInvoiceMoa) — all NUMERIC(20,3) for TND
    total_ht                NUMERIC(20,3) NOT NULL DEFAULT 0,    -- I-176: Total net (HT)
    total_net_before_disc   NUMERIC(20,3) NOT NULL DEFAULT 0,    -- I-182: Net before discounts
    total_gross             NUMERIC(20,3) NOT NULL DEFAULT 0,    -- I-179: Gross total
    total_tva               NUMERIC(20,3) NOT NULL DEFAULT 0,    -- I-181: Total TVA
    timbre_fiscal           NUMERIC(20,3) NOT NULL DEFAULT 0,    -- I-1601: Stamp duty
    total_ttc               NUMERIC(20,3) NOT NULL DEFAULT 0,    -- I-180: Total TTC
    total_ttc_words_fr      VARCHAR(500),                        -- Amount in French words
    total_allowances        NUMERIC(20,3) NOT NULL DEFAULT 0,    -- I-186
    total_charges           NUMERIC(20,3) NOT NULL DEFAULT 0,    -- I-187
    -- Payment
    payment_terms_code      VARCHAR(6),                          -- I-114, I-115, etc.
    payment_terms_text      VARCHAR(500),
    -- Status
    status                  VARCHAR(20) NOT NULL DEFAULT 'draft',
        -- draft, validated, signed, submitted, accepted, rejected, archived
    -- TTN Integration
    ttn_reference           VARCHAR(70),                         -- ReferenceTTN (I-88)
    ttn_validation_date     TIMESTAMP,                           -- I-37
    ttn_cev_qr_base64       TEXT,                                -- ReferenceCEV (base64 PNG)
    ttn_cev_qr_path         VARCHAR(500),                        -- Path to stored QR image
    ttn_submission_error    TEXT,                                 -- Error message if rejected
    ttn_submitted_at        TIMESTAMP,
    -- XML & Signature
    teif_xml                TEXT,                                 -- Generated TEIF XML
    signed_xml              TEXT,                                 -- Signed TEIF XML
    signature_timestamp     TIMESTAMP,
    -- Notes
    special_conditions      JSONB,                               -- Array of condition strings
    free_text_notes         JSONB,                               -- Array of {subjectCode, texts[]}
    -- Metadata
    created_by              UUID REFERENCES users(id),
    updated_by              UUID REFERENCES users(id),
    created_at              TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMP NOT NULL DEFAULT NOW(),
    deleted_at              TIMESTAMP
);

CREATE INDEX idx_oldinvoices_status ON oldinvoices(status);
CREATE INDEX idx_oldinvoices_customer ON oldinvoices(customer_id);
CREATE INDEX idx_oldinvoices_date ON oldinvoices(oldinvoice_date);
CREATE INDEX idx_oldinvoices_document_type ON oldinvoices(document_type_code);

-- ===================== INVOICE LINE ITEMS =====================

CREATE TABLE oldinvoice_lines (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    oldinvoice_id          UUID NOT NULL REFERENCES oldinvoices(id) ON DELETE CASCADE,
    parent_line_id      UUID REFERENCES oldinvoice_lines(id),  -- For sub-lines
    line_number         INTEGER NOT NULL,
    -- Product reference
    product_id          UUID REFERENCES products(id),
    item_code           VARCHAR(35) NOT NULL,
    item_description    VARCHAR(500) NOT NULL,
    item_lang           VARCHAR(6) NOT NULL DEFAULT 'fr',    -- fr, en, ar
    -- Quantity
    quantity            NUMERIC(20,3) NOT NULL,
    unit_of_measure     VARCHAR(8) NOT NULL DEFAULT 'UNIT',
    -- Pricing
    unit_price          NUMERIC(20,3) NOT NULL,               -- I-183
    line_net_amount     NUMERIC(20,3) NOT NULL,               -- I-171
    line_gross_amount   NUMERIC(20,3),                        -- I-172
    -- Tax
    tva_rate            NUMERIC(5,2) NOT NULL,                -- e.g., 19.00
    tva_amount          NUMERIC(20,3) NOT NULL DEFAULT 0,
    -- Discount/Allowance
    discount_percentage NUMERIC(5,2) DEFAULT 0,
    discount_amount     NUMERIC(20,3) DEFAULT 0,
    -- Metadata
    sort_order          INTEGER NOT NULL DEFAULT 0,
    created_at          TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_oldinvoice_lines_oldinvoice ON oldinvoice_lines(oldinvoice_id);

-- ===================== INVOICE TAX SUMMARY =====================

CREATE TABLE oldinvoice_tax_lines (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    oldinvoice_id      UUID NOT NULL REFERENCES oldinvoices(id) ON DELETE CASCADE,
    tax_type_code   VARCHAR(6) NOT NULL,              -- I-1601, I-1602, I-1603
    tax_type_name   VARCHAR(200) NOT NULL,            -- "droit de timbre", "TVA"
    tax_rate        NUMERIC(5,2) NOT NULL,
    taxable_amount  NUMERIC(20,3) NOT NULL DEFAULT 0, -- I-177
    tax_amount      NUMERIC(20,3) NOT NULL DEFAULT 0, -- I-178
    created_at      TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_oldinvoice_tax_lines_oldinvoice ON oldinvoice_tax_lines(oldinvoice_id);

-- ===================== PAYMENTS =====================

CREATE TABLE payments (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    oldinvoice_id      UUID NOT NULL REFERENCES oldinvoices(id),
    payment_date    DATE NOT NULL,
    amount          NUMERIC(20,3) NOT NULL,            -- TND
    payment_method  VARCHAR(30) NOT NULL,
        -- bank_transfer, postal_payment, cash, check
    bank_reference  VARCHAR(100),                      -- Bank transaction ref
    account_number  VARCHAR(20),                       -- RIB or CCP used
    institution     VARCHAR(70),                       -- Bank or Post name
    notes           VARCHAR(500),
    created_by      UUID REFERENCES users(id),
    created_at      TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_payments_oldinvoice ON payments(oldinvoice_id);

-- ===================== INVOICE ALLOWANCES (OldInvoice-level) =====================

CREATE TABLE oldinvoice_allowances (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    oldinvoice_id          UUID NOT NULL REFERENCES oldinvoices(id) ON DELETE CASCADE,
    allowance_code      VARCHAR(6) NOT NULL,           -- I-151 to I-155
    description         VARCHAR(200),
    amount              NUMERIC(20,3) NOT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT NOW()
);

-- ===================== AUDIT LOG =====================

CREATE TABLE audit_logs (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id         UUID REFERENCES users(id),
    action          VARCHAR(50) NOT NULL,               -- create, update, delete, sign, submit, etc.
    auditable_type  VARCHAR(100) NOT NULL,              -- e.g., App\Models\OldInvoice
    auditable_id    UUID NOT NULL,
    old_values      JSONB,
    new_values      JSONB,
    ip_address      VARCHAR(45),
    user_agent      VARCHAR(500),
    created_at      TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_audit_logs_auditable ON audit_logs(auditable_type, auditable_id);
CREATE INDEX idx_audit_logs_user ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_created ON audit_logs(created_at);

-- ===================== TTN SUBMISSION LOG =====================

CREATE TABLE ttn_submission_logs (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    oldinvoice_id      UUID NOT NULL REFERENCES oldinvoices(id),
    attempt_number  INTEGER NOT NULL DEFAULT 1,
    request_xml     TEXT,                               -- XML sent to TTN
    response_xml    TEXT,                               -- XML received from TTN
    response_status VARCHAR(20),                        -- success, error, timeout
    error_code      VARCHAR(50),
    error_message   TEXT,
    submitted_at    TIMESTAMP NOT NULL DEFAULT NOW(),
    responded_at    TIMESTAMP
);

CREATE INDEX idx_ttn_logs_oldinvoice ON ttn_submission_logs(oldinvoice_id);
```

### 5.3 Entity Relationship Summary

```
tenants (central) ─────1:N───── domains (central)
tenants (central) ─────N:1───── plans (central)

users ─────────────────1:N───── oldinvoices (created_by)
users ─────────────────1:N───── audit_logs
customers ─────────────1:N───── oldinvoices
oldinvoices ──────────────1:N───── oldinvoice_lines
oldinvoices ──────────────1:N───── oldinvoice_tax_lines
oldinvoices ──────────────1:N───── oldinvoice_allowances
oldinvoices ──────────────1:N───── payments
oldinvoices ──────────────1:N───── ttn_submission_logs
oldinvoices ──────────────1:1───── oldinvoices (parent for credit notes)
products ──────────────1:N───── oldinvoice_lines
products ──────────────1:N───── stock_movements
oldinvoice_lines ─────────1:N───── oldinvoice_lines (sub-lines via parent_line_id)
```

---

## 6. API Architecture

### 6.1 Route Structure (Inertia.js Server-Side)

All routes use Inertia.js — server returns React page components, not JSON.

```
MIDDLEWARE GROUPS:
  - web: session, CSRF, Inertia
  - auth: Sanctum authentication
  - tenant: Tenant identification + DB switching
  - role:{role}: Role-based access

ROUTES:
  GET    /login                          → Auth/Login
  POST   /login                          → Auth/LoginController@store
  POST   /logout                         → Auth/LoginController@destroy
  GET    /register                       → Auth/Register
  POST   /register                       → Auth/RegisterController@store

  -- Dashboard
  GET    /dashboard                      → Dashboard/Index

  -- Company Settings
  GET    /settings                       → Settings/Edit
  PUT    /settings                       → Settings/Update
  POST   /settings/certificate           → Settings/UploadCertificate

  -- Customers
  GET    /customers                      → Customers/Index
  GET    /customers/create               → Customers/Create
  POST   /customers                      → Customers/Store
  GET    /customers/{id}                 → Customers/Show
  GET    /customers/{id}/edit            → Customers/Edit
  PUT    /customers/{id}                 → Customers/Update
  DELETE /customers/{id}                 → Customers/Destroy
  POST   /customers/import              → Customers/Import
  GET    /customers/export              → Customers/Export

  -- Products
  GET    /products                       → Products/Index
  GET    /products/create                → Products/Create
  POST   /products                       → Products/Store
  GET    /products/{id}                  → Products/Show
  GET    /products/{id}/edit             → Products/Edit
  PUT    /products/{id}                  → Products/Update
  DELETE /products/{id}                  → Products/Destroy

  -- Inventory
  GET    /inventory                      → Inventory/Index
  POST   /inventory/adjust              → Inventory/Adjust
  GET    /inventory/{product_id}/history → Inventory/History

  -- OldInvoices
  GET    /oldinvoices                       → OldInvoices/Index
  GET    /oldinvoices/create                → OldInvoices/Create
  POST   /oldinvoices                       → OldInvoices/Store
  GET    /oldinvoices/{id}                  → OldInvoices/Show
  GET    /oldinvoices/{id}/edit             → OldInvoices/Edit
  PUT    /oldinvoices/{id}                  → OldInvoices/Update
  DELETE /oldinvoices/{id}                  → OldInvoices/Destroy
  POST   /oldinvoices/{id}/validate        → OldInvoices/Validate
  POST   /oldinvoices/{id}/sign            → OldInvoices/Sign
  POST   /oldinvoices/{id}/submit-ttn      → OldInvoices/SubmitToTTN
  GET    /oldinvoices/{id}/pdf             → OldInvoices/DownloadPDF
  GET    /oldinvoices/{id}/xml             → OldInvoices/DownloadXML
  POST   /oldinvoices/{id}/duplicate       → OldInvoices/Duplicate
  POST   /oldinvoices/{id}/credit-note     → OldInvoices/CreateCreditNote
  POST   /oldinvoices/{id}/email           → OldInvoices/EmailToCustomer

  -- Payments
  GET    /oldinvoices/{id}/payments         → Payments/Index
  POST   /oldinvoices/{id}/payments         → Payments/Store
  DELETE /payments/{id}                  → Payments/Destroy

  -- Reports
  GET    /reports/sales                  → Reports/Sales
  GET    /reports/tva                    → Reports/TVA
  GET    /reports/timbre                 → Reports/Timbre
  GET    /reports/customer-statement     → Reports/CustomerStatement
  GET    /reports/aging                  → Reports/Aging
  GET    /reports/export/{type}         → Reports/Export

  -- Administration
  GET    /admin/users                    → Admin/Users/Index
  POST   /admin/users                    → Admin/Users/Store
  PUT    /admin/users/{id}               → Admin/Users/Update
  DELETE /admin/users/{id}               → Admin/Users/Destroy
  GET    /admin/audit-log                → Admin/AuditLog/Index
```

### 6.2 TTN API Integration Service

```php
// Pseudocode for TTN service interface
interface TTNServiceInterface {
    public function buildTeifXml(OldInvoice $oldinvoice): string;
    public function validateTeifXml(string $xml): ValidationResult;
    public function signTeifXml(string $xml, Certificate $cert): string;
    public function submitToTTN(string $signedXml): TTNResponse;
    public function parseResponse(string $responseXml): TTNResult;
    public function extractCEV(TTNResult $result): string; // Base64 PNG
}
```

### 6.3 Error Handling Protocol for TTN

| Error Scenario | Action |
|----------------|--------|
| XML validation failure | Return validation errors, keep oldinvoice in DRAFT |
| Certificate expired | Block signing, alert admin, log error |
| Certificate chain invalid | Block signing, show certificate management page |
| TTN API timeout (30s) | Queue for retry (max 3 attempts, exponential backoff) |
| TTN API 4xx error | Parse error response, update oldinvoice to REJECTED, log details |
| TTN API 5xx error | Queue for retry with exponential backoff |
| TTN rejects oldinvoice | Set status to REJECTED, store error, allow user correction |
| Network failure | Queue for retry, notify user |

---

## 7. Implementation Roadmap

### Phase 1: Foundation (Weeks 1-2)

#### Phase 1A: Project Scaffolding
- [ ] Initialize Laravel 11 project with `composer create-project laravel/laravel`
- [ ] Configure PHP 8.3 in `composer.json`
- [ ] Install and configure PostgreSQL connection in `.env` and `config/database.php`
- [ ] Install Inertia.js: `composer require inertiajs/inertia-laravel`
- [ ] Install React + Vite: `npm install react react-dom @inertiajs/react`
- [ ] Install Tailwind CSS: `npm install -D tailwindcss postcss autoprefixer @tailwindcss/forms`
- [ ] Configure Tailwind in `tailwind.config.js` and `postcss.config.js`
- [ ] Install Laravel Sanctum: `composer require laravel/sanctum`
- [ ] Install stancl/tenancy: `composer require stancl/tenancy`
- [ ] Create Inertia middleware (`HandleInertiaRequests`)
- [ ] Create base React layout component (`AuthenticatedLayout.tsx`, `GuestLayout.tsx`)
- [ ] Set up TypeScript configuration (`tsconfig.json`)
- [ ] Configure ESLint for React/TypeScript

#### Phase 1B: Multi-Tenancy Setup
- [ ] Configure `stancl/tenancy` in `config/tenancy.php`
- [ ] Create central database migrations: `tenants`, `domains`, `plans`
- [ ] Create Tenant model with domain relationships
- [ ] Configure tenant database creation flow
- [ ] Set up tenant-specific migration path (`database/migrations/tenant/`)
- [ ] Test: create a tenant → verify new database is provisioned
- [ ] Configure subdomain routing in `RouteServiceProvider`

#### Phase 1C: Authentication
- [ ] Create tenant migration for `users` table
- [ ] Implement registration flow (creates tenant + first admin user)
- [ ] Implement login page (React + Inertia)
- [ ] Implement logout
- [ ] Implement password reset
- [ ] Implement email verification
- [ ] Create role-based middleware
- [ ] Test: register → login → access dashboard

### Phase 2: Core Business Data (Weeks 3-4)

#### Phase 2A: Company Settings
- [ ] Create tenant migration for `company_settings` table
- [ ] Create `CompanySetting` Eloquent model
- [ ] Create `SettingsController` with `edit()` and `update()` actions
- [ ] Create React form page `Settings/Edit.tsx` with sections:
  - Company info, address, contact, bank, oldinvoice config, tax config
- [ ] Implement file upload for company logo
- [ ] Implement Matricule Fiscale validation in form request
- [ ] Create seeder with default company settings on tenant creation
- [ ] Test: update company settings → verify persistence

#### Phase 2B: Customer Management
- [ ] Create tenant migration for `customers` table
- [ ] Create `Customer` Eloquent model with soft deletes
- [ ] Create `CustomerController` (full CRUD)
- [ ] Create form request `StoreCustomerRequest` with identifier validation rules
- [ ] Create form request `UpdateCustomerRequest`
- [ ] Create React pages:
  - `Customers/Index.tsx` (list + search + filter + pagination)
  - `Customers/Create.tsx` (form with dynamic validation per identifier type)
  - `Customers/Edit.tsx`
  - `Customers/Show.tsx`
- [ ] Implement identifier pattern validation:
  - I-01: `/^[0-9]{7}[ABCDEFGHJKLMNPQRSTVWXYZ][ABDNP][CMNP][0]{3}$/`
  - I-02: `/^[0-9]{8}$/`
  - I-03: `/^[0-9]{9}$/`
  - I-04: any string ≤ 35 chars
- [ ] Implement CSV import/export
- [ ] Test: CRUD operations, validation, search

#### Phase 2C: Product Catalog
- [ ] Create tenant migration for `products` table
- [ ] Create `Product` Eloquent model with soft deletes
- [ ] Create `ProductController` (full CRUD)
- [ ] Create form requests for validation
- [ ] Create React pages:
  - `Products/Index.tsx` (list + search + filter)
  - `Products/Create.tsx`
  - `Products/Edit.tsx`
  - `Products/Show.tsx`
- [ ] Implement multilingual descriptions (fr, en, ar)
- [ ] Test: CRUD operations

### Phase 3: Invoicing Engine (Weeks 5-7)

#### Phase 3A: OldInvoice CRUD
- [ ] Create tenant migrations: `oldinvoices`, `oldinvoice_lines`, `oldinvoice_tax_lines`, `oldinvoice_allowances`
- [ ] Create Eloquent models: `OldInvoice`, `OldInvoiceLine`, `OldInvoiceTaxLine`, `OldInvoiceAllowance`
- [ ] Define model relationships (hasMany, belongsTo)
- [ ] Create `OldInvoiceController` with all actions
- [ ] Create React pages:
  - `OldInvoices/Index.tsx` (list + status filter + date range + customer filter)
  - `OldInvoices/Create.tsx` (dynamic form with line items)
  - `OldInvoices/Edit.tsx` (only for DRAFT status)
  - `OldInvoices/Show.tsx` (read-only view with all details + actions)
- [ ] Implement dynamic line item form (add/remove/reorder)
- [ ] Implement product autocomplete in line items
- [ ] Auto-fill unit price and TVA rate from product catalog
- [ ] Implement auto-numbering per `oldinvoice_format` setting

#### Phase 3B: Calculation Engine
- [ ] Create `App\Services\OldInvoiceCalculationService`
- [ ] Implement line-level calculation: `net = qty × price - discount`
- [ ] Implement TVA calculation per line
- [ ] Implement oldinvoice totals calculation (HT, TVA, Timbre, TTC)
- [ ] Implement tax summary grouping by rate
- [ ] Implement amount-in-words converter (French) for TND
  - Example: `2.540` → `"DEUX DINARS ET CINQ CENT QUARANTE MILLIMES"`
- [ ] Implement Timbre Fiscal logic (flat amount, configurable)
- [ ] Create unit tests for all calculation scenarios
- [ ] Test: edge cases (0 TVA, multiple rates, discounts, sub-lines)

#### Phase 3C: TEIF XML Generator
- [ ] Create `App\Services\TeifXmlBuilder` service
- [ ] Implement root `<TEIF>` element with version and agency attributes
- [ ] Implement `<OldInvoiceHeader>` builder (sender/receiver MF)
- [ ] Implement `<Bgm>` builder (document ID, type code)
- [ ] Implement `<Dtm>` builder (format dates as `ddMMyy`, `ddMMyyHHmm`)
- [ ] Implement `<PartnerSection>` builder:
  - Seller (I-62) with all references and contacts
  - Buyer (I-64) with all references and contacts
- [ ] Implement `<PytSection>` builder (payment terms, bank details)
- [ ] Implement `<LinSection>` builder (all line items with tax and amounts)
- [ ] Implement `<OldInvoiceMoa>` builder (all oldinvoice-level amounts)
- [ ] Implement `<OldInvoiceTax>` builder (tax summary per rate + timbre fiscal)
- [ ] Implement `<OldInvoiceAlc>` builder (allowances/charges if any)
- [ ] Validate generated XML against XSD schema
- [ ] Create unit tests with expected XML output
- [ ] Test: generate XML → validate against `facture_INVOIC_V1.8.8_withoutSig_xsd`

#### Phase 3D: OldInvoice Status Machine
- [ ] Implement status transitions with Laravel model events
- [ ] Validate → lock oldinvoice, generate XML, store in `teif_xml` column
- [ ] Reject transition from non-DRAFT to edit
- [ ] Implement duplicate oldinvoice action
- [ ] Implement credit note creation from oldinvoice
- [ ] Add status badges in UI (color-coded)
- [ ] Test: full lifecycle DRAFT → VALIDATED → SIGNED → SUBMITTED → ACCEPTED

### Phase 4: Digital Signature & TTN Integration (Weeks 8-10)

#### Phase 4A: Certificate Management
- [ ] Create certificate upload form in Company Settings
- [ ] Implement `.p12` file upload with encrypted storage
- [ ] Parse certificate to extract: subject CN, expiry date, issuer chain
- [ ] Display certificate info in settings UI
- [ ] Validate certificate chain (Tunisia National Root CA → Gov CA → TnTrust → entity)
- [ ] Implement certificate expiry alert (30 days before)
- [ ] Test: upload certificate → verify parsed info

#### Phase 4B: XAdES-BES Signature Implementation
- [ ] Create `App\Services\XadesSignatureService`
- [ ] Implement Exclusive C14N canonicalization of XML content
- [ ] Implement XPath transform: `not(ancestor-or-self::ds:Signature)`
- [ ] Implement XPath transform: `not(ancestor-or-self::RefTtnVal)`
- [ ] Compute SHA-256 digest of canonicalized content
- [ ] Build `xades:SignedProperties`:
  - `SigningTime` (UTC ISO 8601)
  - `SigningCertificateV2` (SHA-1 cert digest + IssuerSerialV2)
  - `SignaturePolicyIdentifier` (OID: `urn:2.16.788.1.2.1`)
  - `SignerRoleV2` (configurable claimed role)
  - `DataObjectFormat` (reference to content)
- [ ] Compute SHA-256 digest of canonicalized SignedProperties
- [ ] Build `ds:SignedInfo` with both references
- [ ] Sign canonicalized SignedInfo with RSA-SHA256 (via openssl)
- [ ] Build complete `ds:Signature` block with X509 certificate chain
- [ ] Insert signature into TEIF document
- [ ] Validate signed XML against `facture_INVOIC_V1.8.8_withSig_xsd`
- [ ] Create comprehensive unit tests
- [ ] Test: sign → verify signature validity

#### Phase 4C: TTN API Client
- [ ] Create `App\Services\TTNApiClient`
- [ ] Implement HTTPS connection to TTN endpoint
- [ ] Implement signed XML submission
- [ ] Implement response parsing (success + RefTtnVal extraction)
- [ ] Implement error response parsing
- [ ] Create `App\Jobs\SubmitOldInvoiceToTTN` queued job
- [ ] Implement retry logic (3 attempts, exponential backoff: 30s, 120s, 300s)
- [ ] Log all requests/responses in `ttn_submission_logs` table
- [ ] Update oldinvoice status based on TTN response
- [ ] Extract and store CEV QR code on success
- [ ] Test: mock TTN API → verify full submission flow

#### Phase 4D: CEV QR Code Handling
- [ ] Decode Base64 `ReferenceCEV` to PNG binary
- [ ] Store QR image file in tenant storage
- [ ] Display QR code in oldinvoice detail view
- [ ] Embed QR code in PDF oldinvoice
- [ ] Display TTN reference number alongside QR code
- [ ] Test: verify QR code display in UI and PDF

### Phase 5: Payments & Inventory (Weeks 11-12)

#### Phase 5A: Payment Tracking
- [ ] Create tenant migration for `payments` table
- [ ] Create `Payment` Eloquent model
- [ ] Create `PaymentController`
- [ ] Implement payment recording form in oldinvoice detail view
- [ ] Auto-calculate remaining balance
- [ ] Update oldinvoice payment status (unpaid, partial, paid)
- [ ] Create payment receipt PDF
- [ ] Test: record payments → verify balance calculation

#### Phase 5B: Inventory Management
- [ ] Create tenant migration for `stock_movements` table
- [ ] Create `StockMovement` model
- [ ] Create `InventoryController`
- [ ] Implement automatic stock deduction on oldinvoice validation
- [ ] Implement automatic stock restoration on credit note
- [ ] Create inventory dashboard page (`Inventory/Index.tsx`)
- [ ] Implement manual stock adjustment
- [ ] Implement low stock alert logic
- [ ] Create stock movement history view
- [ ] Test: oldinvoice validation → verify stock deduction

### Phase 6: Reporting & Dashboard (Weeks 13-14)

#### Phase 6A: Dashboard
- [ ] Create `DashboardController` with aggregated metrics
- [ ] Create `Dashboard/Index.tsx` with:
  - Revenue cards (month, year)
  - Outstanding receivables card
  - Overdue oldinvoices card
  - Recent oldinvoices list
  - Low stock alerts
  - TTN status summary
  - Monthly revenue chart (using recharts or chart.js)
- [ ] Test: verify metric accuracy

#### Phase 6B: Reports
- [ ] Create `ReportController` with report generation methods
- [ ] Implement Sales report (by period, customer, product)
- [ ] Implement TVA declaration report (grouped by rate)
- [ ] Implement Timbre Fiscal report
- [ ] Implement Customer Statement report
- [ ] Implement Aging report (0-30, 31-60, 61-90, 90+ days)
- [ ] Implement PDF export for each report (DomPDF)
- [ ] Implement Excel export for each report (Laravel Excel)
- [ ] Create React report pages with filter forms
- [ ] Test: generate reports with sample data → verify totals

### Phase 7: PDF Generation & Polish (Weeks 15-16)

#### Phase 7A: OldInvoice PDF
- [ ] Design oldinvoice PDF template (Blade or React-PDF)
- [ ] Include: company logo, company info, customer info, line items table
- [ ] Include: tax summary, totals, amount in words
- [ ] Include: payment terms, bank details
- [ ] Include: CEV QR code (if oldinvoice is accepted by TTN)
- [ ] Include: TTN reference number
- [ ] Implement PDF download endpoint
- [ ] Implement email sending with PDF attachment
- [ ] Test: generate PDF → verify layout and data

#### Phase 7B: Final Polish
- [ ] Implement global search
- [ ] Add breadcrumb navigation
- [ ] Add toast notifications for all actions
- [ ] Add confirmation dialogs for destructive actions
- [ ] Implement keyboard shortcuts (Ctrl+S to save, etc.)
- [ ] Add loading states for all async operations
- [ ] Implement responsive design for tablet/mobile
- [ ] Accessibility audit (ARIA labels, keyboard navigation)
- [ ] Performance optimization (lazy loading, query optimization)
- [ ] Test: end-to-end user flow

### Phase 8: Testing & Deployment (Weeks 17-18)

#### Phase 8A: Testing
- [ ] Write feature tests for all controllers (PHPUnit/Pest)
- [ ] Write unit tests for calculation engine
- [ ] Write unit tests for TEIF XML builder
- [ ] Write unit tests for XAdES signature service
- [ ] Write unit tests for Matricule Fiscale validation
- [ ] Write frontend component tests (Vitest + Testing Library)
- [ ] Write E2E tests for critical flows (Cypress or Playwright)
- [ ] Achieve minimum 80% code coverage
- [ ] Manual QA: test full oldinvoice lifecycle with TTN sandbox

#### Phase 8B: Deployment
- [ ] Set up production server (Ubuntu 22.04 + Nginx + PHP-FPM)
- [ ] Configure PostgreSQL in production
- [ ] Configure Redis for queues and caching
- [ ] Set up SSL certificate
- [ ] Configure Laravel Horizon for queue monitoring
- [ ] Set up log aggregation
- [ ] Create CI/CD pipeline (GitHub Actions):
  - Lint (PHP CS Fixer + ESLint)
  - Test (PHPUnit + Vitest)
  - Build (Vite production build)
  - Deploy (SSH/rsync to server)
- [ ] Run database migrations
- [ ] Seed default plans
- [ ] Smoke test in production

---

## 8. Coding Standards

### 8.1 PHP / Laravel Standards

| Rule | Standard |
|------|----------|
| Code Style | **PSR-12** (enforced via `laravel/pint` with PSR-12 preset) |
| Static Analysis | PHPStan Level 6 minimum |
| Type Declarations | Strict types in all files: `declare(strict_types=1);` |
| Return Types | All methods MUST have explicit return types |
| Model Properties | Use `$casts` array for type casting (dates, decimals, JSON) |
| Database | Use migrations, NEVER raw SQL in application code |
| Validation | Use Form Request classes, NEVER inline validation |
| Services | Business logic in Service classes, NOT in controllers |
| Controllers | Thin controllers — delegate to services |
| Naming | Models: singular PascalCase (`OldInvoice`), Tables: plural snake_case (`oldinvoices`) |
| Money | ALWAYS use `NUMERIC(20,3)` — NEVER use `float` for monetary values |
| Dates | Store as `DATE` or `TIMESTAMP`, format `ddMMyy` only when generating XML |
| UUIDs | Use UUIDs for all primary keys (PostgreSQL `gen_random_uuid()`) |

### 8.2 React / TypeScript Standards

| Rule | Standard |
|------|----------|
| Language | **TypeScript** (strict mode) — no `any` types allowed |
| Linting | **ESLint** with `@typescript-eslint` + `eslint-plugin-react` + `eslint-plugin-react-hooks` |
| Formatting | **Prettier** with 2-space indent, single quotes, trailing commas |
| Components | Functional components only, use hooks |
| State | Use React hooks (`useState`, `useReducer`) for local state |
| Forms | Use Inertia.js `useForm` hook for form submissions |
| Types | Define TypeScript interfaces for all props, API responses, form data |
| File Naming | PascalCase for components (`OldInvoiceForm.tsx`), camelCase for utilities |
| Folder Structure | Group by feature/module under `resources/js/Pages/{Module}/` |
| Imports | Absolute imports using `@/` alias for `resources/js/` |

### 8.3 Error Handling Protocol

```
BACKEND:
  - All exceptions handled in App\Exceptions\Handler
  - Custom exception classes for domain errors:
    - TeifValidationException (XML fails XSD validation)
    - SignatureException (certificate or signing failure)
    - TTNSubmissionException (TTN API errors)
    - OldInvoiceStateException (invalid status transition)
  - Log ALL TTN-related errors with full request/response
  - Return user-friendly error messages via Inertia flash
  - NEVER expose stack traces in production

FRONTEND:
  - Wrap all Inertia form submissions in try/catch
  - Display server validation errors inline on form fields
  - Display flash messages as toast notifications
  - Handle network errors with retry prompt
  - Loading states on all buttons during async operations

TTN-SPECIFIC:
  - Timeout: 30 seconds for API calls
  - Retry: 3 attempts with exponential backoff (30s, 120s, 300s)
  - Circuit breaker: after 10 consecutive failures, pause for 1 hour
  - Log every submission attempt in ttn_submission_logs
  - Alert admin on repeated failures
```

### 8.4 File & Folder Structure

```
smart-erp-lite/
├── app/
│   ├── Enums/
│   │   ├── DocumentTypeCode.php
│   │   ├── OldInvoiceStatus.php
│   │   ├── IdentifierType.php
│   │   ├── TaxTypeCode.php
│   │   └── AmountTypeCode.php
│   ├── Exceptions/
│   │   ├── TeifValidationException.php
│   │   ├── SignatureException.php
│   │   ├── TTNSubmissionException.php
│   │   └── OldInvoiceStateException.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   ├── CustomerController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── OldInvoiceController.php
│   │   │   ├── InventoryController.php
│   │   │   ├── PaymentController.php
│   │   │   ├── ProductController.php
│   │   │   ├── ReportController.php
│   │   │   └── SettingsController.php
│   │   ├── Middleware/
│   │   │   └── HandleInertiaRequests.php
│   │   └── Requests/
│   │       ├── StoreCustomerRequest.php
│   │       ├── StoreOldInvoiceRequest.php
│   │       ├── StoreProductRequest.php
│   │       └── UpdateCompanySettingsRequest.php
│   ├── Jobs/
│   │   └── SubmitOldInvoiceToTTN.php
│   ├── Models/
│   │   ├── AuditLog.php
│   │   ├── CompanySetting.php
│   │   ├── Customer.php
│   │   ├── OldInvoice.php
│   │   ├── OldInvoiceAllowance.php
│   │   ├── OldInvoiceLine.php
│   │   ├── OldInvoiceTaxLine.php
│   │   ├── Payment.php
│   │   ├── Product.php
│   │   ├── StockMovement.php
│   │   ├── Tenant.php
│   │   └── TTNSubmissionLog.php
│   ├── Policies/
│   │   ├── CustomerPolicy.php
│   │   ├── OldInvoicePolicy.php
│   │   └── ProductPolicy.php
│   └── Services/
│       ├── OldInvoiceCalculationService.php
│       ├── OldInvoiceNumberingService.php
│       ├── AmountInWordsService.php
│       ├── MatriculeFiscaleValidator.php
│       ├── TeifXmlBuilder.php
│       ├── XadesSignatureService.php
│       ├── TTNApiClient.php
│       └── CertificateManager.php
├── database/
│   ├── migrations/
│   │   ├── landlord/         # Central DB migrations
│   │   │   ├── create_tenants_table.php
│   │   │   ├── create_domains_table.php
│   │   │   └── create_plans_table.php
│   │   └── tenant/           # Per-tenant DB migrations
│   │       ├── create_users_table.php
│   │       ├── create_company_settings_table.php
│   │       ├── create_customers_table.php
│   │       ├── create_products_table.php
│   │       ├── create_stock_movements_table.php
│   │       ├── create_oldinvoices_table.php
│   │       ├── create_oldinvoice_lines_table.php
│   │       ├── create_oldinvoice_tax_lines_table.php
│   │       ├── create_oldinvoice_allowances_table.php
│   │       ├── create_payments_table.php
│   │       ├── create_audit_logs_table.php
│   │       └── create_ttn_submission_logs_table.php
│   └── seeders/
│       ├── PlanSeeder.php
│       └── TenantDatabaseSeeder.php
├── resources/
│   └── js/
│       ├── app.tsx
│       ├── types/
│       │   ├── index.d.ts     # Global TypeScript types
│       │   ├── customer.ts
│       │   ├── oldinvoice.ts
│       │   ├── product.ts
│       │   └── company.ts
│       ├── Components/
│       │   ├── ui/            # Reusable UI components
│       │   │   ├── Button.tsx
│       │   │   ├── Input.tsx
│       │   │   ├── Select.tsx
│       │   │   ├── Table.tsx
│       │   │   ├── Modal.tsx
│       │   │   ├── Badge.tsx
│       │   │   ├── Toast.tsx
│       │   │   └── Pagination.tsx
│       │   ├── OldInvoiceLineForm.tsx
│       │   └── QRCodeDisplay.tsx
│       ├── Layouts/
│       │   ├── AuthenticatedLayout.tsx
│       │   └── GuestLayout.tsx
│       └── Pages/
│           ├── Auth/
│           │   ├── Login.tsx
│           │   └── Register.tsx
│           ├── Dashboard/
│           │   └── Index.tsx
│           ├── Settings/
│           │   └── Edit.tsx
│           ├── Customers/
│           │   ├── Index.tsx
│           │   ├── Create.tsx
│           │   ├── Edit.tsx
│           │   └── Show.tsx
│           ├── Products/
│           │   ├── Index.tsx
│           │   ├── Create.tsx
│           │   ├── Edit.tsx
│           │   └── Show.tsx
│           ├── Inventory/
│           │   ├── Index.tsx
│           │   └── History.tsx
│           ├── OldInvoices/
│           │   ├── Index.tsx
│           │   ├── Create.tsx
│           │   ├── Edit.tsx
│           │   └── Show.tsx
│           ├── Payments/
│           │   └── Index.tsx
│           └── Reports/
│               ├── Sales.tsx
│               ├── TVA.tsx
│               ├── Timbre.tsx
│               ├── CustomerStatement.tsx
│               └── Aging.tsx
├── tests/
│   ├── Feature/
│   │   ├── CustomerTest.php
│   │   ├── OldInvoiceTest.php
│   │   ├── ProductTest.php
│   │   └── TTNSubmissionTest.php
│   └── Unit/
│       ├── OldInvoiceCalculationServiceTest.php
│       ├── TeifXmlBuilderTest.php
│       ├── XadesSignatureServiceTest.php
│       ├── MatriculeFiscaleValidatorTest.php
│       └── AmountInWordsServiceTest.php
└── xsd/
    ├── facture_INVOIC_V1.8.8_withoutSig.xsd
    └── facture_INVOIC_V1.8.8_withSig.xsd
```

---

## 9. Appendix — TEIF Code Reference Tables

### 9.1 Partner Function Codes

| Code | French | English |
|------|--------|---------|
| `I-61` | Acheteur | Buyer |
| `I-62` | Vendeur (Fournisseur) | Seller (Supplier) — **MF validated** |
| `I-63` | Facturé | OldInvoicee |
| `I-64` | Destinataire de la facture | OldInvoiced party |
| `I-65` | Livré à | Delivery party |
| `I-66` | Payeur | Payer |
| `I-67` | Bénéficiaire | Payee |
| `I-68` | Autre | Other |

### 9.2 Identifier Type Codes

| Code | Description | Pattern | Length |
|------|-------------|---------|--------|
| `I-01` | Matricule Fiscale | `[0-9]{7}[ABCDEFGHJKLMNPQRSTVWXYZ][ABDNP][CMNPE][0-9]{3}` | 13 |
| `I-02` | CIN (Carte d'Identité Nationale) | `[0-9]{8}` | 8 |
| `I-03` | Carte de Séjour | `[0-9]{9}` | 9 |
| `I-04` | Autre (Free format) | `.*` | ≤ 35 |

### 9.3 Document Type Codes

| Code | French | English |
|------|--------|---------|
| `I-11` | Facture | OldInvoice |
| `I-12` | Facture rectificative | Corrective oldinvoice |
| `I-13` | Avoir | Credit note |
| `I-14` | Note de débit | Debit note |
| `I-15` | Facture proforma | Proforma oldinvoice |
| `I-16` | Facture d'acompte | Advance oldinvoice |

### 9.4 Date Function Codes

| Code | Description | Format |
|------|-------------|--------|
| `I-31` | OldInvoice date | `ddMMyy` |
| `I-32` | Due date | `ddMMyy` |
| `I-33` | Delivery date | `ddMMyy` |
| `I-34` | Order date | `ddMMyy` |
| `I-35` | Dispatch date | `ddMMyy` |
| `I-36` | Billing period | `ddMMyy-ddMMyy` |
| `I-37` | TTN validation date | `ddMMyyHHmm` |
| `I-38` | Other date | `ddMMyy` |

### 9.5 Tax Type Codes

| Code | French | English |
|------|--------|---------|
| `I-1601` | Droit de timbre | Stamp duty (Timbre Fiscal) |
| `I-1602` | TVA | Value Added Tax |
| `I-1603` | Autre taxe | Other tax |
| `I-161` to `I-169`, `I-160` | Catégories additionnelles | Additional tax categories |

### 9.6 Amount Type Codes

| Code | French | English | Level |
|------|--------|---------|-------|
| `I-171` | Montant net ligne | Line net amount | Line |
| `I-172` | Montant brut ligne | Line gross amount | Line |
| `I-173` | Prix unitaire | Unit price | Line |
| `I-174` | Montant remise | Discount amount | Line |
| `I-175` | Montant charge | Charge amount | Line |
| `I-176` | Total HT | Total excl. tax | OldInvoice |
| `I-177` | Base imposable | Taxable base | Tax |
| `I-178` | Montant taxe | Tax amount | Tax |
| `I-179` | Total brut | Total gross | OldInvoice |
| `I-180` | Total TTC | Total incl. tax | OldInvoice |
| `I-181` | Total TVA | Total VAT | OldInvoice |
| `I-182` | Total net avant remise | Net before discount | OldInvoice |
| `I-183` | Prix unitaire ligne | Line unit price | Line |
| `I-184` | Acompte | Advance payment | OldInvoice |
| `I-185` | Reste à payer | Remaining balance | OldInvoice |
| `I-186` | Total remises | Total allowances | OldInvoice |
| `I-187` | Total charges | Total charges | OldInvoice |
| `I-188` | Arrondi | Rounding | OldInvoice |

### 9.7 Payment Terms Type Codes

| Code | Description |
|------|-------------|
| `I-114` | Bank payment terms |
| `I-115` | Postal payment terms |

### 9.8 Communication Means Codes

| Code | Description |
|------|-------------|
| `I-101` | Phone |
| `I-102` | Fax |
| `I-103` | Email |
| `I-104` | Website |

### 9.9 Allowance/Charge Codes

| Code | Description |
|------|-------------|
| `I-151` | Discount / Remise |
| `I-152` | Charge / Frais |
| `I-153` | Packing / Emballage |
| `I-154` | Transport |
| `I-155` | Other |

### 9.10 Contact Function Codes

| Code | Description |
|------|-------------|
| `I-91` | Technical contact |
| `I-92` | Administrative contact |
| `I-93` | Sales contact |
| `I-94` | General contact |

### 9.11 FII (Financial Institution) Function Codes

| Code | Description |
|------|-------------|
| `I-141` | Ordering party bank (Sender) |
| `I-142` | Beneficiary bank (Receiver) |
| `I-143` | Other financial institution |

### 9.12 Free Text Subject Codes

| Code | Description |
|------|-------------|
| `I-41` | General information |
| `I-42` | Terms and conditions |
| `I-43` | Legal mention |
| `I-44` | Quality specification |
| `I-45` | Packaging information |
| `I-46` | Transport information |
| `I-47` | Payment instruction |
| `I-48` | Other |

### 9.13 Location Function Codes

| Code | Description |
|------|-------------|
| `I-51` | Place of loading |
| `I-52` | Place of delivery |
| `I-53` | Place of discharge |
| `I-54` | Place of payment |
| `I-55` | Warehouse |
| `I-56` | Customs office |
| `I-57` | Port of entry |
| `I-58` | Port of exit |
| `I-59` | Other location |

### 9.14 Reference ID Codes

| Code | Description |
|------|-------------|
| `I-80` | Other reference |
| `I-81` | Matricule Fiscale |
| `I-82` | Purchase order number |
| `I-83` | Delivery note number |
| `I-84` | Contract number |
| `I-85` | Customs declaration number |
| `I-86` | Transport document number |
| `I-87` | Previous oldinvoice reference |
| `I-88` | TTN reference |
| `I-89` | Other external reference |
| `I-811` | Category type |
| `I-812` | Person type (P/M) |
| `I-813` | Tax office |
| `I-814` | Secondary identifier |
| `I-815` | Registre de Commerce |
| `I-816` | Legal form |
| `I-817` | Additional reference |

---

> **END OF BLUEPRINT — This document is the single source of truth. All development decisions must align with this specification.**
