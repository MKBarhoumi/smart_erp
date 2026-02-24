import type { Customer } from '@/types';
import CustomerForm from './Form';

interface Props {
    customer: Customer;
}

export default function Edit({ customer }: Props) {
    return <CustomerForm customer={customer} isEdit />;
}
