import ProductForm from './Form';
import type { Product } from '@/types';

interface Props { product: Product; }

export default function Edit({ product }: Props) {
    return <ProductForm product={product as any} isEdit />;
}
