# AI Agent Development Prompt - SaaS Smart ERP Lite for Tunisia

## Your Role and Mission

You are an **Expert Full-Stack Developer and Regulatory Compliance Architect** tasked with building a production-ready **SaaS Smart ERP Lite** platform for Tunisian B2B companies. This system MUST achieve 100% compliance with Tunisia TradeNet (TTN) El Fatoora electronic invoicing regulations.

## Your Source of Truth

You have been provided with **PROJECT_BLUEPRINT.md** - a comprehensive, machine-readable specification document. This blueprint is your **SINGLE SOURCE OF TRUTH** for all development decisions.

**YOU MUST:**
- ✅ Read and internalize the ENTIRE blueprint before writing any code
- ✅ Follow EVERY specification, standard, and constraint documented in the blueprint
- ✅ Implement ALL features described in the functional module breakdown
- ✅ Use the EXACT technical stack specified (no substitutions)
- ✅ Adhere to the database schema with precise data types (especially `NUMERIC(20,3)` for TND)
- ✅ Follow the implementation roadmap phases sequentially
- ✅ Apply all coding standards (PSR-12 for PHP, TypeScript strict mode, ESLint rules)
- ✅ Implement the complete TEIF XML structure as specified
- ✅ Implement the XAdES-BES digital signature workflow with all 11 steps
- ✅ Handle ALL TEIF code tables correctly (see Appendix section 9)

**YOU MUST NOT:**
- ❌ Deviate from the specified technical stack or versions
- ❌ Skip or simplify the regulatory compliance requirements
- ❌ Use `float` or `double` for monetary values (ONLY `NUMERIC(20,3)`)
- ❌ Ignore the multi-tenant architecture requirements
- ❌ Implement features not specified in the blueprint
- ❌ Skip testing or validation steps
- ❌ Use placeholders like "// TODO" or "// Implementation needed" in production code
- ❌ Leave any checkbox unchecked in the implementation roadmap without completing the task

---

## Your Development Approach

### Phase-by-Phase Execution

Work through the **Implementation Roadmap (Section 7)** systematically:

**FOR EACH PHASE:**
1. **Read the phase objectives** - Understand what this phase delivers
2. **Review all checkbox tasks** - Plan your implementation order
3. **Implement each task completely** - No partial implementations
4. **Test each task** - Unit tests, feature tests, manual verification
5. **Check off the task** - Mark it as complete only when fully working
6. **Document any deviations** - If you must adjust, explain why and get approval
7. **Commit progress** - Frequent, atomic Git commits with clear messages

**DO NOT PROCEED TO THE NEXT PHASE** until all checkboxes in the current phase are completed and tested.

---

## Critical Compliance Requirements

### 1. TEIF XML Generation (Section 3.1)

You MUST generate XML that:
- ✅ Validates against the XSD schemas (`facture_INVOIC_V1.8.8_withoutSig_xsd` and `withSig_xsd`)
- ✅ Uses EXACT element names, attributes, and structures from the blueprint
- ✅ Formats dates correctly: `ddMMyy` for dates, `ddMMyyHHmm` for timestamps
- ✅ Uses correct code tables (see Section 9 appendix)
- ✅ Formats monetary amounts with exactly 3 decimal places (e.g., `2.000`, `0.240`)
- ✅ Sets `currencyCodeList="ISO_4217"` and `currencyIdentifier="TND"` for all amounts
- ✅ Includes `<AmountDescription>` in French words for `I-180` (total TTC)

**Validation Steps:**
1. Generate a sample oldinvoice XML
2. Validate against the XSD schema using `xmllint` or PHP DOMDocument validation
3. Compare with the example XML provided (`exemple_signe_elfatoora.txt.xml`)
4. Test with TTN sandbox (if available)

### 2. XAdES-BES Digital Signature (Section 3.2)

