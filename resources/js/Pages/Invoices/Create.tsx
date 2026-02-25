import { Head } from '@inertiajs/react';
import type { Customer, Product } from '@/types';
import InvoiceForm from './Form';

interface Props {
    customers: Customer[];
    products: Product[];
    documentTypes: Array<{ value: string; label: string }>;
    identifierTypes: Array<{ value: string; label: string }>;
    taxTypes: Array<{ value: string; label: string }>;
    companySettings: {
        identifier: string;
        name: string;
        street: string;
        city: string;
        postal_code: string;
        country: string;
    };
}

export default function Create(props: Props) {
    return (
        <>
            <Head title="New Invoice" />
            <InvoiceForm {...props} />
        </>
    );
}
