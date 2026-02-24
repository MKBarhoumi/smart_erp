import { Head } from '@inertiajs/react';
import type { Product, Invoice } from '@/types';
import InvoiceForm from './Form';

interface Props {
    customers: Array<{ id: string; name: string; identifier_value: string }>;
    products: Product[];
    documentTypes: Array<{ value: string; label: string }>;
    invoice: Partial<Invoice>;
}

export default function Edit(props: Props) {
    return (
        <>
            <Head title={`Edit: ${props.invoice.invoice_number}`} />
            <InvoiceForm {...props} isEdit />
        </>
    );
}