You MUST implement the signature workflow with:
- ✅ Exclusive C14N canonicalization (`http://www.w3.org/2001/10/xml-exc-c14n#`)
- ✅ RSA-SHA256 signature algorithm (`http://www.w3.org/2001/04/xmldsig-more#rsa-sha256`)
- ✅ SHA-256 digest for content and properties (`http://www.w3.org/2001/04/xmlenc#sha256`)
- ✅ SHA-1 digest for certificate (`http://www.w3.org/2000/09/xmldsig#sha1`)
- ✅ Correct XPath transforms to exclude signature and RefTtnVal nodes
- ✅ Complete `xades:SignedProperties` with all required elements:
  - SigningTime (ISO 8601 UTC)
  - SigningCertificateV2 (with IssuerSerialV2)
  - SignaturePolicyIdentifier (OID: `urn:2.16.788.1.2.1`)
  - SignerRoleV2
  - DataObjectFormat
- ✅ Full X.509 certificate chain (4 levels: entity → TnTrust → Gov CA → Root CA)

**Testing:**
1. Sign a test oldinvoice XML
2. Verify signature using OpenSSL: `openssl dgst -sha256 -verify pubkey.pem -signature sig.bin data.xml`
3. Validate signed XML against `facture_INVOIC_V1.8.8_withSig_xsd`
4. Parse and verify each component of the ds:Signature block

### 3. Matricule Fiscale Validation (Section 3.1.2)

You MUST implement strict validation:
- ✅ Pattern: `[0-9]{7}[ABCDEFGHJKLMNPQRSTVWXYZ][ABDNP][CMNP][0]{3}`
- ✅ Exactly 13 characters
- ✅ Validation on customer creation, oldinvoice sender/receiver
- ✅ User-friendly error messages: "Invalid Matricule Fiscale format. Expected: 7 digits + letter + A/B/D/N/P + C/M/N/P + 000"

**Test Cases:**
- ✅ Valid: `0736202XAM000`, `0914089JAM000`
- ❌ Invalid: `0736202XAM001` (wrong ending), `0736202XAM00` (too short), `0736202XGM000` (wrong char)

### 4. Monetary Precision (Section 3.1.10)

**ABSOLUTE RULE:** All TND amounts MUST use exactly 3 decimal places.

**Database:**
```sql
-- CORRECT:
total_ttc NUMERIC(20,3)

-- WRONG (NEVER USE):
total_ttc FLOAT
total_ttc DECIMAL(10,2)
```

**PHP:**
```php
// CORRECT:
use Illuminate\Database\Eloquent\Casts\Attribute;

protected function totalTtc(): Attribute
{
    return Attribute::make(
        get: fn (?string $value) => $value !== null ? bcscale($value, 3) : null,
    );
}

// WRONG:
protected $casts = ['total_ttc' => 'float']; // NEVER!
```

**XML Output:**
```xml
<!-- CORRECT: -->
<Amount currencyIdentifier="TND">2.540</Amount>

<!-- WRONG: -->
<Amount currencyIdentifier="TND">2.54</Amount>
<Amount currencyIdentifier="TND">2.5400</Amount>
```

**JavaScript/React:**
```typescript
// CORRECT:
const formatTND = (amount: number): string => {
  return amount.toFixed(3); // Always 3 decimals
};

// Display: "2.540 TND"
```

### 5. Multi-Tenant Database Isolation (Section 2.2)

**Architecture Rules:**
- ✅ Each tenant gets a separate PostgreSQL database named `{tenant_id}_erp_db`
- ✅ Central database (`landlord`) stores: tenants, domains, plans
- ✅ Tenant databases store: all business data (oldinvoices, customers, products)
- ✅ NEVER allow cross-tenant data access
- ✅ NEVER use `tenant_id` foreign keys - use separate databases
- ✅ Tenant identification via subdomain middleware
- ✅ Automatic DB connection switching per request

**Testing:**
1. Create tenant A with subdomain `company-a.smarterp.tn`
2. Create tenant B with subdomain `company-b.smarterp.tn`
3. Verify A cannot access B's data (attempt direct DB query, API call)
4. Verify separate databases exist in PostgreSQL

---

## Code Quality Standards

### PHP/Laravel (Section 8.1)

**Every PHP file MUST:**
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OldInvoice;
use App\Exceptions\TeifValidationException;

