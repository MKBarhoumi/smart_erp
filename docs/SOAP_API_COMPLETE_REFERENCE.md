# Elfatoora SOAP API - Complete Technical Reference

> **Version:** 1.0.0  
> **Last Updated:** February 27, 2026  
> **TEIF Specification:** v1.8.8  
> **Compliance:** Tunisia TradeNet (TTN)

---

## Table of Contents

1. [Service Overview](#1-service-overview)
2. [Technical Specifications](#2-technical-specifications)
3. [Connection Details](#3-connection-details)
4. [Invoice Lifecycle & Status Flow](#4-invoice-lifecycle--status-flow)
5. [API Operations Reference](#5-api-operations-reference)
   - [5.1 SubmitInvoice](#51-submitinvoice)
   - [5.2 GetInvoice](#52-getinvoice)
   - [5.3 ValidateInvoice](#53-validateinvoice)
   - [5.4 ListInvoices](#54-listinvoices)
   - [5.5 UpdateInvoice](#55-updateinvoice)
   - [5.6 DeleteInvoice](#56-deleteinvoice)
   - [5.7 SignInvoice](#57-signinvoice)
   - [5.8 VerifySignature](#58-verifysignature)
6. [TEIF XML Schema Reference](#6-teif-xml-schema-reference)
7. [Code Reference Tables](#7-code-reference-tables)
8. [Error Handling Reference](#8-error-handling-reference)
9. [Data Types & Validation Rules](#9-data-types--validation-rules)
10. [SoapUI Testing Guide](#10-soapui-testing-guide)

---

## 1. Service Overview

### 1.1 Purpose

The Elfatoora Invoicing SOAP API provides a standardized interface for electronic invoice management conforming to the Tunisia TradeNet (TTN) TEIF format version 1.8.8.

### 1.2 Key Capabilities

| Capability | Description |
|------------|-------------|
| Invoice Submission | Create new invoices with TEIF XML validation |
| Invoice Retrieval | Fetch invoice data by ID or with filters |
| XSD Validation | Validate invoices against official TTN schemas |
| Digital Signatures | XML-DSig signature support (planned) |
| Lifecycle Management | Full CRUD operations with status tracking |
| Pagination | Efficient data retrieval with filtering |

### 1.3 Supported Operations Summary

| Operation | Method | Description | Implementation Status |
|-----------|--------|-------------|----------------------|
| SubmitInvoice | POST | Create new invoice | ✅ Fully Implemented |
| GetInvoice | POST | Retrieve invoice by ID | ✅ Fully Implemented |
| ValidateInvoice | POST | Validate against XSD | ✅ Fully Implemented |
| ListInvoices | POST | List invoices with filters | ✅ Fully Implemented |
| UpdateInvoice | POST | Update existing invoice | ✅ Fully Implemented |
| DeleteInvoice | POST | Soft delete invoice | ✅ Fully Implemented |
| SignInvoice | POST | Apply digital signature | ⏳ Not Yet Implemented |
| VerifySignature | POST | Verify digital signature | ⏳ Not Yet Implemented |

---

## 2. Technical Specifications

### 2.1 Service Properties

| Property | Value |
|----------|-------|
| Service Name | `ElfatooraInvoicingService` |
| XML Namespace | `http://elfatoora.tn/invoicing/v1` |
| Namespace Prefix | `tns` or `v1` |
| SOAP Version | 1.1 |
| Transport Protocol | HTTP/HTTPS |
| Binding Style | Document/Literal |
| Character Encoding | UTF-8 |
| Maximum Request Size | 10 MB |

### 2.2 Required Software Stack

| Component | Requirement |
|-----------|-------------|
| PHP Version | 8.1 or higher |
| PHP Extension | `php-soap` (enabled) |
| Framework | Laravel |
| XML Support | libxml2 |

### 2.3 WSDL Access

```
GET {base_url}/soap/invoicing?wsdl
Content-Type: text/xml
```

---

## 3. Connection Details

### 3.1 Environment URLs

| Environment | Base URL | WSDL URL |
|-------------|----------|----------|
| Development | `http://localhost:8000` | `http://localhost:8000/soap/invoicing?wsdl` |
| Staging | `https://staging.your-domain.com` | `https://staging.your-domain.com/soap/invoicing?wsdl` |
| Production | `https://your-domain.com` | `https://your-domain.com/soap/invoicing?wsdl` |

### 3.2 Endpoints

| Endpoint Path | HTTP Method | Purpose |
|---------------|-------------|---------|
| `/soap/invoicing` | GET | Returns WSDL definition document |
| `/soap/invoicing` | POST | Handles all SOAP operation requests |

### 3.3 Authentication

**Current Implementation:** No authentication required (development mode)

**Production Recommendations:**
- SOAP Header credentials (WS-Security)
- API key validation via custom headers
- OAuth 2.0 token-based authentication
- Client certificate authentication (mutual TLS)

### 3.4 Request Headers

| Header | Value | Required |
|--------|-------|----------|
| Content-Type | `text/xml; charset=utf-8` | Yes |
| SOAPAction | Operation-specific (often empty) | Optional |

---

## 4. Invoice Lifecycle & Status Flow

### 4.1 Invoice Status Values

| Status | Description | Allowed Operations |
|--------|-------------|-------------------|
| `draft` | Invoice created, not yet signed | Update, Delete, Sign, Validate |
| `signed` | Invoice has digital signature applied | Validate, Verify |
| `validated` | Invoice validated by TTN system | Read-only (no modifications) |

### 4.2 Status Transition Diagram

```
┌─────────────────────────────────────────────────────────┐
│                                                         │
│   ┌─────────┐    SignInvoice    ┌─────────┐            │
│   │  draft  │ ────────────────► │ signed  │            │
│   └─────────┘                   └─────────┘            │
│        │                             │                  │
│        │ TTN Validation              │ TTN Validation   │
│        ▼                             ▼                  │
│   ┌──────────────────────────────────────┐             │
│   │             validated                 │             │
│   └──────────────────────────────────────┘             │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### 4.3 Status-Based Operation Restrictions

| Operation | draft | signed | validated |
|-----------|-------|--------|-----------|
| GetInvoice | ✅ | ✅ | ✅ |
| ValidateInvoice | ✅ | ✅ | ✅ |
| UpdateInvoice | ✅ | ✅ | ❌ Blocked |
| DeleteInvoice | ✅ | ✅ | ❌ Blocked |
| SignInvoice | ✅ | ❌ (already signed) | ❌ |
| ListInvoices | ✅ | ✅ | ✅ |

---

## 5. API Operations Reference

### 5.1 SubmitInvoice

Creates a new invoice from TEIF XML content with automatic XSD validation.

#### 5.1.1 Operation Details

| Property | Value |
|----------|-------|
| Operation Name | `SubmitInvoice` |
| SOAP Action | `http://elfatoora.tn/invoicing/v1/SubmitInvoice` |
| Request Element | `SubmitInvoiceRequest` |
| Response Element | `SubmitInvoiceResponse` |

#### 5.1.2 Request Parameters

| Parameter | XML Element | Data Type | Required | Description | Constraints |
|-----------|-------------|-----------|----------|-------------|-------------|
| teifXml | `<v1:teifXml>` | string | **Yes** | Complete TEIF XML document | Must be valid XML, wrapped in CDATA |
| withSignature | `<v1:withSignature>` | boolean | **Yes** | Indicates if XML contains signature | `true` or `false` |

#### 5.1.3 Request XML Structure

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:v1="http://elfatoora.tn/invoicing/v1">
   <soapenv:Header/>
   <soapenv:Body>
      <v1:SubmitInvoiceRequest>
         <v1:teifXml><![CDATA[<?xml version="1.0" encoding="UTF-8"?>
<TEIF controlingAgency="TTN" version="1.8.8">
  <!-- Complete TEIF XML content here -->
</TEIF>]]></v1:teifXml>
         <v1:withSignature>false</v1:withSignature>
      </v1:SubmitInvoiceRequest>
   </soapenv:Body>
</soapenv:Envelope>
```

#### 5.1.4 Response Parameters

| Parameter | XML Element | Data Type | Description | Possible Values |
|-----------|-------------|-----------|-------------|-----------------|
| invoiceId | `<tns:invoiceId>` | integer | System-generated unique identifier | Positive integer (e.g., 1, 2, 3...) |
| status | `<tns:status>` | string | Invoice status after creation | `draft` (always for new submissions) |
| message | `<tns:message>` | string | Human-readable result message | Success or error description |

#### 5.1.5 Success Response

**HTTP Status:** 200 OK

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

#### 5.1.6 Error Responses

**XSD Validation Error:**

| Property | Value |
|----------|-------|
| Fault Code | `CLIENT` |
| Fault String Pattern | `XSD validation failed: [specific error details]` |

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

**Server Error:**

| Property | Value |
|----------|-------|
| Fault Code | `SERVER` |
| Fault String Pattern | `Internal error: [error details]` |

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

#### 5.1.7 Error Scenarios Summary

| Scenario | Fault Code | Error Message | Resolution |
|----------|------------|---------------|------------|
| Invalid XML structure | `CLIENT` | XSD validation failed: [details] | Fix XML syntax errors |
| Missing required elements | `CLIENT` | XSD validation failed: Element 'X' is required | Add missing elements |
| Invalid element values | `CLIENT` | XSD validation failed: [validation details] | Correct element values |
| Database connection failure | `SERVER` | Internal error: Database connection failed | Contact administrator |
| Internal processing error | `SERVER` | Internal error: [details] | Contact administrator |

---

### 5.2 GetInvoice

Retrieves an invoice by its database ID and returns the TEIF XML representation.

#### 5.2.1 Operation Details

| Property | Value |
|----------|-------|
| Operation Name | `GetInvoice` |
| SOAP Action | `http://elfatoora.tn/invoicing/v1/GetInvoice` |
| Request Element | `GetInvoiceRequest` |
| Response Element | `GetInvoiceResponse` |

#### 5.2.2 Request Parameters

| Parameter | XML Element | Data Type | Required | Description | Constraints |
|-----------|-------------|-----------|----------|-------------|-------------|
| invoiceId | `<v1:invoiceId>` | integer | **Yes** | Invoice database identifier | Positive integer |

#### 5.2.3 Request XML Structure

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

#### 5.2.4 Response Parameters

| Parameter | XML Element | Data Type | Description | Notes |
|-----------|-------------|-----------|-------------|-------|
| teifXml | `<tns:teifXml>` | string | Complete TEIF XML document | CDATA wrapped |
| status | `<tns:status>` | string | Current invoice status | `draft`, `signed`, or `validated` |

#### 5.2.5 Success Response

**HTTP Status:** 200 OK

```xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
   <SOAP-ENV:Body>
      <tns:GetInvoiceResponse xmlns:tns="http://elfatoora.tn/invoicing/v1">
         <tns:teifXml><![CDATA[<?xml version="1.0" encoding="UTF-8"?>
<TEIF controlingAgency="TTN" version="1.8.8">
  <!-- Complete invoice content -->
</TEIF>]]></tns:teifXml>
         <tns:status>draft</tns:status>
      </tns:GetInvoiceResponse>
   </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

#### 5.2.6 Error Responses

**Invoice Not Found:**

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

#### 5.2.7 Error Scenarios Summary

| Scenario | Fault Code | Error Message | Resolution |
|----------|------------|---------------|------------|
| Invoice doesn't exist | `CLIENT` | Invoice not found. | Verify invoice ID exists |
| Invalid ID format | `CLIENT` | Invalid invoice ID | Use positive integer |
| Database error | `SERVER` | Internal error: [details] | Contact administrator |

---

### 5.3 ValidateInvoice

Validates an existing invoice against the official XSD schema.

#### 5.3.1 Operation Details

| Property | Value |
|----------|-------|
| Operation Name | `ValidateInvoice` |
| SOAP Action | `http://elfatoora.tn/invoicing/v1/ValidateInvoice` |
| Request Element | `ValidateInvoiceRequest` |
| Response Element | `ValidateInvoiceResponse` |

#### 5.3.2 Request Parameters

| Parameter | XML Element | Data Type | Required | Description | Constraints |
|-----------|-------------|-----------|----------|-------------|-------------|
| invoiceId | `<v1:invoiceId>` | integer | **Yes** | Invoice database identifier | Positive integer |

#### 5.3.3 Request XML Structure

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

#### 5.3.4 Response Parameters

| Parameter | XML Element | Data Type | Description | Possible Values |
|-----------|-------------|-----------|-------------|-----------------|
| valid | `<tns:valid>` | boolean | Validation result | `true` = valid, `false` = invalid |
| errors | `<tns:errors>` | string | Validation error details | Empty if valid, newline-separated errors if invalid |

#### 5.3.5 Success Response (Valid Invoice)

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

#### 5.3.6 Response with Validation Errors

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

#### 5.3.7 Error Response Example

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

---

### 5.4 ListInvoices

Retrieves a paginated list of invoices with optional filters.

#### 5.4.1 Operation Details

| Property | Value |
|----------|-------|
| Operation Name | `ListInvoices` |
| SOAP Action | `http://elfatoora.tn/invoicing/v1/ListInvoices` |
| Request Element | `ListInvoicesRequest` |
| Response Element | `ListInvoicesResponse` |

#### 5.4.2 Request Parameters

| Parameter | XML Element | Data Type | Required | Default | Description | Constraints |
|-----------|-------------|-----------|----------|---------|-------------|-------------|
| senderIdentifier | `<v1:senderIdentifier>` | string | No | `null` | Filter by sender TTN ID | TTN identifier format |
| status | `<v1:status>` | string | No | `null` | Filter by invoice status | `draft`, `signed`, or `validated` |
| page | `<v1:page>` | integer | No | `1` | Page number | Minimum: 1 |
| perPage | `<v1:perPage>` | integer | No | `20` | Items per page | Minimum: 1, Maximum: 100 |

#### 5.4.3 Request XML Structure

**Full Request (with all filters):**

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

**Minimal Request (defaults applied):**

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:v1="http://elfatoora.tn/invoicing/v1">
   <soapenv:Header/>
   <soapenv:Body>
      <v1:ListInvoicesRequest>
      </v1:ListInvoicesRequest>
   </soapenv:Body>
</soapenv:Envelope>
```

#### 5.4.4 Response Parameters

| Parameter | XML Element | Data Type | Description |
|-----------|-------------|-----------|-------------|
| invoicesJson | `<tns:invoicesJson>` | string | JSON array of invoice objects |
| total | `<tns:total>` | integer | Total count of matching invoices |

#### 5.4.5 Invoice Object Properties (in JSON)

| Property | Data Type | Description | Example |
|----------|-----------|-------------|---------|
| id | integer | Invoice database ID | `1` |
| document_identifier | string | Invoice document number | `"INV-2026-001"` |
| sender_identifier | string | Sender TTN identifier | `"0736202XAM000"` |
| receiver_identifier | string | Receiver TTN identifier | `"0914089JAM000"` |
| status | string | Invoice status | `"draft"` |
| created_at | datetime | Creation timestamp (ISO 8601) | `"2026-02-24T10:30:00Z"` |

#### 5.4.6 Success Response

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

#### 5.4.7 Empty Result Response

```xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
   <SOAP-ENV:Body>
      <tns:ListInvoicesResponse xmlns:tns="http://elfatoora.tn/invoicing/v1">
         <tns:invoicesJson>[]</tns:invoicesJson>
         <tns:total>0</tns:total>
      </tns:ListInvoicesResponse>
   </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

---

### 5.5 UpdateInvoice

Updates an existing invoice with new TEIF XML content.

#### 5.5.1 Operation Details

| Property | Value |
|----------|-------|
| Operation Name | `UpdateInvoice` |
| SOAP Action | `http://elfatoora.tn/invoicing/v1/UpdateInvoice` |
| Request Element | `UpdateInvoiceRequest` |
| Response Element | `UpdateInvoiceResponse` |

#### 5.5.2 Operation Restrictions

| Condition | Allowed | Error Message |
|-----------|---------|---------------|
| Invoice status = `draft` | ✅ Yes | - |
| Invoice status = `signed` | ✅ Yes | - |
| Invoice status = `validated` | ❌ No | Cannot update a validated invoice. |

#### 5.5.3 Request Parameters

| Parameter | XML Element | Data Type | Required | Description | Constraints |
|-----------|-------------|-----------|----------|-------------|-------------|
| invoiceId | `<v1:invoiceId>` | integer | **Yes** | Invoice database identifier | Positive integer, must exist |
| teifXml | `<v1:teifXml>` | string | **Yes** | Updated TEIF XML document | Must pass XSD validation |

#### 5.5.4 Request XML Structure

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:v1="http://elfatoora.tn/invoicing/v1">
   <soapenv:Header/>
   <soapenv:Body>
      <v1:UpdateInvoiceRequest>
         <v1:invoiceId>1</v1:invoiceId>
         <v1:teifXml><![CDATA[<?xml version="1.0" encoding="UTF-8"?>
<TEIF controlingAgency="TTN" version="1.8.8">
  <!-- Updated TEIF XML content -->
</TEIF>]]></v1:teifXml>
      </v1:UpdateInvoiceRequest>
   </soapenv:Body>
</soapenv:Envelope>
```

#### 5.5.5 Response Parameters

| Parameter | XML Element | Data Type | Description | Possible Values |
|-----------|-------------|-----------|-------------|-----------------|
| success | `<tns:success>` | boolean | Operation result | `true` or `false` |
| message | `<tns:message>` | string | Result description | Success or error message |

#### 5.5.6 Success Response

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

#### 5.5.7 Error Responses

**Cannot Update Validated Invoice:**

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

**XSD Validation Error:**

```xml
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
   <SOAP-ENV:Body>
      <SOAP-ENV:Fault>
         <faultcode>CLIENT</faultcode>
         <faultstring>XSD validation failed: Element 'Amount' is missing.</faultstring>
      </SOAP-ENV:Fault>
   </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

#### 5.5.8 Error Scenarios Summary

| Scenario | Fault Code | Error Message | Resolution |
|----------|------------|---------------|------------|
| Invoice is validated | `CLIENT` | Cannot update a validated invoice. | Create new invoice instead |
| Invalid XML | `CLIENT` | XSD validation failed: [details] | Fix XML content |
| Invoice not found | `CLIENT` | Invoice not found. | Verify invoice ID |
| Database error | `SERVER` | Internal error: [details] | Contact administrator |

---

### 5.6 DeleteInvoice

Performs a soft delete on an invoice.

#### 5.6.1 Operation Details

| Property | Value |
|----------|-------|
| Operation Name | `DeleteInvoice` |
| SOAP Action | `http://elfatoora.tn/invoicing/v1/DeleteInvoice` |
| Request Element | `DeleteInvoiceRequest` |
| Response Element | `DeleteInvoiceResponse` |
| Delete Type | Soft delete (data preserved, marked as deleted) |

#### 5.6.2 Operation Restrictions

| Condition | Allowed | Error Message |
|-----------|---------|---------------|
| Invoice status = `draft` | ✅ Yes | - |
| Invoice status = `signed` | ✅ Yes | - |
| Invoice status = `validated` | ❌ No | Cannot delete a validated invoice. |

#### 5.6.3 Request Parameters

| Parameter | XML Element | Data Type | Required | Description | Constraints |
|-----------|-------------|-----------|----------|-------------|-------------|
| invoiceId | `<v1:invoiceId>` | integer | **Yes** | Invoice database identifier | Positive integer, must exist |

#### 5.6.4 Request XML Structure

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

#### 5.6.5 Response Parameters

| Parameter | XML Element | Data Type | Description | Possible Values |
|-----------|-------------|-----------|-------------|-----------------|
| success | `<tns:success>` | boolean | Operation result | `true` or `false` |
| message | `<tns:message>` | string | Result description | Success or error message |

#### 5.6.6 Success Response

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

#### 5.6.7 Error Responses

**Cannot Delete Validated Invoice:**

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

**Invoice Not Found:**

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

#### 5.6.8 Error Scenarios Summary

| Scenario | Fault Code | Error Message | Resolution |
|----------|------------|---------------|------------|
| Invoice is validated | `CLIENT` | Cannot delete a validated invoice. | Validated invoices cannot be deleted |
| Invoice not found | `CLIENT` | Invoice not found. | Verify invoice ID |
| Database error | `SERVER` | Internal error: [details] | Contact administrator |

---

### 5.7 SignInvoice

Applies XML-DSig digital signature to an invoice.

> **⚠️ Implementation Status:** Not yet fully implemented. Returns placeholder response.

#### 5.7.1 Operation Details

| Property | Value |
|----------|-------|
| Operation Name | `SignInvoice` |
| SOAP Action | `http://elfatoora.tn/invoicing/v1/SignInvoice` |
| Request Element | `SignInvoiceRequest` |
| Response Element | `SignInvoiceResponse` |
| Implementation Status | **Pending** |

#### 5.7.2 Request Parameters

| Parameter | XML Element | Data Type | Required | Description | Constraints |
|-----------|-------------|-----------|----------|-------------|-------------|
| invoiceId | `<v1:invoiceId>` | integer | **Yes** | Invoice database identifier | Positive integer |
| signerRole | `<v1:signerRole>` | string | **Yes** | Role of the signer | `supplier` or `buyer` |
| privateKeyPem | `<v1:privateKeyPem>` | string | **Yes** | PEM-encoded private key | Valid PEM format |
| certificatePem | `<v1:certificatePem>` | string | **Yes** | PEM-encoded X.509 certificate | Valid PEM format |

#### 5.7.3 Signer Role Values

| Role | Description |
|------|-------------|
| `supplier` | Invoice issuer/seller |
| `buyer` | Invoice recipient/purchaser |

#### 5.7.4 Request XML Structure

```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:v1="http://elfatoora.tn/invoicing/v1">
   <soapenv:Header/>
   <soapenv:Body>
      <v1:SignInvoiceRequest>
         <v1:invoiceId>1</v1:invoiceId>
         <v1:signerRole>supplier</v1:signerRole>
         <v1:privateKeyPem>-----BEGIN PRIVATE KEY-----
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQC...
-----END PRIVATE KEY-----</v1:privateKeyPem>
         <v1:certificatePem>-----BEGIN CERTIFICATE-----
MIIDXTCCAkWgAwIBAgIJAJC1HiIAZAiUMBYGA1UEAwwPdGVzdC5...
-----END CERTIFICATE-----</v1:certificatePem>
      </v1:SignInvoiceRequest>
   </soapenv:Body>
</soapenv:Envelope>
```

#### 5.7.5 Response Parameters

| Parameter | XML Element | Data Type | Description |
|-----------|-------------|-----------|-------------|
| signedXml | `<tns:signedXml>` | string | Signed TEIF XML (empty if failed) |
| success | `<tns:success>` | boolean | Operation result |
| message | `<tns:message>` | string | Result description |

#### 5.7.6 Current Response (Not Implemented)

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

### 5.8 VerifySignature

Verifies the digital signature of a signed invoice.

> **⚠️ Implementation Status:** Not yet fully implemented. Returns placeholder response.

#### 5.8.1 Operation Details

| Property | Value |
|----------|-------|
| Operation Name | `VerifySignature` |
| SOAP Action | `http://elfatoora.tn/invoicing/v1/VerifySignature` |
| Request Element | `VerifySignatureRequest` |
| Response Element | `VerifySignatureResponse` |
| Implementation Status | **Pending** |

#### 5.8.2 Request Parameters

| Parameter | XML Element | Data Type | Required | Description | Constraints |
|-----------|-------------|-----------|----------|-------------|-------------|
| invoiceId | `<v1:invoiceId>` | integer | **Yes** | Invoice database identifier | Positive integer |

#### 5.8.3 Request XML Structure

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

#### 5.8.4 Response Parameters

| Parameter | XML Element | Data Type | Description |
|-----------|-------------|-----------|-------------|
| valid | `<tns:valid>` | boolean | Signature validity |
| message | `<tns:message>` | string | Verification result details |

#### 5.8.5 Current Response (Not Implemented)

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

## 6. TEIF XML Schema Reference

### 6.1 Schema Files

| Schema File | Purpose | Signature |
|-------------|---------|-----------|
| `facture_INVOIC_V1.8.8_withoutSig.xsd` | Validates invoices without digital signature | No |
| `facture_INVOIC_V1.8.8_withSig.xsd` | Validates invoices with digital signature | Yes |

### 6.2 Root Element Structure

```xml
<?xml version="1.0" encoding="UTF-8"?>
<TEIF controlingAgency="TTN" version="1.8.8">
  <OldInvoiceHeader>...</OldInvoiceHeader>
  <OldInvoiceBody>...</OldInvoiceBody>
  <ds:Signature>...</ds:Signature>  <!-- Optional: Only for signed invoices -->
</TEIF>
```

### 6.3 Root Element Attributes

| Attribute | Required | Value | Description |
|-----------|----------|-------|-------------|
| controlingAgency | **Yes** | `TTN` | Tunisia TradeNet controlling agency |
| version | **Yes** | `1.8.8` | TEIF specification version |

### 6.4 Complete Element Hierarchy

```
TEIF
├── OldInvoiceHeader (Required)
│   ├── MessageSenderIdentifier (Required)
│   │   └── @type (Required: "I-01")
│   └── MessageRecieverIdentifier (Required)
│       └── @type (Required: "I-01")
│
├── OldInvoiceBody (Required)
│   ├── Bgm (Required) - Business Message Header
│   │   ├── DocumentIdentifier (Required)
│   │   └── DocumentType (Required)
│   │       └── @code (Required)
│   │
│   ├── Dtm (Required) - Date/Time
│   │   └── DateText (Required)
│   │       ├── @format (Required: "ddMMyy")
│   │       └── @functionCode (Required)
│   │
│   ├── PartnerSection (Required)
│   │   └── PartnerDetails (Required, multiple)
│   │       ├── @functionCode (Required: "I-62" or "I-64")
│   │       └── Nad (Required)
│   │           ├── PartnerIdentifier (Required)
│   │           │   └── @type (Required: "I-01")
│   │           ├── PartnerName (Required)
│   │           │   └── @nameType (Required)
│   │           └── PartnerAdresses (Required)
│   │               ├── @lang (Required)
│   │               ├── AdressDescription (Required)
│   │               ├── Street (Required)
│   │               ├── CityName (Required)
│   │               ├── PostalCode (Required)
│   │               └── Country (Required)
│   │                   └── @codeList (Required: "ISO_3166-1")
│   │
│   ├── LinSection (Required) - Line Items
│   │   └── Lin (Required, multiple)
│   │       ├── ItemIdentifier (Required)
│   │       ├── LinImd (Required)
│   │       │   ├── @lang (Required)
│   │       │   ├── ItemCode (Required)
│   │       │   └── ItemDescription (Required)
│   │       ├── LinQty (Required)
│   │       │   └── Quantity (Required)
│   │       │       └── @measurementUnit (Required)
│   │       ├── LinTax (Required)
│   │       │   ├── TaxTypeName (Required)
│   │       │   │   └── @code (Required)
│   │       │   └── TaxDetails (Required)
│   │       │       └── TaxRate (Required)
│   │       └── LinMoa (Required)
│   │           └── MoaDetails (Required)
│   │               └── Moa (Required)
│   │                   ├── @amountTypeCode (Required)
│   │                   ├── @currencyCodeList (Required: "ISO_4217")
│   │                   └── Amount (Required)
│   │                       └── @currencyIdentifier (Required)
│   │
│   ├── OldInvoiceMoa (Required) - Invoice Totals
│   │   └── AmountDetails (Required, multiple)
│   │       └── Moa (Required)
│   │           ├── @amountTypeCode (Required)
│   │           ├── @currencyCodeList (Required: "ISO_4217")
│   │           └── Amount (Required)
│   │               └── @currencyIdentifier (Required)
│   │
│   └── OldInvoiceTax (Required) - Tax Summary
│       └── OldInvoiceTaxDetails (Required)
│           ├── Tax (Required)
│           │   ├── TaxTypeName (Required)
│           │   │   └── @code (Required)
│           │   └── TaxDetails (Required)
│           │       └── TaxRate (Required)
│           └── AmountDetails (Required, multiple)
│               └── Moa (Required)
│
└── ds:Signature (Optional) - Digital Signature
```

### 6.5 Minimal Valid Invoice Example

```xml
<?xml version="1.0" encoding="UTF-8"?>
<TEIF controlingAgency="TTN" version="1.8.8">
  <OldInvoiceHeader>
    <MessageSenderIdentifier type="I-01">0736202XAM000</MessageSenderIdentifier>
    <MessageRecieverIdentifier type="I-01">0914089JAM000</MessageRecieverIdentifier>
  </OldInvoiceHeader>
  <OldInvoiceBody>
    <Bgm>
      <DocumentIdentifier>INV-2026-001</DocumentIdentifier>
      <DocumentType code="I-11">Facture</DocumentType>
    </Bgm>
    <Dtm>
      <DateText format="ddMMyy" functionCode="I-31">270226</DateText>
    </Dtm>
    <PartnerSection>
      <!-- Supplier (Sender) -->
      <PartnerDetails functionCode="I-62">
        <Nad>
          <PartnerIdentifier type="I-01">0736202XAM000</PartnerIdentifier>
          <PartnerName nameType="Qualification">Société ABC SARL</PartnerName>
          <PartnerAdresses lang="fr">
            <AdressDescription>Siège Social</AdressDescription>
            <Street>123 Avenue Habib Bourguiba</Street>
            <CityName>Tunis</CityName>
            <PostalCode>1000</PostalCode>
            <Country codeList="ISO_3166-1">TN</Country>
          </PartnerAdresses>
        </Nad>
      </PartnerDetails>
      <!-- Buyer (Receiver) -->
      <PartnerDetails functionCode="I-64">
        <Nad>
          <PartnerIdentifier type="I-01">0914089JAM000</PartnerIdentifier>
          <PartnerName nameType="Qualification">Client XYZ SA</PartnerName>
          <PartnerAdresses lang="fr">
            <AdressDescription>Siège Social</AdressDescription>
            <Street>456 Rue de la Liberté</Street>
            <CityName>Sfax</CityName>
            <PostalCode>3000</PostalCode>
            <Country codeList="ISO_3166-1">TN</Country>
          </PartnerAdresses>
        </Nad>
      </PartnerDetails>
    </PartnerSection>
    <LinSection>
      <Lin>
        <ItemIdentifier>1</ItemIdentifier>
        <LinImd lang="fr">
          <ItemCode>PROD001</ItemCode>
          <ItemDescription>Service de consultation</ItemDescription>
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
              <Amount currencyIdentifier="TND">1000.000</Amount>
            </Moa>
          </MoaDetails>
        </LinMoa>
      </Lin>
    </LinSection>
    <OldInvoiceMoa>
      <!-- Taxable Amount (HT) -->
      <AmountDetails>
        <Moa amountTypeCode="I-176" currencyCodeList="ISO_4217">
          <Amount currencyIdentifier="TND">1000.000</Amount>
        </Moa>
      </AmountDetails>
      <!-- Total Tax -->
      <AmountDetails>
        <Moa amountTypeCode="I-181" currencyCodeList="ISO_4217">
          <Amount currencyIdentifier="TND">190.000</Amount>
        </Moa>
      </AmountDetails>
      <!-- Grand Total (TTC) -->
      <AmountDetails>
        <Moa amountTypeCode="I-180" currencyCodeList="ISO_4217">
          <Amount currencyIdentifier="TND">1190.000</Amount>
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
        <!-- Tax Base -->
        <AmountDetails>
          <Moa amountTypeCode="I-177" currencyCodeList="ISO_4217">
            <Amount currencyIdentifier="TND">1000.000</Amount>
          </Moa>
        </AmountDetails>
        <!-- Tax Amount -->
        <AmountDetails>
          <Moa amountTypeCode="I-178" currencyCodeList="ISO_4217">
            <Amount currencyIdentifier="TND">190.000</Amount>
          </Moa>
        </AmountDetails>
      </OldInvoiceTaxDetails>
    </OldInvoiceTax>
  </OldInvoiceBody>
</TEIF>
```

---

## 7. Code Reference Tables

### 7.1 Document Type Codes

| Code | Name (French) | Name (English) | Description |
|------|---------------|----------------|-------------|
| `I-11` | Facture | Invoice | Standard commercial invoice |
| `I-12` | Facture rectificative | Corrective Invoice | Invoice correction document |
| `I-13` | Avoir | Credit Note | Credit memo for returns/adjustments |
| `I-14` | Note de débit | Debit Note | Additional charges document |
| `I-15` | Facture proforma | Proforma Invoice | Preliminary/quote invoice |
| `I-16` | Facture d'acompte | Advance Payment Invoice | Deposit/advance invoice |

### 7.2 Identifier Type Codes

| Code | Description | Usage |
|------|-------------|-------|
| `I-01` | TTN Identifier | Official Tunisia TradeNet registration number |

### 7.3 Partner Function Codes

| Code | Role | Description | XML Location |
|------|------|-------------|--------------|
| `I-62` | Supplier | Invoice sender/seller | `PartnerDetails[@functionCode='I-62']` |
| `I-64` | Buyer | Invoice receiver/purchaser | `PartnerDetails[@functionCode='I-64']` |

### 7.4 Tax Type Codes

| Code | Name (French) | Name (English) | Standard Rate |
|------|---------------|----------------|---------------|
| `I-1601` | Droit de timbre | Stamp Duty | Fixed amount |
| `I-1602` | TVA | Value Added Tax | 7%, 13%, 19% |

### 7.5 Amount Type Codes

| Code | Description (French) | Description (English) | Usage |
|------|---------------------|----------------------|-------|
| `I-176` | Montant imposable | Taxable Amount | Total before tax (HT) |
| `I-177` | Base d'imposition | Tax Base Amount | Amount subject to specific tax |
| `I-178` | Montant de taxe | Tax Amount | Calculated tax amount |
| `I-179` | Total général (centimes) | Grand Total (cents) | Total in smallest currency unit |
| `I-180` | Total général (TTC) | Grand Total (with tax) | Total including all taxes |
| `I-181` | Total taxes | Total Tax Amount | Sum of all taxes |
| `I-182` | Montant net | Net Amount | Amount after deductions |
| `I-183` | Montant ligne | Line Amount | Individual line item amount |

### 7.6 Date Function Codes

| Code | Description (French) | Description (English) | Format |
|------|---------------------|----------------------|--------|
| `I-31` | Date de facture | Invoice Date | ddMMyy |
| `I-32` | Date d'échéance | Due Date | ddMMyy |
| `I-36` | Période couverte | Period Covered | ddMMyy |

### 7.7 Currency Codes (ISO 4217)

| Code | Currency | Country |
|------|----------|---------|
| `TND` | Tunisian Dinar | Tunisia |
| `EUR` | Euro | European Union |
| `USD` | US Dollar | United States |

### 7.8 Country Codes (ISO 3166-1)

| Code | Country |
|------|---------|
| `TN` | Tunisia |
| `FR` | France |
| `IT` | Italy |
| `DE` | Germany |

### 7.9 Invoice Status Values

| Status | Description | Editable | Deletable | Can Sign |
|--------|-------------|----------|-----------|----------|
| `draft` | Invoice created, not yet signed | ✅ Yes | ✅ Yes | ✅ Yes |
| `signed` | Invoice has digital signature | ✅ Yes | ✅ Yes | ❌ No |
| `validated` | Invoice validated by TTN | ❌ No | ❌ No | ❌ No |

---

## 8. Error Handling Reference

### 8.1 SOAP Fault Structure

```xml
<SOAP-ENV:Fault>
   <faultcode>{FAULT_CODE}</faultcode>
   <faultstring>{ERROR_MESSAGE}</faultstring>
</SOAP-ENV:Fault>
```

### 8.2 Fault Code Definitions

| Fault Code | Type | Description | Responsibility |
|------------|------|-------------|----------------|
| `CLIENT` | Client Error | Input validation failure, invalid request | Client application |
| `SERVER` | Server Error | Internal processing failure | Server/Administrator |

### 8.3 Complete Error Catalog

#### 8.3.1 Client Errors (Fault Code: CLIENT)

| Error ID | Error Message | Cause | Resolution |
|----------|---------------|-------|------------|
| CLI-001 | Invoice not found. | Requested invoice ID doesn't exist | Verify invoice ID exists in system |
| CLI-002 | Cannot update a validated invoice. | Attempting to modify validated invoice | Create a corrective invoice instead |
| CLI-003 | Cannot delete a validated invoice. | Attempting to delete validated invoice | Validated invoices cannot be deleted |
| CLI-004 | Invalid invoice ID | Non-numeric or negative invoice ID | Use positive integer for invoice ID |
| CLI-005 | XSD validation failed: [details] | XML doesn't conform to schema | Fix XML according to error details |
| CLI-006 | Missing required element 'X' | Required XML element is absent | Add the missing element |
| CLI-007 | Invalid attribute value for 'X' | Attribute has invalid value | Correct the attribute value |

#### 8.3.2 Server Errors (Fault Code: SERVER)

| Error ID | Error Message | Cause | Resolution |
|----------|---------------|-------|------------|
| SRV-001 | Internal error: Database connection failed | Database unavailable | Contact system administrator |
| SRV-002 | Internal error: File system error | Cannot read/write files | Contact system administrator |
| SRV-003 | Internal error: [details] | Unhandled exception | Contact development team |

### 8.4 Error Response Examples by Operation

#### SubmitInvoice Errors

| Scenario | Response |
|----------|----------|
| Invalid XML Structure | `<faultcode>CLIENT</faultcode><faultstring>XSD validation failed: Element 'TEIF' is missing.</faultstring>` |
| Missing Required Element | `<faultcode>CLIENT</faultcode><faultstring>XSD validation failed: Element 'DocumentIdentifier' is required.</faultstring>` |
| Invalid Attribute | `<faultcode>CLIENT</faultcode><faultstring>XSD validation failed: Attribute 'code' is required on element 'DocumentType'.</faultstring>` |

#### GetInvoice Errors

| Scenario | Response |
|----------|----------|
| Invoice Not Found | `<faultcode>CLIENT</faultcode><faultstring>Invoice not found.</faultstring>` |
| Invalid ID | `<faultcode>CLIENT</faultcode><faultstring>Invalid invoice ID</faultstring>` |

#### UpdateInvoice Errors

| Scenario | Response |
|----------|----------|
| Validated Invoice | `<faultcode>CLIENT</faultcode><faultstring>Cannot update a validated invoice.</faultstring>` |
| XML Validation | `<faultcode>CLIENT</faultcode><faultstring>XSD validation failed: [details]</faultstring>` |
| Not Found | `<faultcode>CLIENT</faultcode><faultstring>Invoice not found.</faultstring>` |

#### DeleteInvoice Errors

| Scenario | Response |
|----------|----------|
| Validated Invoice | `<faultcode>CLIENT</faultcode><faultstring>Cannot delete a validated invoice.</faultstring>` |
| Not Found | `<faultcode>CLIENT</faultcode><faultstring>Invoice not found.</faultstring>` |

---

## 9. Data Types & Validation Rules

### 9.1 Parameter Data Types

| Data Type | Description | Example | Validation |
|-----------|-------------|---------|------------|
| `integer` | Whole number | `123` | No decimals, can be negative |
| `positiveInteger` | Positive whole number | `1`, `100` | Must be > 0 |
| `string` | Text value | `"INV-001"` | UTF-8 encoded |
| `boolean` | True/False | `true`, `false` | Case-sensitive |
| `decimal` | Decimal number | `100.500` | Up to 3 decimal places for currency |
| `datetime` | ISO 8601 timestamp | `2026-02-27T10:30:00Z` | UTC timezone |

### 9.2 Field Validation Rules

| Field | Type | Min Length | Max Length | Pattern | Required |
|-------|------|------------|------------|---------|----------|
| invoiceId | positiveInteger | 1 | - | `^[1-9]\d*$` | Yes |
| teifXml | string | 100 | - | Valid XML | Yes |
| withSignature | boolean | - | - | `true\|false` | Yes |
| senderIdentifier | string | 10 | 20 | TTN format | No |
| status | string | 4 | 10 | `draft\|signed\|validated` | No |
| page | positiveInteger | 1 | - | `^[1-9]\d*$` | No |
| perPage | positiveInteger | 1 | 100 | `^[1-9]\d*$` | No |

### 9.3 TTN Identifier Format

| Property | Value |
|----------|-------|
| Pattern | `^[0-9]{7}[A-Z]{3}[0-9]{3}$` |
| Length | 13 characters |
| Example | `0736202XAM000` |
| Components | 7 digits + 3 letters + 3 digits |

### 9.4 Amount Format Rules

| Property | Value |
|----------|-------|
| Decimal Places | 3 (for TND) |
| Decimal Separator | `.` (period) |
| Thousand Separator | None |
| Negative Amounts | Not allowed for standard invoices |
| Example | `1234.567` |

### 9.5 Date Format Rules

| Format Code | Pattern | Example | Description |
|-------------|---------|---------|-------------|
| ddMMyy | 6 digits | `270226` | Day (27), Month (02), Year (26) |

---

## 10. SoapUI Testing Guide

### 10.1 Project Import

1. Open SoapUI (version 5.7.0 or later recommended)
2. Navigate to **File → Import Project**
3. Select `docs/soapui/elfatoora-soapui-project.xml`
4. Click **Open**

### 10.2 Project Structure

```
Elfatoora Invoicing API
│
├── ElfatooraPortType (Interface)
│   │
│   ├── SubmitInvoice
│   │   ├── Happy Path - Valid invoice submission
│   │   └── Invalid XML - XSD validation error test
│   │
│   ├── GetInvoice
│   │   ├── Happy Path - Retrieve existing invoice
│   │   └── Not Found - Non-existent invoice ID
│   │
│   ├── ValidateInvoice
│   │   └── Happy Path - Validate existing invoice
│   │
│   ├── ListInvoices
│   │   ├── All - List all invoices
│   │   ├── Filter by Sender - Filter by sender ID
│   │   └── Filter by Status - Filter by status
│   │
│   ├── UpdateInvoice
│   │   ├── Happy Path - Update draft invoice
│   │   └── Cannot Update Validated - Validated invoice error
│   │
│   ├── DeleteInvoice
│   │   ├── Happy Path - Delete draft invoice
│   │   └── Cannot Delete Validated - Validated invoice error
│   │
│   ├── SignInvoice
│   │   └── Request - Signing request (not implemented)
│   │
│   └── VerifySignature
│       └── Request - Verification request (not implemented)
│
└── Elfatoora API Test Suite
    └── Invoice CRUD Operations
        ├── 1. Submit Invoice
        ├── 2. Get Invoice
        ├── 3. Validate Invoice
        ├── 4. List Invoices
        ├── 5. Update Invoice
        └── 6. Delete Invoice
```

### 10.3 Endpoint Configuration

| Setting | Development Value |
|---------|-------------------|
| Endpoint URL | `http://localhost:8000/soap/invoicing` |
| WSDL URL | `http://localhost:8000/soap/invoicing?wsdl` |

**To change endpoint:**
1. Double-click on **ElfatooraPortType** interface
2. Go to **Service Endpoints** tab
3. Modify the endpoint URL

### 10.4 Running Tests

**Individual Request:**
1. Expand operation folder
2. Double-click on request (e.g., "Happy Path")
3. Click green **Run** button (▶)
4. View response in right panel

**Full Test Suite:**
1. Right-click on **Elfatoora API Test Suite**
2. Select **Run**
3. View results in TestRunner window

### 10.5 Test Assertions

| Test | Expected Result |
|------|-----------------|
| SubmitInvoice - Happy Path | Status: `draft`, invoiceId: integer |
| GetInvoice - Happy Path | Contains `<teifXml>` with CDATA |
| GetInvoice - Not Found | Fault: `Invoice not found.` |
| ValidateInvoice | `<valid>true</valid>` or errors |
| UpdateInvoice - Validated | Fault: `Cannot update a validated invoice.` |
| DeleteInvoice - Validated | Fault: `Cannot delete a validated invoice.` |

---

## Appendix A: XML Namespace Reference

| Prefix | Namespace URI | Usage |
|--------|---------------|-------|
| `soapenv` | `http://schemas.xmlsoap.org/soap/envelope/` | SOAP Envelope |
| `v1` / `tns` | `http://elfatoora.tn/invoicing/v1` | API namespace |
| `ds` | `http://www.w3.org/2000/09/xmldsig#` | XML Digital Signature |
| `xsi` | `http://www.w3.org/2001/XMLSchema-instance` | XML Schema Instance |

---

## Appendix B: Quick Reference Card

### Operations Summary

| Operation | Request | Response Success | Response Error |
|-----------|---------|------------------|----------------|
| SubmitInvoice | `teifXml`, `withSignature` | `invoiceId`, `status`, `message` | SOAP Fault |
| GetInvoice | `invoiceId` | `teifXml`, `status` | SOAP Fault |
| ValidateInvoice | `invoiceId` | `valid`, `errors` | SOAP Fault |
| ListInvoices | `senderIdentifier?`, `status?`, `page?`, `perPage?` | `invoicesJson`, `total` | SOAP Fault |
| UpdateInvoice | `invoiceId`, `teifXml` | `success`, `message` | SOAP Fault |
| DeleteInvoice | `invoiceId` | `success`, `message` | SOAP Fault |
| SignInvoice | `invoiceId`, `signerRole`, `privateKeyPem`, `certificatePem` | `signedXml`, `success`, `message` | SOAP Fault |
| VerifySignature | `invoiceId` | `valid`, `message` | SOAP Fault |

### Status Values

| Status | Description | Editable | Deletable |
|--------|-------------|----------|-----------|
| `draft` | New/unsigned | ✅ | ✅ |
| `signed` | Has signature | ✅ | ✅ |
| `validated` | TTN validated | ❌ | ❌ |

### Common Fault Codes

| Code | Type | Example |
|------|------|---------|
| `CLIENT` | Client error | Invalid input, not found |
| `SERVER` | Server error | Database failure |

---

## Appendix C: Changelog

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2026-02-24 | Initial release |
| 1.0.1 | 2026-02-27 | Documentation update - comprehensive reference |

---

*Document generated for Smart ERP - Elfatoora Integration*  
*TEIF v1.8.8 Compliance - Tunisia TradeNet (TTN)*
