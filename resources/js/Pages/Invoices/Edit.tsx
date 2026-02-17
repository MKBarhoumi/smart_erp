import { Head } from '@inertiajs/react';
import InvoiceForm from './Form';

interface Props {
    customers: Array<{ id: string; name: string; identifier_value: string }>;
    products: any[];
    documentTypes: Array<{ value: string; label: string }>;
    invoice: any;
}

export default function Edit(props: Props) {
    return (
        <>
            <Head title={`Edit: ${props.invoice.invoice_number}`} />
            <InvoiceForm {...props} isEdit />
        </>
    );
}
