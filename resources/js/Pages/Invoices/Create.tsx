import { Head } from '@inertiajs/react';
import InvoiceForm from './Form';

interface Props {
    customers: Array<{ id: string; name: string; identifier_value: string }>;
    products: any[];
    documentTypes: Array<{ value: string; label: string }>;
}

export default function Create(props: Props) {
    return (
        <>
            <Head title="New Invoice" />
            <InvoiceForm {...props} />
        </>
    );
}
