import CustomerForm from './Form';
import type { Customer } from '@/types';

interface Props {
    customer: Customer;
}

export default function Edit({ customer }: Props) {
    return <CustomerForm customer={customer as any} isEdit />;
}
