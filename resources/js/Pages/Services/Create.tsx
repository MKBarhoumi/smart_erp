import ServiceForm from './Form';

interface Props {
    categories: string[];
    billingUnits: string[];
}

export default function Create({ categories, billingUnits }: Props) {
    return <ServiceForm categories={categories} billingUnits={billingUnits} />;
}
