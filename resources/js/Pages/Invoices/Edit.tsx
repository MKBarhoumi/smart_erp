import { Head } from '@inertiajs/react';
import type { Customer, Product, InvoiceFormData } from '@/types';
import InvoiceForm from './Form';

interface Props {
    invoice: Partial<InvoiceFormData & { id: string; document_identifier: string }>;
    customers: Customer[];
    products: Product[];
    documentTypes: Array<{ value: string; label: string }>;
    identifierTypes: Array<{ value: string; label: string }>;
    companySettings: {
        identifier: string;
        name: string;
        street: string;
        city: string;
        postal_code: string;
        country: string;
    };
}

export default function Edit({ invoice, ...props }: Props) {
    return (
        <>
            <Head title={`Edit Invoice ${invoice.document_identifier}`} />
            <InvoiceForm invoice={invoice} isEdit={true} {...props} />
        </>
    );
}
