# Elfatoora Invoicing SOAP API Documentation

## Table of Contents

1. [Overview](#overview)
2. [Getting Started](#getting-started)
3. [Authentication](#authentication)
4. [Endpoints](#endpoints)
5. [API Operations](#api-operations)
   - [SubmitInvoice](#1-submitinvoice)
   - [GetInvoice](#2-getinvoice)
   - [ValidateInvoice](#3-validateinvoice)
   - [ListInvoices](#4-listinvoices)
   - [UpdateInvoice](#5-updateinvoice)
   - [DeleteInvoice](#6-deleteinvoice)
   - [SignInvoice](#7-signinvoice)
   - [VerifySignature](#8-verifysignature)
6. [TEIF XML Structure](#teif-xml-structure)
7. [Error Handling](#error-handling)
8. [Code Reference Tables](#code-reference-tables)
9. [SoapUI Integration](#soapui-integration)

---

## Overview

The Elfatoora Invoicing SOAP API provides a standardized interface for submitting, managing, and validating electronic invoices conforming to the Tunisia TradeNet (TTN) TEIF format version 1.8.8.

### Key Features

- **TEIF v1.8.8 Compliance**: Full support for the Tunisia electronic invoicing standard
- **XSD Validation**: Automatic validation against official XSD schemas
- **Digital Signature Support**: XML-DSig signature integration
- **CRUD Operations**: Complete invoice lifecycle management
- **Pagination**: Efficient listing with filtering capabilities

### Service Information

| Property | Value |
|----------|-------|
| Service Name | ElfatooraInvoicingService |
| Namespace | `http://elfatoora.tn/invoicing/v1` |
| SOAP Version | 1.1 |
| Transport | HTTP |
| Binding Style | Document/Literal |

---

## Getting Started

### Prerequisites

1. Laravel application with SOAP extension enabled
2. PHP 8.1+ with `php-soap` extension
3. Running application server (default: `http://localhost:8000`)

### Quick Start

```bash
# Start the Laravel development server
php artisan serve

# Access the WSDL
curl http://localhost:8000/soap/invoicing?wsdl
```

### SoapUI Setup

1. Open SoapUI
2. Create New Project or Import Project
3. Select the provided `elfatoora-soapui-project.xml` file
4. Or import WSDL from: `http://localhost:8000/soap/invoicing?wsdl`

---

## Authentication

The current implementation operates without authentication. For production deployments, implement authentication via:

- SOAP Header credentials
- API key validation
- OAuth 2.0 token-based authentication

---

## Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/soap/invoicing` | GET | Returns WSDL definition |
| `/soap/invoicing` | POST | SOAP operation handler |

### Base URLs

| Environment | URL |
|-------------|-----|
| Development | `http://localhost:8000/soap/invoicing` |
| Production | `https://your-domain.com/soap/invoicing` |

---

## API Operations

### 1. SubmitInvoice

Creates a new invoice from TEIF XML content with XSD validation.

#### Request

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:v1="http://elfatoora.tn/invoicing/v1">
   <soapenv:Header/>
   <soapenv:Body>
      <v1:SubmitInvoiceRequest>
         <v1:teifXml><![CDATA[<!-- TEIF XML content -->]]></v1:teifXml>
         <v1:withSignature>false</v1:withSignature>
      </v1:SubmitInvoiceRequest>
   </soapenv:Body>
</soapenv:Envelope>
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| teifXml | string | Yes | Complete TEIF XML document (CDATA wrapped) |
| withSignature | boolean | Yes | `true` if XML contains digital signature |

#### Happy Path Response

```xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
   <SOAP-ENV:Body>
      <tns:SubmitInvoiceResponse xmlns:tns="http://elfatoora.tn/invoicing/v1">
         <tns:invoiceId>1</tns:invoiceId>
         <tns:status>draft</tns:status>
         <tns:message>Invoice submitted successfully.</tns:message>
      </tns:SubmitInvoiceResponse>
   </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| invoiceId | integer | Unique invoice identifier in the system |
| status | string | Invoice status: `draft`, `signed`, `validated` |
| message | string | Human-readable result message |

#### Error Responses

**XSD Validation Error (CLIENT fault)**
```xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
   <SOAP-ENV:Body>
      <SOAP-ENV:Fault>
         <faultcode>CLIENT</faultcode>
         <faultstring>XSD validation failed: Element 'InvalidElement' is not expected.</faultstring>
      </SOAP-ENV:Fault>
   </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

**Server Error (SERVER fault)**
```xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
   <SOAP-ENV:Body>
      <SOAP-ENV:Fault>
         <faultcode>SERVER</faultcode>
         <faultstring>Internal error: Database connection failed</faultstring>
      </SOAP-ENV:Fault>
   </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

#### Error Scenarios

| Scenario | Fault Code | Error Message |
|----------|------------|---------------|
| Invalid XML structure | CLIENT | XSD validation failed: [details] |
| Missing required elements | CLIENT | XSD validation failed: Element 'X' is required |
| Database error | SERVER | Internal error: [details] |

---

### 2. GetInvoice

Retrieves an invoice by ID and returns its TEIF XML representation.

#### Request

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:v1="http://elfatoora.tn/invoicing/v1">
   <soapenv:Header/>
   <soapenv:Body>
      <v1:GetInvoiceRequest>
         <v1:invoiceId>1</v1:invoiceId>
      </v1:GetInvoiceRequest>
   </soapenv:Body>
</soapenv:Envelope>
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| invoiceId | integer | Yes | Invoice database ID |

#### Happy Path Response

```xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
   <SOAP-ENV:Body>
      <tns:GetInvoiceResponse xmlns:tns="http://elfatoora.tn/invoicing/v1">
         <tns:teifXml><![CDATA[<?xml version="1.0" encoding="UTF-8"?>
<TEIF controlingAgency="TTN" version="1.8.8">
  <!-- Invoice content -->
</TEIF>]]></tns:teifXml>
         <tns:status>draft</tns:status>
      </tns:GetInvoiceResponse>
   </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| teifXml | string | Complete TEIF XML document |
| status | string | Current invoice status |

#### Error Responses

**Invoice Not Found**
```xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
   <SOAP-ENV:Body>
      <SOAP-ENV:Fault>
         <faultcode>CLIENT</faultcode>
         <faultstring>Invoice not found.</faultstring>
      </SOAP-ENV:Fault>
   </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

#### Error Scenarios

| Scenario | Fault Code | Error Message |
|----------|------------|---------------|
| Invoice doesn't exist | CLIENT | Invoice not found. |
| Invalid ID format | CLIENT | Invalid invoice ID |
| Database error | SERVER | Internal error: [details] |

---

### 3. ValidateInvoice

Validates an existing invoice against the XSD schema.

#### Request

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:v1="http://elfatoora.tn/invoicing/v1">
   <soapenv:Header/>
   <soapenv:Body>
      <v1:ValidateInvoiceRequest>
         <v1:invoiceId>1</v1:invoiceId>
      </v1:ValidateInvoiceRequest>
   </soapenv:Body>
</soapenv:Envelope>
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| invoiceId | integer | Yes | Invoice database ID |

#### Happy Path Response (Valid)

```xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
   <SOAP-ENV:Body>
      <tns:ValidateInvoiceResponse xmlns:tns="http://elfatoora.tn/invoicing/v1">
         <tns:valid>true</tns:valid>
         <tns:errors></tns:errors>
      </tns:ValidateInvoiceResponse>
   </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

#### Response with Validation Errors

```xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
   <SOAP-ENV:Body>
      <tns:ValidateInvoiceResponse xmlns:tns="http://elfatoora.tn/invoicing/v1">
         <tns:valid>false</tns:valid>
         <tns:errors>Element 'Amount' is missing required attribute 'currencyIdentifier'
Element 'TaxRate' must be a positive number</tns:errors>
      </tns:ValidateInvoiceResponse>
   </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| valid | boolean | `true` if invoice passes XSD validation |
| errors | string | Newline-separated list of validation errors |

---

### 4. ListInvoices

Retrieves a paginated list of invoices with optional filters.

#### Request

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:v1="http://elfatoora.tn/invoicing/v1">
   <soapenv:Header/>
   <soapenv:Body>
      <v1:ListInvoicesRequest>
         <v1:senderIdentifier>0736202XAM000</v1:senderIdentifier>
         <v1:status>draft</v1:status>
         <v1:page>1</v1:page>
         <v1:perPage>20</v1:perPage>
      </v1:ListInvoicesRequest>
   </soapenv:Body>
</soapenv:Envelope>
```

#### Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| senderIdentifier | string | No | null | Filter by sender TTN identifier |
| status | string | No | null | Filter by status (draft/signed/validated) |
| page | integer | No | 1 | Page number (1-based) |
| perPage | integer | No | 20 | Items per page (max: 100) |

#### Happy Path Response

```xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
   <SOAP-ENV:Body>
      <tns:ListInvoicesResponse xmlns:tns="http://elfatoora.tn/invoicing/v1">
         <tns:invoicesJson>[
           {
             "id": 1,
             "document_identifier": "INV-2026-001",
             "sender_identifier": "0736202XAM000",
             "receiver_identifier": "0914089JAM000",
             "status": "draft",
             "created_at": "2026-02-24T10:30:00Z"
           },
           {
             "id": 2,
             "document_identifier": "INV-2026-002",
             "sender_identifier": "0736202XAM000",
             "receiver_identifier": "0914089JAM000",
             "status": "signed",
             "created_at": "2026-02-24T11:00:00Z"
           }
         ]</tns:invoicesJson>
         <tns:total>25</tns:total>
      </tns:ListInvoicesResponse>
   </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

#### Response Fields

| Field | Type | Description |
|-------|------|-------------|
| invoicesJson | string | JSON array of invoice objects |
| total | integer | Total count of matching invoices |

#### Invoice Object Properties

| Property | Type | Description |
|----------|------|-------------|
| id | integer | Invoice database ID |
| document_identifier | string | Invoice document number |
| sender_identifier | string | Sender TTN identifier |
| receiver_identifier | string | Receiver TTN identifier |
| status | string | Invoice status |
| created_at | datetime | Creation timestamp (ISO 8601) |

---

### 5. UpdateInvoice

Updates an existing invoice with new TEIF XML content. Cannot update validated invoices.

#### Request

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:v1="http://elfatoora.tn/invoicing/v1">
   <soapenv:Header/>
   <soapenv:Body>
      <v1:UpdateInvoiceRequest>
         <v1:invoiceId>1</v1:invoiceId>
         <v1:teifXml><![CDATA[<!-- Updated TEIF XML content -->]]></v1:teifXml>
      </v1:UpdateInvoiceRequest>
   </soapenv:Body>
</soapenv:Envelope>
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| invoiceId | integer | Yes | Invoice database ID |
| teifXml | string | Yes | Updated TEIF XML document |

#### Happy Path Response

```xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
   <SOAP-ENV:Body>
      <tns:UpdateInvoiceResponse xmlns:tns="http://elfatoora.tn/invoicing/v1">
         <tns:success>true</tns:success>
         <tns:message>Invoice updated successfully.</tns:message>
      </tns:UpdateInvoiceResponse>
   </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

#### Error Responses

**Cannot Update Validated Invoice**
```xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
   <SOAP-ENV:Body>
      <SOAP-ENV:Fault>
         <faultcode>CLIENT</faultcode>
         <faultstring>Cannot update a validated invoice.</faultstring>
      </SOAP-ENV:Fault>
   </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

#### Error Scenarios

| Scenario | Fault Code | Error Message |
|----------|------------|---------------|
| Invoice is validated | CLIENT | Cannot update a validated invoice. |
| Invalid XML | CLIENT | XSD validation failed: [details] |
| Invoice not found | CLIENT | Invoice not found. |
| Database error | SERVER | Internal error: [details] |

---

### 6. DeleteInvoice

Soft deletes an invoice. Cannot delete validated invoices.

#### Request

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:v1="http://elfatoora.tn/invoicing/v1">
   <soapenv:Header/>
   <soapenv:Body>
      <v1:DeleteInvoiceRequest>
         <v1:invoiceId>1</v1:invoiceId>
      </v1:DeleteInvoiceRequest>
   </soapenv:Body>
</soapenv:Envelope>
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| invoiceId | integer | Yes | Invoice database ID |

#### Happy Path Response

```xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
   <SOAP-ENV:Body>
      <tns:DeleteInvoiceResponse xmlns:tns="http://elfatoora.tn/invoicing/v1">
         <tns:success>true</tns:success>
         <tns:message>Invoice deleted.</tns:message>
      </tns:DeleteInvoiceResponse>
   </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

#### Error Responses

**Cannot Delete Validated Invoice**
```xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
   <SOAP-ENV:Body>
      <SOAP-ENV:Fault>
         <faultcode>CLIENT</faultcode>
         <faultstring>Cannot delete a validated invoice.</faultstring>
      </SOAP-ENV:Fault>
   </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

#### Error Scenarios

| Scenario | Fault Code | Error Message |
|----------|------------|---------------|
| Invoice is validated | CLIENT | Cannot delete a validated invoice. |
| Invoice not found | CLIENT | Invoice not found. |
| Database error | SERVER | Internal error: [details] |

---

### 7. SignInvoice

Signs an invoice with XML-DSig digital signature. **Note: Not yet fully implemented.**

#### Request

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:v1="http://elfatoora.tn/invoicing/v1">
   <soapenv:Header/>
   <soapenv:Body>
      <v1:SignInvoiceRequest>
         <v1:invoiceId>1</v1:invoiceId>
         <v1:signerRole>supplier</v1:signerRole>
         <v1:privateKeyPem>-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASC...
-----END PRIVATE KEY-----</v1:privateKeyPem>
         <v1:certificatePem>-----BEGIN CERTIFICATE-----
MIIDXTCCAkWgAwIBAgIJAJC1HiIAZAiU...
-----END CERTIFICATE-----</v1:certificatePem>
      </v1:SignInvoiceRequest>
   </soapenv:Body>
</soapenv:Envelope>
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| invoiceId | integer | Yes | Invoice database ID |
| signerRole | string | Yes | Role: `supplier` or `buyer` |
| privateKeyPem | string | Yes | PEM-encoded private key |
| certificatePem | string | Yes | PEM-encoded X.509 certificate |

#### Response (Current Implementation)

```xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
   <SOAP-ENV:Body>
      <tns:SignInvoiceResponse xmlns:tns="http://elfatoora.tn/invoicing/v1">
         <tns:signedXml></tns:signedXml>
         <tns:success>false</tns:success>
         <tns:message>Signing not yet implemented in this demo.</tns:message>
      </tns:SignInvoiceResponse>
   </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

---

### 8. VerifySignature

Verifies the digital signature of a signed invoice. **Note: Not yet fully implemented.**

#### Request

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:v1="http://elfatoora.tn/invoicing/v1">
   <soapenv:Header/>
   <soapenv:Body>
      <v1:VerifySignatureRequest>
         <v1:invoiceId>1</v1:invoiceId>
      </v1:VerifySignatureRequest>
   </soapenv:Body>
</soapenv:Envelope>
```

#### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| invoiceId | integer | Yes | Invoice database ID |

#### Response (Current Implementation)

```xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
   <SOAP-ENV:Body>
      <tns:VerifySignatureResponse xmlns:tns="http://elfatoora.tn/invoicing/v1">
         <tns:valid>false</tns:valid>
         <tns:message>Signature verification not yet implemented.</tns:message>
      </tns:VerifySignatureResponse>
   </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

---

## TEIF XML Structure

### Minimal Invoice Example

```xml
<?xml version="1.0" encoding="UTF-8"?>
<TEIF controlingAgency="TTN" version="1.8.8">
  <OldInvoiceHeader>
    <MessageSenderIdentifier type="I-01">SENDER_TTN_ID</MessageSenderIdentifier>
    <MessageRecieverIdentifier type="I-01">RECEIVER_TTN_ID</MessageRecieverIdentifier>
  </OldInvoiceHeader>
  <OldInvoiceBody>
    <Bgm>
      <DocumentIdentifier>INV-001</DocumentIdentifier>
      <DocumentType code="I-11">Facture</DocumentType>
    </Bgm>
    <Dtm>
      <DateText format="ddMMyy" functionCode="I-31">240226</DateText>
    </Dtm>
    <PartnerSection>
      <PartnerDetails functionCode="I-62">
        <Nad>
          <PartnerIdentifier type="I-01">SENDER_TTN_ID</PartnerIdentifier>
          <PartnerName nameType="Qualification">Sender Name</PartnerName>
          <PartnerAdresses lang="fr">
            <AdressDescription>Address</AdressDescription>
            <Street>Street</Street>
            <CityName>City</CityName>
            <PostalCode>1000</PostalCode>
            <Country codeList="ISO_3166-1">TN</Country>
          </PartnerAdresses>
        </Nad>
      </PartnerDetails>
      <PartnerDetails functionCode="I-64">
        <Nad>
          <PartnerIdentifier type="I-01">RECEIVER_TTN_ID</PartnerIdentifier>
          <PartnerName nameType="Qualification">Receiver Name</PartnerName>
          <PartnerAdresses lang="fr">
            <AdressDescription>Address</AdressDescription>
            <Street>Street</Street>
            <CityName>City</CityName>
            <PostalCode>2000</PostalCode>
            <Country codeList="ISO_3166-1">TN</Country>
          </PartnerAdresses>
        </Nad>
      </PartnerDetails>
    </PartnerSection>
    <LinSection>
      <Lin>
        <ItemIdentifier>1</ItemIdentifier>
        <LinImd lang="fr">
          <ItemCode>ITEM001</ItemCode>
          <ItemDescription>Product Description</ItemDescription>
        </LinImd>
        <LinQty>
          <Quantity measurementUnit="UNIT">1.0</Quantity>
        </LinQty>
        <LinTax>
          <TaxTypeName code="I-1602">TVA</TaxTypeName>
          <TaxDetails>
            <TaxRate>19</TaxRate>
          </TaxDetails>
        </LinTax>
        <LinMoa>
          <MoaDetails>
            <Moa amountTypeCode="I-183" currencyCodeList="ISO_4217">
              <Amount currencyIdentifier="TND">100.000</Amount>
            </Moa>
          </MoaDetails>
        </LinMoa>
      </Lin>
    </LinSection>
    <OldInvoiceMoa>
      <AmountDetails>
        <Moa amountTypeCode="I-176" currencyCodeList="ISO_4217">
          <Amount currencyIdentifier="TND">100.000</Amount>
        </Moa>
      </AmountDetails>
      <AmountDetails>
        <Moa amountTypeCode="I-181" currencyCodeList="ISO_4217">
          <Amount currencyIdentifier="TND">19.000</Amount>
        </Moa>
      </AmountDetails>
      <AmountDetails>
        <Moa amountTypeCode="I-180" currencyCodeList="ISO_4217">
          <Amount currencyIdentifier="TND">119.000</Amount>
        </Moa>
      </AmountDetails>
    </OldInvoiceMoa>
    <OldInvoiceTax>
      <OldInvoiceTaxDetails>
        <Tax>
          <TaxTypeName code="I-1602">TVA</TaxTypeName>
          <TaxDetails>
            <TaxRate>19.0</TaxRate>
          </TaxDetails>
        </Tax>
        <AmountDetails>
          <Moa amountTypeCode="I-177" currencyCodeList="ISO_4217">
            <Amount currencyIdentifier="TND">100.000</Amount>
          </Moa>
        </AmountDetails>
        <AmountDetails>
          <Moa amountTypeCode="I-178" currencyCodeList="ISO_4217">
            <Amount currencyIdentifier="TND">19.000</Amount>
          </Moa>
        </AmountDetails>
      </OldInvoiceTaxDetails>
    </OldInvoiceTax>
  </OldInvoiceBody>
</TEIF>
```

### TEIF Elements Overview

| Element | Description | Required |
|---------|-------------|----------|
| TEIF | Root element with version and controlling agency | Yes |
| OldInvoiceHeader | Message routing information | Yes |
| OldInvoiceBody | Invoice content | Yes |
| Bgm | Business message header | Yes |
| Dtm | Date/time section | Yes |
| PartnerSection | Sender and receiver information | Yes |
| LinSection | Invoice line items | Yes |
| OldInvoiceMoa | Invoice amounts summary | Yes |
| OldInvoiceTax | Tax breakdown | Yes |
| ds:Signature | Digital signature (if signed) | No |

---

## Error Handling

### SOAP Fault Structure

```xml
<SOAP-ENV:Fault>
   <faultcode>CLIENT|SERVER</faultcode>
   <faultstring>Error message description</faultstring>
</SOAP-ENV:Fault>
```

### Fault Codes

| Code | Description | Cause |
|------|-------------|-------|
| CLIENT | Client-side error | Invalid input, validation failure, resource not found |
| SERVER | Server-side error | Database failure, internal processing error |

### Common Error Scenarios

| Operation | Error | Fault Code | Message |
|-----------|-------|------------|---------|
| SubmitInvoice | Invalid XML | CLIENT | XSD validation failed: [details] |
| GetInvoice | Not found | CLIENT | Invoice not found. |
| UpdateInvoice | Already validated | CLIENT | Cannot update a validated invoice. |
| UpdateInvoice | Invalid XML | CLIENT | XSD validation failed: [details] |
| DeleteInvoice | Already validated | CLIENT | Cannot delete a validated invoice. |
| DeleteInvoice | Not found | CLIENT | Invoice not found. |
| Any | Database error | SERVER | Internal error: [details] |

---

## Code Reference Tables

### Document Type Codes

| Code | Name | Description |
|------|------|-------------|
| I-11 | Facture | Standard invoice |
| I-12 | Facture rectificative | Corrective invoice |
| I-13 | Avoir | Credit note |
| I-14 | Note de débit | Debit note |
| I-15 | Facture proforma | Proforma invoice |
| I-16 | Facture d'acompte | Advance payment invoice |

### Identifier Types

| Code | Description |
|------|-------------|
| I-01 | TTN Identifier |

### Partner Function Codes

| Code | Description |
|------|-------------|
| I-62 | Supplier (Sender) |
| I-64 | Buyer (Receiver) |

### Tax Type Codes

| Code | Name |
|------|------|
| I-1601 | Droit de timbre (Stamp duty) |
| I-1602 | TVA (Value Added Tax) |

### Amount Type Codes

| Code | Description |
|------|-------------|
| I-176 | Taxable amount |
| I-177 | Tax base amount |
| I-178 | Tax amount |
| I-179 | Grand total (cents) |
| I-180 | Grand total (with tax) |
| I-181 | Total tax amount |
| I-182 | Net amount |
| I-183 | Line amount |

### Date Function Codes

| Code | Description |
|------|-------------|
| I-31 | Invoice date |
| I-32 | Due date |
| I-36 | Period covered |

### Invoice Status Values

| Status | Description |
|--------|-------------|
| draft | Invoice created, not yet signed |
| signed | Invoice has digital signature |
| validated | Invoice validated by TTN system |

---

## SoapUI Integration

### Importing the Project

1. Open SoapUI (5.7.0 or later recommended)
2. File → Import Project
3. Select `docs/soapui/elfatoora-soapui-project.xml`
4. The project includes:
   - All 8 operations pre-configured
   - Happy path request examples
   - Error scenario requests
   - Test suite for CRUD operations

### Project Structure

```
Elfatoora Invoicing API
├── ElfatooraPortType
│   ├── SubmitInvoice
│   │   ├── Happy Path
│   │   └── Invalid XML
│   ├── GetInvoice
│   │   ├── Happy Path
│   │   └── Not Found
│   ├── ValidateInvoice
│   │   └── Happy Path
│   ├── ListInvoices
│   │   ├── All
│   │   ├── Filter by Sender
│   │   └── Filter by Status
│   ├── UpdateInvoice
│   │   ├── Happy Path
│   │   └── Cannot Update Validated
│   ├── DeleteInvoice
│   │   ├── Happy Path
│   │   └── Cannot Delete Validated
│   ├── SignInvoice
│   │   └── Request
│   └── VerifySignature
│       └── Request
└── Test Suite
    └── Invoice CRUD Operations
        ├── 1. Submit Invoice
        ├── 2. Get Invoice
        ├── 3. Validate Invoice
        ├── 4. List Invoices
        ├── 5. Update Invoice
        └── 6. Delete Invoice
```

### Running Tests

1. Right-click on "Elfatoora API Test Suite"
2. Select "Run"
3. View results in the TestRunner window

### Customizing Endpoint

To change the endpoint URL:
1. Double-click on the interface "ElfatooraPortType"
2. Go to Service Endpoints tab
3. Modify the endpoint URL

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2026-02-24 | Initial release |

---

## Contact & Support

For issues or questions related to this API, contact the development team.

---

*This documentation is generated for Smart ERP Elfatoora Integration v1.0*
