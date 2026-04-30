# Drop-in components for `MKBarhoumi/smart_erp`

These are **UI-only React components** designed to be copied into your Laravel + Inertia.js project. They are NOT wired into this TanStack workspace — this workspace exists only to host the source files for delivery.

## Where to paste

| File here | Paste into your repo at |
|---|---|
| `invoices/invoiceStatus.ts` | `resources/js/utils/invoiceStatus.ts` |
| `invoices/InvoiceListPage.tsx` | `resources/js/Pages/Invoices/Index.tsx` (replaces existing) |
| `invoices/InvoiceShowPage.tsx` | `resources/js/Pages/Invoices/Show.tsx` (replaces existing) |
| `invoices/InvoiceLifecycleStepper.tsx` | `resources/js/Components/Invoices/InvoiceLifecycleStepper.tsx` |
| `invoices/ConfirmActionDialog.tsx` | `resources/js/Components/Invoices/ConfirmActionDialog.tsx` |
| `invoices/TtnLogInspector.tsx` | `resources/js/Components/Invoices/TtnLogInspector.tsx` |
| `dashboard/DashboardPage.tsx` | `resources/js/Pages/Dashboard.tsx` (replaces existing) |
| `dashboard/KpiCard.tsx` | `resources/js/Components/Dashboard/KpiCard.tsx` |

## Required dependencies (add to your repo)

```bash
npm install recharts lucide-react
```

You already use `lucide-react`. `recharts` is the only new dep.

## Contracts preserved

- **Same Inertia props** for every page (no controller changes needed).
- **Same routes**: `/invoices`, `/invoices/create`, `/invoices/{id}`, `/invoices/{id}/{validate|sign|submit|duplicate|pdf|xml|payments}`.
- **Same `can*` server-driven gating** — UI never re-checks roles for invoice mutations. Roles are only used to gate the "New Invoice" CTA on the list (a navigation hint), per your rules.
- **Same status machine** `DRAFT → PENDING_VALIDATION → VALIDATED → SIGNED → SUBMITTED → ACCEPTED` is reflected by the stepper but enforced server-side.

## Imports assumed

These match your existing files:

```ts
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Button } from '@/Components/ui/Button';
import { Input } from '@/Components/ui/Input';
import { Select } from '@/Components/ui/Select';
import { Badge, InvoiceStatusBadge } from '@/Components/ui/Badge';
import { Modal } from '@/Components/ui/Modal';
import { Pagination } from '@/Components/ui/Pagination';
import type { PageProps, PaginatedData, Invoice } from '@/types';
```

If a primitive's prop signature differs from what these components assume, see the `// ADAPT:` comments inline.
