import { Head } from '@inertiajs/react';
import type { Customer, Product } from '@/types';
import InvoiceForm from './Form';

interface Service {
    id: string;
    code: string;
    name: string;
    description?: string;
    unit_price: string;
    tva_rate: string;
}

interface Props {
    customers: Customer[];
    products: Product[];
    services: Service[];
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
