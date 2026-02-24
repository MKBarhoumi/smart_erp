import { Head } from '@inertiajs/react';
import type { Product, OldInvoice } from '@/types';
import OldInvoiceForm from './Form';

interface Props {
    customers: Array<{ id: string; name: string; identifier_value: string }>;
    products: Product[];
    documentTypes: Array<{ value: string; label: string }>;
    oldinvoice: Partial<OldInvoice>;
}

export default function Edit(props: Props) {
    return (
        <>
            <Head title={`Edit: ${props.oldinvoice.oldinvoice_number}`} />
            <OldInvoiceForm {...props} isEdit />
        </>
    );
}