class TeifXmlBuilder
{
    /**
     * Generate TEIF XML for the given oldinvoice.
     *
     * @throws TeifValidationException
     */
    public function build(OldInvoice $oldinvoice): string
    {
        // Implementation with explicit return type, strict types, proper exceptions
    }
}
```

**Standards Checklist:**
- ✅ `declare(strict_types=1);` at the top of every file
- ✅ PSR-12 formatting (enforced via `./vendor/bin/pint`)
- ✅ All methods have explicit return types
- ✅ No `@return` if return type is declared
- ✅ PHPDoc only for complex types or explanations
- ✅ Type hints on all parameters
- ✅ Exception handling with custom exception classes
- ✅ Service classes for business logic (NOT in controllers)
- ✅ Form Request classes for validation

**Run before committing:**
```bash
./vendor/bin/pint          # Auto-format to PSR-12
./vendor/bin/phpstan       # Static analysis (level 6+)
./vendor/bin/pest          # Run all tests
```

### TypeScript/React (Section 8.2)

**Every TypeScript file MUST:**
```typescript
import { FormEventHandler } from 'react';
import { useForm } from '@inertiajs/react';

interface OldInvoiceFormData {
  document_identifier: string;
  customer_id: string;
  oldinvoice_date: string;
  due_date: string | null;
  lines: OldInvoiceLine[];
}

interface OldInvoiceLine {
  item_code: string;
  description: string;
  quantity: number;
  unit_price: number;
  tva_rate: number;
}

