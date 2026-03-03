# Elfatoora Invoicing SOAP API Documentation

## Base URLs
- **Development:** `http://localhost:8000/soap/invoicing`
- **Production:** `https://your-domain.com/soap/invoicing`
- **WSDL:** Add `?wsdl` to base URL

---

## 1. SubmitInvoice

**POST** `/soap/invoicing`

Creates a new invoice.

### Request
```xml
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:v1="http://elfatoora.tn/invoicing/v1">
   <soapenv:Body>
      <v1:SubmitInvoiceRequest>
         <v1:teifXml><![CDATA[TEIF_XML_CONTENT_HERE]]></v1:teifXml>
         <v1:withSignature>false</v1:withSignature>
      </v1:SubmitInvoiceRequest>
   </soapenv:Body>
</soapenv:Envelope>
Success Response
XML
<tns:SubmitInvoiceResponse>
   <tns:invoiceId>1</tns:invoiceId>
   <tns:status>draft</tns:status>
   <tns:message>Invoice submitted successfully.</tns:message>
</tns:SubmitInvoiceResponse>
Error Response
XML
<SOAP-ENV:Fault>
   <faultcode>CLIENT</faultcode>
   <faultstring>XSD validation failed</faultstring>
</SOAP-ENV:Fault>
2. GetInvoice
POST /soap/invoicing

Retrieves invoice by ID.

Request
XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:v1="http://elfatoora.tn/invoicing/v1">
   <soapenv:Body>
      <v1:GetInvoiceRequest>
         <v1:invoiceId>1</v1:invoiceId>
      </v1:GetInvoiceRequest>
   </soapenv:Body>
</soapenv:Envelope>
Success Response
XML
<tns:GetInvoiceResponse>
   <tns:teifXml><![CDATA[FULL_TEIF_XML]]></tns:teifXml>
   <tns:status>draft</tns:status>
</tns:GetInvoiceResponse>
3. ValidateInvoice
POST /soap/invoicing

Validates invoice against XSD.

Request
XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:v1="http://elfatoora.tn/invoicing/v1">
   <soapenv:Body>
      <v1:ValidateInvoiceRequest>
         <v1:invoiceId>1</v1:invoiceId>
      </v1:ValidateInvoiceRequest>
   </soapenv:Body>
</soapenv:Envelope>
Success Response
XML
<tns:ValidateInvoiceResponse>
   <tns:valid>true</tns:valid>
   <tns:errors></tns:errors>
</tns:ValidateInvoiceResponse>
4. ListInvoices
POST /soap/invoicing

Lists invoices with pagination.

Request
XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:v1="http://elfatoora.tn/invoicing/v1">
   <soapenv:Body>
      <v1:ListInvoicesRequest>
         <v1:senderIdentifier>0736202XAM000</v1:senderIdentifier>
         <v1:status>draft</v1:status>
         <v1:page>1</v1:page>
         <v1:perPage>20</v1:perPage>
      </v1:ListInvoicesRequest>
   </soapenv:Body>
</soapenv:Envelope>
Success Response
XML
<tns:ListInvoicesResponse>
   <tns:invoicesJson>[{"id":1,"document_identifier":"INV-001",...}]</tns:invoicesJson>
   <tns:total>25</tns:total>
</tns:ListInvoicesResponse>
5. UpdateInvoice
POST /soap/invoicing

Updates existing invoice (draft/signed only).

Request
XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:v1="http://elfatoora.tn/invoicing/v1">
   <soapenv:Body>
      <v1:UpdateInvoiceRequest>
         <v1:invoiceId>1</v1:invoiceId>
         <v1:teifXml><![CDATA[UPDATED_TEIF_XML]]></v1:teifXml>
      </v1:UpdateInvoiceRequest>
   </soapenv:Body>
</soapenv:Envelope>
Success Response
XML
<tns:UpdateInvoiceResponse>
   <tns:success>true</tns:success>
   <tns:message>Invoice updated successfully.</tns:message>
</tns:UpdateInvoiceResponse>
Error Response
XML
<SOAP-ENV:Fault>
   <faultcode>CLIENT</faultcode>
   <faultstring>Cannot update a validated invoice.</faultstring>
</SOAP-ENV:Fault>
6. DeleteInvoice
POST /soap/invoicing

Deletes invoice (draft/signed only).

Request
XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:v1="http://elfatoora.tn/invoicing/v1">
   <soapenv:Body>
      <v1:DeleteInvoiceRequest>
         <v1:invoiceId>1</v1:invoiceId>
      </v1:DeleteInvoiceRequest>
   </soapenv:Body>
</soapenv:Envelope>
Success Response
XML
<tns:DeleteInvoiceResponse>
   <tns:success>true</tns:success>
   <tns:message>Invoice deleted.</tns:message>
</tns:DeleteInvoiceResponse>
7. SignInvoice
POST /soap/invoicing

⚠️ Not yet implemented

Request
XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:v1="http://elfatoora.tn/invoicing/v1">
   <soapenv:Body>
      <v1:SignInvoiceRequest>
         <v1:invoiceId>1</v1:invoiceId>
         <v1:signerRole>supplier</v1:signerRole>
         <v1:privateKeyPem><![CDATA[-----BEGIN PRIVATE KEY-----...-----END PRIVATE KEY-----]]></v1:privateKeyPem>
         <v1:certificatePem><![CDATA[-----BEGIN CERTIFICATE-----...-----END CERTIFICATE-----]]></v1:certificatePem>
      </v1:SignInvoiceRequest>
   </soapenv:Body>
</soapenv:Envelope>
8. VerifySignature
POST /soap/invoicing

⚠️ Not yet implemented

Request
XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                  xmlns:v1="http://elfatoora.tn/invoicing/v1">
   <soapenv:Body>
      <v1:VerifySignatureRequest>
         <v1:invoiceId>1</v1:invoiceId>
      </v1:VerifySignatureRequest>
   </soapenv:Body>
</soapenv:Envelope>
Error Codes
Code	Description
CLIENT	Client error (validation, not found)
SERVER	Server error (database, internal)
Code Tables
Document Types
Code	Name
I-11	Facture
I-12	Facture rectificative
I-13	Avoir
Tax Types
Code	Name
I-1602	TVA
I-1601	Droit de timbre
Amount Codes
Code	Description
I-176	Taxable amount
I-177	Tax base
I-178	Tax amount
I-180	Grand total
I-181	Total tax
I-183	Line amount
Status Values
Status	Description
draft	Created, not signed
signed	Has digital signature
validated	Validated by TTN