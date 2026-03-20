import ServiceForm from './Form';
import type { Service } from '@/types';

interface Props {
    service: Service;
    categories: string[];
    billingUnits: string[];
}

export default function Edit({ service, categories, billingUnits }: Props) {
    return <ServiceForm service={service} categories={categories} billingUnits={billingUnits} isEdit />;
}