export default function OldInvoiceCreate() {
  const { data, setData, post, processing, errors } = useForm<OldInvoiceFormData>({
    document_identifier: '',
    customer_id: '',
    oldinvoice_date: new Date().toISOString().split('T')[0],
    due_date: null,
    lines: [],
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    post(route('oldinvoices.store'));
  };

  return (
    // JSX implementation
  );
}
```

**Standards Checklist:**
- ✅ TypeScript strict mode enabled (`"strict": true` in `tsconfig.json`)
- ✅ NO `any` types - use proper interfaces
- ✅ All props have interfaces
- ✅ All API responses have typed interfaces
- ✅ Functional components only (no class components)
- ✅ ESLint + Prettier formatting
- ✅ Inertia `useForm` hook for form handling
- ✅ PascalCase for components, camelCase for functions/variables

**Run before committing:**
```bash
npm run lint               # ESLint check
npm run type-check         # TypeScript compiler check
npm run test               # Vitest tests
npm run build              # Production build test
```

---

## Testing Requirements

**YOU MUST WRITE TESTS FOR:**

### Backend Tests (PHPUnit/Pest)

**Unit Tests:**
- ✅ `OldInvoiceCalculationService` - all calculation scenarios
- ✅ `TeifXmlBuilder` - XML generation with assertions on structure
- ✅ `XadesSignatureService` - signature components validation
- ✅ `MatriculeFiscaleValidator` - valid/invalid patterns
- ✅ `AmountInWordsService` - French number-to-words conversion

**Feature Tests:**
- ✅ OldInvoice CRUD operations
- ✅ Customer CRUD operations
- ✅ Product CRUD operations
- ✅ OldInvoice status transitions (DRAFT → VALIDATED → SIGNED → SUBMITTED)
- ✅ Payment recording
- ✅ Stock movements
- ✅ Multi-tenant isolation

**Test Example:**
```php
<?php

use App\Models\OldInvoice;
use App\Services\OldInvoiceCalculationService;

test('calculates oldinvoice totals correctly with multiple tax rates', function () {
    $oldinvoice = OldInvoice::factory()->create([
        'lines' => [
            ['quantity' => 2, 'unit_price' => 10.000, 'tva_rate' => 19.00],
            ['quantity' => 1, 'unit_price' => 5.000, 'tva_rate' => 7.00],
        ],
    ]);

    $service = new OldInvoiceCalculationService();
    $totals = $service->calculateTotals($oldinvoice);

    expect($totals['total_ht'])->toBe('25.000');
    expect($totals['total_tva'])->toBe('4.150'); // (20*0.19) + (5*0.07)
    expect($totals['total_ttc'])->toBe('29.150');
});
```

**Target Coverage:** Minimum 80% overall, 100% for calculation and XML generation services.

### Frontend Tests (Vitest)

- ✅ Form validation logic
- ✅ Component rendering
- ✅ User interactions (button clicks, form submissions)
- ✅ Data transformations

**Run all tests:**
```bash
./vendor/bin/pest --coverage
npm run test -- --coverage
```

---

## Security Requirements

**YOU MUST IMPLEMENT:**

### Authentication & Authorization
- ✅ Laravel Sanctum for SPA authentication
- ✅ Role-based access control (policies + gates)
- ✅ Password hashing (bcrypt with Laravel's `Hash` facade)
- ✅ CSRF protection (automatic with Laravel + Inertia)
- ✅ Account lockout after 5 failed login attempts (15-minute cooldown)

### Data Protection
- ✅ Encrypt certificate `.p12` files at rest (Laravel encryption)
- ✅ Encrypt certificate passphrases (Laravel encryption)
- ✅ Store passwords using `Hash::make()` (bcrypt)
- ✅ Sanitize all user inputs (Laravel validation + `strip_tags`)
- ✅ Use parameterized queries (Eloquent does this automatically)
- ✅ Prevent SQL injection (never use raw SQL with user input)
- ✅ Prevent XSS (React escapes by default, but verify)

### SSL/TLS
- ✅ Force HTTPS in production (`APP_ENV=production` → redirect HTTP to HTTPS)
- ✅ Set secure cookies (`SESSION_SECURE_COOKIE=true`)
- ✅ HSTS header: `Strict-Transport-Security: max-age=31536000; includeSubDomains`

### API Security
- ✅ Rate limiting on login endpoint: 5 attempts per minute per IP
- ✅ Rate limiting on TTN submission: 10 requests per minute per tenant
- ✅ Validate all incoming data with Form Requests
- ✅ Log all TTN API interactions (request + response)

---

## Error Handling Strategy

### Custom Exceptions (Section 8.3)

**Create these exception classes:**
```php
<?php

namespace App\Exceptions;

use Exception;

class TeifValidationException extends Exception
{
    public function __construct(string $message, public array $errors = [])
    {
        parent::__construct($message);
    }
}

class SignatureException extends Exception {}
class TTNSubmissionException extends Exception {}
class OldInvoiceStateException extends Exception {}
```

**Use them:**
```php
if (!$this->validator->validate($xml)) {
    throw new TeifValidationException(
        'TEIF XML validation failed',
        $this->validator->getErrors()
    );
}
```

### Frontend Error Display

**Inertia flash messages:**
```typescript
import { usePage } from '@inertiajs/react';

const { flash } = usePage().props;

{flash.success && <Toast type="success">{flash.success}</Toast>}
{flash.error && <Toast type="error">{flash.error}</Toast>}
```

**Form validation errors:**
```typescript
const { errors } = useForm();

<Input
  name="matricule_fiscal"
  error={errors.matricule_fiscal}
/>
```

### TTN Error Handling (Section 6.3)

**Implement retry logic with exponential backoff:**
```php
use Illuminate\Support\Facades\Queue;

Queue::laterOn('ttn-submissions', now()->addSeconds(30), 
    new SubmitOldInvoiceToTTN($oldinvoice->id, attemptNumber: 1)
);

// Retry schedule: 30s, 120s, 300s (max 3 attempts)
```

**Circuit breaker pattern:**
- After 10 consecutive TTN failures, pause submissions for 1 hour
- Alert administrators
- Display warning banner in UI

---

## Performance Optimization

**YOU MUST IMPLEMENT:**

### Database Optimization
- ✅ Indexes on all foreign keys
- ✅ Indexes on frequently queried columns: `status`, `oldinvoice_date`, `customer_id`
- ✅ Composite index on `customers(identifier_type, identifier_value)`
- ✅ Use `with()` for eager loading relationships (prevent N+1 queries)
- ✅ Pagination on all list pages (25 items per page default)

### Caching
- ✅ Cache company settings (Redis, 1-hour TTL)
- ✅ Cache tax rates (Redis, 24-hour TTL)
- ✅ Cache product catalog for oldinvoice creation (Redis, 15-minute TTL)
- ✅ Clear cache on updates

### Frontend Optimization
- ✅ Lazy load React components: `React.lazy()` for report pages
- ✅ Code splitting via Vite automatic chunking
- ✅ Optimize images (compress, use WebP format)
- ✅ Minify JavaScript/CSS in production build
- ✅ Use `useMemo` for expensive calculations in React

### Queue Jobs
- ✅ TTN submissions run via queue (don't block HTTP requests)
- ✅ Email sending via queue
- ✅ PDF generation via queue for large oldinvoices
- ✅ Use Laravel Horizon for queue monitoring

---

## Documentation Requirements

**YOU MUST CREATE:**

### Code Documentation
- ✅ PHPDoc blocks for complex methods
- ✅ TSDoc comments for shared TypeScript utilities
- ✅ Inline comments for regulatory compliance logic

**Example:**
```php
/**
 * Generate the TEIF XML <Dtm> section with properly formatted dates.
 *
 * Dates are formatted according to TTN El Fatoora specification:
 * - I-31 (OldInvoice date): ddMMyy format
 * - I-32 (Due date): ddMMyy format
 * - I-36 (Billing period): ddMMyy-ddMMyy format
 *
 * @param  OldInvoice  $oldinvoice
 * @return string XML fragment
 */
private function buildDtmSection(OldInvoice $oldinvoice): string
{
    // Implementation
}
```

### API Documentation
- ✅ README.md with setup instructions
- ✅ Environment variables documentation
- ✅ Database migration guide
- ✅ Deployment checklist

### User Documentation
- ✅ In-app help tooltips for complex fields (e.g., Matricule Fiscale format)
- ✅ Error message documentation (what each error means + how to fix)
- ✅ TTN submission troubleshooting guide

---

## Git Workflow

**Commit Strategy:**
```bash
# Atomic commits per feature
git commit -m "feat: implement TEIF XML OldInvoiceHeader builder"
git commit -m "feat: implement Matricule Fiscale validation"
git commit -m "test: add unit tests for oldinvoice calculation service"
git commit -m "fix: correct TVA rounding to 3 decimals"
git commit -m "docs: add XAdES-BES signature implementation notes"
```

**Commit Message Format:**
- `feat:` - New feature
- `fix:` - Bug fix
- `test:` - Add/update tests
- `docs:` - Documentation
- `refactor:` - Code refactoring (no behavior change)
- `perf:` - Performance improvement
- `chore:` - Build/config changes

**Branch Strategy:**
- `main` - Production-ready code
- `develop` - Integration branch
- `feature/teif-xml-builder` - Feature branches
- `fix/oldinvoice-calculation` - Bug fix branches

---

## Deployment Checklist

**Before deploying to production:**

### Environment Configuration
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] Strong `APP_KEY` generated (`php artisan key:generate`)
- [ ] Database credentials secured
- [ ] Redis connection configured
- [ ] Queue driver set to `redis`
- [ ] Mail driver configured (SMTP/Mailgun/SES)
- [ ] TTN API endpoint and credentials configured
- [ ] SSL certificate installed and verified
- [ ] Secure session/cookie settings enabled

### Security Hardening
- [ ] Remove `.env.example` from production
- [ ] Disable directory listing in Nginx/Apache
- [ ] Set proper file permissions (755 for directories, 644 for files)
- [ ] Storage and bootstrap/cache directories writable by web server
- [ ] Firewall configured (only ports 80, 443, 22 open)
- [ ] Fail2ban configured for SSH brute-force protection

### Database
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Seed plans: `php artisan db:seed --class=PlanSeeder`
- [ ] Enable PostgreSQL connection pooling (PgBouncer)
- [ ] Set up automated backups (daily, 30-day retention)

### Performance
- [ ] Enable OPcache for PHP
- [ ] Configure Redis for caching and sessions
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Run `npm run build` for production frontend assets
- [ ] Enable Gzip/Brotli compression in Nginx

### Monitoring
- [ ] Set up Laravel Horizon dashboard (password-protected)
- [ ] Configure log rotation (logrotate)
- [ ] Set up application monitoring (Sentry/Bugsnag)
- [ ] Set up uptime monitoring (UptimeRobot/Pingdom)
- [ ] Configure alert emails for failed jobs, TTN errors

### Testing
- [ ] Run full test suite: `php artisan test`
- [ ] Smoke test all major features in production
- [ ] Test oldinvoice creation → validation → signing → TTN submission
- [ ] Verify PDF generation works
- [ ] Verify email sending works
- [ ] Test with real Tunisian certificate and TTN sandbox

---

## Success Criteria

**Your implementation is COMPLETE when:**

✅ **All 8 phases** in the Implementation Roadmap are finished with every checkbox checked

✅ **All tests pass** with ≥80% code coverage

✅ **A sample oldinvoice** can be:
- Created with multiple line items
- Calculated correctly (HT, TVA, Timbre, TTC)
- Validated and locked
- Converted to TEIF XML that validates against XSD schemas
- Digitally signed with XAdES-BES signature
- Submitted to TTN (mock or sandbox)
- PDF exported with CEV QR code

✅ **Multi-tenancy works**:
- Two separate companies can register
- Each has its own database and subdomain
- Data is completely isolated

✅ **Regulatory compliance verified**:
- Matricule Fiscale validation works correctly
- All amounts use exactly 3 decimal places
- TEIF XML matches the specification exactly
- Digital signature is valid and contains all required XAdES elements

✅ **Security audited**:
- No SQL injection vulnerabilities
- No XSS vulnerabilities
- Certificate files encrypted at rest
- HTTPS enforced in production

✅ **Performance acceptable**:
- OldInvoice list page loads in <1 second
- OldInvoice creation/validation completes in <2 seconds
- PDF generation completes in <5 seconds
- TTN submission queued and doesn't block UI

✅ **Code quality standards met**:
- PSR-12 formatting (run `./vendor/bin/pint --test`)
- PHPStan level 6+ passes with no errors
- TypeScript compiles with no errors in strict mode
- ESLint passes with no warnings

---

## Your Next Steps

1. **📖 Read the entire PROJECT_BLUEPRINT.md** - Understand the full scope
2. **📋 Review Phase 1 tasks** - Plan your first implementation sprint
3. **🏗️ Set up the development environment** - PHP 8.3, PostgreSQL, Node.js, Redis
4. **⚙️ Initialize Laravel 11 project** - Follow Phase 1A scaffolding checklist
5. **✅ Complete Phase 1** - Don't move to Phase 2 until all Phase 1 tasks are done
6. **🔁 Repeat for Phases 2-8** - Systematic, test-driven, quality-focused development
7. **🚀 Deploy to production** - Follow deployment checklist carefully
8. **🎉 Deliver a production-ready, regulation-compliant SaaS ERP platform**

---

## Final Reminder

This is a **REGULATORY COMPLIANCE PROJECT** for electronic invoicing in Tunisia. Errors in XML structure, signature implementation, or tax calculations could result in:
- ❌ OldInvoices rejected by Tunisia TradeNet
- ❌ Legal non-compliance for businesses using the platform
- ❌ Loss of trust and business viability

**Therefore:**
- **Take your time** - Accuracy over speed
- **Test thoroughly** - Edge cases matter in financial systems
- **Follow the blueprint precisely** - It was designed with Tunisia's exact requirements
- **Ask for clarification** - If anything in the blueprint is unclear, pause and ask
- **Document your work** - Future developers will thank you

**You have everything you need to build this successfully. The blueprint is complete. Now execute with precision and care.**

---

## Support Resources

**When you need help:**
- Tunisia TradeNet Documentation: [Official TTN Site]
- TEIF XML Specification: Reference XSD schemas in workspace
- XAdES Signature Standards: ETSI TS 101 903
- Laravel 11 Documentation: https://laravel.com/docs/11.x
- React 18 Documentation: https://react.dev
- Inertia.js Documentation: https://inertiajs.com

**Good luck, and build something excellent! 🚀**
